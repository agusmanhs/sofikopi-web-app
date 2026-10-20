@extends('layouts/layoutMaster')

@section('title', 'Dashboard Mitra POS')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js'
])
@endsection

@section('content')
@php
    $canOpenReport = auth()->user()->can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'mitra-report.index', 'read']);
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Dashboard @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>

    <!-- Stat cards -->
    <div class="row g-4 mb-2">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded-3">
                                <i class="ri-money-dollar-circle-line ri-24px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-5">
                        <h5 class="mb-1">Rp {{ number_format($stats['revenue_today'], 0, ',', '.') }}</h5>
                        <p class="mb-0">Omzet Hari Ini</p>
                        <p class="small text-muted mb-1">Total bayar (Grand Total) semua transaksi selesai hari ini, sudah termasuk pajak dan service charge. Transaksi yang dibatalkan tidak dihitung; potongan admin belum dikurangi.</p>
                        @if ($canOpenReport)
                        <a href="{{ $reportUrl }}" class="small">Lihat di Laporan Harian</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded-3">
                                <i class="ri-funds-line ri-24px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-5">
                        <h5 class="mb-1">Rp {{ number_format($stats['revenue_month'], 0, ',', '.') }}</h5>
                        <p class="mb-0">Omzet Bulan Ini</p>
                        <p class="small text-muted mb-1">Sama seperti omzet hari ini, dihitung dari tanggal 1 bulan ini sampai hari ini.</p>
                        @if ($canOpenReport)
                        <a href="{{ $reportUrl }}" class="small">Lihat di Laporan Harian</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded-3">
                                <i class="ri-shopping-cart-2-line ri-24px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-5">
                        <h5 class="mb-1">{{ $stats['tx_count_today'] }} / {{ $stats['tx_count_month'] }}</h5>
                        <p class="mb-0">Transaksi (Hari Ini / Bulan Ini)</p>
                        <p class="small text-muted mb-1">Jumlah transaksi selesai. Transaksi yang dibatalkan tidak dihitung.</p>
                        @if ($canOpenReport)
                        <a href="{{ $reportUrl }}" class="small">Lihat di Laporan Harian</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded-3">
                                <i class="ri-line-chart-line ri-24px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-5">
                        <h5 class="mb-1 {{ $stats['gross_profit_month'] < 0 ? 'text-danger' : '' }}">
                            Rp {{ number_format($stats['gross_profit_month'], 0, ',', '.') }}
                        </h5>
                        <p class="mb-0">Gross Profit Bulan Ini</p>
                        <p class="small text-muted mb-1">Laba kotor = omzet bulan ini dikurangi biaya bahan + cadangan (Faktor Q). Potongan admin dan biaya lain belum dikurangi.</p>
                        @if ($canOpenReport)
                        <a href="{{ $reportUrl }}" class="small">Lihat di Laporan Harian</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Target vs actual -->
    <div class="row g-4 mb-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Target Omzet Bulanan</h5>
                </div>
                <div class="card-body">
                    @if ($target && $target->monthly_revenue_target > 0)
                        @php
                            $progress = min(100, ($stats['revenue_month'] / $target->monthly_revenue_target) * 100);
                        @endphp
                        <div class="d-flex justify-content-between mb-1">
                            <span>Rp {{ number_format($stats['revenue_month'], 0, ',', '.') }} tercapai</span>
                            <span>Target: Rp {{ number_format($target->monthly_revenue_target, 0, ',', '.') }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar {{ $progress >= 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar"
                                style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted small mt-2 mb-0">{{ number_format($progress, 1) }}% dari target bulan ini tercapai.</p>
                    @else
                        <p class="text-muted mb-0">Belum ada target omzet bulanan yang diatur untuk mitra ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-2">
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Komposisi Pembayaran (Bulan Ini)</h5>
                </div>
                <div class="card-body">
                    <div id="paymentMixChart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Top 5 Produk (Omzet)</h5>
                </div>
                <div class="card-body">
                    <div id="topProductsChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock alerts -->
    @php
        $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    @endphp
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Peringatan Stok Bahan Baku</h5>
                    @if ($stockAlertCounts['total'] > 0)
                    <div class="d-flex gap-2">
                        @if ($stockAlertCounts['habis'] > 0)
                        <span class="badge bg-label-danger">{{ $stockAlertCounts['habis'] }} habis</span>
                        @endif
                        @if ($stockAlertCounts['menipis'] > 0)
                        <span class="badge bg-label-warning">{{ $stockAlertCounts['menipis'] }} menipis</span>
                        @endif
                    </div>
                    @endif
                </div>

                @if ($stockAlertCounts['total'] === 0)
                <div class="card-body">
                    <div class="d-flex align-items-center text-success">
                        <i class="ri-checkbox-circle-line fs-3 me-2"></i>
                        <span>Semua stok bahan baku dalam kondisi aman.</span>
                    </div>
                </div>
                @else
                <ul class="list-group list-group-flush">
                    @foreach ($stockAlerts as $material)
                        @php($habis = $material->alert_level === 'red')
                        <li class="list-group-item">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="flex-grow-1" style="min-width: 220px;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-semibold">{{ $material->name }}</span>
                                        <span class="badge {{ $habis ? 'bg-label-danger' : 'bg-label-warning' }}">{{ $habis ? 'Habis' : 'Menipis' }}</span>
                                    </div>
                                    <div class="progress mb-1" style="height: 6px;" role="progressbar"
                                        aria-label="Stok {{ $material->name }} {{ $material->fill_percent }}% dari batas minimum"
                                        aria-valuenow="{{ $material->fill_percent }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $habis ? 'bg-danger' : 'bg-warning' }}" style="width: {{ $material->fill_percent }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                        Stok {{ $fmtQty($material->current_stock) }} dari minimal {{ $fmtQty($material->min_stock) }} {{ $material->unit }}
                                        &middot; kurang {{ $fmtQty($material->shortage) }} {{ $material->unit }}
                                    </small>
                                </div>
                                <a href="{{ $stockRowUrl($material) }}" class="btn btn-sm btn-outline-primary">{{ $stockRowLabel }}</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="card-footer text-end">
                    <a href="{{ $stockAllUrl }}">
                        @if ($stockAlertCounts['total'] > $stockAlerts->count())
                            Lihat semua {{ $stockAlertCounts['total'] }} bahan
                        @else
                            Buka halaman Stok
                        @endif
                        <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let labelColor, borderColor, legendColor, cardColor;

    if (typeof isDarkStyle !== 'undefined' && isDarkStyle) {
        labelColor = config.colors_dark.textMuted;
        borderColor = config.colors_dark.borderColor;
        legendColor = config.colors_dark.bodyColor;
        cardColor = config.colors_dark.cardColor;
    } else {
        labelColor = config.colors.textMuted;
        borderColor = config.colors.borderColor;
        legendColor = config.colors.bodyColor;
        cardColor = config.colors.cardColor;
    }

    function formatRupiah(n) {
        return 'Rp' + Math.round(n || 0).toLocaleString('id-ID');
    }

    // ---- Payment mix donut ----
    const paymentMixData = @json($paymentMix);
    const pmLabels = Object.keys(paymentMixData).map(function (k) { return k.toUpperCase(); });
    const pmSeries = Object.values(paymentMixData);

    const paymentMixEl = document.querySelector('#paymentMixChart');
    if (paymentMixEl) {
        if (pmSeries.length > 0) {
            const paymentMixConfig = {
                chart: {
                    type: 'donut',
                    height: 320
                },
                labels: pmLabels,
                series: pmSeries,
                colors: [config.colors.primary, config.colors.success, config.colors.info, config.colors.warning, config.colors.danger],
                stroke: { colors: [cardColor] },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return val.toFixed(1) + '%'; }
                },
                legend: {
                    position: 'bottom',
                    labels: { colors: legendColor }
                },
                tooltip: {
                    y: { formatter: function (val) { return formatRupiah(val); } }
                }
            };
            new ApexCharts(paymentMixEl, paymentMixConfig).render();
        } else {
            paymentMixEl.innerHTML = '<p class="text-muted text-center py-5 mb-0">Belum ada data transaksi bulan ini.</p>';
        }
    }

    // ---- Top products bar ----
    const topProducts = @json($topProducts);
    const tpCategories = topProducts.map(function (p) { return p.product_name; });
    const tpSeries = topProducts.map(function (p) { return p.total_revenue; });

    const topProductsEl = document.querySelector('#topProductsChart');
    if (topProductsEl) {
        if (tpCategories.length > 0) {
            const topProductsConfig = {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 5,
                        horizontal: true,
                        distributed: true
                    }
                },
                legend: { show: false },
                dataLabels: { enabled: false },
                colors: [config.colors.primary, config.colors.success, config.colors.info, config.colors.warning, config.colors.danger],
                grid: {
                    borderColor: borderColor,
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } }
                },
                xaxis: {
                    categories: tpCategories,
                    labels: {
                        style: { colors: labelColor },
                        formatter: function (val) { return formatRupiah(val); }
                    }
                },
                yaxis: {
                    labels: { style: { colors: labelColor } }
                },
                tooltip: {
                    y: { formatter: function (val) { return formatRupiah(val); } }
                },
                series: [{ name: 'Omzet', data: tpSeries }]
            };
            new ApexCharts(topProductsEl, topProductsConfig).render();
        } else {
            topProductsEl.innerHTML = '<p class="text-muted text-center py-5 mb-0">Belum ada data penjualan produk.</p>';
        }
    }
});
</script>
@endsection
