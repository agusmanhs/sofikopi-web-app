{{-- Mitra name + "Kembali" now live in the global _admin-nav bar
     (contentNavbarLayout); this partial keeps only the akuntansi tabs. --}}
@if(isset($mitra))
<div class="mb-4">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('mitra-pos-manage.akuntansi-coa.*') ? 'active' : '' }}" href="{{ $routes['coa'] }}">Chart of Account</a>
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
