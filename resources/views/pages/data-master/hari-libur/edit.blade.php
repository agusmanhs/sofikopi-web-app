@extends('layouts/layoutMaster')

@section('title', 'Edit Hari Libur')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Master Data / Hari Libur /</span> Edit
         </h4>
         <a href="{{ route('hari-libur.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Kembali
         </a>
      </div>

      <div class="card">
         <div class="card-body">
            <form action="{{ route('hari-libur.update', $data->id) }}" method="POST">
               @csrf
               @method('PUT')
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label fw-bold font-size-base">Tanggal</label>
                     <input type="date" name="tanggal" class="form-control" required
                        value="{{ old('tanggal', $data->tanggal->format('Y-m-d')) }}">
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label fw-bold font-size-base">Nama Libur</label>
                     <input type="text" name="nama" class="form-control" required
                        value="{{ old('nama', $data->nama) }}" placeholder="Contoh: Gathering Kantor">
                  </div>
                  <div class="col-12 mb-3">
                     <label class="form-label fw-bold font-size-base">Deskripsi (Opsional)</label>
                     <textarea name="deskripsi" class="form-control" rows="3" placeholder="Masukkan keterangan tambahan jika ada...">{{ old('deskripsi', $data->deskripsi) }}</textarea>
                  </div>

                  <div class="col-md-6 mb-3">
                     <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_nasional" value="1" id="is_nasional"
                           {{ $data->is_nasional ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="is_nasional">Libur Nasional</label>
                        <div class="form-text small" style="font-size: 0.75rem;">(Label) Menampilkan status Merah di
                           kalender & rekap.</div>
                     </div>
                  </div>
                  <div class="col-md-6 mb-3">
                     <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_cuti_bersama" value="1"
                           id="is_cuti_bersama" {{ $data->is_cuti_bersama ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="is_cuti_bersama">Cuti Bersama</label>
                        <div class="form-text small" style="font-size: 0.75rem;">(Label) Digunakan sebagai penanda periode
                           cuti bersama pemerintah.</div>
                     </div>
                  </div>

                  <div class="col-12">
                     <hr class="my-4">
                     <div class="mb-4">
                        <h6 class="fw-bold text-primary mb-3"><i class="ri-group-line me-1"></i> Target Karyawan yang Libur
                        </h6>
                        <div class="form-check form-switch mb-3">
                           <input class="form-check-input" type="checkbox" name="is_all_divisi" id="is_all_divisi"
                              value="1" {{ $data->is_all_divisi ? 'checked' : '' }} onchange="toggleLiburTarget()">
                           <label class="form-check-label fw-bold" for="is_all_divisi">Berlaku untuk SEMUA Divisi</label>
                        </div>

                        <div id="target_spesifik_area" style="{{ $data->is_all_divisi ? 'display: none;' : '' }}">
                           <div class="card bg-label-secondary border-0 shadow-none">
                              <div class="card-body p-3">
                                 <label class="form-label d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-dark">Pilih Divisi yang Libur</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                       id="btnCheckAll">
                                       Pilih Semua
                                    </button>
                                 </label>
                                 <div class="row g-2 mx-0">
                                    @foreach ($divisis as $divisi)
                                       <div class="col-12 col-sm-6 col-lg-4">
                                          <div
                                             class="p-2 bg-white rounded border d-flex align-items-center h-100 shadow-sm transition-hover">
                                             <div class="form-check mb-0">
                                                <input class="form-check-input check-divisi me-2" type="checkbox"
                                                   name="divisi_ids[]" value="{{ $divisi->id }}"
                                                   id="divisi_{{ $divisi->id }}"
                                                   {{ is_array($data->divisi_ids) && in_array($divisi->id, $data->divisi_ids) ? 'checked' : '' }}>
                                                <label class="form-check-label h6 mb-0 cursor-pointer text-dark"
                                                   for="divisi_{{ $divisi->id }}">
                                                   {{ $divisi->nama }}
                                                </label>
                                             </div>
                                          </div>
                                       </div>
                                    @endforeach
                                 </div>
                                 <div class="form-text text-info mt-2"><i class="ri-information-line me-1"></i>Hanya
                                    karyawan pada divisi yang dipilih yang akan diliburkan.</div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>

                  <div class="col-12 text-center text-md-end mt-2">
                     <button type="submit" class="btn btn-primary btn-lg w-100 w-md-auto">
                        <i class="ri-save-line me-1"></i> Simpan Perubahan
                     </button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         const $ = window.jQuery;
         if (!$) return;

         // Pilih Semua Handler
         $('#btnCheckAll').on('click', function() {
            const checkboxes = $('.check-divisi');
            const allChecked = checkboxes.length && checkboxes.filter(':checked').length === checkboxes.length;

            checkboxes.prop('checked', !allChecked);
            $(this).text(allChecked ? 'Pilih Semua' : 'Batal Pilih Semua')
               .toggleClass('btn-outline-primary', allChecked)
               .toggleClass('btn-outline-secondary', !allChecked);
         });

         // Initialize Select2 (keep existing)
         const select2 = $('.select2');
         if (select2.length) {
            select2.each(function() {
               var $this = $(this);
               $this.wrap('<div class="position-relative"></div>').select2({
                  placeholder: $this.data('placeholder') || 'Pilih opsi',
                  dropdownParent: $this.parent()
               });
            });
         }
      });

      function toggleLiburTarget() {
         const isAll = document.getElementById('is_all_divisi').checked;
         const area = document.getElementById('target_spesifik_area');
         area.style.display = isAll ? 'none' : 'block';
      }
   </script>
@endsection
