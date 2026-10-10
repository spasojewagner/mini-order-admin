<x-mail::message>
# Order backlog

{{ $summary }}

<x-mail::table>
| Order | Customer | Amount (RSD) |
|:------|:---------|-------------:|
@foreach ($orders as $order)
| SO-{{ str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT) }} | {{ $order['customer'] }} | {{ number_format((float) $order['amount'], 2, ',', '.') }} |
@endforeach
</x-mail::table>

@if ($recommendation)
**Recommendation:** {{ $recommendation }}
@endif

<x-mail::button :url="rtrim(config('app.url'), '/') . '/admin/orders'">
Open orders
</x-mail::button>

<x-mail::subcopy>
Written by the order monitor agent. Thresholds can be changed under Settings in the admin panel.
</x-mail::subcopy>
</x-mail::message>
