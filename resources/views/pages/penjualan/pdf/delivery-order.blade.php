<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $data->do_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2d2d2d; padding: 20mm; }
        .wrap { padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { height: 56px; }
        .company-name { font-weight: bold; font-size: 15px; color: #2d2d2d; }
        .company-line { color: #444; font-size: 10px; line-height: 1.45; }
        .doc-title { font-size: 22px; font-weight: bold; color: #2c3e50; }
        .doc-no { font-size: 11px; color: #555; margin-top: 2px; }
        .divider { border: none; border-top: 2px solid #2c3e50; margin: 14px 0; }
        .addr-table { margin-top: 6px; }
        .addr-table td { width: 50%; vertical-align: top; padding-right: 16px; }
        .addr-box { border: 1px solid #999; padding: 10px 12px; min-height: 78px; }
        .addr-title { font-size: 11px; color: #2d2d2d; font-weight: bold; margin-bottom: 6px; text-decoration: underline; }
        .addr-name { font-weight: bold; font-size: 12px; margin-bottom: 3px; }
        .addr-line { color: #444; line-height: 1.5; }
        .pickup-notice { margin-top: 14px; background: #eaf4f7; border: 1px solid #b6dde8; padding: 10px 12px; color: #155e75; font-weight: bold; text-align: center; }
        .items { margin-top: 16px; }
        .items th { background: #2c3e50; color: #fff; padding: 8px 6px; font-size: 10px; text-align: left; font-weight: bold; }
        .items th.ctr, .items td.ctr { text-align: center; }
        .items td { padding: 9px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
        .items tbody tr:nth-child(even) { background: #f4f4f4; }
        .prod-name { font-weight: bold; }
        .prod-desc { color: #666; font-size: 9.5px; margin-top: 2px; }
        .sign { margin-top: 48px; }
        .sign td { width: 50%; vertical-align: top; text-align: center; }
        .sign .role { margin-bottom: 60px; }
        .sign .line { border-top: 1px solid #999; width: 180px; margin: 0 auto; padding-top: 4px; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    @php $company = config('company'); @endphp

    <table class="header-table">
        <tr>
            <td style="width: 12%;">
                <img src="{{ public_path('images/sofikopi-store.png') }}" class="logo" alt="Sofikopi">
            </td>
            <td style="width: 58%; padding-left: 6px;">
                <div class="company-name">{{ $company['display_name'] }}</div>
                <div class="company-line">{{ $company['address'] ?: '-' }}</div>
                <div class="company-line">Telp: {{ $company['phone'] ?: '-' }}</div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div class="doc-title">Surat Jalan</div>
                <div class="doc-no">{{ $data->do_number ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <table class="addr-table">
        <tr>
            <td>
                <div class="addr-box">
                    <div class="addr-title">Ditujukan untuk:</div>
                    <div class="addr-name">{{ $data->salesOrder->customer_name ?? '-' }}</div>
                    <div class="addr-line">{{ $data->salesOrder->customer_address ?? '-' }}</div>
                    <div class="addr-line">Telp: {{ $data->salesOrder->customer_phone ?? '-' }}</div>
                </div>
            </td>
            <td>
                <div class="addr-box">
                    <div class="addr-line"><strong>Tanggal :</strong> {{ $data->delivery_date ? $data->delivery_date->translatedFormat('d M Y') : date('d M Y') }}</div>
                    <div class="addr-line" style="margin-top: 4px;">Ref. SO: {{ $data->salesOrder->order_number ?? '-' }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if($data->delivery_type == 'self_pickup')
    <div class="pickup-notice">Ambil di Store</div>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th class="ctr" style="width: 6%;">No</th>
                <th>Nama Produk &amp; Deskripsi Produk</th>
                <th style="width: 22%;">Kode Produk (SKU)</th>
                <th class="ctr" style="width: 12%;">Kuantitas</th>
                <th class="ctr" style="width: 10%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data->salesOrder->items ?? [] as $index => $item)
            <tr>
                <td class="ctr">{{ $index + 1 }}</td>
                <td>
                    <div class="prod-name">{{ $item->product->name ?? '-' }}</div>
                    @if(!empty($item->product->description))
                    <div class="prod-desc">{{ $item->product->description }}</div>
                    @endif
                </td>
                <td>{{ $item->product->sku ?? '-' }}</td>
                <td class="ctr">{{ $item->quantity }}</td>
                <td class="ctr">{{ $item->product->unit ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="ctr" style="color:#999;">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>
                <div class="role">Diterima oleh,</div>
                <div class="line">{{ $data->received_by_name ?? '(..................)' }}</div>
            </td>
            <td>
                <div class="role">Dikirim oleh,</div>
                <div class="line">{{ $company['display_name'] }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
