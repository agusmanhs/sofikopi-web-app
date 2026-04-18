@extends('layouts/layoutMaster')

@section('title', 'Manajemen Menu')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">
      <span class="text-muted fw-light">Manajemen /</span> Menu
    </h4>
    <a href="{{ route('menu.create') }}" class="btn btn-primary">
      <i class="ri-add-line me-1"></i> Tambah Menu
    </a>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Urutan</th>
            <th>Nama</th>
            <th>Icon</th>
            <th>Path</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($menus as $menu)
            <tr>
              <td>{{ $menu->order_no }}</td>
              <td><strong>{{ $menu->name }}</strong></td>
              <td><i class="{{ $menu->icon }}"></i></td>
              <td><code>{{ $menu->path }}</code></td>
              <td>{{ $menu->slug }}</td>
              <td>
                <span class="badge bg-label-{{ $menu->is_active ? 'success' : 'danger' }}">
                  {{ $menu->is_active ? 'Aktif' : 'Non-aktif' }}
                </span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="{{ route('menu.edit', $menu->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="ri-pencil-line"></i></a>
                  <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
                    onclick="confirmDeleteMenu({{ $menu->id }}, '{{ addslashes($menu->name) }}', {{ $menu->children->count() }})">
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </div>
              </td>
            </tr>
            @foreach($menu->children as $child)
              <tr>
                <td>{{ $menu->order_no }}.{{ $child->order_no }}</td>
                <td class="ps-5">— {{ $child->name }}</td>
                <td><i class="{{ $child->icon }}"></i></td>
                <td><code>{{ $child->url }}</code></td>
                <td>{{ $child->slug }}</td>
                <td>
                  <span class="badge bg-label-{{ $child->is_active ? 'success' : 'danger' }}">
                    {{ $child->is_active ? 'Aktif' : 'Non-aktif' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('menu.edit', $child->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="ri-pencil-line"></i></a>
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
                      onclick="confirmDeleteMenu({{ $child->id }}, '{{ addslashes($child->name) }}', 0)">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script type="module">
  /**
   * Strict delete confirmation:
   * - Jika menu punya children, langsung tolak (info saja)
   * - Jika tidak, muncul SweetAlert dengan input ketik nama menu untuk konfirmasi
   */
  window.confirmDeleteMenu = function(menuId, menuName, childCount) {
    // Lapis 1: Cek children
    if (childCount > 0) {
      window.AlertHandler.swal.fire({
        icon: 'error',
        title: 'Tidak Bisa Dihapus',
        html: `Menu <strong>"${menuName}"</strong> memiliki <strong>${childCount} sub-menu</strong>.<br>Hapus semua sub-menu terlebih dahulu.`,
        customClass: {
          confirmButton: 'btn btn-primary',
        },
        buttonsStyling: false,
      });
      return;
    }

    // Lapis 2: Type-to-confirm
    window.AlertHandler.swal.fire({
      icon: 'warning',
      title: 'Hapus Menu?',
      html: `
        <div class="text-start">
          <p>Menu <strong>"${menuName}"</strong> akan dihapus <strong>permanen</strong>.</p>
          <p class="text-danger mb-2"><small><i class="ri-error-warning-line me-1"></i>Aksi ini TIDAK BISA dibatalkan. Semua permission terkait menu ini juga akan dihapus.</small></p>
          <hr>
          <label class="form-label">Ketik <strong>"${menuName}"</strong> untuk konfirmasi:</label>
          <input type="text" id="confirmMenuName" class="form-control" placeholder="Ketik nama menu..." autocomplete="off">
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> Hapus Permanen',
      cancelButtonText: 'Batal',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary',
      },
      buttonsStyling: false,
      preConfirm: () => {
        const inputVal = document.getElementById('confirmMenuName').value.trim();
        if (inputVal !== menuName) {
          window.AlertHandler.swal.showValidationMessage('Nama menu tidak cocok! Ketik ulang dengan benar.');
          return false;
        }
        return true;
      },
      didOpen: () => {
        // Disable confirm button until user types
        const confirmBtn = window.AlertHandler.swal.getConfirmButton();
        confirmBtn.disabled = true;

        const input = document.getElementById('confirmMenuName');
        input.addEventListener('input', function() {
          confirmBtn.disabled = this.value.trim() !== menuName;
        });

        input.focus();
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Execute delete via AJAX
        fetch(`{{ url('menu') }}/${menuId}`, {
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
            setTimeout(() => location.reload(), 1500);
          }
        })
        .catch(err => {
          console.error(err);
          window.AlertHandler.showError('Terjadi kesalahan sistem');
        });
      }
    });
  }
</script>
@endsection
