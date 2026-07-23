@extends('layouts/layoutMaster')

@section('title', 'Pengaturan Mitra POS')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Pengaturan</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('mitra-setting.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header">
                        <h5 class="mb-0">Target &amp; Struk</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Target Omzet Bulanan (Rp)</label>
                                <input type="text" inputmode="numeric" name="monthly_revenue_target" class="form-control rupiah-input"
                                    value="{{ old('monthly_revenue_target', $setting->monthly_revenue_target) }}" placeholder="Contoh: 15.000.000">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Catatan Kaki Struk</label>
                                <textarea name="receipt_footer" class="form-control" rows="2" placeholder="Contoh: Terima kasih atas kunjungan Anda">{{ old('receipt_footer', $setting->receipt_footer) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-header border-top">
                        <h5 class="mb-0">Biaya Layanan &amp; Pajak</h5>
                        <small class="text-muted">Dihitung otomatis dari subtotal setiap transaksi kasir. Kosongkan/isi 0 jika tidak dipakai.</small>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Service Charge (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="service_charge_percent" class="form-control"
                                    value="{{ old('service_charge_percent', $setting->service_charge_percent) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pajak / PPN (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_percent" class="form-control"
                                    value="{{ old('tax_percent', $setting->tax_percent) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="card-header border-top">
                        <h5 class="mb-0">Potongan Administrasi per Metode Bayar</h5>
                        <small class="text-muted">Dipotong penyedia pembayaran, tidak menambah tagihan pelanggan — hanya dicatat untuk laporan penerimaan bersih.</small>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">QRIS (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="qris_fee_percent" class="form-control"
                                    value="{{ old('qris_fee_percent', $setting->qris_fee_percent) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transfer (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="transfer_fee_percent" class="form-control"
                                    value="{{ old('transfer_fee_percent', $setting->transfer_fee_percent) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">EDC (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="edc_fee_percent" class="form-control"
                                    value="{{ old('edc_fee_percent', $setting->edc_fee_percent) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.rupiah-input').forEach(function(el) {
            const format = function() {
                const digits = el.value.replace(/[^\d]/g, '');
                el.value = digits ? Number(digits).toLocaleString('id-ID') : '';
            };
            el.addEventListener('input', format);
            format();
        });
    });
</script>
@endsection
