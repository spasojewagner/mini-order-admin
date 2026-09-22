<x-mail::message>
# Neobrađene porudžbine

{!! nl2br(e($body)) !!}

<x-mail::button :url="config('app.url') . '/admin/orders'">
Otvori porudžbine
</x-mail::button>

Ovu poruku je sastavio automatski agent.
</x-mail::message>