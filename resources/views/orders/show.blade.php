@extends('layouts.app')
@section('title', 'Porudžbina #' . $order->id)

@section('content')
    <h1>Porudžbina #{{ $order->id }}</h1>
    @if(session('error'))
        <div class="card" style="border:1px solid #dc2626; margin-bottom:16px; color:#dc2626;">
            {{ session('error') }}
        </div>
    @endif
    <div class="card" style="margin-bottom:16px;">
        <p><strong>Kupac:</strong> {{ $order->customer->name ?? '-' }}
            {{ $order->customer->company_name ? '(' . $order->customer->company_name . ')' : '' }}
        </p>
        <p><strong>Status:</strong> {{ $order->status }}</p>
        <p><strong>Datum:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>
        @if($order->note)
            <p><strong>Napomena:</strong> {{ $order->note }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Proizvod</th>
                <th>Cena</th>
                <th>Količina</th>
                <th>Ukupno</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:16px; font-size:18px;">
        <strong>Ukupna vrednost: {{ number_format($order->total_amount, 2) }}</strong>
    </div>

    <div style="margin-top:20px; display:flex; gap:8px;">
        <a class="btn btn-secondary" href="{{ route('orders.index') }}">← Nazad na listu</a>

        @if(in_array($order->status, ['draft', 'new']))
            <form method="POST" action="{{ route('orders.confirm', $order) }}"
                onsubmit="return confirm('Potvrditi porudžbinu i skinuti lager?')">
                @csrf
                <button class="btn" type="submit">✓ Potvrdi porudžbinu</button>
            </form>
        @endif
    </div>
@endsection