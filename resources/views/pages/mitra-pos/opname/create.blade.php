@extends('layouts/layoutMaster')

@section('title', 'Stock Opname Baru')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Stock Opname /</span> Hitung Fisik</h4>
        <a href="{{ route('mitra-opname.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <form action="{{ route('mitra-opname.store') }}" method="POST">
        @csrf
        <div class="card mb-4">
            <div class="card-body">
                <p class="text-muted mb-0">Isi jumlah fisik hasil hitung langsung. Baris dengan jumlah sama seperti stok sistem tidak akan menghasilkan penyesuaian.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Material</th>
                            <th>Satuan</th>
                            <th class="text-end">Stok Sistem</th>
                            <th class="text-end" style="width: 180px;">Stok Fisik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                        <tr>
                            <td>{{ $material->sku }}</td>
                            <td>{{ $material->name }}</td>
                            <td>{{ $material->unit }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }}</td>
                            <td>
                                <input type="number" step="0.001" min="0" class="form-control text-end"
                                    name="physical_qty[{{ $material->id }}]"
                                    value="{{ old('physical_qty.'.$material->id, rtrim(rtrim(number_format($material->current_stock, 3, '.', ''), '0'), '.')) }}"
                                    required>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada material aktif untuk dihitung.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Opname akhir bulan Juli 2026">{{ old('notes') }}</textarea>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" {{ $materials->isEmpty() ? 'disabled' : '' }}>
                    <i class="ri-save-line me-1"></i> Simpan Stock Opname
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
