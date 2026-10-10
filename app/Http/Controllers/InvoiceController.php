<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Money;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Printable A4 invoice (the browser's "Save as PDF" makes the PDF).
 * Issued when the order ships; see OrderWorkflow.
 */
class InvoiceController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $user = $request->user();
        abort_unless($user->isStaff() || $order->customer_id === $user->customer_id, 403);
        abort_unless($order->invoice_number, 404, 'This order has not been invoiced yet.');

        $order->load(['items', 'customer']);
        $issued = $order->invoiced_at ?? $order->shipped_at ?? now();

        return view('invoices.show', [
            'order' => $order,
            'customer' => $order->customer,
            'company' => [
                'name' => Settings::get('company.name'),
                'address' => Settings::get('company.address'),
                'tax_id' => Settings::get('company.tax_id'),
                'registration_number' => Settings::get('company.registration_number'),
                'bank_account' => Settings::get('company.bank_account'),
                'email' => Settings::get('company.email'),
                'phone' => Settings::get('company.phone'),
            ],
            'totals' => Money::totals($order->total_amount),
            'issued' => $issued,
            'due' => $issued->copy()->addDays(Settings::int('invoice.payment_days')),
        ]);
    }
}
