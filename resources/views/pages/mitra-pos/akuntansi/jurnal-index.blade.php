@extends('layouts/layoutMaster')

@section('title', 'Jurnal Akuntansi' . (isset($mitra) ? ' - ' . $mitra->name : ''))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Jurnal</h4>
        @can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'akuntansi-jurnal.index', 'create'])
        <a href="{{ $routes['create'] }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Jurnal Manual
        </a>
        @endcan
    </div>

    @include('pages.mitra-pos.akuntansi._nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $hasFilter = collect($filters ?? [])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
        $selectedAccount = ! empty($filters['account_id']) ? $filterAccounts->firstWhere('id', (int) $filters['account_id']) : null;
    @endphp

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ $routes['index'] }}" class="row g-3 align-items-end" data-live-filter>
                <div class="col-md-2">
                    <label class="form-label" for="filter-from">Dari Tanggal</label>
                    <input type="date" id="filter-from" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter-to">Sampai Tanggal</label>
                    <input type="date" id="filter-to" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter-q">Cari keterangan / no. jurnal</label>
                    <input type="search" id="filter-q" name="q" class="form-control" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: listrik" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter-transaction">No. transaksi</label>
                    <input type="search" id="filter-transaction" name="transaction" class="form-control" maxlength="100" value="{{ $filters['transaction'] ?? '' }}" placeholder="Contoh: 0012" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="filter-account">Akun (opsional)</label>
                    <select id="filter-account" name="account_id" class="form-select js-select2" data-placeholder="Semua akun" data-allow-clear>
                        <option value="">Semua akun</option>
                        @foreach ($filterAccounts as $account)
                        <option value="{{ $account->id }}" @selected((int) ($filters['account_id'] ?? 0) === $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div id="live-results">
    <p class="text-muted mb-2">
        @if ($hasFilter)
            Ditemukan <strong>{{ number_format($entries->total(), 0, ',', '.') }}</strong> jurnal sesuai filter
            @if (! empty($filters['transaction']))
                untuk transaksi <strong>{{ $filters['transaction'] }}</strong>
            @endif
            @if ($selectedAccount)
                pada akun <strong>{{ $selectedAccount->code }} {{ $selectedAccount->name }}</strong>
            @endif
            .
        @else
            Total <strong>{{ number_format($entries->total(), 0, ',', '.') }}</strong> jurnal.
        @endif
    </p>

    @if (! empty($accountSummary))
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="fw-semibold mb-2">{{ $accountSummary['account']->code }} {{ $accountSummary['account']->name }}</div>
            <div class="row g-2 small">
                <div class="col-md-4"><span class="text-muted">Saldo awal</span><br>Rp {{ number_format($accountSummary['opening_balance'], 0, ',', '.') }}</div>
                <div class="col-md-4"><span class="text-muted">Total Debit (jurnal di bawah)</span><br>Rp {{ number_format($accountSummary['total_debit'], 0, ',', '.') }}</div>
                <div class="col-md-4"><span class="text-muted">Total Kredit (jurnal di bawah)</span><br>Rp {{ number_format($accountSummary['total_credit'], 0, ',', '.') }}</div>
            </div>
            <div class="small text-muted mt-2">Hanya baris milik akun ini yang dihitung. Saldo awal tidak tercatat di jurnal.</div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No. Jurnal</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Sumber</th>
                        <th class="text-end">Nilai Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    @php
                        $trx = $entry->reference instanceof \App\Models\PosTransaction ? $entry->reference : null;
                        $rincianId = 'rincian-jurnal-'.$entry->id;
                    @endphp
                    <tr role="button" data-bs-toggle="collapse" data-bs-target="#{{ $rincianId }}" aria-expanded="false" aria-controls="{{ $rincianId }}">
                        <td><i class="ri-arrow-right-s-line me-1"></i>{{ $entry->entry_no }}</td>
                        <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>
                            @switch($entry->source_type)
                                @case('manual')
                                    <span class="badge bg-label-secondary">Manual</span>
                                    @break
                                @case('pos_sale')
                                    <span class="badge bg-label-success">Penjualan POS</span>
                                    @break
                                @case('pos_void')
                                    <span class="badge bg-label-danger">Void POS</span>
                                    @if ($trx)
                                    <div class="small mt-1"><a href="{{ $routes['index'] }}?{{ http_build_query(['transaction' => $trx->transaction_no]) }}" onclick="event.stopPropagation()">Lihat jurnal penjualan asli</a></div>
                                    @endif
                                    @break
                                @case('stock_purchase')
                                    <span class="badge bg-label-info">Pembelian Stok</span>
                                    @break
                                @default
                                    <span class="badge bg-label-secondary">Lainnya</span>
                            @endswitch
                        </td>
                        <td class="text-end">Rp {{ number_format($entry->nilai_transaksi, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="collapse" id="{{ $rincianId }}">
                        <td colspan="5" class="bg-lighter">
                            @if ($trx)
                            <div class="mb-3">
                                <h6 class="mb-2">Detail Transaksi {{ $trx->transaction_no }}</h6>
                                <div class="small text-muted mb-2">
                                    Pembayaran: {{ strtoupper($trx->payment_method) }}
                                    · Mode: {{ str_replace('_', ' ', $trx->sales_mode) }}
                                    @if ($entry->source_type === 'pos_void')
                                        · <span class="text-danger">Void — jurnal ini membalik jurnal penjualan</span>
                                    @endif
                                </div>
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Harga</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($trx->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name }}</td>
                                            <td class="text-end">{{ rtrim(rtrim(number_format($item->qty, 3, ',', '.'), '0'), ',') }}</td>
                                            <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                        @if ((float) $trx->discount > 0)
                                        <tr><td colspan="3" class="text-end">Diskon</td><td class="text-end">− Rp {{ number_format($trx->discount, 0, ',', '.') }}</td></tr>
                                        @endif
                                        @if ((float) $trx->service_charge > 0)
                                        <tr><td colspan="3" class="text-end">Service charge</td><td class="text-end">Rp {{ number_format($trx->service_charge, 0, ',', '.') }}</td></tr>
                                        @endif
                                        @if ((float) $trx->tax > 0)
                                        <tr><td colspan="3" class="text-end">Pajak</td><td class="text-end">Rp {{ number_format($trx->tax, 0, ',', '.') }}</td></tr>
                                        @endif
                                        <tr class="fw-semibold">
                                            <td colspan="3" class="text-end">Total dibayar pelanggan</td>
                                            <td class="text-end">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            <h6 class="mb-2">Rincian Jurnal</h6>
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Akun</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($entry->lines as $line)
                                    <tr>
                                        <td>{{ $line->account?->code }} {{ $line->account?->name }}</td>
                                        <td class="text-end">{{ (float) $line->debit > 0 ? 'Rp '.number_format($line->debit, 0, ',', '.') : '' }}</td>
                                        <td class="text-end">{{ (float) $line->credit > 0 ? 'Rp '.number_format($line->credit, 0, ',', '.') : '' }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="fw-semibold">
                                        <td>Total</td>
                                        <td class="text-end">Rp {{ number_format($entry->lines->sum('debit'), 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($entry->lines->sum('credit'), 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ $hasFilter ? 'Tidak ada jurnal yang cocok dengan filter ini.' : 'Belum ada jurnal.' }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $entries->withQueryString()->links() }}
        </div>
    </div>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
@endsection
