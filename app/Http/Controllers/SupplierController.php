<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataSearchRequest;
use App\Http\Requests\Suppliers\SaveSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(MasterDataSearchRequest $request): View
    {
        $suppliers = Supplier::query()
            ->when($request->validated('q'), fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(SaveSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::query()->create($request->validated());

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Pemasok berhasil dibuat.');
    }

    public function show(string $supplier): View
    {
        return view('suppliers.show', ['supplier' => Supplier::query()->findOrFail($supplier)]);
    }

    public function edit(string $supplier): View
    {
        return view('suppliers.edit', ['supplier' => Supplier::query()->findOrFail($supplier)]);
    }

    public function update(SaveSupplierRequest $request, string $supplier): RedirectResponse
    {
        $record = Supplier::query()->findOrFail($supplier);
        $record->update($request->validated());

        return redirect()->route('suppliers.show', $record)->with('status', 'Pemasok berhasil diperbarui.');
    }
}
