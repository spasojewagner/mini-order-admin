<div>
    <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Pretraga po nazivu ili SKU..." style="flex:1; min-width:200px;">

        <select wire:model.live="status">
            <option value="">Svi statusi</option>
            <option value="active">Aktivni</option>
            <option value="inactive">Neaktivni</option>
        </select>

        <label style="display:flex; align-items:center; gap:6px; white-space:nowrap;">
            <input type="checkbox" wire:model.live="inStock" style="width:auto;">
            Samo na lageru
        </label>

        <button class="btn btn-secondary" wire:click="resetFilters">Reset filtera</button>

        <div wire:loading style="color:#6b7280; font-size:14px;">Učitavanje...</div>
    </div>

    @if($products->count())
        <table>
            <thead>
                <tr>
                    <th style="cursor:pointer;" wire:click="sortBy('name')">
                        Naziv {{ $sortField === 'name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '' }}
                    </th>
                    <th>SKU</th>
                    <th style="cursor:pointer;" wire:click="sortBy('price')">
                        Cena {{ $sortField === 'price' ? ($sortDirection === 'asc' ? '▲' : '▼') : '' }}
                    </th>
                    <th>Lager</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->sku ?? '-' }}</td>
                        <td>{{ number_format($product->price, 2) }}</td>
                        <td>{{ $product->stock_quantity }}</td>
                        <td>{{ $product->is_active ? 'Aktivan' : 'Neaktivan' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $products->links('vendor.pagination.default') }}
        </div>
    @else
        <div class="card">Nema proizvoda za zadate filtere.</div>
    @endif
</div>