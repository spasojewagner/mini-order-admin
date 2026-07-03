@extends('layouts.app')
@section('title', 'Nova porudžbina')

@section('content')
    <h1>Nova porudžbina</h1>

    @if($errors->any())
        <div class="card" style="border:1px solid #dc2626; margin-bottom:16px;">
            <strong style="color:#dc2626;">Greške:</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('orders.store') }}">
            @csrf

            <label>Kupac *</label>
            <input type="text" list="customers-list" id="customer-search" placeholder="Ukucaj ime kupca..." autocomplete="off">
            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
            <datalist id="customers-list">
                @foreach($customers as $customer)
                    <option data-id="{{ $customer->id }}" value="{{ $customer->name }}{{ $customer->company_name ? ' (' . $customer->company_name . ')' : '' }}"></option>
                @endforeach
            </datalist>

            <label style="margin-top:20px;">Stavke *</label>
            <table id="items-table" style="margin-bottom:12px;">
                <thead>
                    <tr>
                        <th>Proizvod</th>
                        <th style="width:110px;">Cena</th>
                        <th style="width:100px;">Količina</th>
                        <th style="width:120px;">Ukupno</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    <!-- redovi se dodaju preko JS-a -->
                </tbody>
            </table>

            <button type="button" class="btn btn-secondary" onclick="addRow()">+ Dodaj stavku</button>

            <div style="margin-top:16px; font-size:18px;">
                <strong>Ukupno: <span id="grand-total">0.00</span></strong>
            </div>

            <label style="margin-top:20px;">Napomena</label>
            <textarea name="note" rows="2">{{ old('note') }}</textarea>

            <div style="margin-top:20px; display:flex; gap:8px;">
                <button class="btn" type="submit">Sačuvaj porudžbinu</button>
                <a class="btn btn-secondary" href="{{ route('orders.index') }}">Otkaži</a>
            </div>
        </form>
    </div>

    <script>
        // Podaci o proizvodima iz baze (id -> {name, price})
        const products = @json($products->mapWithKeys(fn($p) => [$p->id => ['name' => $p->name, 'price' => (float) $p->price]]));

        let rowIndex = 0;

        function addRow() {
            const body = document.getElementById('items-body');
            const i = rowIndex++;

            let options = '';
            for (const [id, p] of Object.entries(products)) {
                options += `<option data-id="${id}" value="${p.name}"></option>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" list="products-list-${i}" placeholder="Ukucaj proizvod..." autocomplete="off" onchange="selectProduct(this)">
                    <datalist id="products-list-${i}">${options}</datalist>
                    <input type="hidden" name="items[${i}][product_id]" class="product-id">
                </td>
                <td class="price">0.00</td>
                <td>
                    <input type="number" name="items[${i}][quantity]" value="1" min="1" onchange="updateRow(this)" oninput="updateRow(this)">
                </td>
                <td class="subtotal">0.00</td>
                <td>
                    <button type="button" class="btn btn-danger" onclick="removeRow(this)">×</button>
                </td>
            `;
            body.appendChild(tr);
        }

        // Kad se izabere proizvod u redu, upiši id i preračunaj
        function selectProduct(input) {
            const row = input.closest('tr');
            const datalist = row.querySelector('datalist');
            const opt = [...datalist.querySelectorAll('option')].find(o => o.value === input.value);
            row.querySelector('.product-id').value = opt ? opt.dataset.id : '';
            updateRow(input);
        }

        function updateRow(el) {
            const row = el.closest('tr');
            const productId = row.querySelector('.product-id').value;
            const qty = parseInt(row.querySelector('input[type=number]').value) || 0;
            const price = productId ? products[productId].price : 0;

            row.querySelector('.price').textContent = price.toFixed(2);
            row.querySelector('.subtotal').textContent = (price * qty).toFixed(2);

            updateTotal();
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            updateTotal();
        }

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('#items-body .subtotal').forEach(cell => {
                total += parseFloat(cell.textContent) || 0;
            });
            document.getElementById('grand-total').textContent = total.toFixed(2);
        }

        // Kad se izabere kupac iz datalist-a, upiši njegov id u skriveno polje
        document.getElementById('customer-search').addEventListener('change', function () {
            const opt = [...document.querySelectorAll('#customers-list option')].find(o => o.value === this.value);
            document.getElementById('customer_id').value = opt ? opt.dataset.id : '';
        });

        // Dodaj prvu stavku odmah pri otvaranju
        addRow();
    </script>
@endsection