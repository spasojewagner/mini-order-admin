<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use Livewire\Component;

class Dashboard extends Component
{
    public $dateFrom = '';
    public $dateTo = '';

    public function resetFilter()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    public function render()
    {
        // Osnovni brojevi (agregacije u bazi — count, ne vučemo sve u PHP)
        $customersCount = Customer::count();
        $productsCount = Product::count();

        // Porudžbine u izabranom periodu
        $ordersQuery = Order::query()
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        $ordersCount = (clone $ordersQuery)->count();

        // Vrednost potvrđenih porudžbina — SUM u bazi, ne u PHP-u
        $confirmedValue = (clone $ordersQuery)
            ->where('status', 'confirmed')
            ->sum('total_amount');

        return view('livewire.dashboard', compact(
            'customersCount',
            'productsCount',
            'ordersCount',
            'confirmedValue'
        ));
    }
}