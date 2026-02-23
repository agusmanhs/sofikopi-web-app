@extends('layouts/layoutMaster')

@section('title', 'Hak Akses Role')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      @if (session('success'))
         <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Manajemen /</span> Hak Akses
         </h4>
      </div>

      <div class="card mb-4">
         <div class="card-body">
            <form action="{{ route('permission.index') }}" method="GET" id="roleFilterForm">
               <div class="row align-items-end">
                  <div class="col-md-4">
                     <label class="form-label" for="role_id">Pilih Role</label>
                     <select name="role_id" id="role_id" class="form-select"
                        onchange="document.getElementById('roleFilterForm').submit()">
                        <option value="">-- Pilih Role --</option>
                        @foreach ($roles as $role)
                           <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                              {{ $role->name }}</option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-8 text-end">
                     @if ($selectedRole)
                        <button type="button" class="btn btn-outline-secondary" id="checkAllBtn">
                           <i class="ri-check-double-line me-1"></i> Check All
                        </button>
                     @endif
                  </div>
               </div>
            </form>
         </div>
      </div>

      @if ($selectedRole)
         <form action="{{ route('permission.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">
            <div class="card">
               <div class="table-responsive text-nowrap">
                  <table class="table table-bordered table-hover">
                     <thead class="table-light">
                        <tr>
                           <th rowspan="2" class="text-center align-middle" style="width: 40%">Menu</th>
                           <th colspan="4" class="text-center">{{ $selectedRole->name }}</th>
                        </tr>
                        <tr>
                           <th class="text-center">
                              <div class="form-check d-flex justify-content-center">
                                 <input type="checkbox" class="form-check-input check-col" data-col="c" id="check-all-c"
                                    title="Check All Create">
                              </div>
                              <span class="d-block small mt-1">C</span>
                           </th>
                           <th class="text-center">
                              <div class="form-check d-flex justify-content-center">
                                 <input type="checkbox" class="form-check-input check-col" data-col="r" id="check-all-r"
                                    title="Check All Read">
                              </div>
                              <span class="d-block small mt-1">R</span>
                           </th>
                           <th class="text-center">
                              <div class="form-check d-flex justify-content-center">
                                 <input type="checkbox" class="form-check-input check-col" data-col="u" id="check-all-u"
                                    title="Check All Update">
                              </div>
                              <span class="d-block small mt-1">U</span>
                           </th>
                           <th class="text-center">
                              <div class="form-check d-flex justify-content-center">
                                 <input type="checkbox" class="form-check-input check-col" data-col="d" id="check-all-d"
                                    title="Check All Delete">
                              </div>
                              <span class="d-block small mt-1">D</span>
                           </th>
                        </tr>
                     </thead>
                     <tbody>
                        @foreach ($menus as $menu)
                           <tr class="table-secondary">
                              <td><strong>{{ $menu->name }}</strong></td>
                              @php
                                 $pivot = $selectedRole->menus->find($menu->id)?->pivot;
                              @endphp
                              <td class="text-center">
                                 <input type="checkbox"
                                    name="permissions[{{ $selectedRole->id }}][{{ $menu->id }}][c]" value="1"
                                    {{ $pivot?->can_create ? 'checked' : '' }}
                                    class="form-check-input perm-check perm-c parent-check"
                                    data-menu-id="{{ $menu->id }}" data-type="c">
                              </td>
                              <td class="text-center">
                                 <input type="checkbox"
                                    name="permissions[{{ $selectedRole->id }}][{{ $menu->id }}][r]" value="1"
                                    {{ $pivot?->can_read ? 'checked' : '' }}
                                    class="form-check-input perm-check perm-r parent-check"
                                    data-menu-id="{{ $menu->id }}" data-type="r">
                              </td>
                              <td class="text-center">
                                 <input type="checkbox"
                                    name="permissions[{{ $selectedRole->id }}][{{ $menu->id }}][u]" value="1"
                                    {{ $pivot?->can_update ? 'checked' : '' }}
                                    class="form-check-input perm-check perm-u parent-check"
                                    data-menu-id="{{ $menu->id }}" data-type="u">
                              </td>
                              <td class="text-center">
                                 <input type="checkbox"
                                    name="permissions[{{ $selectedRole->id }}][{{ $menu->id }}][d]" value="1"
                                    {{ $pivot?->can_delete ? 'checked' : '' }}
                                    class="form-check-input perm-check perm-d parent-check"
                                    data-menu-id="{{ $menu->id }}" data-type="d">
                              </td>
                           </tr>
                           @foreach ($menu->children as $child)
                              <tr>
                                 <td class="ps-5">— {{ $child->name }}</td>
                                 @php
                                    $pivotChild = $selectedRole->menus->find($child->id)?->pivot;
                                 @endphp
                                 <td class="text-center">
                                    <input type="checkbox"
                                       name="permissions[{{ $selectedRole->id }}][{{ $child->id }}][c]"
                                       value="1" {{ $pivotChild?->can_create ? 'checked' : '' }}
                                       class="form-check-input perm-check perm-c child-check"
                                       data-parent-id="{{ $menu->id }}" data-type="c">
                                 </td>
                                 <td class="text-center">
                                    <input type="checkbox"
                                       name="permissions[{{ $selectedRole->id }}][{{ $child->id }}][r]"
                                       value="1" {{ $pivotChild?->can_read ? 'checked' : '' }}
                                       class="form-check-input perm-check perm-r child-check"
                                       data-parent-id="{{ $menu->id }}" data-type="r">
                                 </td>
                                 <td class="text-center">
                                    <input type="checkbox"
                                       name="permissions[{{ $selectedRole->id }}][{{ $child->id }}][u]"
                                       value="1" {{ $pivotChild?->can_update ? 'checked' : '' }}
                                       class="form-check-input perm-check perm-u child-check"
                                       data-parent-id="{{ $menu->id }}" data-type="u">
                                 </td>
                                 <td class="text-center">
                                    <input type="checkbox"
                                       name="permissions[{{ $selectedRole->id }}][{{ $child->id }}][d]"
                                       value="1" {{ $pivotChild?->can_delete ? 'checked' : '' }}
                                       class="form-check-input perm-check perm-d child-check"
                                       data-parent-id="{{ $menu->id }}" data-type="d">
                                 </td>
                              </tr>
                           @endforeach
                        @endforeach
                     </tbody>
                  </table>
               </div>
               <div class="card-footer text-end">
                  <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
               </div>
            </div>
         </form>
      @else
         <div class="alert alert-info border-0 shadow-sm" role="alert">
            <i class="ri-information-line me-1"></i> Silakan pilih role terlebih dahulu untuk mengelola hak akses.
         </div>
      @endif
   </div>

   <script>
      document.addEventListener('DOMContentLoaded', function() {
         const checkAllBtn = document.getElementById('checkAllBtn');
         const checkboxes = document.querySelectorAll('.perm-check');
         const columnChecks = document.querySelectorAll('.check-col');

         // Global Check All
         if (checkAllBtn) {
            checkAllBtn.addEventListener('click', function() {
               const allChecked = Array.from(checkboxes).every(cb => cb.checked);
               checkboxes.forEach(cb => cb.checked = !allChecked);
               columnChecks.forEach(cb => cb.checked = !allChecked);

               this.innerHTML = !allChecked ?
                  '<i class="ri-checkbox-blank-line me-1"></i> Uncheck All' :
                  '<i class="ri-check-double-line me-1"></i> Check All';
            });
         }

         // Column Check All
         columnChecks.forEach(colCheck => {
            colCheck.addEventListener('change', function() {
               const col = this.dataset.col;
               const targetCheckboxes = document.querySelectorAll('.perm-' + col);
               targetCheckboxes.forEach(cb => cb.checked = this.checked);
            });
         });

         // Sync state (optional: update header checkbox if all in column are manually checked/unchecked)
         const syncColumnHeader = (col) => {
            const header = document.querySelector('#check-all-' + col);
            const colChecks = document.querySelectorAll('.perm-' + col);
            header.checked = Array.from(colChecks).every(cb => cb.checked);
         };

         checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
               const classList = Array.from(this.classList);
               const colClass = classList.find(c => c.startsWith('perm-') && c.length === 6);
               if (colClass) {
                  syncColumnHeader(colClass.split('-')[1]);
               }

               // Logic: If child is checked, parent MUST be checked
               if (this.classList.contains('child-check') && this.checked) {
                  const parentId = this.dataset.parentId;
                  const type = this.dataset.type;
                  const parentCb = document.querySelector(
                     `.parent-check[data-menu-id="${parentId}"][data-type="${type}"]`);
                  if (parentCb) {
                     parentCb.checked = true;
                     // Also sync parent column header
                     syncColumnHeader(type);
                  }
               }

               // Logic: If parent is checked/unchecked, toggle all children (UX enhancement)
               if (this.classList.contains('parent-check')) {
                  const menuId = this.dataset.menuId;
                  const type = this.dataset.type;
                  const childCbs = document.querySelectorAll(
                     `.child-check[data-parent-id="${menuId}"][data-type="${type}"]`);
                  childCbs.forEach(child => child.checked = this.checked);
                  // Also sync column header
                  syncColumnHeader(type);
               }
            });
         });
      });
   </script>
   </div>
@endsection
