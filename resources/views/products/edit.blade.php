@extends('layouts.app')
@section('title', 'Izmena proizvoda')

@section('content')
    <h1>Izmena proizvoda</h1>

    <div class="card">
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PUT')

            <label>Naziv *</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}">
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label>SKU</label>
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}">
            @error('sku') <div class="error">{{ $message }}</div> @enderror

            <label>Cena *</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}">
            @error('price') <div class="error">{{ $message }}</div> @enderror

            <label>Lager *</label>
            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}">
            @error('stock_quantity') <div class="error">{{ $message }}</div> @enderror

            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} style="width:auto;">
                Aktivan
            </label>
            @error('is_active') <div class="error">{{ $message }}</div> @enderror

            <div style="margin-top:20px; display:flex; gap:8px;">
                <button class="btn" type="submit">Sačuvaj izmene</button>
                <a class="btn btn-secondary" href="{{ route('products.index') }}">Otkaži</a>
            </div>
        </form>
    </div>
@endsection