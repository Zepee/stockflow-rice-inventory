<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\SaveCustomerRequest;
use App\Http\Requests\MasterDataSearchRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(MasterDataSearchRequest $request): View
    {
        $customers = Customer::query()
            ->when($request->validated('q'), fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(SaveCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        return redirect()->route('customers.show', $customer)->with('status', 'Pelanggan berhasil dibuat.');
    }

    public function show(string $customer): View
    {
        return view('customers.show', ['customer' => Customer::query()->findOrFail($customer)]);
    }

    public function edit(string $customer): View
    {
        return view('customers.edit', ['customer' => Customer::query()->findOrFail($customer)]);
    }

    public function update(SaveCustomerRequest $request, string $customer): RedirectResponse
    {
        $record = Customer::query()->findOrFail($customer);
        $record->update($request->validated());

        return redirect()->route('customers.show', $record)->with('status', 'Pelanggan berhasil diperbarui.');
    }
}
