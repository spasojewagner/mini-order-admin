@extends('layouts.app')
@section('title', 'Kupci')

@section('content')
    <h1>Kupci</h1>

    <form method="GET" action="{{ route('customers.index') }}" style="display:flex; gap:8px; margin-bottom:16px;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Pretraga po imenu, firmi, emailu, telefonu...">
        <button class="btn" type="submit">Pretraži</button>
        <a class="btn btn-secondary" href="{{ route('customers.create') }}">+ Novi kupac</a>
    </form>

    @if($customers->count())
        <table>
            <thead>
                <tr>
                    <th>Tip</th>
                    <th>Ime</th>
                    <th>Firma</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Akcije</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                    <tr>
                        <td>{{ $customer->type === 'company' ? 'Firma' : 'Fizičko lice' }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->company_name ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td class="actions">
                            <a class="btn btn-secondary" href="{{ route('customers.edit', $customer) }}">Izmeni</a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Obrisati kupca?')">
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
            {{ $customers->links() }}
        </div>
    @else
        <div class="card">Nema kupaca. Dodaj prvog klikom na "Novi kupac".</div>
    @endif
@endsection