<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\PreparePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->isOwner();
        $columns = ['id', 'transaction_number', 'supplier_id', 'business_date', 'created_by', 'confirmed_at', 'status'];
        if ($owner) {
            $columns[] = 'total_value';
        }

        $purchases = Purchase::query()
            ->select($columns)
            ->with(['supplier:id,name', 'creator:id,name'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(function ($query) use ($search) {
                    $query->where('transaction_number', 'like', '%'.$search.'%')
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('business_date')
            ->orderByDesc('confirmed_at')
            ->paginate(15)
            ->withQueryString();

        return view('purchases.index', compact('purchases', 'owner'));
    }

    public function create(Request $request, ?string $draftKey = null): View
    {
        $draft = $draftKey ? $this->draft($request, $draftKey) : null;
        $owner = $request->user()->isOwner();
        $productColumns = ['id', 'name', 'sku', 'unit', 'current_stock'];
        if ($owner) {
            $productColumns[] = 'purchase_price';
        }
        $oldDraftKey = $request->old('draft_key');
        $resolvedDraftKey = $draftKey ?? (is_string($oldDraftKey) && Str::isUuid($oldDraftKey) ? $oldDraftKey : (string) Str::uuid());

        return view('purchases.create', [
            'draftKey' => $resolvedDraftKey,
            'draftData' => $draft['data'] ?? [],
            'owner' => $owner,
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get($productColumns),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function prepare(PreparePurchaseRequest $request, PurchaseService $purchases): RedirectResponse
    {
        $data = $request->validated();
        $purchases->preview($data, $request->user());
        $key = $data['draft_key'];
        session()->put($this->sessionKey($key), [
            'actor_id' => $request->user()->id,
            'data' => $data,
        ]);

        return redirect()->route('purchases.review', $key);
    }

    public function review(Request $request, string $draftKey, PurchaseService $purchases): View|RedirectResponse
    {
        $draft = $this->draft($request, $draftKey);
        try {
            $preview = $purchases->preview($draft['data'], $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('purchases.create', $draftKey)
                ->withErrors($exception->errors())
                ->withInput($draft['data']);
        }
        $draft['data']['price_snapshots'] = $preview['price_snapshots'];
        session()->put($this->sessionKey($draftKey), $draft);

        return view('purchases.review', [
            'draftKey' => $draftKey,
            'supplier' => Supplier::query()->findOrFail($draft['data']['supplier_id']),
            'businessDate' => $draft['data']['business_date'],
            'notes' => $draft['data']['notes'] ?? null,
            'lines' => $preview['lines'],
            'totalValue' => $preview['total_value'],
            'owner' => $request->user()->isOwner(),
        ]);
    }

    public function confirm(Request $request, string $draftKey, PurchaseService $purchases): RedirectResponse
    {
        $draft = $this->draft($request, $draftKey);
        abort_unless(isset($draft['data']['price_snapshots']), 409, 'Tinjau transaksi sebelum mengonfirmasi.');

        $purchase = $purchases->confirm($draft['data'], $draftKey, $request->user());
        session()->forget($this->sessionKey($draftKey));

        return redirect()->route('purchases.show', $purchase)->with('status', 'Pembelian berhasil dikonfirmasi dan stok telah diperbarui.');
    }

    public function show(Request $request, string $purchase): View
    {
        $owner = $request->user()->isOwner();
        $purchaseColumns = ['id', 'transaction_number', 'submission_key', 'warehouse_id', 'supplier_id', 'business_date', 'notes', 'created_by', 'confirmed_at', 'status'];
        $itemColumns = ['id', 'purchase_id', 'product_id', 'quantity', 'unit'];
        if ($owner) {
            $purchaseColumns[] = 'total_value';
            array_push($itemColumns, 'default_purchase_price', 'purchase_price', 'override_reason', 'line_subtotal');
        }

        $purchase = Purchase::query()
            ->select($purchaseColumns)
            ->with([
                'supplier:id,name,phone,address',
                'warehouse:id,name',
                'creator:id,name',
                'cancellation.authorizer:id,name',
                'items' => fn ($items) => $items->select($itemColumns)->with('product:id,name,sku'),
            ])
            ->findOrFail($purchase);

        return view('purchases.show', compact('purchase', 'owner'));
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
        return 'purchase_drafts.'.$draftKey;
    }
}
