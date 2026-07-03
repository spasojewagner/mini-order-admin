@extends('layouts.app')
@section('title', 'Porudžbine')

@section('content')
    <h1>Porudžbine</h1>

    <div style="margin-bottom:16px;">
        <a class="btn" href="{{ route('orders.create') }}">+ Nova porudžbina</a>
    </div>

    @if($orders->count())
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kupac</th>
                    <th>Status</th>
                    <th>Ukupno</th>
                    <th>Datum</th>
                    <th>Akcije</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->customer->name ?? '-' }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ number_format($order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at->format('d.m.Y') }}</td>
                        <td>
                            <a class="btn btn-secondary" href="{{ route('orders.show', $order) }}">Detalji</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $orders->onEachSide(1)->links('vendor.pagination.default') }}
        </div>
    @else
        <div class="card">Nema porudžbina. Kreiraj prvu klikom na "Nova porudžbina".</div>
    @endif
@endsection