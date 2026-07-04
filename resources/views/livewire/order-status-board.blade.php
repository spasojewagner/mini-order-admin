<div>
    @if($errors->has('status'))
        <div class="card" style="border:1px solid #dc2626; margin-bottom:16px; color:#dc2626;">
            {{ $errors->first('status') }}
        </div>
    @endif

    <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
        <select wire:model.live="customerId">
            <option value="">Svi kupci</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
            @endforeach
        </select>

        <label style="font-size:14px;">Od:
            <input type="date" wire:model.live="dateFrom" style="width:auto;">
        </label>
        <label style="font-size:14px;">Do:
            <input type="date" wire:model.live="dateTo" style="width:auto;">
        </label>
    </div>

    <div style="display:flex; gap:12px; overflow-x:auto; padding-bottom:8px;">
        @foreach($statuses as $status)
            <div style="min-width:220px; flex:1; background:#f9fafb; border-radius:8px; padding:12px;">
                <div style="font-weight:600; margin-bottom:8px; text-transform:capitalize;">
                    {{ $status }} ({{ isset($orders[$status]) ? $orders[$status]->count() : 0 }})
                </div>

                @if(isset($orders[$status]))
                    @foreach($orders[$status] as $order)
                        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:10px; margin-bottom:8px;">
                            <div style="font-weight:500;">#{{ $order->id }} — {{ $order->customer->name ?? '-' }}</div>
                            <div style="font-size:13px; color:#6b7280;">{{ number_format($order->total_amount, 2) }} · {{ $order->created_at->format('d.m.Y') }}</div>

                            @if(count($this->allowedFor($status)) > 0)
                                <div style="margin-top:8px; display:flex; gap:4px; flex-wrap:wrap;">
                                    @foreach($this->allowedFor($status) as $next)
                                        <button wire:click="changeStatus({{ $order->id }}, '{{ $next }}')" style="font-size:12px; padding:4px 8px; border:1px solid #d1d5db; border-radius:4px; background:#fff; cursor:pointer;">→ {{ $next }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div style="font-size:13px; color:#9ca3af;">Nema porudžbina</div>
                @endif
            </div>
        @endforeach
    </div>
</div>