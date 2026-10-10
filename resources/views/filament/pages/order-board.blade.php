<x-filament-panels::page>
    <div wire:poll.30s style="display:grid; grid-template-columns:repeat(auto-fit, minmax(215px, 1fr)); gap:1rem; align-items:start">
        @foreach ($this->getColumns() as $column)
            @php($status = $column['status'])
            <x-filament::section compact>
                <x-slot name="heading">
                    <span style="display:inline-flex; align-items:center; gap:.5rem">
                        <x-filament::icon :icon="$status->icon()" style="width:1.1rem; height:1.1rem" />
                        {{ $status === \App\Enums\OrderStatus::Shipped ? 'Shipped (3 days)' : $status->label() }}
                        <x-filament::badge :color="$status->color()" size="sm">{{ $column['orders']->count() }}</x-filament::badge>
                    </span>
                </x-slot>

                <div style="display:flex; flex-direction:column; gap:.6rem">
                    @forelse ($column['orders'] as $order)
                        @php($since = $order->statusSince())
                        @php($late = $status !== \App\Enums\OrderStatus::Shipped && $since->lt(now()->subHours(\App\Support\Settings::int('orders.waiting_hours'))))
                        <div wire:key="order-{{ $order->id }}" style="border:1px solid rgba(148,163,184,.35); border-left:3px solid {{ $late ? '#ef4444' : 'rgba(99,102,241,.6)' }}; border-radius:.5rem; padding:.6rem .7rem">
                            <div style="display:flex; justify-content:space-between; gap:.5rem">
                                <x-filament::link :href="$this->orderUrl($order)" weight="semibold">{{ $order->number() }}</x-filament::link>
                                <span style="font-size:.8rem; {{ $late ? 'color:#ef4444; font-weight:600' : 'opacity:.7' }}">{{ $since->diffForHumans(short: true) }}</span>
                            </div>
                            <div style="font-size:.875rem; margin-top:.15rem">{{ $order->customer?->displayName() }}</div>
                            <div style="font-size:.8rem; opacity:.7; margin-top:.1rem">
                                {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('line', $order->items_count) }} ·
                                {{ \App\Support\Money::short($order->total_amount) }}
                            </div>
                            @if ($steps = $this->nextSteps($order))
                                <div style="display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.5rem">
                                    @foreach ($steps as $step)
                                        <x-filament::button size="xs" :color="$step->color()" :icon="$step->icon()"
                                            wire:click="move({{ $order->id }}, '{{ $step->value }}')"
                                            wire:loading.attr="disabled">
                                            {{ $step->actionLabel() }}
                                        </x-filament::button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:.875rem; opacity:.6">Nothing here.</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
