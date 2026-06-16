<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $data->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2d2d2d; padding: 20mm; }
        .wrap { padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { height: 90px; }
        .doc-title { font-size: 30px; font-weight: bold; color: #2f6f9e; }
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
        .below { margin-top: 24px; }
        .below td { vertical-align: top; }
        .below .left { width: 58%; padding-right: 24px; }
        .below .right { width: 42%; }
        .sec-title { font-weight: bold; color: #2d2d2d; padding-bottom: 5px; border-bottom: 1.5px solid #2c3e50; margin-bottom: 8px; }
        .sec-block { margin-bottom: 22px; }
        .pay-line { line-height: 1.9; white-space: nowrap; }
        .terms { font-style: italic; font-weight: bold; line-height: 1.5; }
        .sum-table td { padding: 6px 0; font-size: 11px; }
        .sum-table td.lbl { text-align: right; font-weight: bold; color: #444; padding-right: 20px; }
        .sum-table td.val { text-align: right; }
        .sum-div td { border-top: 1.5px solid #2c3e50; padding-top: 0; height: 1px; }
        .sum-grand td { font-weight: bold; font-size: 12px; padding-top: 8px; }
        .sign { margin-top: 30px; text-align: center; }
        .sign .date { font-weight: bold; margin-bottom: 50px; }
        .sign .role { font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $company = config('company');
        $so = $data->salesOrder;
        $billName = $so->mitra->name ?? $so->customer_name ?? '-';
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <img src="{{ public_path('images/sofikopi-store.png') }}" class="logo" alt="Sofikopi">
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="doc-title">Invoice</div>
                <table class="meta-table" style="margin-top: 10px;">
                    <tr><td class="meta-label">Referensi</td><td class="meta-value">{{ $data->invoice_number ?? '-' }}</td></tr>
                    <tr><td class="meta-label">Tanggal</td><td class="meta-value">{{ $data->invoice_date ? $data->invoice_date->format('d/m/Y') : '-' }}</td></tr>
                    <tr><td class="meta-label">Tgl. Jatuh Tempo</td><td class="meta-value">{{ $data->due_date ? $data->due_date->format('d/m/Y') : '-' }}</td></tr>
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
                <div class="party-title">Tagihan Untuk</div>
                <div class="party-name">{{ $billName }}</div>
                <div class="party-line">{{ $so->customer_name ?? '-' }}</div>
                <div class="party-line">{{ $so->customer_address ?? '-' }}</div>
                <div class="party-line">Telp: {{ $so->customer_phone ?? '-' }}</div>
                <div class="party-line">Email: {{ $so->customer_email ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="ctr">Qty</th>
                <th class="ctr">Satuan</th>
                <th class="num">Harga</th>
                <th class="ctr">Disc</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($so->items ?? [] as $item)
            <tr>
                <td>{{ $item->product->sku ?? '-' }} - {{ $item->product->name ?? '-' }}</td>
                <td class="ctr">{{ $item->quantity }}</td>
                <td class="ctr">{{ $item->product->unit ?? '-' }}</td>
                <td class="num">{{ number_format($item->unit_price, 2, '.', ',') }}</td>
                <td class="ctr">{{ $item->discount_percent ?? 0 }}%</td>
                <td class="num">{{ number_format($item->line_total, 2, '.', ',') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="ctr" style="color:#999;">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="below">
        <tr>
            <td class="left">
                <div class="sec-block">
                    <div class="sec-title">Keterangan</div>
                    <div>Pembayaran dengan metode transfer dapat dilakukan melalui rekening :</div>
                    <div class="pay-line" style="margin-top: 8px;">
                        Nama Rekening : <strong>{{ $data->bank_account_name ?? $company['bank_account_name'] }}</strong><br>
                        Nomor Rekening : <strong>{{ $data->bank_account_number ?? $company['bank_account_number'] }}</strong><br>
                        Nama Bank : <strong>{{ $data->bank_name ?? $company['bank_name'] }}</strong>
                    </div>
                </div>
                <div class="sec-block">
                    <div class="sec-title">Syarat &amp; Ketentuan</div>
                    <div class="terms">{{ $data->terms ?? 'Mohon melakukan pembayaran invoice satu hari sebelum tanggal jatuh tempo.' }}</div>
                </div>
            </td>
            <td class="right">
                <table class="sum-table">
                    <tr><td class="lbl">Subtotal</td><td class="val">Rp {{ number_format($data->subtotal, 2, '.', ',') }}</td></tr>
                    <tr><td class="lbl">Total</td><td class="val">Rp {{ number_format($data->grand_total, 2, '.', ',') }}</td></tr>
                    <tr class="sum-div"><td colspan="2"></td></tr>
                    <tr class="sum-grand"><td class="lbl">Jumlah Tertagih:</td><td class="val">Rp {{ number_format($data->grand_total, 2, '.', ',') }}</td></tr>
                </table>
                <div class="sign">
                    <div class="date">{{ $data->invoice_date ? $data->invoice_date->translatedFormat('d M, Y') : date('d M, Y') }}</div>
                    <div class="role">CFO {{ $company['display_name'] }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
