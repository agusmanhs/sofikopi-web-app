<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order {{ $data->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2d2d2d; padding: 20mm; }
        .wrap { padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { height: 90px; }
        .doc-title { font-size: 28px; font-weight: bold; color: #2f6f9e; }
        .meta-table td { padding: 2px 0; font-size: 10.5px; }
        .meta-label { color: #555; text-align: right; font-weight: bold; padding-right: 18px; width: 50%; }
        .meta-value { text-align: right; }
        .party-table { margin-top: 24px; }
        .party-table td { width: 50%; vertical-align: top; padding-right: 24px; }
        .party-title { font-size: 12px; font-weight: bold; color: #2d2d2d; padding-bottom: 6px; border-bottom: 1.5px solid #2c3e50; margin-bottom: 10px; }
        .party-name { font-weight: bold; font-size: 12px; color: #2f6f9e; margin-bottom: 6px; }
        .party-line { color: #444; line-height: 1.55; }
        .items { margin-top: 22px; }
        .items th { background: #2c3e50; color: #fff; padding: 9px 8px; font-size: 10.5px; text-align: left; font-weight: bold; }
        .items th.num, .items td.num { text-align: right; }
        .items th.ctr, .items td.ctr { text-align: center; }
        .items td { padding: 10px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        .items tbody tr:nth-child(even) { background: #f4f4f4; }
        .prod-name { font-weight: bold; }
        .prod-desc { color: #666; font-size: 10px; margin-top: 2px; }
        .below { margin-top: 24px; }
        .below td { vertical-align: top; }
        .below .left { width: 50%; }
        .below .right { width: 50%; }
        .sum-table td { padding: 9px 0; font-size: 11px; border-bottom: 1px solid #ddd; }
        .sum-table td.lbl { font-weight: bold; color: #444; }
        .sum-table td.val { text-align: right; }
        .sum-grand td { font-weight: bold; }
        .sign { margin-top: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $company = config('company');
        $orderFrom = $data->mitra->name ?? 'NON MITRA';
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <img src="{{ public_path('images/sofikopi-store.png') }}" class="logo" alt="Sofikopi">
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="doc-title">Sales Order</div>
                <table class="meta-table" style="margin-top: 10px;">
                    <tr><td class="meta-label">Referensi</td><td class="meta-value">{{ $data->order_number ?? '-' }}</td></tr>
                    <tr><td class="meta-label">Tanggal</td><td class="meta-value">{{ $data->order_date ? $data->order_date->format('d/m/Y') : '-' }}</td></tr>
                    <tr><td class="meta-label">Status</td><td class="meta-value">{{ ucfirst($data->status) }}</td></tr>
                    <tr><td class="meta-label">NPWP</td><td class="meta-value">{{ $company['npwp'] ?: '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td>
                <div class="party-title">Info Perusahaan</div>
                <div class="party-name">{{ $company['display_name'] }}</div>
                @foreach(array_filter(array_map('trim', explode(',', $company['address']))) as $line)
                <div class="party-line">{{ $line }}</div>
                @endforeach
                <div class="party-line">Telp: {{ $company['phone'] ?: '-' }}</div>
                <div class="party-line">Email: {{ $company['email'] ?: '-' }}</div>
            </td>
            <td>
                <div class="party-title">Order Dari</div>
                <div class="party-name">{{ $orderFrom }}</div>
                <div class="party-line">Telp: {{ $data->customer_phone ?: '-' }}</div>
                <div class="party-line">Email: {{ $data->customer_email ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="ctr">Kuantitas</th>
                <th class="num">Harga</th>
                <th class="ctr">Diskon</th>
                <th class="ctr">Pajak</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data->items ?? [] as $item)
            <tr>
                <td>
                    <div class="prod-name">{{ $item->product->sku ?? '-' }} - {{ $item->product->name ?? '-' }}</div>
                    @if(!empty($item->product->description))
                    <div class="prod-desc">{{ $item->product->description }}</div>
                    @endif
                </td>
                <td class="ctr">{{ $item->quantity }}</td>
                <td class="num">{{ number_format($item->unit_price, 2, '.', ',') }}</td>
                <td class="ctr">{{ $item->discount_percent ?? 0 }} %</td>
                <td class="ctr">{{ ($item->tax_amount ?? 0) > 0 ? number_format($item->tax_amount, 2, '.', ',') : '-' }}</td>
                <td class="num">{{ number_format($item->line_total, 2, '.', ',') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="ctr" style="color:#999;">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="below">
        <tr>
            <td class="left"></td>
            <td class="right">
                <table class="sum-table">
                    <tr><td class="lbl">Subtotal</td><td class="val">Rp {{ number_format($data->subtotal, 2, '.', ',') }}</td></tr>
                    <tr><td class="lbl">Total Diskon</td><td class="val">Rp ({{ number_format($data->discount_total, 2, '.', ',') }})</td></tr>
                    <tr><td class="lbl">Diskon Tambahan</td><td class="val">Rp ({{ number_format($data->additional_discount ?? 0, 2, '.', ',') }})</td></tr>
                    <tr><td class="lbl">Pajak</td><td class="val">Rp {{ number_format($data->tax_amount ?? 0, 2, '.', ',') }}</td></tr>
                    <tr><td class="lbl">Total</td><td class="val">Rp {{ number_format($data->grand_total, 2, '.', ',') }}</td></tr>
                    <tr><td class="lbl">Pajak Inclusive</td><td class="val">Rp {{ number_format(0, 2, '.', ',') }}</td></tr>
                    <tr class="sum-grand"><td class="lbl">Jumlah Tertagih</td><td class="val">Rp {{ number_format($data->grand_total, 2, '.', ',') }}</td></tr>
                </table>
                <div class="sign">{{ $data->order_date ? $data->order_date->translatedFormat('M d, Y') : date('M d, Y') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
