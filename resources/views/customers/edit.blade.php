@extends('layouts.app')
@section('title', 'Izmena kupca')

@section('content')
    <h1>Izmena kupca</h1>

    <div class="card">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')

            <label>Tip</label>
            <select name="type">
                <option value="individual" {{ old('type', $customer->type) === 'individual' ? 'selected' : '' }}>Fizičko lice</option>
                <option value="company" {{ old('type', $customer->type) === 'company' ? 'selected' : '' }}>Firma</option>
            </select>
            @error('type') <div class="error">{{ $message }}</div> @enderror

            <label>Ime *</label>
            <input type="text" name="name" value="{{ old('name', $customer->name) }}">
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label>Naziv firme</label>
            <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}">
            @error('company_name') <div class="error">{{ $message }}</div> @enderror

            <label>PIB</label>
            <input type="text" name="tax_id" value="{{ old('tax_id', $customer->tax_id) }}">
            @error('tax_id') <div class="error">{{ $message }}</div> @enderror

            <label>Email</label>
            <input type="text" name="email" value="{{ old('email', $customer->email) }}">
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label>Telefon</label>
            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}">
            @error('phone') <div class="error">{{ $message }}</div> @enderror

            <label>Adresa</label>
            <input type="text" name="address" value="{{ old('address', $customer->address) }}">
            @error('address') <div class="error">{{ $message }}</div> @enderror

            <div style="margin-top:20px; display:flex; gap:8px;">
                <button class="btn" type="submit">Sačuvaj izmene</button>
                <a class="btn btn-secondary" href="{{ route('customers.index') }}">Otkaži</a>
            </div>
        </form>
    </div>
@endsection