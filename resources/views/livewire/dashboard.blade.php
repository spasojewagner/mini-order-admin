<div>
    <div style="display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; align-items:center;">
        <label style="font-size:14px;">Od:
            <input type="date" wire:model.live="dateFrom" style="width:auto;">
        </label>
        <label style="font-size:14px;">Do:
            <input type="date" wire:model.live="dateTo" style="width:auto;">
        </label>
        <button class="btn btn-secondary" wire:click="resetFilter">Reset</button>
        <div wire:loading style="color:#6b7280; font-size:14px;">Osvežavam...</div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
        <div class="card">
            <div style="font-size:14px; color:#6b7280;">Kupci</div>
            <div style="font-size:32px; font-weight:700;">{{ $customersCount }}</div>
        </div>

        <div class="card">
            <div style="font-size:14px; color:#6b7280;">Proizvodi</div>
            <div style="font-size:32px; font-weight:700;">{{ $productsCount }}</div>
        </div>

        <div class="card">
            <div style="font-size:14px; color:#6b7280;">Porudžbine {{ ($dateFrom || $dateTo) ? '(period)' : '(ukupno)' }}</div>
            <div style="font-size:32px; font-weight:700;">{{ $ordersCount }}</div>
        </div>

        <div class="card">
            <div style="font-size:14px; color:#6b7280;">Vrednost potvrđenih</div>
            <div style="font-size:32px; font-weight:700;">{{ number_format($confirmedValue, 2) }}</div>
        </div>
    </div>
</div>