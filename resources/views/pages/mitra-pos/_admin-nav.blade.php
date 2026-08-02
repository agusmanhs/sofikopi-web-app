{{--
    Global admin-context bar for Sofikopi staff browsing a mitra's POS pages
    (mitra-pos/manage/{mitra}/...). Included by contentNavbarLayout — pages
    never include this themselves. Requires $mitra in scope.
--}}
<div class="container-xxl pt-4">
    <div class="card">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <span class="text-muted">
                    <i class="ri-shield-user-line me-1"></i>
                    Mode admin — mengelola: <strong>{{ $mitra->name }}</strong> ({{ $mitra->code }})
                </span>
                <a href="{{ route('mitra-pos-manage.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali ke Kelola Mitra POS
                </a>
            </div>
            <ul class="nav nav-pills flex-wrap gap-1">
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.dashboard.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.dashboard.index', $mitra) }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.pos.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.pos.index', $mitra) }}">Kasir</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.transaction.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.transaction.index', $mitra) }}">Transaksi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.stock.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.stock.index', $mitra) }}">Stok</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.opname.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.opname.index', $mitra) }}">Opname</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.report.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.report.index', $mitra) }}">Laporan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-material.*') ? 'active' : '' }}"
                        href="{{ route('mitra-material.index', $mitra) }}">Material</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-product.*') ? 'active' : '' }}"
                        href="{{ route('mitra-product.index', $mitra) }}">Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.akuntansi-*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.akuntansi-coa.index', $mitra) }}">Akuntansi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-1 px-2 {{ request()->routeIs('mitra-pos-manage.setting.*') ? 'active' : '' }}"
                        href="{{ route('mitra-pos-manage.setting.index', $mitra) }}">Pengaturan</a>
                </li>
            </ul>
        </div>
    </div>
</div>
