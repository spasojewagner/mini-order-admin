<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductFilters extends Component
{
    use WithPagination;

    // Stanje — svaki filter je svoj property
    public $search = '';
    public $status = '';        // '', 'active', 'inactive'
    public $inStock = false;    // true = samo proizvodi na lageru
    public $sortField = 'name'; // po čemu sortiramo
    public $sortDirection = 'asc'; // asc ili desc

    // Kad se bilo koji filter promeni, vrati na prvu stranu
    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingInStock() { $this->resetPage(); }

    // Sortiranje: klik na kolonu menja polje/smer
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            // isti klik = obrni smer
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Reset svih filtera
    public function resetFilters()
    {
        $this->reset(['search', 'status', 'inStock', 'sortField', 'sortDirection']);
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->when($this->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($this->inStock, fn($q) => $q->where('stock_quantity', '>', 0))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.product-filters', compact('products'));
    }
}