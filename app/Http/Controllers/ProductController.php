<?php

namespace App\Http\Controllers;

use App\Http\Requests\Products\ProductIndexRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request): View
    {
        $owner = $request->user()->isOwner();
        $query = Product::query()->select($this->visibleColumns($owner));

        if (! $owner) {
            $query->where(function ($query) {
                $query->where('is_active', true)->orWhere('current_stock', '>', 0);
            });
        } elseif (($status = $request->validated('status', 'active')) === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search = $request->validated('q')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('products.index', [
            'products' => $products,
            'filters' => $request->validated(),
            'owner' => $owner,
        ]);
    }

    public function show(Request $request, string $product): View
    {
        $owner = $request->user()->isOwner();
        $query = Product::query()->select($this->visibleColumns($owner));

        if (! $owner) {
            $query->where(function ($query) {
                $query->where('is_active', true)->orWhere('current_stock', '>', 0);
            });
        } else {
            $query->with(['sellingPriceChanges' => fn ($changes) => $changes
                ->with('actor:id,name')
                ->orderByDesc('changed_at')]);
        }

        return view('products.show', [
            'product' => $query->findOrFail($product),
            'owner' => $owner,
        ]);
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request, ProductService $products): RedirectResponse
    {
        $product = $products->create($request->validated(), $request->user());

        return redirect()->route('products.show', $product)->with('status', 'Produk berhasil dibuat.');
    }

    public function edit(string $product): View
    {
        return view('products.edit', ['product' => Product::query()->findOrFail($product)]);
    }

    public function update(UpdateProductRequest $request, string $product, ProductService $products): RedirectResponse
    {
        $record = Product::query()->findOrFail($product);
        $record = $products->update($record, $request->validated(), $request->user());

        return redirect()->route('products.show', $record)->with('status', 'Produk berhasil diperbarui.');
    }

    public function archive(string $product): RedirectResponse
    {
        Product::query()->findOrFail($product)->update(['is_active' => false]);

        return redirect()->route('products.index')->with('status', 'Produk berhasil diarsipkan.');
    }

    /** @return array<int, string> */
    private function visibleColumns(bool $owner): array
    {
        $columns = ['id', 'name', 'sku', 'description', 'unit', 'selling_price', 'minimum_stock', 'current_stock', 'is_active', 'created_at'];

        if ($owner) {
            $columns[] = 'purchase_price';
        }

        return $columns;
    }
}
