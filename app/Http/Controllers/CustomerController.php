<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Lista kupaca + pretraga
    public function index(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    // Forma za novog kupca
    public function create()
    {
        return view('customers.create');
    }

    // Snimanje novog kupca
    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);
        Customer::create($data);

        return redirect()->route('customers.index')
            ->with('success', 'Kupac je uspešno dodat.');
    }

    // Forma za izmenu
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    // Snimanje izmene
    public function update(Request $request, Customer $customer)
    {
        $data = $this->validateCustomer($request, $customer->id);
        $customer->update($data);

        return redirect()->route('customers.index')
            ->with('success', 'Kupac je uspešno izmenjen.');
    }

    // Brisanje
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Kupac je obrisan.');
    }

    // Validacija (koristi je i store i update)
    private function validateCustomer(Request $request, $ignoreId = null)
    {
        return $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $ignoreId,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);
    }
}