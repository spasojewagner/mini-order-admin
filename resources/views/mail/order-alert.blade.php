<x-mail::message>
# Neobrađene porudžbine

{{ $summary }}

<x-mail::table>
| #  | Kupac            | Vrednost |
|:---|:-----------------|---------:|
@foreach ($orders as $order)
| {{ $order['id'] }} | {{ $order['customer'] }} | {{ number_format((float) $order['amount'], 2, ',', '.') }} |
@endforeach
</x-mail::table>

@if ($recommendation)
**Preporuka:** {{ $recommendation }}
@endif

<x-mail::button :url="rtrim(config('app.url'), '/') . '/admin/orders'">
Otvori porudžbine
</x-mail::button>

<x-mail::subcopy>
Ovu poruku je sastavio automatski agent koji prati porudžbine.
</x-mail::subcopy>
</x-mail::message>