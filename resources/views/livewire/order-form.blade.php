<div>
    @if($errors->any())
        <div class="card" style="border:1px solid #dc2626; margin-bottom:16px;">
            <strong style="color:#dc2626;">Greške:</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <label>Kupac *</label>
        <select wire:model="customerId">
            <option value="">— izaberi kupca —</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->company_name ? ' (' . $customer->company_name . ')' : '' }}</option>
            @endforeach
        </select>

        <label style="margin-top:20px;">Dodaj proizvod</label>
        <div style="position:relative;">
            <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Ukucaj naziv ili SKU proizvoda...">

            @if($foundProducts->count() > 0)
                <div style="border:1px solid #d1d5db; border-radius:6px; margin-top:4px; background:#fff; max-height:240px; overflow:auto;">
                    @foreach($foundProducts as $product)
                        <div wire:click="addProduct({{ $product->id }})" style="padding:8px 12px; cursor:pointer; border-bottom:1px solid #f3f4f6;">
                            <span>{{ $product->name }} ({{ $product->sku ?? '—' }})</span>
                            <span style="float:right;">{{ number_format($product->price, 2) }} · lager: {{ $product->stock_quantity }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <label style="margin-top:20px;">Stavke</label>
        @if(count($items) > 0)
            <table style="margin-bottom:12px;">
                <thead>
                    <tr>
                        <th>Proizvod</th>
                        <th style="width:110px;">Cena</th>
                        <th style="width:120px;">Količina</th>
                        <th style="width:120px;">Ukupno</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ number_format($item['price'], 2) }}</td>
                            <td>
                                <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity" style="width:80px;">
                            </td>
                            <td>{{ number_format($item['price'] * (int) $item['quantity'], 2) }}</td>
                            <td>
                                <button type="button" class="btn btn-danger" wire:click="removeItem({{ $index }})">×</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="card" style="margin-bottom:12px;">Nema stavki. Pretraži i dodaj proizvod gore.</div>
        @endif

        <div style="font-size:18px; margin-top:8px;">
            <strong>Ukupno: {{ number_format($this->total, 2) }}</strong>
        </div>

        <label style="margin-top:20px;">Napomena</label>
        <textarea wire:model="note" rows="2"></textarea>

        <div style="margin-top:20px; display:flex; gap:8px;">
            <button class="btn" wire:click="save">Sačuvaj porudžbinu</button>
            <a class="btn btn-secondary" href="{{ route('orders.index') }}">Otkaži</a>
        </div>
    </div>
</div>