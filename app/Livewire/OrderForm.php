<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class OrderForm extends Component
{
    // Izbor kupca
    public $customerId = '';

    // Pretraga proizvoda
    public $productSearch = '';

    // Stavke porudžbine — drže se u state-u kao niz
    public $items = [];

    // Napomena
    public $note = '';

    // Pravila validacije
    protected function rules()
    {
        return [
            'customerId' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    protected $messages = [
        'customerId.required' => 'Morate izabrati kupca.',
        'items.required' => 'Dodajte bar jednu stavku.',
        'items.min' => 'Dodajte bar jednu stavku.',
    ];

    // Dodavanje proizvoda u porudžbinu
    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if (! $product) return;

        // Ako proizvod već postoji u stavkama — samo povećaj količinu
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] == $productId) {
                $this->items[$index]['quantity']++;
                $this->productSearch = '';
                return;
            }
        }

        // Inače dodaj novu stavku
        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'quantity' => 1,
        ];

        $this->productSearch = '';
    }

    // Promena količine (ne dozvoli ispod 1)
    public function updatedItems()
    {
        foreach ($this->items as $index => $item) {
            if ((int) $item['quantity'] < 1) {
                $this->items[$index]['quantity'] = 1;
            }
        }
    }

    // Uklanjanje stavke
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // reindeksiraj
    }

    // Ukupna vrednost — računa se uživo
    public function getTotalProperty()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['price'] * (int) $item['quantity'];
        }
        return $total;
    }

    // Snimanje porudžbine
    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $order = Order::create([
                'customer_id' => $this->customerId,
                'status' => 'draft',
                'total_amount' => 0,
                'note' => $this->note ?: null,
            ]);

            $total = 0;
            foreach ($this->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * (int) $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => (int) $item['quantity'],
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_amount' => $total]);
        });

        session()->flash('success', 'Porudžbina je uspešno kreirana.');
        return redirect()->route('orders.index');
    }

    public function render()
    {
        $customers = Customer::orderBy('name')->get();

        // Live pretraga proizvoda — prikaži rezultate samo ako se kuca
        $foundProducts = collect();
        if (strlen($this->productSearch) >= 1) {
            $foundProducts = Product::where('is_active', true)
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->productSearch}%")
                        ->orWhere('sku', 'like', "%{$this->productSearch}%");
                })
                ->limit(8)
                ->get();
        }

        return view('livewire.order-form', compact('customers', 'foundProducts'));
    }
}