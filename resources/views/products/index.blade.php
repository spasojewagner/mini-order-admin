@extends('layouts.app')
@section('title', 'Proizvodi')

@section('content')
    <h1>Proizvodi</h1>

    <form method="GET" action="{{ route('products.index') }}" style="display:flex; gap:8px; margin-bottom:16px;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Pretraga po nazivu ili SKU...">
        <button class="btn" type="submit">Pretraži</button>
        <a class="btn btn-secondary" href="{{ route('products.create') }}">+ Novi proizvod</a>
    </form>

    @if($products->count())
        <table>
            <thead>
                <tr>
                    <th>Naziv</th>
                    <th>SKU</th>
                    <th>Cena</th>
                    <th>Lager</th>
                    <th>Status</th>
                    <th>Akcije</th>
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
                        <td class="actions">
                            <a class="btn btn-secondary" href="{{ route('products.edit', $product) }}">Izmeni</a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Obrisati proizvod?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Obriši</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $products->links() }}
        </div>
    @else
        <div class="card">Nema proizvoda. Dodaj prvi klikom na "Novi proizvod".</div>
    @endif
@endsection