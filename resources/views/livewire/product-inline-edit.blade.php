<div>
    @if(session('saved'))
        <div class="alert">{{ session('saved') }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Naziv</th>
                <th>SKU</th>
                <th>Cena</th>
                <th>Lager</th>
                <th style="width:200px;">Akcije</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>
                        @if($editingId === $product->id)
                            <input type="text" wire:model="editName" wire:keydown.enter="saveEdit" style="width:100%;">
                            @error('editName')
                                <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $message }}</div>
                            @enderror
                        @else
                            {{ $product->name }}
                        @endif
                    </td>
                    <td>{{ $product->sku ?? '-' }}</td>
                    <td>{{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->stock_quantity }}</td>
                    <td>
                        @if($editingId === $product->id)
                            <button class="btn" wire:click="saveEdit">Sačuvaj</button>
                            <button class="btn btn-secondary" wire:click="cancelEdit">Otkaži</button>
                        @else
                            <button class="btn btn-secondary" wire:click="startEdit({{ $product->id }})">Izmeni naziv</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:16px;">
        {{ $products->links('vendor.pagination.default') }}
    </div>
</div>