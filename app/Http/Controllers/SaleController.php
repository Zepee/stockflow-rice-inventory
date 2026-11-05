<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\PrepareSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->select(['id', 'transaction_number', 'customer_id', 'business_date', 'notes', 'total_value', 'created_by', 'confirmed_at', 'status'])
            ->with(['customer:id,name', 'creator:id,name'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(function ($query) use ($search) {
                    $query->where('transaction_number', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('business_date')
            ->orderByDesc('confirmed_at')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create(Request $request, ?string $draftKey = null): View
    {
        $draft = $draftKey ? $this->draft($request, $draftKey) : null;
        $oldDraftKey = $request->old('draft_key');
        $resolvedDraftKey = $draftKey ?? (is_string($oldDraftKey) && Str::isUuid($oldDraftKey) ? $oldDraftKey : (string) Str::uuid());

        return view('sales.create', [
            'draftKey' => $resolvedDraftKey,
            'draftData' => $draft['data'] ?? [],
            'products' => Product::query()->where('is_active', true)->where('current_stock', '>', 0)->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'selling_price', 'current_stock']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'owner' => $request->user()->isOwner(),
        ]);
    }

    public function prepare(PrepareSaleRequest $request, SaleService $sales): RedirectResponse
    {
        $data = $request->validated();
        $sales->preview($data, $request->user());
        $key = $data['draft_key'];
        session()->put($this->sessionKey($key), ['actor_id' => $request->user()->id, 'data' => $data]);

        return redirect()->route('sales.review', $key);
    }

    public function review(Request $request, string $draftKey, SaleService $sales): View|RedirectResponse
    {
        $draft = $this->draft($request, $draftKey);
        try {
            $preview = $sales->preview($draft['data'], $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('sales.create', $draftKey)
                ->withErrors($exception->errors())
                ->withInput($draft['data']);
        }
        $draft['data']['price_snapshots'] = $preview['price_snapshots'];
        $draft['data']['stock_snapshots'] = $preview['stock_snapshots'];
        session()->put($this->sessionKey($draftKey), $draft);

        return view('sales.review', [
            'draftKey' => $draftKey,
            'customer' => Customer::query()->findOrFail($draft['data']['customer_id']),
            'businessDate' => $draft['data']['business_date'],
            'notes' => $draft['data']['notes'] ?? null,
            'lines' => $preview['lines'],
            'totalValue' => $preview['total_value'],
            'canConfirm' => collect($preview['lines'])->every(fn (array $line) => $line['enough_stock']),
            'owner' => $request->user()->isOwner(),
        ]);
    }

    public function confirm(Request $request, string $draftKey, SaleService $sales): RedirectResponse
    {
        $draft = $this->draft($request, $draftKey);
        abort_unless(isset($draft['data']['price_snapshots'], $draft['data']['stock_snapshots']), 409, 'Tinjau transaksi sebelum mengonfirmasi.');

        $sale = $sales->confirm($draft['data'], $draftKey, $request->user());
        session()->forget($this->sessionKey($draftKey));

        return redirect()->route('sales.show', $sale)->with('status', 'Penjualan berhasil dikonfirmasi dan stok telah diperbarui.');
    }

    public function show(Request $request, string $sale): View
    {
        $columns = ['id', 'transaction_number', 'submission_key', 'warehouse_id', 'customer_id', 'business_date', 'notes', 'total_value', 'created_by', 'confirmed_at', 'status'];
        $itemColumns = ['id', 'sale_id', 'product_id', 'configured_selling_price', 'selling_price', 'quantity', 'unit', 'line_subtotal'];
        if ($request->user()->isOwner()) {
            $itemColumns[] = 'override_reason';
        }

        $sale = Sale::query()->select($columns)->with([
            'customer:id,name,phone,address',
            'warehouse:id,name',
            'creator:id,name',
            'cancellation.authorizer:id,name',
            'items' => fn ($items) => $items->select($itemColumns)->with('product:id,name,sku'),
        ])->findOrFail($sale);

        return view('sales.show', ['sale' => $sale, 'owner' => $request->user()->isOwner()]);
    }

    /** @return array{actor_id: int, data: array<string, mixed>} */
    private function draft(Request $request, string $draftKey): array
    {
        abort_unless(Str::isUuid($draftKey), 404);
        $draft = session()->get($this->sessionKey($draftKey));
        abort_unless(is_array($draft) && ($draft['actor_id'] ?? null) === $request->user()->id, 404);

        return $draft;
    }

    private function sessionKey(string $draftKey): string
    {
        return 'sale_drafts.'.$draftKey;
    }
}
