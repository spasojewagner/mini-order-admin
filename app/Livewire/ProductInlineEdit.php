<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductInlineEdit extends Component
{
    use WithPagination;

    public $editingId = null;
    public $editName = '';

    public function startEdit($id)
    {
        $product = Product::find($id);
        if (! $product) return;

        $this->editingId = $id;
        $this->editName = $product->name;
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->editName = '';
        $this->resetErrorBag();
    }

    public function saveEdit()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
        ], [
            'editName.required' => 'Naziv je obavezan.',
            'editName.max' => 'Naziv je predugačak.',
        ]);

    $product = Product::find($this->editingId);
        if ($product && $product->name !== $this->editName) {
            $product->update(['name' => $this->editName]);
            session()->flash('saved', 'Naziv je izmenjen.');
        }

        $this->editingId = null;
        $this->editName = '';
    }

    public function render()
    {
        $products = Product::latest()->paginate(10);
        return view('livewire.product-inline-edit', compact('products'));
    }
}