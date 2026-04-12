@extends('layouts/layoutMaster')

@section('title', 'Database Backup')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <span class="text-muted fw-light">Sistem /</span> Backup Database
            </h4>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-history-line me-1"></i> Jalankan Backup
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <form action="{{ route('backup.run') }}" method="POST">
                            @csrf
                            <input type="hidden" name="option" value="--only-db">
                            <button type="submit" class="dropdown-item">Hanya Database</button>
                        </form>
                    </li>
                    <li>
                        <form action="{{ route('backup.run') }}" method="POST">
                            @csrf
                            <input type="hidden" name="option" value="">
                            <button type="submit" class="dropdown-item">Database & File</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

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

        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Daftar Backup Tersedia</h5>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-backup table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Tanggal Dibuat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $index => $backup)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><code>{{ $backup['file_name'] }}</code></td>
                                <td>{{ $backup['file_size'] }}</td>
                                <td>{{ $backup['last_modified'] }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('backup.download', ['file' => $backup['file_path']]) }}"
                                            class="btn btn-sm btn-outline-success" title="Download">
                                            <i class="ri-download-2-line"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-backup"
                                            data-file="{{ $backup['file_path'] }}" data-name="{{ $backup['file_name'] }}"
                                            title="Hapus">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card bg-lighter shadow-none border">
                    <div class="card-body">
                        <h6><i class="ri-information-line me-1"></i> Informasi Tambahan:</h6>
                        <ul class="mb-0 small text-muted">
                            <li>Backup otomatis dijadwalkan setiap hari pada pukul <b>02:00 WIB</b>.</li>
                            <li>Pastikan server Anda memiliki ruang penyimpanan yang cukup sebelum menjalankan backup manual.</li>
                            <li>Laporan hasil backup otomatis akan dikirim ke <b>Telegram Admin</b> jika fitur notifikasi aktif.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        window.addEventListener('load', function() {
            const dt_backup = $('.datatables-backup');

            if (dt_backup.length) {
                dt_backup.DataTable({
                    responsive: true,
                    displayLength: 10,
                    lengthMenu: [10, 25, 50, 75, 100],
                    language: {
                        paginate: {
                            next: '<i class="ri-arrow-right-s-line"></i>',
                            previous: '<i class="ri-arrow-left-s-line"></i>'
                        },
                        search: "",
                        searchPlaceholder: "Cari backup...",
                        lengthMenu: "_MENU_",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    },
                    dom: '<"row mx-1"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                });
            }

            $('.delete-backup').on('click', function() {
                const file = $(this).data('file');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Hapus File Backup?',
                    text: `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
                        cancelButton: 'btn btn-outline-secondary waves-effect'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.value) {
                        // Form delete dummy
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = "{{ route('backup.delete') }}";
                        
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = "{{ csrf_token() }}";
                        form.appendChild(csrfToken);
                        
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'DELETE';
                        form.appendChild(methodField);
                        
                        const fileField = document.createElement('input');
                        fileField.type = 'hidden';
                        fileField.name = 'file';
                        fileField.value = file;
                        form.appendChild(fileField);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
