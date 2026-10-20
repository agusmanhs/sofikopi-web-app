@extends('layouts/layoutMaster')

@section('title', 'Pengaturan Mitra POS')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Pengaturan @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>

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
                <form action="{{ $routes['update'] }}" method="POST" enctype="multipart/form-data" id="formPengaturan" data-lock-submit>
                    @csrf
                    @method('PUT')
                    <div class="card-header">
                        <h5 class="mb-0">Logo Mitra</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Logo Mitra</label>
                                @if ($currentMitra->logo)
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="{{ Storage::url($currentMitra->logo) }}" alt="Logo mitra saat ini" style="max-width: 120px; max-height: 120px; object-fit: contain;" class="border rounded p-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_mitra_logo" value="1" id="removeMitraLogo">
                                        <label class="form-check-label" for="removeMitraLogo">Hapus logo ini</label>
                                    </div>
                                </div>
                                @endif
                                <input type="file" name="mitra_logo" class="form-control" accept="image/png,image/jpeg">
                                <small class="text-muted">JPG/PNG, maks 2MB. Logo utama mitra — tampil di halaman profil/identitas mitra. Logo baru atau penghapusan logo baru berlaku setelah tombol Simpan ditekan.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-header border-top">
                        <h5 class="mb-0">Target &amp; Struk</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Target Omzet Bulanan (Rp)</label>
                                <input type="text" inputmode="numeric" name="monthly_revenue_target" class="form-control rupiah-input"
                                    value="{{ old('monthly_revenue_target', $setting->monthly_revenue_target !== null ? number_format((float) $setting->monthly_revenue_target, 0, ',', '.') : '') }}" placeholder="Contoh: 15.000.000">
                                <small class="text-muted">Dipakai untuk membandingkan omzet bulan ini di dashboard. Tidak memengaruhi harga atau pembukuan.</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Catatan Kaki Struk</label>
                                <textarea name="receipt_footer" class="form-control" rows="2" placeholder="Contoh: Terima kasih atas kunjungan Anda">{{ old('receipt_footer', $setting->receipt_footer) }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Logo Struk</label>
                                @if ($setting->receipt_logo_path)
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="{{ Storage::url($setting->receipt_logo_path) }}" alt="Logo saat ini" style="max-width: 120px; max-height: 120px; object-fit: contain;" class="border rounded p-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo">
                                        <label class="form-check-label" for="removeLogo">Hapus logo ini</label>
                                    </div>
                                </div>
                                @endif
                                <input type="file" name="receipt_logo" class="form-control" accept="image/png,image/jpeg">
                                <small class="text-muted">JPG/PNG, maks 2MB. Tampil di bagian atas struk cetak.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-header border-top">
                        <h5 class="mb-0">Biaya Layanan &amp; Pajak</h5>
                        <small class="text-muted">Dihitung otomatis dari subtotal setiap transaksi kasir. Kosongkan/isi 0 jika tidak dipakai.
                            Contoh: belanja Rp 100.000, service charge 5%, pajak 11% → service charge Rp 5.000, pajak Rp 11.550 (11% dari 105.000), pelanggan membayar Rp 116.550.
                            Pajak dihitung <strong>di atas</strong> harga produk (ditambahkan), bukan sudah termasuk di harga.</small>
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
@include('pages.mitra-pos._form-safety')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formPengaturan');
        const removeBoxes = form.querySelectorAll('input[name="remove_logo"], input[name="remove_mitra_logo"]');
        const syncConfirm = function() {
            const anyChecked = Array.from(removeBoxes).some(function(box) { return box.checked; });
            if (anyChecked) {
                form.setAttribute('data-confirm-title', 'Hapus logo?');
                form.setAttribute('data-confirm-text', 'Logo yang Anda centang akan dihapus saat pengaturan disimpan.|Pengaturan lain di halaman ini ikut tersimpan.');
                form.setAttribute('data-confirm-button', 'Ya, simpan dan hapus logo');
                form.setAttribute('data-confirm-danger', '');
            } else {
                ['data-confirm-title', 'data-confirm-text', 'data-confirm-button', 'data-confirm-danger'].forEach(function(attr) {
                    form.removeAttribute(attr);
                });
            }
        };
        removeBoxes.forEach(function(box) { box.addEventListener('change', syncConfirm); });
        syncConfirm();
    });
</script>
@endsection
