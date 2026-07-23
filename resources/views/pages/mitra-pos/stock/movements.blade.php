@extends('layouts/layoutMaster')

@section('title', 'Riwayat Mutasi Stok')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Stok Material /</span> Riwayat Mutasi</h4>
        <a href="{{ route('mitra-stock.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Material</label>
                    <select name="material_id" class="form-select">
                        <option value="">-- Semua Material --</option>
                        @foreach ($materials as $material)
                        <option value="{{ $material->id }}" {{ ($filters['material_id'] ?? null) == $material->id ? 'selected' : '' }}>
                            {{ $material->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select">
                        <option value="">-- Semua --</option>
                        @foreach (['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian'] as $value => $label)
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-filter-3-line me-1"></i> Filter
                    </button>
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
                        <th>Tipe</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Sisa Stok</th>
                        <th>Referensi</th>
                        <th>Oleh</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php
                            $typeBadge = match ($movement->type) {
                                'in' => 'bg-label-success',
                                'out' => 'bg-label-danger',
                                'adjustment' => 'bg-label-warning',
                                default => 'bg-label-secondary',
                            };
                            $typeLabel = match ($movement->type) {
                                'in' => 'Masuk',
                                'out' => 'Keluar',
                                'adjustment' => 'Penyesuaian',
                                default => ucfirst($movement->type),
                            };
                            $reference = match ($movement->reference_type) {
                                \App\Models\PosTransaction::class => 'Transaksi POS',
                                default => null,
                            };
                        @endphp
                        <tr>
                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->material->name ?? '-' }}</td>
                            <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($movement->qty, 3, ',', '.'), '0'), ',') }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($movement->balance_after, 3, ',', '.'), '0'), ',') }}</td>
                            <td>{{ $reference ?? '-' }}</td>
                            <td>{{ $movement->user->name ?? '-' }}</td>
                            <td>{{ $movement->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada mutasi stok pada periode ini.</td>
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
