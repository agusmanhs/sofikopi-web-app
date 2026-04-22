@extends('layouts/layoutMaster')

@section('title', 'Manajemen Produksi & Stok')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl grow container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Logistik /</span> Produksi & Mutasi Stok
         </h4>
         <button class="btn btn-primary" onclick="openLogModal()">
            <i class="ri-add-line me-1"></i> Tambah Log Stok
         </button>
      </div>

      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-produksi table table-hover">
               <thead>
                  <tr>
                     <th></th>
                     <th>Tanggal</th>
                     <th>Produk</th>
                     <th>Tipe</th>
                     <th>Jumlah</th>
                     <th>Petugas</th>
                     <th>Keterangan</th>
                     <th>Aksi</th>
                  </tr>
               </thead>
            </table>
         </div>
      </div>
   </div>

   <!-- Modal Log Stok -->
   <div class="modal fade" id="modalLog" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <form id="formLog" class="modal-content" onsubmit="saveLog(event)">
            @csrf
            <div class="modal-header">
               <h5 class="modal-title">Tambah Log Stok / Produksi</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <div class="mb-3">
                  <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                  <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
               </div>
               <div class="mb-3">
                  <label class="form-label">Produk <span class="text-danger">*</span></label>
                  <select name="product_id" class="form-select select2" required>
                     <option value="">Pilih Produk</option>
                     @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Stok: {{ $p->current_stock }})</option>
                     @endforeach
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                  <select name="tipe" id="tipe" class="form-select" required>
                     <option value="produksi">Produksi (Tambah Stok)</option>
                     <option value="penjualan">Penjualan (Kurangi Stok)</option>
                     <option value="adjustment">Adjustment (Koreksi Manual)</option>
                     <option value="retur">Retur (Tambah Stok)</option>
                  </select>
                  <small class="text-muted" id="tipe-hint">Stok akan otomatis bertambah sesuai jumlah yang dimasukkan.</small>
               </div>
               <div class="mb-3">
                  <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                  <input type="number" name="jumlah" id="jumlah" class="form-control" placeholder="Masukkan jumlah" min="1" required>
                  <small class="text-muted" id="jumlah-hint"></small>
               </div>
               <div class="mb-3">
                  <label class="form-label">Keterangan</label>
                  <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
               <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
            </div>
         </form>
      </div>
   </div>
@endsection

@section('page-script')
   <script type="module">
      $(function() {
         $('.select2').each(function() {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>').select2({
               placeholder: 'Pilih Produk',
               dropdownParent: $this.parent()
            });
         });

         // Dynamic tipe hints
         const tipeHints = {
            produksi:   'Stok akan otomatis <strong class="text-success">bertambah</strong> sesuai jumlah yang dimasukkan.',
            penjualan:  'Stok akan otomatis <strong class="text-danger">berkurang</strong> sesuai jumlah yang dimasukkan.',
            adjustment: 'Stok akan <strong class="text-warning">dikoreksi</strong>. Masukkan jumlah penambahan atau pengurangan.',
            retur:      'Stok akan otomatis <strong class="text-info">bertambah</strong> dari barang retur.'
         };
         $('#tipe').on('change', function() {
            const val = $(this).val();
            $('#tipe-hint').html(tipeHints[val] || '');
            if (val === 'adjustment') {
               $('#jumlah').removeAttr('min').attr('placeholder', 'Contoh: 10 (tambah) atau -5 (kurang)');
               $('#jumlah-hint').text('Untuk adjustment, gunakan nilai negatif jika ingin mengurangi.');
            } else {
               $('#jumlah').attr('min', '1').attr('placeholder', 'Masukkan jumlah');
               $('#jumlah-hint').text('');
               // Force positive
               const val = parseInt($('#jumlah').val());
               if (val < 0) $('#jumlah').val(Math.abs(val));
            }
         }).trigger('change');

         const dt = $('.datatables-produksi').DataTable({
            processing: true,
            ajax: "{{ route('aktivitas.produksi.index') }}",
            responsive: {
               details: {
                  display: $.fn.dataTable.Responsive.display.modal({
                     header: function(row) { return 'Detail Mutasi Stok'; }
                  }),
                  type: 'column',
                  renderer: $.fn.dataTable.Responsive.renderer.tableAll({ tableClass: 'table' })
               }
            },
            columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
            columns: [
               {
                  data: null,
                  defaultContent: ''
               },
               { 
                  data: 'tanggal',
                  render: (data) => new Date(data).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
               },
               { 
                  data: 'product',
                  render: (p) => p ? `<strong>${p.sku}</strong><br><small>${p.name}</small>` : '-'
               },
               { 
                  data: 'tipe',
                  render: (data) => {
                     let badge = 'primary';
                     if(data == 'penjualan') badge = 'danger';
                     if(data == 'retur') badge = 'info';
                     if(data == 'adjustment') badge = 'warning';
                     return `<span class="badge bg-label-${badge}">${data.toUpperCase()}</span>`;
                  }
               },
               { 
                  data: 'jumlah',
                  render: (data) => {
                     let color = data > 0 ? 'success' : 'danger';
                     let sign = data > 0 ? '+' : '';
                     return `<span class="fw-bold text-${color}">${sign}${data}</span>`;
                  }
               },
               { 
                  data: 'user',
                  render: (u) => u ? u.name : '-'
               },
               { data: 'keterangan' },
               { 
                  data: 'id',
                  render: (data, type, row) => `
                     <button class="btn btn-sm btn-icon btn-label-danger" onclick="deleteLog(${data})">
                        <i class="ri-delete-bin-line"></i>
                     </button>
                  `
               }
            ],
            order: [[1, 'desc']]
         });

         window.openLogModal = () => {
            $('#formLog')[0].reset();
            $('.select2').val('').trigger('change');
            new bootstrap.Modal($('#modalLog')).show();
         };

         window.saveLog = (e) => {
            e.preventDefault();
            const btn = $('#btnSave');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

            fetch("{{ route('aktivitas.produksi.store') }}", {
               method: 'POST',
               body: new FormData($('#formLog')[0]),
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Accept': 'application/json'
               }
            })
            .then(async r => {
               const data = await r.json();
               window.AlertHandler.handle(data);
               if(data.success) {
                  $('#modalLog').modal('hide');
                  dt.ajax.reload();
               }
            })
            .finally(() => {
               btn.prop('disabled', false).text('Simpan');
            });
         };

         window.deleteLog = (id) => {
            window.AlertHandler.confirm(
               'Hapus Log?',
               'Data stok yang telah berubah akan dikembalikan (revert). Lanjutkan?',
               'Ya, Hapus!',
               () => {
                  window.AlertHandler.swal.showLoading();
                  fetch(`{{ url('aktivitas/produksi') }}/${id}`, {
                     method: 'DELETE',
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                     }
                  })
                  .then(async r => {
                     const data = await r.json();
                     window.AlertHandler.swal.close();
                     window.AlertHandler.handle(data);
                     if(data.success) {
                        dt.ajax.reload(null, false);
                     }
                  })
                  .catch(err => {
                     window.AlertHandler.swal.close();
                     window.AlertHandler.showError('Terjadi kesalahan sistem');
                  });
               }
            );
         };
      });
   </script>
@endsection
