@extends('layouts/layoutMaster')

@section('title', 'Manajemen Mitra')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl grow container-p-y">
      <h4 class="fw-bold py-3 mb-4">
         <span class="text-muted fw-light">Data Master /</span> Mitra
      </h4>

      <!-- Navigation Tabs -->
      <div class="nav-align-top mb-4">
         <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
               <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-mitra"
                  aria-controls="navs-mitra" aria-selected="true">
                  <i class="ri-team-line me-1"></i> Data Mitra
               </button>
            </li>
            <li class="nav-item">
               <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-categories" aria-controls="navs-categories" aria-selected="false">
                  <i class="ri-contacts-book-line me-1"></i> Kategori Mitra
               </button>
            </li>
            <li class="nav-item">
               <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-wilayah"
                  aria-controls="navs-wilayah" aria-selected="false">
                  <i class="ri-map-pin-line me-1"></i> Sinkronisasi Wilayah
               </button>
            </li>
         </ul>
         <div class="tab-content">
            <!-- TAB: MITRA -->
            <div class="tab-pane fade show active" id="navs-mitra" role="tabpanel">
               <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Daftar Mitra (Supplier, Reseller, Customer)</h5>
                  <button class="btn btn-primary" onclick="window.openMitraModal()">
                     <i class="ri-user-add-line me-1"></i> Tambah Mitra
                  </button>
               </div>
               <div class="card-datatable table-responsive">
                  <table class="table table-hover" id="table-mitra">
                     <thead>
                        <tr>
                           <th>Kode</th>
                           <th>Mitra</th>
                           <th>Kategori</th>
                           <th>PIC / No HP</th>
                           <th>Alamat</th>
                           <th>Wilayah</th>
                           <th>Status</th>
                           <th class="text-center">Aksi</th>
                        </tr>
                     </thead>
                  </table>
               </div>
            </div>

            <!-- TAB: MITRA CATEGORIES -->
            <div class="tab-pane fade" id="navs-categories" role="tabpanel">
               <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Kategori Mitra</h5>
                  <button class="btn btn-primary" onclick="window.openCategoryModal()">
                     <i class="ri-add-line me-1"></i> Tambah Kategori
                  </button>
               </div>
               <div class="table-responsive">
                  <table class="table table-hover" id="table-categories">
                     <thead>
                        <tr>
                           <th>Nama Kategori</th>
                           <th>Status</th>
                           <th class="text-center">Aksi</th>
                        </tr>
                     </thead>
                  </table>
               </div>
            </div>

            <!-- TAB: WILAYAH SYNC -->
            <div class="tab-pane fade" id="navs-wilayah" role="tabpanel">
               <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Sinkronisasi Data Wilayah Indonesia</h5>
                  <div class="d-flex gap-2">
                     <button class="btn btn-primary" onclick="window.syncProvinces()">
                        <i class="ri-refresh-line me-1"></i> Sinkronkan Provinsi
                     </button>
                     <button class="btn btn-outline-primary" onclick="window.syncAllRegional()">
                        <i class="ri-global-line me-1"></i> Sync Seluruh Kab/Kota
                     </button>
                  </div>
               </div>
               <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                  <span class="alert-icon text-primary me-2">
                     <i class="ri-information-line"></i>
                  </span>
                  <div>
                     <strong>Penting:</strong> Sinkronkan <strong>Provinsi</strong> terlebih dahulu. Setelah itu, jalankan
                     <strong>Sync Semua Kab/Kota</strong>.
                     <br><small class="text-muted">Proses Sinkronisasi Kecamatan dilakukan per-Provinsi melalui tabel di
                        bawah untuk menjaga stabilitas koneksi.</small>
                  </div>
               </div>
               <div class="table-responsive">
                  <table class="table table-hover" id="table-wilayah-sync">
                     <thead>
                        <tr>
                           <th>Provinsi (Indonesia)</th>
                           <th>Data Downloaded</th>
                           <th class="text-end">Aksi (Kabupaten & Kecamatan)</th>
                        </tr>
                     </thead>
                     <tbody id="list-provinces-sync">
                        <!-- Loaded via JS -->
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Mitra -->
   <div class="modal fade" id="modalMitra" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header border-bottom">
               <h5 class="modal-title" id="modalMitraTitle">Tambah Mitra</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formMitra" onsubmit="window.saveMitra(event)">
               @csrf
               <input type="hidden" name="id" id="mitra_id">
               <div class="modal-body">
                  <div class="row g-3">
                     <div class="col-md-6">
                        <div class="mb-3">
                           <label class="form-label">Kategori Mitra <span class="text-danger">*</span></label>
                           <select name="mitra_category_id" id="mitra_category_id" class="form-select select2" required>
                              <option value="">Pilih Kategori</option>
                              @foreach ($categories as $cat)
                                 <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                              @endforeach
                           </select>
                        </div>
                        <div class="mb-3">
                           <label class="form-label">Kode Mitra <span class="text-danger">*</span></label>
                           <input type="text" name="code" id="mitra_code" class="form-control"
                              placeholder="MTR-001" required>
                        </div>
                        <div class="mb-3">
                           <label class="form-label">Nama Mitra <span class="text-danger">*</span></label>
                           <input type="text" name="name" id="mitra_name" class="form-control"
                              placeholder="Nama Usaha / Individu" required>
                        </div>

                        <div class="mb-3">
                           <label class="form-label">PIC (Person In Charge)</label>
                           <input type="text" name="pic" id="mitra_pic" class="form-control"
                              placeholder="Nama Narahubung">
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="mb-3">
                           <label class="form-label">No. HP / WhatsApp</label>
                           <input type="text" name="phone" id="mitra_phone" class="form-control"
                              placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="mb-3">
                           <label class="form-label">Alamat</label>
                           <textarea name="address" id="mitra_address" class="form-control" rows="2" placeholder="Alamat lengkap..."></textarea>
                        </div>

                        <div class="row g-2">
                           <div class="col-md-4 mb-3">
                              <label class="form-label">Provinsi</label>
                              <select name="province_code" id="mitra_province_code" class="form-select select2">
                                 <option value="">Pilih Provinsi</option>
                              </select>
                           </div>
                           <div class="col-md-4 mb-3">
                              <label class="form-label">Kabupaten / Kota</label>
                              <select name="regency_code" id="mitra_regency_code" class="form-select select2">
                                 <option value="">Pilih Kab/Kota</option>
                              </select>
                           </div>
                           {{-- <div class="col-md-4 mb-3">
                              <label class="form-label">Kecamatan (Opsional)</label>
                              <select name="district_code" id="mitra_district_code" class="form-select select2">
                                 <option value="">Pilih Kecamatan</option>
                              </select>
                           </div> --}}
                        </div>
                        <div class="mb-3">
                           <label class="form-label" for="titik_lokasi">Titik Lokasi (Google Maps)</label>
                           <div class="input-group">
                              <span class="input-group-text"><i class="ri-map-pin-line"></i></span>
                              <input type="text" name="titik_lokasi" id="mitra_titik_lokasi" class="form-control"
                                 placeholder="-6.xxxx, 106.xxxx">
                              <button type="button" class="btn btn-outline-info" id="btn-get-location">
                                 <i class="ri-gps-line"></i>
                              </button>
                           </div>
                           <small class="text-muted">Format: latitude, longitude</small>
                        </div>
                        <div class="form-check form-switch pt-2">
                           <input class="form-check-input" type="checkbox" id="mitra_is_active" name="is_active"
                              value="1" checked>
                           <label class="form-check-label" for="mitra_is_active">Aktif</label>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="modal-footer border-top">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan</button>
               </div>
            </form>
         </div>
      </div>
   </div>

   <!-- Modal Category -->
   <div class="modal fade" id="modalCategory" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modalCategoryTitle">Tambah Kategori Mitra</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCategory" onsubmit="window.saveCategory(event)">
               @csrf
               <input type="hidden" name="id" id="category_id">
               <div class="modal-body">
                  <div class="mb-3">
                     <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                     <input type="text" name="name" id="category_name" class="form-control"
                        placeholder="Supplier, Reseller, dll" required>
                  </div>
                  <div class="form-check form-switch">
                     <input class="form-check-input" type="checkbox" id="category_is_active" name="is_active"
                        value="1" checked>
                     <label class="form-check-label" for="category_is_active">Aktif</label>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan</button>
               </div>
            </form>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script type="module">
      // --- HELPERS ---
      const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

      async function fetchWithRetry(url, options = {}, retries = 3, backoff = 1000) {
         try {
            const response = await fetch(url, options);
            if (!response.ok) {
               const errorData = await response.json().catch(() => ({}));
               throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            return await response.json();
         } catch (error) {
            if (retries > 0) {
               console.warn(`Fetch failed for ${url}, retrying in ${backoff}ms... (${retries} left)`, error);
               await sleep(backoff);
               return fetchWithRetry(url, options, retries - 1, backoff * 1.5);
            }
            throw error;
         }
      }

      // --- REGIONAL SYNC FUNCTIONS ---
      window.loadSyncStatus = async function() {
         try {
            const provinces = await fetchWithRetry("{{ route('wilayah.provinces') }}");
            const $tbody = $('#list-provinces-sync');
            $tbody.empty();

            if (provinces.length === 0) {
               $tbody.append(
                  '<tr><td colspan="3" class="text-center">Belum ada data provinsi. Silakan sinkronkan.</td></tr>'
               );
               return;
            }

            provinces.forEach(p => {
               $tbody.append(`
                  <tr>
                     <td><strong>${p.name}</strong></td>
                     <td><span class="badge bg-label-secondary">Ready</span></td>
                     <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="window.syncRegencies('${p.code}')">
                           <i class="ri-refresh-line"></i> Sync Kab/Kota
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="window.syncDistrictsByProvince('${p.code}')">
                           <i class="ri-refresh-line"></i> Sync Semua Kecamatan
                        </button>
                     </td>
                  </tr>
               `);
            });
         } catch (err) {
            console.error('Load sync status error:', err);
         }
      };

      window.syncProvinces = async function() {
         window.AlertHandler.confirm('Sinkronkan Provinsi?', 'Mengambil data semua provinsi dari API.',
            'Ya, Sinkronkan!', async () => {
               window.AlertHandler.swal.fire({
                  title: 'Menyinkronkan Provinsi...',
                  html: 'Mohon tunggu, sedang mengambil data.',
                  allowOutsideClick: false,
                  didOpen: () => {
                     window.AlertHandler.swal.showLoading();
                  }
               });

               try {
                  const data = await fetchWithRetry("{{ route('wilayah.sync-provinces') }}", {
                     method: 'POST',
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                     }
                  });
                  window.AlertHandler.swal.close();
                  window.AlertHandler.handle(data);
                  if (data.success) window.loadSyncStatus();
               } catch (err) {
                  window.AlertHandler.swal.close();
                  window.AlertHandler.showError('Gagal sinkronisasi: ' + err.message);
               }
            });
      };

      window.syncRegencies = async function(provinceCode) {
         window.AlertHandler.swal.fire({
            title: 'Menyinkronkan Kab/Kota...',
            html: 'Mohon tunggu, sedang mengambil data.',
            allowOutsideClick: false,
            didOpen: () => {
               window.AlertHandler.swal.showLoading();
            }
         });

         try {
            const data = await fetchWithRetry("{{ route('wilayah.sync-regencies') }}", {
               method: 'POST',
               body: JSON.stringify({
                  province_code: provinceCode
               }),
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
               }
            });
            window.AlertHandler.swal.close();
            window.AlertHandler.handle(data);
         } catch (err) {
            window.AlertHandler.swal.close();
            window.AlertHandler.showError('Gagal menyinkronkan data.');
         }
      };

      window.syncDistrictsByProvince = async function(provinceCode) {
         try {
            const regencies = await fetchWithRetry(`{{ url('master/wilayah/regencies') }}/${provinceCode}`);
            if (regencies.length === 0) {
               window.AlertHandler.showError('Sinkronkan Kabupaten/Kota untuk provinsi ini terlebih dahulu.');
               return;
            }

            window.AlertHandler.swal.fire({
               title: 'Menyinkronkan Kecamatan...',
               html: `Memproses 0 / ${regencies.length} Kabupaten/Kota`,
               allowOutsideClick: false,
               didOpen: () => {
                  window.AlertHandler.swal.showLoading();
               }
            });

            let successCount = 0;
            const htmlContainer = window.AlertHandler.swal.getHtmlContainer();
            for (let i = 0; i < regencies.length; i++) {
               if (htmlContainer) htmlContainer.textContent =
                  `Memproses ${i + 1} / ${regencies.length} — ${regencies[i].name}`;
               try {
                  await fetchWithRetry("{{ route('wilayah.sync-districts') }}", {
                     method: 'POST',
                     body: JSON.stringify({
                        regency_code: regencies[i].code
                     }),
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                     }
                  });
                  successCount++;
                  await sleep(300); // Backoff logic
               } catch (err) {
                  console.error(`Error syncing ${regencies[i].name}:`, err);
               }
            }

            window.AlertHandler.swal.close();
            window.AlertHandler.showSuccess(`Berhasil menyinkronkan kecamatan untuk ${successCount} Kab/Kota.`);
         } catch (err) {
            window.AlertHandler.showError('Gagal mengambil data kabupaten.');
         }
      };

      window.syncAllRegional = async function() {
         window.AlertHandler.confirm('Sync Semua Wilayah?',
            'Mengambil data Provinsi & Kab/Kota Indonesia (Mungkin butuh waktu).',
            'Ya, Sync Semua!', async () => {
               window.AlertHandler.swal.fire({
                  title: 'Sinkronisasi Global...',
                  html: 'Memulai sinkronisasi provinsi...',
                  allowOutsideClick: false,
                  didOpen: () => {
                     window.AlertHandler.swal.showLoading();
                  }
               });

               try {
                  const dataP = await fetchWithRetry("{{ route('wilayah.sync-provinces') }}", {
                     method: 'POST',
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                     }
                  });
                  if (!dataP.success) throw new Error(dataP.message);

                  const provinces = await fetchWithRetry("{{ route('wilayah.provinces') }}");
                  const htmlContainer = window.AlertHandler.swal.getHtmlContainer();

                  for (let i = 0; i < provinces.length; i++) {
                     const p = provinces[i];
                     if (htmlContainer) htmlContainer.textContent =
                        `[${i+1}/${provinces.length}] Memproses Kab/Kota: ${p.name}`;

                     await fetchWithRetry("{{ route('wilayah.sync-regencies') }}", {
                        method: 'POST',
                        body: JSON.stringify({
                           province_code: p.code
                        }),
                        headers: {
                           'X-CSRF-TOKEN': '{{ csrf_token() }}',
                           'Content-Type': 'application/json'
                        }
                     });

                     await sleep(500); // Increased backoff delay for network stability
                  }

                  window.AlertHandler.swal.close();
                  window.AlertHandler.showSuccess('Sinkronisasi Provinsi & Kabupaten Selesai.');
                  window.loadSyncStatus();
               } catch (err) {
                  window.AlertHandler.swal.close();
                  window.AlertHandler.showError('Terjadi gangguan jaringan: ' + err.message);
               }
            });
      };

      // --- CASCADING FUNCTIONS ---
      window.loadRegional = async function(selectedProv = null, selectedReg = null, selectedDist = null) {
         const $prov = $('#mitra_province_code');
         const $reg = $('#mitra_regency_code');
         const $dist = $('#mitra_district_code');
         try {
            const provinces = await fetchWithRetry("{{ route('wilayah.provinces') }}");
            $prov.empty().append('<option value="">Pilih Provinsi</option>');
            provinces.forEach(p => {
               $prov.append(new Option(p.name, p.code, false, false));
            });
            $prov.trigger('change.select2');

            if (selectedProv) {
               $prov.val(selectedProv).trigger('change.select2');
               const regencies = await fetchWithRetry(`{{ url('master/wilayah/regencies') }}/${selectedProv}`);
               $reg.empty().append('<option value="">Pilih Kab/Kota</option>');
               regencies.forEach(r => {
                  $reg.append(new Option(r.name, r.code, false, false));
               });
               $reg.val(selectedReg).trigger('change.select2');

               if (selectedReg) {
                  const districts = await fetchWithRetry(`{{ url('master/wilayah/districts') }}/${selectedReg}`);
                  $dist.empty().append('<option value="">Pilih Kecamatan</option>');
                  districts.forEach(d => {
                     $dist.append(new Option(d.name, d.code, false, false));
                  });
                  $dist.val(selectedDist).trigger('change.select2');
               }
            }
         } catch (err) {
            console.error('Load regional error:', err);
         }
      };

      // --- MAIN INITIALIZATION ---
      $(function() {
         $('.select2').each(function() {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>').select2({
               placeholder: 'Pilih Opsian',
               dropdownParent: $this.parent()
            });
         });

         initTables();
         initGeolocation();

         const $prov = $('#mitra_province_code');
         const $reg = $('#mitra_regency_code');
         const $dist = $('#mitra_district_code');

         $prov.on('change', async function() {
            const code = $(this).val();
            $reg.empty().append('<option value="">Pilih Kab/Kota</option>');
            $dist.empty().append('<option value="">Pilih Kecamatan</option>');
            $reg.val('').trigger('change.select2');
            $dist.val('').trigger('change.select2');

            if (code) {
               try {
                  const data = await fetchWithRetry(`{{ url('master/wilayah/regencies') }}/${code}`);
                  data.forEach(item => {
                     $reg.append(new Option(item.name, item.code, false, false));
                  });
                  $reg.trigger('change.select2');
               } catch (err) {}
            }
         });

         $reg.on('change', async function() {
            const code = $(this).val();
            $dist.empty().append('<option value="">Pilih Kecamatan</option>');
            $dist.val('').trigger('change.select2');

            if (code) {
               try {
                  const data = await fetchWithRetry(`{{ url('master/wilayah/districts') }}/${code}`);
                  data.forEach(item => {
                     $dist.append(new Option(item.name, item.code, false, false));
                  });
                  $dist.trigger('change.select2');
               } catch (err) {}
            }
         });

         window.loadRegional();
      });

      // --- OTHER FUNCTIONS ---
      function initGeolocation() {
         const btn = document.getElementById('btn-get-location');
         if (!btn) return;
         btn.addEventListener('click', function() {
            if (navigator.geolocation) {
               this.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';
               this.disabled = true;
               navigator.geolocation.getCurrentPosition(
                  (position) => {
                     const lat = position.coords.latitude.toFixed(8);
                     const lng = position.coords.longitude.toFixed(8);
                     document.getElementById('mitra_titik_lokasi').value = `${lat}, ${lng}`;
                     this.innerHTML = '<i class="ri-gps-line"></i>';
                     this.disabled = false;
                  },
                  (error) => {
                     window.AlertHandler.showError('Gagal: ' + error.message);
                     this.innerHTML = '<i class="ri-gps-line"></i>';
                     this.disabled = false;
                  }
               );
            }
         });
      }

      function initTables() {
         $('#table-mitra').DataTable({
            ajax: "{{ route('mitra.index') }}",
            columns: [{
                  data: 'code'
               },
               {
                  data: 'name',
                  render: (data) => `<strong>${data}</strong>`
               },
               {
                  data: 'category.name',
                  defaultContent: '-'
               },
               {
                  data: 'pic',
                  render: (data, type, row) => `<span>${data || '-'}</span><br><small>${row.phone || '-'}</small>`
               },
               {
                  data: 'address',
                  render: (data, type, row) => {
                     let text = data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : '-';
                     if (row.latitude && row.longitude) text +=
                        `<br><a href="https://www.google.com/maps?q=${row.latitude},${row.longitude}" target="_blank" class="badge bg-label-info mt-1">Peta</a>`;
                     return text;
                  }
               },
               {
                  data: 'id',
                  render: (data, type, row) => {
                     let res = [];
                     if (row.province) res.push(row.province.name);
                     if (row.regency) res.push(row.regency.name);
                     if (row.district) res.push(row.district.name);
                     return res.length > 0 ? res.join(', ') : '-';
                  }
               },
               {
                  data: 'is_active',
                  render: data =>
                     `<span class="badge bg-label-${data ? 'success' : 'secondary'}">${data ? 'Aktif' : 'Non-Aktif'}</span>`
               },
               {
                  data: 'id',
                  render: (data, type, row) => `<div class="d-flex justify-content-center gap-2">
                     <button class="btn btn-sm btn-icon btn-label-primary" onclick="window.editMitra(${row.id})"><i class="ri-pencil-line"></i></button>
                     <button class="btn btn-sm btn-icon btn-label-danger" onclick="window.deleteRecord('mitra', ${row.id}, '${row.name}')"><i class="ri-delete-bin-line"></i></button>
                  </div>`
               }
            ]
         });

         $('#table-categories').DataTable({
            ajax: "{{ route('mitra-category.index') }}",
            columns: [{
                  data: 'name'
               },
               {
                  data: 'is_active',
                  render: data =>
                     `<span class="badge bg-label-${data ? 'success' : 'secondary'}">${data ? 'Aktif' : 'Non-Aktif'}</span>`
               },
               {
                  data: 'id',
                  render: (data, type, row) => `<div class="d-flex justify-content-center gap-2">
                     <button class="btn btn-sm btn-icon btn-label-primary" onclick="window.editCategory(${row.id})"><i class="ri-pencil-line"></i></button>
                     <button class="btn btn-sm btn-icon btn-label-danger" onclick="window.deleteRecord('mitra-category', ${row.id}, '${row.name}')"><i class="ri-delete-bin-line"></i></button>
                  </div>`
               }
            ]
         });
      }

      window.openMitraModal = function() {
         $('#formMitra')[0].reset();
         $('#mitra_id').val('');
         $('#mitra_category_id').val('').trigger('change');
         window.loadRegional();
         $('#modalMitraTitle').text('Tambah Mitra');
         new bootstrap.Modal($('#modalMitra')).show();
      }

      window.editMitra = async function(id) {
         try {
            const {
               data
            } = await fetchWithRetry(`{{ url('master/mitra') }}/${id}`);
            $('#mitra_id').val(data.id);
            $('#mitra_category_id').val(data.mitra_category_id).trigger('change');
            $('#mitra_code').val(data.code);
            $('#mitra_name').val(data.name);
            $('#mitra_pic').val(data.pic);
            $('#mitra_phone').val(data.phone);
            $('#mitra_address').val(data.address);
            window.loadRegional(data.province_code, data.regency_code, data.district_code);
            $('#mitra_titik_lokasi').val(data.latitude && data.longitude ? `${data.latitude}, ${data.longitude}` :
               '');
            $('#mitra_is_active').prop('checked', !!data.is_active);
            $('#modalMitraTitle').text('Edit Mitra');
            new bootstrap.Modal($('#modalMitra')).show();
         } catch (err) {
            window.AlertHandler.showError('Gagal memuat data mitra.');
         }
      }

      window.openCategoryModal = function() {
         $('#formCategory')[0].reset();
         $('#category_id').val('')
         $('#modalCategoryTitle').text('Tambah Kategori Mitra');
         new bootstrap.Modal($('#modalCategory')).show();
      }

      window.editCategory = async function(id) {
         try {
            const {
               data
            } = await fetchWithRetry(`{{ url('master/mitra-category') }}/${id}`);
            $('#category_id').val(data.id);
            $('#category_name').val(data.name);
            $('#category_is_active').prop('checked', !!data.is_active);
            $('#modalCategoryTitle').text('Edit Kategori Mitra');
            new bootstrap.Modal($('#modalCategory')).show();
         } catch (err) {
            window.AlertHandler.showError('Gagal memuat kategori.');
         }
      }

      window.saveMitra = function(e) {
         e.preventDefault();
         submitForm($('#mitra_id').val(), 'mitra', new FormData($('#formMitra')[0]), '#modalMitra', '#table-mitra');
      }

      window.saveCategory = function(e) {
         e.preventDefault();
         submitForm($('#category_id').val(), 'mitra-category', new FormData($('#formCategory')[0]), '#modalCategory',
            '#table-categories');
      }

      function submitForm(id, route, formData, modalSelector, tableSelector) {
         const url = id ? `{{ url('master') }}/${route}/${id}` : `{{ url('master') }}/${route}`;
         if (id) formData.append('_method', 'PUT');
         fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
               'X-CSRF-TOKEN': '{{ csrf_token() }}',
               'Accept': 'application/json'
            }
         }).then(async r => {
            const data = await r.json();
            window.AlertHandler.handle(data);
            if (data.success) {
               $(modalSelector).modal('hide');
               $(tableSelector).DataTable().ajax.reload();
            }
         });
      }

      window.deleteRecord = function(route, id, name) {
         window.AlertHandler.confirm('Hapus Data?', `Hapus "${name}"?`, 'Ya, Hapus!', () => {
            fetch(`{{ url('master') }}/${route}/${id}`, {
               method: 'DELETE',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Accept': 'application/json'
               }
            }).then(r => r.json()).then(data => {
               window.AlertHandler.handle(data);
               if (data.success) $(`#table-${route == 'mitra' ? 'mitra' : 'categories'}`).DataTable().ajax
                  .reload();
            });
         });
      }

      $('button[data-bs-target="#navs-wilayah"]').on('shown.bs.tab', () => window.loadSyncStatus());
   </script>
@endsection
