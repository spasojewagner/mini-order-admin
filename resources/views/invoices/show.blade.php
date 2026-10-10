@php($money = fn ($v) => \App\Support\Money::format($v, false))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->invoice_number }} · {{ $company['name'] }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Inter", "Segoe UI", Arial, sans-serif; color: #1f2937; font-size: 13px; line-height: 1.45; margin: 0; background: #f3f4f6; }
        .sheet { max-width: 210mm; margin: 24px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button, .toolbar a { font: inherit; padding: 8px 14px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #111827; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #111827; color: #fff; border-color: #111827; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 14px; }
        .brand { font-size: 20px; font-weight: 700; color: #111827; }
        .muted { color: #6b7280; }
        h1 { font-size: 26px; letter-spacing: .04em; margin: 0; text-align: right; }
        .meta { text-align: right; margin-top: 6px; }
        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 22px 0; }
        .label { text-transform: uppercase; font-size: 11px; letter-spacing: .06em; color: #6b7280; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding: 8px 6px; }
        td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: 300px; margin-top: 14px; }
        .totals td { border: 0; padding: 4px 6px; }
        .totals .grand td { border-top: 2px solid #111827; font-size: 16px; font-weight: 700; padding-top: 8px; }
        .payment { margin-top: 28px; padding: 12px 14px; background: #f9fafb; border-radius: 6px; }
        footer { margin-top: 32px; font-size: 11px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="{{ url()->previous() }}">← Back</a>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">
    <header>
        <div>
            <div class="brand">{{ $company['name'] }}</div>
            <div class="muted">{{ $company['address'] }}</div>
            <div class="muted">Tax ID (PIB) {{ $company['tax_id'] }} · Reg. no. {{ $company['registration_number'] }}</div>
            <div class="muted">{{ $company['email'] }} · {{ $company['phone'] }}</div>
        </div>
        <div>
            <h1>INVOICE</h1>
            <div class="meta">
                <div><strong>{{ $order->invoice_number }}</strong></div>
                <div class="muted">Issued {{ $issued->format('d.m.Y') }}</div>
                <div class="muted">Delivery {{ ($order->shipped_at ?? $issued)->format('d.m.Y') }}</div>
                <div class="muted">Due {{ $due->format('d.m.Y') }}</div>
            </div>
        </div>
    </header>

    <section class="parties">
        <div>
            <div class="label">Bill to</div>
            <div><strong>{{ $customer->company_name ?: $customer->name }}</strong></div>
            @if ($customer->company_name)<div>Attn. {{ $customer->name }}</div>@endif
            @if ($customer->address)<div>{{ $customer->address }}</div>@endif
            @if ($customer->tax_id)<div>Tax ID (PIB) {{ $customer->tax_id }}</div>@endif
            @if ($customer->email)<div class="muted">{{ $customer->email }}</div>@endif
        </div>
        <div>
            <div class="label">Order</div>
            <div>{{ $order->number() }} of {{ $order->created_at->format('d.m.Y') }}</div>
            @if ($order->note)<div class="muted">Note: {{ $order->note }}</div>@endif
        </div>
    </section>

    <table>
        <thead>
        <tr>
            <th style="width: 36px">#</th>
            <th>Item</th>
            <th class="num">Qty</th>
            <th class="num">Unit price</th>
            <th class="num">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->items as $i => $item)
            <tr>
                <td class="muted">{{ $i + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ $money($item->unit_price) }}</td>
                <td class="num">{{ $money($item->subtotal) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Net amount</td><td class="num">{{ $money($totals['net']) }}</td></tr>
        <tr><td>VAT {{ $totals['vat_rate'] }}%</td><td class="num">{{ $money($totals['vat']) }}</td></tr>
        <tr class="grand"><td>Total RSD</td><td class="num">{{ $money($totals['gross']) }}</td></tr>
    </table>

    <div class="payment">
        <div class="label">Payment</div>
        Please pay <strong>{{ \App\Support\Money::format($totals['gross']) }}</strong> by {{ $due->format('d.m.Y') }}
        to account <strong>{{ $company['bank_account'] }}</strong>, reference <strong style="white-space: nowrap">{{ $order->invoice_number }}</strong>.
    </div>

    <footer>
        Generated by {{ $company['name'] }} order management. This invoice is valid without a signature or stamp.
    </footer>
</div>
</body>
</html>
