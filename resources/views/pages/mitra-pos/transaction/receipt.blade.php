<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $transaction->transaction_no }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            width: 58mm;
            margin: 0 auto;
            padding: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .item-name {
            display: block;
        }

        .item-detail {
            display: flex;
            justify-content: space-between;
        }

        .void-stamp {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #000;
            padding: 4px;
            margin: 8px 0;
            transform: rotate(-3deg);
        }

        .print-controls {
            text-align: center;
            margin-bottom: 10px;
        }

        @media print {
            .print-controls {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button onclick="window.print()">Cetak</button>
    </div>

    <div class="center bold">{{ $mitra->name }}</div>
    @if ($mitra->address)
        <div class="center">{{ $mitra->address }}</div>
    @endif
    @if ($mitra->phone)
        <div class="center">{{ $mitra->phone }}</div>
    @endif

    <div class="divider"></div>

    @if ($transaction->status === 'voided')
        <div class="void-stamp">VOID</div>
    @endif

    <div>No: {{ $transaction->transaction_no }}</div>
    <div>Tgl: {{ optional($transaction->transacted_at)->format('d/m/Y H:i') }}</div>
    <div>Kasir: {{ $transaction->user->name ?? '-' }}</div>
    <div>Bayar: {{ strtoupper($transaction->payment_method) }}</div>

    <div class="divider"></div>

    @foreach ($transaction->items as $item)
        <div class="item-name">{{ $item->product_name }}</div>
        <div class="item-detail">
            <span>{{ rtrim(rtrim(number_format($item->qty, 3, ',', '.'), '0'), ',') }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
            <span>{{ number_format($item->line_total, 0, ',', '.') }}</span>
        </div>
    @endforeach

    <div class="divider"></div>

    <div class="row">
        <span>Subtotal</span>
        <span>{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
    </div>
    <div class="row">
        <span>Diskon</span>
        <span>{{ number_format($transaction->discount, 0, ',', '.') }}</span>
    </div>
    @if ((float) $transaction->service_charge > 0)
    <div class="row">
        <span>Service Charge</span>
        <span>{{ number_format($transaction->service_charge, 0, ',', '.') }}</span>
    </div>
    @endif
    @if ((float) $transaction->tax > 0)
    <div class="row">
        <span>Pajak</span>
        <span>{{ number_format($transaction->tax, 0, ',', '.') }}</span>
    </div>
    @endif
    <div class="row bold">
        <span>TOTAL</span>
        <span>{{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
    </div>

    <div class="divider"></div>

    <div class="center">{{ $footer ?? 'Terima kasih atas kunjungan Anda' }}</div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
