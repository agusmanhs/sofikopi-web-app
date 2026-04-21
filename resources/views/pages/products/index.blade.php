@extends('layouts/layoutMaster')

@section('title', 'Manajemen Produk')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl grow container-p-y">
      <h4 class="fw-bold py-3 mb-4">
         <span class="text-muted fw-light">Data Master /</span> Produk
      </h4>

      <!-- Navigation Tabs -->
      <div class="nav-align-top mb-4">
         <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
               <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-products" aria-controls="navs-products" aria-selected="true">
                  <i class="ri-shopping-bag-3-line me-1"></i> Data Produk
               </button>
            </li>
            <li class="nav-item">
               <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-sub-categories" aria-controls="navs-sub-categories" aria-selected="false">
                  <i class="ri-node-tree me-1"></i> Sub Kategori
               </button>
            </li>
            <li class="nav-item">
               <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                  data-bs-target="#navs-categories" aria-controls="navs-categories" aria-selected="false">
                  <i class="ri-folders-line me-1"></i> Kategori
               </button>
            </li>
         </ul>
         <div class="tab-content">
            <!-- TAB: PRODUCTS -->
            <div class="tab-pane fade show active" id="navs-products" role="tabpanel">
               <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Daftar Produk</h5>
                  <div class="d-flex gap-2">
                     <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImportProducts">
                        <i class="ri-file-upload-line me-1"></i> Import
                     </button>
                     <button class="btn btn-primary" onclick="openProductModal()">
                        <i class="ri-add-line me-1"></i> Tambah Produk
                     </button>
                  </div>
               </div>
               <div class="card-datatable table-responsive text-nowrap">
                  <table class="table table-hover" id="table-products">
                     <thead>
                        <tr>
                           <th></th>
                           <th>SKU</th>
                           <th>Produk</th>
                           <th>Sub Kategori</th>
                           <th>Harga Jual</th>
                           <th>Stok</th>
                           <th>Status</th>
                           <th>Aksi</th>
                        </tr>
                     </thead>
                  </table>
               </div>
            </div>

            <!-- TAB: SUB CATEGORIES -->
            <div class="tab-pane fade" id="navs-sub-categories" role="tabpanel">
               <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Sub Kategori Produk</h5>
                  <button class="btn btn-primary" onclick="openSubCategoryModal()">
                     <i class="ri-add-line me-1"></i> Tambah Sub Kategori
                  </button>
               </div>
               <div class="table-responsive">
                  <table class="table table-hover" id="table-sub-categories">
                     <thead>
                        <tr>
                           <th></th>
                           <th>Kategori Utama</th>
                           <th>Nama Sub Kategori</th>
                           <th>Status</th>
                           <th>Aksi</th>
                        </tr>
                     </thead>
                  </table>
               </div>
            </div>

            <!-- TAB: CATEGORIES -->
            <div class="tab-pane fade" id="navs-categories" role="tabpanel">
               <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0">Kategori Utama</h5>
                  <button class="btn btn-primary" onclick="openCategoryModal()">
                     <i class="ri-add-line me-1"></i> Tambah Kategori
                  </button>
               </div>
               <div class="table-responsive">
                  <table class="table table-hover" id="table-categories">
                     <thead>
                        <tr>
                           <th></th>
                           <th>Nama Kategori</th>
                           <th>Status</th>
                           <th>Aksi</th>
                        </tr>
                     </thead>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Category -->
   <div class="modal fade" id="modalCategory" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
         <form id="formCategory" class="modal-content" onsubmit="saveCategory(event)">
            @csrf
            <div class="modal-header">
               <h5 class="modal-title" id="modalCategoryTitle">Tambah Kategori</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" name="id" id="category_id">
            <div class="modal-body">
               <div class="mb-3">
                  <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                  <input type="text" name="name" id="category_name" class="form-control" placeholder="CONTOH: Kopi"
                     required>
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

   <!-- Modal Import Products -->
   <div class="modal fade" id="modalImportProducts" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header border-bottom">
               <h5 class="modal-title">Import Produk dari Excel</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formImportProducts" onsubmit="window.importProducts(event)">
               @csrf
               <div class="modal-body">
                  <div class="alert alert-primary mb-4" role="alert">
                     <h6 class="alert-heading mb-1 d-flex align-items-center">
                        <i class="ri-information-line me-2"></i> Panduan Pengisian Excel
                     </h6>
                     <ul class="mb-0 small ps-3">
                        <li>Gunakan file template resmi yang tersedia untuk menghindari error.</li>
                        <li><strong>Nama Produk</strong>: Wajib diisi.</li>
                        <li><strong>SKU</strong>: Jika kosong, sistem akan generate otomatis.</li>
                        <li><strong>Harga</strong>: Masukkan hanya angka (tanpa titik/koma).</li>
                        <li><strong>Stok Sekarang</strong>: Akan otomatis diset ke <strong>0</strong> (Produksi awal).</li>
                        <li><strong>Kategori & Sub</strong>: Jika belum ada, sistem akan membuatkan otomatis.</li>
                     </ul>
                  </div>

                  <div class="mb-4">
                     <label class="form-label fw-semibold">Pilih File Excel (.xlsx, .xls, .csv)</label>
                     <input type="file" name="file" class="form-control" accept=".xlsx, .xls, .csv" required>
                  </div>

                  <div class="d-grid mb-2">
                     <a href="{{ route('products.template') }}" class="btn btn-sm btn-label-secondary">
                        <i class="ri-download-line me-1"></i> Download Template Excel
                     </a>
                  </div>
               </div>
               <div class="modal-footer border-top">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary" id="btnImport">Mulai Import</button>
               </div>
            </form>
         </div>
      </div>
   </div>

   <!-- Modal Sub Category -->
   <div class="modal fade" id="modalSubCategory" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
         <form id="formSubCategory" class="modal-content" onsubmit="saveSubCategory(event)">
            @csrf
            <div class="modal-header">
               <h5 class="modal-title" id="modalSubCategoryTitle">Tambah Sub Kategori</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" name="id" id="sub_category_id">
            <div class="modal-body">
               <div class="mb-3">
                  <label class="form-label">Kategori Utama <span class="text-danger">*</span></label>
                  <select name="product_category_id" id="sub_category_parent" class="form-select select2" required>
                     <option value="">Pilih Kategori</option>
                     @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                     @endforeach
                  </select>
               </div>
               <div class="mb-3">
                  <label class="form-label">Nama Sub Kategori <span class="text-danger">*</span></label>
                  <input type="text" name="name" id="sub_category_name" class="form-control"
                     placeholder="CONTOH: Arabika" required>
               </div>
               <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="sub_category_is_active" name="is_active"
                     value="1" checked>
                  <label class="form-check-label" for="sub_category_is_active">Aktif</label>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
               <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
         </form>
      </div>
   </div>

   <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
         <form id="formProduct" class="modal-content" onsubmit="saveProduct(event)" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
               <h5 class="modal-title" id="modalProductTitle">Tambah Produk</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" name="id" id="product_id">
            <div class="modal-body">
               <div class="row g-3">
                  <!-- Left Side: Basic Info -->
                  <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label">Sub Kategori <span class="text-danger">*</span></label>
                        <select name="product_sub_category_id" id="product_sub_category_id" class="form-select select2"
                           required onchange="window.handleSubCategoryChange(this.value)">
                           <option value="">Pilih Sub Kategori</option>
                           @foreach ($subCategories as $sub)
                              <option value="{{ $sub->id }}" data-category="{{ $sub->category->name }}">
                                 {{ $sub->category->name }} - {{ $sub->name }}</option>
                           @endforeach
                        </select>
                     </div>
                     <div class="mb-3">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" id="product_sku" class="form-control"
                           placeholder="SKU-XXX" required>
                     </div>
                     <div class="mb-3">
                        <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="product_name" class="form-control"
                           placeholder="Nama Barang" required>
                     </div>
                     <div class="mb-3">
                        <label class="form-label">Satuan <span class="text-danger">*</span></label>
                        <select name="unit" id="product_unit" class="form-select" required>
                           <option value="Pcs">Pcs</option>
                           <option value="Bag">Bag</option>
                           <option value="Bottle">Bottle</option>
                           <option value="Gram">Gram</option>
                           <option value="Kilogram">Kilogram</option>
                           <option value="Mililiter">Mililiter</option>
                           <option value="Liter">Liter</option>
                           <option value="Pack">Pack</option>
                        </select>
                     </div>
                     <div class="row">
                        <div class="col-6 mb-3">
                           <label class="form-label">Netto</label>
                           <input type="number" step="0.01" name="netto" id="product_netto" class="form-control"
                              placeholder="0.00">
                        </div>
                        <div class="col-6 mb-3">
                           <label class="form-label">Berat Kotor (Gross)</label>
                           <input type="number" step="0.01" name="gross_weight" id="product_gross"
                              class="form-control" placeholder="0.00">
                        </div>
                     </div>
                  </div>

                  <!-- Right Side: Pricing & Stock -->
                  <div class="col-md-6">
                     <div class="mb-3">
                        <label class="form-label">Cover Produk</label>
                        <input type="file" name="cover" id="product_cover" class="form-control" accept="image/*">
                     </div>
                     <div class="mb-3">
                        <label class="form-label">Harga Beli <span class="text-danger">*</span></label>
                        <div class="input-group">
                           <span class="input-group-text">Rp</span>
                           <input type="number" name="buying_price" id="product_buying_price" class="form-control"
                              placeholder="0" required>
                        </div>
                     </div>
                     <div class="mb-3">
                        <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                        <div class="input-group">
                           <span class="input-group-text">Rp</span>
                           <input type="number" name="selling_price" id="product_selling_price" class="form-control"
                              placeholder="0" required>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-6 mb-3">
                           <label class="form-label">Stok Sekarang <span class="text-danger">*</span></label>
                           <input type="number" name="current_stock" id="product_stock" class="form-control"
                              value="0" required>
                        </div>
                        <div class="col-6 mb-3">
                           <label class="form-label">Stok Aman <span class="text-danger">*</span></label>
                           <input type="number" name="min_stock" id="product_min_stock" class="form-control"
                              value="0" required>
                        </div>
                     </div>
                     <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" id="product_is_active" name="is_active"
                           value="1" checked>
                        <label class="form-check-label" for="product_is_active">Aktif</label>
                     </div>
                  </div>

                  <div class="col-12">
                     <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" id="product_description" class="form-control" rows="2"></textarea>
                     </div>
                  </div>

                  <!-- DYNAMIC ATTRIBUTES AREA -->
                  <div class="col-12">
                     <hr>
                     <h6 class="mb-3 text-primary"><i class="ri-list-settings-line me-1"></i> Atribut Spesifik <small
                           class="text-muted">(Opsional, otomatis muncul sesuai kategori)</small></h6>
                     <div id="dynamic-attributes-container" class="row g-3">
                        <!-- JS will inject fields here -->
                        <div class="col-12 text-center py-3 text-muted" id="placeholder-attributes">
                           <small>Pilih Sub-Kategori untuk melihat atribut tambahan</small>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
               <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
         </form>
      </div>
   </div>

   <!-- Modal Preview Foto (Premium Style) -->
   <div class="modal fade animate__animated animate__fadeIn" id="modalPreviewFoto" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content bg-transparent shadow-none border-0">
            <div class="modal-header border-0 p-0 mb-3 justify-content-end">
               <button type="button" class="btn btn-icon btn-light rounded-circle shadow-lg" data-bs-dismiss="modal"
                  aria-label="Close" style="width: 40px; height: 40px;">
                  <i class="ri-close-line ri-xl text-dark"></i>
               </button>
            </div>
            <div class="modal-body p-0 text-center">
               <div class="position-relative overflow-hidden rounded-4 shadow-2xl">
                  <div id="modal-photo-title"
                     class="position-absolute top-0 start-50 translate-middle-x mt-3 px-4 py-2 rounded-pill shadow-lg"
                     style="z-index: 10; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; font-weight: 600; letter-spacing: 0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                  </div>
                  <img src="" id="foto-preview" class="img-fluid w-100 shadow-lg"
                     style="max-height: 85vh; object-fit: contain; background: #000; border-radius: 12px;">
               </div>
            </div>
         </div>
      </div>
   </div>

   <style>
      .modal-backdrop.show {
         backdrop-filter: blur(8px);
         -webkit-backdrop-filter: blur(8px);
         background-color: rgba(0, 0, 0, 0.6);
      }
      .shadow-2xl {
         box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      }
   </style>
@endsection

@section('page-script')
   <script type="module">
      $(function() {
         $('.select2').each(function() {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>').select2({
               placeholder: 'Pilih Opsian',
               dropdownParent: $this.parent()
            });
         });

         // Initialize DataTables
         initTables();
      });

      const config = {
         kopi: ['Origin', 'Process', 'Roast Level', 'Variety', 'Tasting Notes', 'Altitude'],
         syrup: ['Flavor', 'Shelf Life', 'Brand'],
         powder: ['Flavor', 'Shelf Life', 'Brand'],
         alat: ['Brand', 'Power (Watt)', 'Garansi']
      };

      window.handleSubCategoryChange = function(subId) {
         const container = $('#dynamic-attributes-container');
         const placeholder = $('#placeholder-attributes');
         const select = $('#product_sub_category_id');
         const categoryName = select.find(':selected').data('category')?.toLowerCase() || '';

         container.find('.dynamic-field').remove();
         placeholder.show();

         let fields = [];
         if (categoryName.includes('kopi')) fields = config.kopi;
         else if (categoryName.includes('syrup')) fields = config.syrup;
         else if (categoryName.includes('powder')) fields = config.powder;
         else if (categoryName.includes('alat') || categoryName.includes('equipment')) fields = config.alat;

         if (fields.length > 0) {
            placeholder.hide();
            fields.forEach(field => {
               const slug = field.toLowerCase().replace(/ /g, '_').replace(/[()]/g, '');
               const html = `
                    <div class="col-md-4 dynamic-field">
                        <label class="form-label">${field}</label>
                        <input type="text" name="attributes[${slug}]" class="form-control form-control-sm attr-input" data-slug="${slug}" placeholder="...">
                    </div>
                `;
               container.append(html);
            });
         }
      }

      // --- CRUD FUNCTIONS ---

      function initTables() {
         window.previewProductFoto = function(url, title) {
            const modal = new bootstrap.Modal(document.getElementById('modalPreviewFoto'));
            document.getElementById('foto-preview').src = url;
            document.getElementById('modal-photo-title').textContent = title;
            modal.show();
         }

         const dt_products = $('#table-products').DataTable({
            ajax: "{{ route('products.index') }}",
            responsive: {
               details: {
                  display: $.fn.dataTable.Responsive.display.modal({
                     header: function(row) {
                        return 'Detail Produk';
                     }
                  }),
                  type: 'column',
                  renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                     tableClass: 'table'
                  })
               }
            },
            columnDefs: [{
               className: 'control',
               orderable: false,
               targets: 0
            }],
            columns: [{
                  data: null,
                  defaultContent: ''
               },
               {
                  data: 'sku'
               },
               {
                  data: 'name',
                  render: function(data, type, row) {
                     return `<div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <a href="javascript:void(0);" onclick="window.previewProductFoto('${row.cover_url}', '${data}')">
                                   <img src="${row.cover_url}" class="rounded-circle" style="object-fit:cover">
                                </a>
                            </div>
                            <div>
                                <span class="fw-bold">${data}</span><br>
                                <small>${row.unit} | ${row.netto || '-'} ${row.unit == 'Gram' ? '' : ''}</small>
                            </div>
                        </div>`;
                  }
               },
               {
                  data: 'sub_category.name',
                  defaultContent: '-'
               },
               {
                  data: 'selling_price',
                  render: (data) => `Rp ${new Intl.NumberFormat('id-ID').format(data)}`
               },
               {
                  data: 'current_stock',
                  render: function(data, type, row) {
                     let color = data <= row.min_stock ? 'danger' : 'success';
                     return `<span class="badge bg-label-${color}">${data}</span>`;
                  }
               },
               {
                  data: 'is_active',
                  render: data =>
                     `<span class="badge bg-label-${data ? 'success' : 'secondary'}">${data ? 'Aktif' : 'Non-Aktif'}</span>`
               },
               {
                  data: 'id',
                  render: (data, type, row) => `
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-icon btn-label-primary" onclick="window.editProduct(${row.id})"><i class="ri-pencil-line"></i></button>
                            <button class="btn btn-sm btn-icon btn-label-danger" onclick="window.deleteRecord('products', ${row.id}, '${row.name}')"><i class="ri-delete-bin-line"></i></button>
                        </div>
                    `
               }
            ]
         });

         $('#table-sub-categories').DataTable({
            ajax: "{{ route('product-sub-category.index') }}",
            responsive: {
               details: {
                  display: $.fn.dataTable.Responsive.display.modal({
                     header: function(row) {
                        return 'Detail Sub Kategori';
                     }
                  }),
                  type: 'column',
                  renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                     tableClass: 'table'
                  })
               }
            },
            columnDefs: [{
               className: 'control',
               orderable: false,
               targets: 0
            }],
            columns: [{
                  data: null,
                  defaultContent: ''
               },
               {
                  data: 'category.name'
               },
               {
                  data: 'name'
               },
               {
                  data: 'is_active',
                  render: data =>
                     `<span class="badge bg-label-${data ? 'success' : 'secondary'}">${data ? 'Aktif' : 'Non-Aktif'}</span>`
               },
               {
                  data: 'id',
                  render: (data, type, row) => `
                         <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-icon btn-label-primary" onclick="window.editSubCategory(${row.id})"><i class="ri-pencil-line"></i></button>
                            <button class="btn btn-sm btn-icon btn-label-danger" onclick="window.deleteRecord('product-sub-category', ${row.id}, '${row.name}')"><i class="ri-delete-bin-line"></i></button>
                        </div>
                    `
               }
            ]
         });

         $('#table-categories').DataTable({
            ajax: "{{ route('product-category.index') }}",
            responsive: {
               details: {
                  display: $.fn.dataTable.Responsive.display.modal({
                     header: function(row) {
                        return 'Detail Kategori';
                     }
                  }),
                  type: 'column',
                  renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                     tableClass: 'table'
                  })
               }
            },
            columnDefs: [{
               className: 'control',
               orderable: false,
               targets: 0
            }],
            columns: [{
                  data: null,
                  defaultContent: ''
               },
               {
                  data: 'name'
               },
               {
                  data: 'is_active',
                  render: data =>
                     `<span class="badge bg-label-${data ? 'success' : 'secondary'}">${data ? 'Aktif' : 'Non-Aktif'}</span>`
               },
               {
                  data: 'id',
                  render: (data, type, row) => `
                         <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-icon btn-label-primary" onclick="window.editCategory(${row.id})"><i class="ri-pencil-line"></i></button>
                            <button class="btn btn-sm btn-icon btn-label-danger" onclick="window.deleteRecord('product-category', ${row.id}, '${row.name}')"><i class="ri-delete-bin-line"></i></button>
                        </div>
                    `
               }
            ]
         });
      }

      // --- MODAL & SAVE WRAPPERS ---

      window.openCategoryModal = function() {
         $('#formCategory')[0].reset();
         $('#category_id').val('');
         $('#modalCategoryTitle').text('Tambah Kategori');
         new bootstrap.Modal($('#modalCategory')).show();
      }

      window.editCategory = async function(id) {
         const resp = await fetch(`{{ url('master/product-category') }}/${id}`, {
            headers: {
               'Accept': 'application/json'
            }
         });
         const {
            data
         } = await resp.json();
         $('#category_id').val(data.id);
         $('#category_name').val(data.name);
         $('#category_is_active').prop('checked', !!data.is_active);
         $('#modalCategoryTitle').text('Edit Kategori');
         new bootstrap.Modal($('#modalCategory')).show();
      }

      window.openSubCategoryModal = function() {
         $('#formSubCategory')[0].reset();
         $('#sub_category_id').val('');
         $('#sub_category_parent').val('').trigger('change');
         $('#modalSubCategoryTitle').text('Tambah Sub Kategori');
         new bootstrap.Modal($('#modalSubCategory')).show();
      }

      window.editSubCategory = async function(id) {
         const resp = await fetch(`{{ url('master/product-sub-category') }}/${id}`, {
            headers: {
               'Accept': 'application/json'
            }
         });
         const json = await resp.json();
         const data = json.data;
         $('#sub_category_id').val(data.id);
         $('#sub_category_parent').val(data.product_category_id).trigger('change');
         $('#sub_category_name').val(data.name);
         $('#sub_category_is_active').prop('checked', !!data.is_active);
         $('#modalSubCategoryTitle').text('Edit Sub Kategori');
         new bootstrap.Modal($('#modalSubCategory')).show();
      }

      window.openProductModal = function() {
         $('#formProduct')[0].reset();
         $('#product_id').val('');
         $('#product_sub_category_id').val('').trigger('change');
         $('#modalProductTitle').text('Tambah Produk');
         new bootstrap.Modal($('#modalProduct')).show();
      }

      window.editProduct = async function(id) {
         $('#formProduct')[0].reset();
         const resp = await fetch(`{{ url('master/products') }}/${id}`, {
            headers: {
               'Accept': 'application/json'
            }
         });
         const {
            data
         } = await resp.json();
         $('#product_id').val(data.id);
         $('#product_sub_category_id').val(data.product_sub_category_id).trigger('change');
         $('#product_sku').val(data.sku);
         $('#product_name').val(data.name);
         $('#product_unit').val(data.unit);
         $('#product_netto').val(data.netto);
         $('#product_gross').val(data.gross_weight);
         $('#product_buying_price').val(data.buying_price);
         $('#product_selling_price').val(data.selling_price);
         $('#product_stock').val(data.current_stock);
         $('#product_min_stock').val(data.min_stock);
         $('#product_description').val(data.description);
         $('#product_is_active').prop('checked', !!data.is_active);

         setTimeout(() => {
            if (data.attributes) {
               Object.keys(data.attributes).forEach(key => {
                  $(`.attr-input[data-slug="${key}"]`).val(data.attributes[key]);
               });
            }
         }, 300);

         $('#modalProductTitle').text('Edit Produk');
         new bootstrap.Modal($('#modalProduct')).show();
      }

      window.saveCategory = function(e) {
         e.preventDefault();
         submitForm($('#category_id').val(), 'product-category', new FormData($('#formCategory')[0]), '#modalCategory',
            '#table-categories');
      }

      window.saveSubCategory = function(e) {
         e.preventDefault();
         submitForm($('#sub_category_id').val(), 'product-sub-category', new FormData($('#formSubCategory')[0]),
            '#modalSubCategory', '#table-sub-categories');
      }

      window.saveProduct = function(e) {
         e.preventDefault();
         submitForm($('#product_id').val(), 'products', new FormData($('#formProduct')[0]), '#modalProduct',
            '#table-products');
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
            })
            .then(async r => {
               const data = await r.json();
               if (!r.ok) {
                  window.AlertHandler.handle(data);
                  return;
               }
               if (data.success) {
                  window.AlertHandler.handle(data);
                  $(modalSelector).modal('hide');
                  $(tableSelector).DataTable().ajax.reload();
               }
            })
            .catch(err => {
               console.error(err);
               window.AlertHandler.showError('Terjadi kesalahan sistem');
            });
      }

      window.deleteRecord = function(route, id, name) {
         window.AlertHandler.confirm(
            'Hapus Data?',
            `Apakah Anda yakin ingin menghapus "${name}"?`,
            'Ya, Hapus!',
            () => {
               fetch(`{{ url('master') }}/${route}/${id}`, {
                     method: 'DELETE',
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                     }
                  })
                  .then(r => r.json())
                  .then(data => {
                     window.AlertHandler.handle(data);
                     if (data.success) {
                        $(`#table-${route == 'products' ? 'products' : (route == 'product-sub-category' ? 'sub-categories' : 'categories')}`)
                           .DataTable().ajax.reload();
                     }
                  });
            }
         );
      }

      window.importProducts = function(e) {
         e.preventDefault();
         const btn = $('#btnImport');
         btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Importing...');

         const formData = new FormData($('#formImportProducts')[0]);
         fetch("{{ route('products.import') }}", {
               method: 'POST',
               body: formData,
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Accept': 'application/json'
               }
            })
            .then(async r => {
               const data = await r.json();
               window.AlertHandler.handle(data);
               if (data.success) {
                  $('#modalImportProducts').modal('hide');
                  $('#table-products').DataTable().ajax.reload();
               }
            })
            .finally(() => {
               btn.prop('disabled', false).text('Mulai Import');
            });
      }
   </script>
@endsection
