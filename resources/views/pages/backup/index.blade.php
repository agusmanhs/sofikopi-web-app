@extends('layouts/layoutMaster')

@section('title', 'Export Database')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold mb-4">
            <span class="text-muted fw-light">Sistem /</span> Export Database
        </h4>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <span class="alert-icon text-success me-2">
                    <i class="ri-checkbox-circle-line"></i>
                </span>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <span class="alert-icon text-danger me-2">
                    <i class="ri-error-warning-line"></i>
                </span>
                {{ session('error') }}
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-label-primary rounded-3 shadow-sm mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="ri-database-2-line" style="font-size: 36px;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Export Database</h5>
                    <p class="text-muted small mb-0">
                        Unduh seluruh database dalam format <code>.sql</code><br>
                        seperti export dari phpMyAdmin.
                    </p>
                </div>

                <form action="{{ route('backup.export') }}" method="POST" id="exportForm">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm" id="btnExport"
                        onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\'></span> Mengexport...'; this.form.submit();">
                        <i class="ri-download-cloud-2-line me-2"></i> Export & Download Sekarang
                    </button>
                </form>

                <p class="text-muted small mt-3 mb-0">
                    <i class="ri-information-line me-1"></i>
                    Notifikasi akan dikirim ke Telegram setelah proses selesai.
                </p>
            </div>
        </div>

        <div class="card bg-lighter shadow-none border mt-4">
            <div class="card-body">
                <h6><i class="ri-information-line me-1"></i> Informasi:</h6>
                <ul class="mb-0 small text-muted">
                    <li>File akan langsung diunduh ke perangkat Anda (tidak disimpan di server).</li>
                    <li>Proses export mungkin memerlukan waktu beberapa detik tergantung ukuran database.</li>
                    <li>Laporan hasil export akan dikirim ke <b>Telegram Admin</b>.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
