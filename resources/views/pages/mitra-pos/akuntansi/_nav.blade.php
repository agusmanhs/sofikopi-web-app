{{-- Mitra name + "Kembali" now live in the global _admin-nav bar
     (contentNavbarLayout); this partial keeps the akuntansi tabs and a
     visible mitra badge so staff never lose track of whose books they edit. --}}
@if(isset($mitra))
<div class="mb-4">
    <div class="mb-3">
        <span class="badge bg-label-primary fs-6"><i class="ri-store-2-line me-1"></i>Mitra: {{ $mitra->name }}</span>
    </div>
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mitra-pos-manage.akuntansi-coa.*') ? 'active' : '' }}" href="{{ $routes['coa'] }}">Daftar Akun</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mitra-pos-manage.akuntansi-jurnal.*') ? 'active' : '' }}" href="{{ $routes['jurnal'] }}">Jurnal</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mitra-pos-manage.akuntansi-neraca.*') ? 'active' : '' }}" href="{{ $routes['neraca'] }}">Neraca</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mitra-pos-manage.akuntansi-laba-rugi.*') ? 'active' : '' }}" href="{{ $routes['laba_rugi'] }}">Laba Rugi</a>
        </li>
    </ul>
</div>
@endif
