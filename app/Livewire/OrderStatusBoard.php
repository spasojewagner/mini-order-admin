<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Customer;
use Livewire\Component;

class OrderStatusBoard extends Component
{
    public $customerId = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Svi statusi (kolone table)
    public $statuses = ['draft', 'new', 'confirmed', 'in_progress', 'shipped', 'cancelled'];

    // Dozvoljeni prelazi: iz kog statusa u koje sme
    protected $allowedTransitions = [
        'draft' => ['new', 'cancelled'],
        'new' => ['confirmed', 'cancelled'],
        'confirmed' => ['in_progress', 'cancelled'],
        'in_progress' => ['shipped', 'cancelled'],
        'shipped' => [],
        'cancelled' => [],
    ];

    // Promena statusa uz proveru da je prelaz dozvoljen
    public function changeStatus($orderId, $newStatus)
    {
        $order = Order::find($orderId);
        if (! $order) return;

        $allowed = $this->allowedTransitions[$order->status] ?? [];

        if (! in_array($newStatus, $allowed)) {
            $this->addError('status', "Prelaz {$order->status} → {$newStatus} nije dozvoljen.");
            return;
        }

        $order->update(['status' => $newStatus]);
    }

    // Koji prelazi su mogući iz datog statusa (za dugmad u šablonu)
    public function allowedFor($status)
    {
        return $this->allowedTransitions[$status] ?? [];
    }

    public function render()
    {
        $query = Order::with('customer')
            ->when($this->customerId, fn($q) => $q->where('customer_id', $this->customerId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        // Grupiši porudžbine po statusu
        $orders = $query->latest()->get()->groupBy('status');

        $customers = Customer::orderBy('name')->get();

        return view('livewire.order-status-board', compact('orders', 'customers'));
    }
}