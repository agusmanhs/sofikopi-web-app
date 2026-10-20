@extends('layouts/layoutMaster')

@section('title', 'Riwayat Mutasi Stok')

@section('content')
@php
    $fmtQty = fn ($qty) => rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
    $canOpenTransaction = auth()->user()->can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'pos-transaction.index', 'read']);
    $canOpenOpname = auth()->user()->can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'mitra-opname.index', 'read']);
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Stok Material /</span> Riwayat Mutasi @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>
        <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Cari</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Nama bahan atau catatan">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Material</label>
                    <select name="material_id" class="form-select">
                        <option value="">Semua material</option>
                        @foreach ($materials as $material)
                        <option value="{{ $material->id }}" {{ ($filters['material_id'] ?? null) == $material->id ? 'selected' : '' }}>
                            {{ $material->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis</label>
                    <select name="type" class="form-select">
                        <option value="">Semua jenis</option>
                        @foreach (['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian / koreksi'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? null) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-filter-3-line me-1"></i> Tampilkan
                    </button>
                    @if (collect($filters)->filter()->isNotEmpty())
                    <a href="{{ $routes['movements'] }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Material</th>
                        <th>Jenis</th>
                        <th class="text-end">Jumlah (+ masuk / - keluar)</th>
                        <th class="text-end">Sisa Stok</th>
                        <th>Asal Mutasi</th>
                        <th>Oleh</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php
                            $signedQty = match ($movement->type) {
                                'out' => -abs((float) $movement->qty),
                                'in' => abs((float) $movement->qty),
                                default => (float) $movement->qty,
                            };
                            $typeBadge = $signedQty > 0 ? 'bg-label-success' : ($signedQty < 0 ? 'bg-label-danger' : 'bg-label-secondary');
                            $typeLabel = match (true) {
                                $movement->type === 'in' => 'Masuk',
                                $movement->type === 'out' => 'Keluar',
                                $signedQty > 0 => 'Koreksi tambah',
                                $signedQty < 0 => 'Koreksi kurang',
                                default => 'Penyesuaian',
                            };
                            $unit = $movement->material->unit ?? '';
                            $ref = $movement->reference;
                            $refLabel = null;
                            $refUrl = null;
                            if ($ref instanceof \App\Models\PosTransaction) {
                                $refLabel = ($movement->type === 'in' ? 'Void ' : 'Penjualan POS ').$ref->transaction_no;
                                $refUrl = $canOpenTransaction ? $routes['transaction_show']($ref->transaction_no) : null;
                            } elseif ($ref instanceof \App\Models\MitraStockOpname) {
                                $refLabel = 'Stok opname '.$ref->opname_no;
                                $refUrl = $canOpenOpname ? $routes['opname_show']($ref->opname_no) : null;
                            } elseif ($movement->reference_type === \App\Models\PosTransaction::class) {
                                $refLabel = $movement->type === 'in' ? 'Void transaksi POS' : 'Penjualan POS';
                            } elseif ($movement->reference_type === \App\Models\MitraStockOpname::class) {
                                $refLabel = 'Stok opname';
                            } else {
                                $refLabel = match ($movement->type) {
                                    'in' => 'Pembelian stok (tercatat di jurnal)',
                                    'out' => 'Pengeluaran manual',
                                    default => 'Penyesuaian manual',
                                };
                            }
                        @endphp
                        <tr>
                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->material->name ?? '-' }}</td>
                            <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                            <td class="text-end fw-medium {{ $signedQty > 0 ? 'text-success' : ($signedQty < 0 ? 'text-danger' : '') }}">
                                {{ $signedQty > 0 ? '+' : ($signedQty < 0 ? '-' : '') }}{{ $fmtQty(abs($signedQty)) }} {{ $unit }}
                            </td>
                            <td class="text-end">{{ $fmtQty($movement->balance_after) }} {{ $unit }}</td>
                            <td>
                                @if ($refUrl)
                                <a href="{{ $refUrl }}">{{ $refLabel }}</a>
                                @else
                                {{ $refLabel }}
                                @endif
                            </td>
                            <td>{{ $movement->user->name ?? '-' }}</td>
                            <td>{{ $movement->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada mutasi stok yang cocok dengan pilihan Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
