<div>
    <div style="display:flex; gap:8px; margin-bottom:16px; align-items:center;">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Pretraga po imenu, firmi, emailu, telefonu..."
               style="flex:1;">
        <div wire:loading style="color:#6b7280; font-size:14px;">Pretraga...</div>
    </div>

    @if($customers->count())
        <table>
            <thead>
                <tr>
                    <th>Tip</th>
                    <th>Ime</th>
                    <th>Firma</th>
                    <th>Email</th>
                    <th>Telefon</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                    <tr>
                        <td>{{ $customer->type === 'company' ? 'Firma' : 'Fizičko lice' }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->company_name ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $customers->links('vendor.pagination.default') }}
        </div>
    @else
        <div class="card">Nema rezultata za "{{ $search }}".</div>
    @endif
</div>