@extends('layouts/layoutMaster')

@section('title', 'Kasir POS')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Kasir @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>

    <div class="row g-4">
        <!-- Product grid -->
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <input type="search" class="form-control mb-3" id="productSearch" placeholder="Cari produk atau kategori..." autocomplete="off">
                    <div class="d-flex flex-wrap gap-2" id="categoryTabs">
                        <button type="button" class="btn btn-sm btn-primary category-tab" data-category="all">Semua</button>
                        @foreach ($products->pluck('category')->filter()->unique()->values() as $category)
                            <button type="button" class="btn btn-sm btn-outline-primary category-tab" data-category="{{ $category }}">{{ $category }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row g-3" id="productGrid">
                @forelse ($products as $product)
                    <div class="col-6 col-md-4 col-xl-3 product-card-col" data-category="{{ $product->category }}">
                        <div class="card product-card h-100" role="button" tabindex="0"
                            data-id="{{ $product->id }}"
                            data-name="{{ $product->name }}"
                            data-price="{{ $product->sale_price }}">
                            <div class="card-body text-center p-3">
                                <h6 class="mb-1 text-truncate" title="{{ $product->name }}">{{ $product->name }}</h6>
                                <p class="mb-2 text-muted small text-truncate">{{ $product->category }}</p>
                                <p class="fw-bold mb-2">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</p>
                                <span class="badge bg-label-secondary stock-badge" data-product-id="{{ $product->id }}">Memuat...</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">Belum ada produk aktif untuk mitra ini.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Cart panel -->
        <div class="col-12 col-lg-4">
            <div class="card" style="position: sticky; top: 1rem;">
                <div class="card-header">
                    <h5 class="mb-0">Keranjang</h5>
                </div>
                <div class="card-body">
                    <div id="cartEmpty" class="text-center text-muted py-4">
                        <i class="ri-shopping-cart-line ri-24px d-block mb-2"></i>
                        Keranjang masih kosong
                    </div>
                    <div id="cartList"></div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Mode Penjualan</label>
                        <div class="btn-group w-100" role="group" aria-label="sales mode toggle">
                            <input type="radio" class="btn-check" name="salesMode" id="mode_dine_in" value="dine_in" checked>
                            <label class="btn btn-outline-primary" for="mode_dine_in">Dine In</label>

                            <input type="radio" class="btn-check" name="salesMode" id="mode_take_away" value="take_away">
                            <label class="btn btn-outline-primary" for="mode_take_away">Take Away</label>

                            <input type="radio" class="btn-check" name="salesMode" id="mode_online" value="online">
                            <label class="btn btn-outline-primary" for="mode_online">Online</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <div class="btn-group w-100 flex-wrap" role="group" aria-label="payment method toggle">
                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_cash" value="cash" checked>
                            <label class="btn btn-outline-success" for="pay_cash">Cash</label>

                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_qris" value="qris">
                            <label class="btn btn-outline-success" for="pay_qris">QRIS</label>

                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_transfer" value="transfer">
                            <label class="btn btn-outline-success" for="pay_transfer">Transfer</label>

                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_edc" value="edc">
                            <label class="btn btn-outline-success" for="pay_edc">EDC</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0" for="discountInput">Diskon</label>
                            <div class="btn-group btn-group-sm" role="group" aria-label="jenis diskon">
                                <input type="radio" class="btn-check" name="discountMode" id="discount_rp" value="rp" checked>
                                <label class="btn btn-outline-secondary" for="discount_rp">Rp</label>
                                <input type="radio" class="btn-check" name="discountMode" id="discount_pct" value="pct">
                                <label class="btn btn-outline-secondary" for="discount_pct">%</label>
                            </div>
                        </div>
                        <input type="text" inputmode="numeric" autocomplete="off" class="form-control" id="discountInput" placeholder="0">
                        <div class="form-text" id="discountHint"></div>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Subtotal</span>
                        <span id="subtotalDisplay">Rp0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Diskon</span>
                        <span id="discountDisplay">Rp0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 d-none" id="serviceRow">
                        <span class="text-muted" id="serviceLabel">Service charge</span>
                        <span id="serviceDisplay">Rp0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 d-none" id="taxRow">
                        <span class="text-muted" id="taxLabel">Pajak</span>
                        <span id="taxDisplay">Rp0</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold fs-5">Total bayar</span>
                        <span class="fw-bold fs-5" id="grandTotalDisplay">Rp0</span>
                    </div>
                    <div class="small text-muted mb-3 d-none" id="paymentHint"></div>

                    <div class="mb-3" id="cashSection">
                        <label class="form-label" for="cashReceivedInput">Uang diterima (Rp)</label>
                        <div class="input-group">
                            <input type="text" class="form-control rupiah-input" id="cashReceivedInput" placeholder="0">
                            <button type="button" class="btn btn-outline-secondary" id="exactCashBtn">Uang pas</button>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted">Kembalian</span>
                            <span class="fw-bold" id="changeDisplay">Rp0</span>
                        </div>
                        <div class="alert alert-danger py-2 px-3 small mt-2 mb-0 d-none" id="cashShortMsg"></div>
                    </div>

                    <div id="cartValidationMsg" class="alert alert-warning py-2 px-3 small d-none">
                        Tambahkan minimal 1 produk sebelum checkout.
                    </div>

                    <button type="button" class="btn btn-primary w-100" id="checkoutBtn" disabled>
                        <i class="ri-shopping-cart-2-line me-1"></i> Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name=csrf-token]').content;
    const productsUrl = "{{ $routes['products'] }}";
    const checkoutUrl = "{{ $routes['store'] }}";
    // transaction_no (e.g. POS/LALLO/20260722/0001) contains literal slashes
    // matched by the route's '.*' constraint — do NOT encodeURIComponent()
    // the replacement, that would %2F-escape the slashes and break routing.
    const receiptUrlTemplate = "{{ $routes['receipt'] }}";
    const charges = @json($charges);

    const escapeHtml = MitraForm.escapeHtml;
    const MAX_QTY = 999;
    const PAYMENT_LABELS = { cash: 'Cash', qris: 'QRIS', transfer: 'Transfer', edc: 'EDC' };

    let cart = [];

    function newClientToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 3 | 8)).toString(16);
        });
    }

    // Kept across network failures so a retry cannot be recorded twice; renewed after a confirmed sale or an emptied cart.
    let clientToken = newClientToken();
    let isBusy = false;
    let productStock = {};
    let currentCategory = 'all';

    function formatRupiah(n) {
        return 'Rp' + Math.round(n || 0).toLocaleString('id-ID');
    }

    function formatPercent(n) {
        return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    // Same rounding as PHP round(x, 2) for non-negative values.
    function round2(n) {
        return Math.round(n * 100 + 1e-8) / 100;
    }

    function selectedValue(name, fallback) {
        const input = document.querySelector('input[name="' + name + '"]:checked');
        return input ? input.value : fallback;
    }

    // ---- Product filter (category + search) ----
    const categoryTabs = document.querySelectorAll('.category-tab');
    const searchInput = document.getElementById('productSearch');

    function applyProductFilter() {
        const term = searchInput.value.trim().toLowerCase();
        document.querySelectorAll('.product-card-col').forEach(function (col) {
            const card = col.querySelector('.product-card');
            const name = (card ? card.dataset.name : '').toLowerCase();
            const category = (col.dataset.category || '').toLowerCase();
            const matchCategory = currentCategory === 'all' || col.dataset.category === currentCategory;
            const matchTerm = term === '' || name.indexOf(term) !== -1 || category.indexOf(term) !== -1;
            col.classList.toggle('d-none', !(matchCategory && matchTerm));
        });
    }

    categoryTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            categoryTabs.forEach(function (t) {
                t.classList.remove('btn-primary');
                t.classList.add('btn-outline-primary');
            });
            tab.classList.remove('btn-outline-primary');
            tab.classList.add('btn-primary');

            currentCategory = tab.dataset.category;
            applyProductFilter();
        });
    });
    searchInput.addEventListener('input', applyProductFilter);
    // Default active tab = "Semua"
    document.querySelector('.category-tab[data-category="all"]').classList.remove('btn-outline-primary');
    document.querySelector('.category-tab[data-category="all"]').classList.add('btn-primary');

    // ---- Cart logic ----
    function addToCart(id, name, price) {
        const existing = cart.find(function (c) { return c.mitra_product_id === id; });
        if (existing) {
            existing.qty = Math.min(MAX_QTY, existing.qty + 1);
        } else {
            cart.push({ mitra_product_id: id, name: name, price: price, qty: 1 });
        }
        renderCart();
    }

    function changeQty(id, delta) {
        const item = cart.find(function (c) { return c.mitra_product_id === id; });
        if (!item) return;
        item.qty = Math.min(MAX_QTY, item.qty + delta);
        if (item.qty <= 0) {
            cart = cart.filter(function (c) { return c.mitra_product_id !== id; });
        }
        renderCart();
    }

    function setQty(id, raw) {
        const item = cart.find(function (c) { return c.mitra_product_id === id; });
        if (!item) return;
        const value = parseInt(String(raw).trim(), 10);
        if (!isNaN(value)) {
            item.qty = Math.min(MAX_QTY, Math.max(1, value));
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(function (c) { return c.mitra_product_id !== id; });
        renderCart();
    }

    function isStockLow(item) {
        const stock = productStock[item.mitra_product_id];
        if (!stock || stock.current_stock === null || stock.current_stock === undefined) return false;
        return stock.low_stock || stock.current_stock <= 0 || item.qty > stock.current_stock;
    }

    // Mirrors PosTransactionService::checkout() — keep the two in sync.
    function computeTotals() {
        const subtotal = cart.reduce(function (sum, c) { return sum + (c.price * c.qty); }, 0);
        const maxDiscount = Math.floor(subtotal);
        const mode = selectedValue('discountMode', 'rp');
        const digits = document.getElementById('discountInput').value.replace(/[^\d]/g, '');
        const typed = digits ? Number(digits) : 0;

        let discount;
        let isClamped = false;
        if (mode === 'pct') {
            const pct = Math.min(100, typed);
            isClamped = typed > 100;
            discount = Math.min(maxDiscount, Math.round(subtotal * pct / 100));
        } else {
            discount = Math.min(maxDiscount, typed);
            isClamped = typed > maxDiscount;
        }

        const base = Math.max(0, subtotal - discount);
        const serviceCharge = round2(base * charges.service_charge_percent / 100);
        const tax = round2((base + serviceCharge) * charges.tax_percent / 100);
        const grandTotal = base + serviceCharge + tax;

        return {
            subtotal: subtotal, discount: discount, maxDiscount: maxDiscount, mode: mode, typed: typed,
            payable: Math.round(grandTotal), isClamped: isClamped, serviceCharge: serviceCharge, tax: tax, grandTotal: grandTotal
        };
    }

    function renderDiscount(totals) {
        const input = document.getElementById('discountInput');
        const hint = document.getElementById('discountHint');

        if (totals.isClamped) {
            input.value = totals.mode === 'pct' ? '100' : MitraForm.formatRupiah(totals.maxDiscount);
        }

        hint.classList.toggle('text-danger', totals.isClamped);
        hint.textContent = totals.mode === 'pct'
            ? 'Maksimal 100% (' + formatRupiah(totals.maxDiscount) + ')'
            : 'Maksimal ' + formatRupiah(totals.maxDiscount);
    }

    function renderCashSection(totals) {
        const isCash = selectedValue('paymentMethod', 'cash') === 'cash';
        document.getElementById('cashSection').classList.toggle('d-none', !isCash);

        const received = MitraForm.toRupiahNumber(document.getElementById('cashReceivedInput').value);
        const shortMsg = document.getElementById('cashShortMsg');
        const change = received !== null && received >= totals.payable ? received - totals.payable : 0;
        document.getElementById('changeDisplay').textContent = formatRupiah(change);

        if (isCash && cart.length > 0 && received !== null && received < totals.payable) {
            shortMsg.textContent = 'Uang diterima kurang ' + formatRupiah(totals.payable - received) + ' dari total bayar.';
            shortMsg.classList.remove('d-none');
        } else {
            shortMsg.classList.add('d-none');
        }
    }

    function renderPaymentHint() {
        const method = selectedValue('paymentMethod', 'cash');
        const hint = document.getElementById('paymentHint');
        const percent = method === 'cash' ? 0 : (charges[method + '_fee_percent'] || 0);

        if (percent > 0) {
            hint.textContent = 'Potongan admin ' + formatPercent(percent) + '% (tidak menambah tagihan pelanggan)';
            hint.classList.remove('d-none');
        } else {
            hint.classList.add('d-none');
        }
    }

    function renderCart() {
        const cartList = document.getElementById('cartList');
        const cartEmpty = document.getElementById('cartEmpty');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const validationMsg = document.getElementById('cartValidationMsg');

        cartList.innerHTML = '';

        if (cart.length === 0) {
            cartEmpty.classList.remove('d-none');
        } else {
            cartEmpty.classList.add('d-none');

            cart.forEach(function (item) {
                const line = document.createElement('div');
                line.className = 'd-flex align-items-center justify-content-between border-bottom py-2 cart-line';
                const stockBadge = isStockLow(item)
                    ? '<span class="badge bg-label-warning mt-1"><i class="ri-error-warning-line me-1"></i>Bahan hampir/sudah habis</span>'
                    : '';
                line.innerHTML = `
                    <div class="flex-grow-1 me-2">
                        <div class="fw-medium">${escapeHtml(item.name)}</div>
                        <div class="text-muted small">${formatRupiah(item.price)} x ${item.qty} = ${formatRupiah(item.price * item.qty)}</div>
                        ${stockBadge}
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon qty-minus" aria-label="Kurangi"><i class="ri-subtract-line"></i></button>
                        <input type="number" class="form-control form-control-sm text-center qty-input" style="width: 4.2rem;" min="1" max="${MAX_QTY}" step="1" value="${item.qty}" aria-label="Jumlah">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon qty-plus" aria-label="Tambah"><i class="ri-add-line"></i></button>
                        <button type="button" class="btn btn-sm btn-text-danger btn-icon remove-line ms-1" aria-label="Hapus"><i class="ri-delete-bin-line"></i></button>
                    </div>
                `;
                line.querySelector('.qty-minus').addEventListener('click', function () { changeQty(item.mitra_product_id, -1); });
                line.querySelector('.qty-plus').addEventListener('click', function () { changeQty(item.mitra_product_id, 1); });
                line.querySelector('.qty-input').addEventListener('change', function (e) { setQty(item.mitra_product_id, e.target.value); });
                line.querySelector('.remove-line').addEventListener('click', function () { removeFromCart(item.mitra_product_id); });
                cartList.appendChild(line);
            });
        }

        if (cart.length === 0) clientToken = newClientToken();
        checkoutBtn.disabled = cart.length === 0 || isBusy;
        validationMsg.classList.add('d-none');

        const totals = computeTotals();
        renderDiscount(totals);

        document.getElementById('subtotalDisplay').textContent = formatRupiah(totals.subtotal);
        document.getElementById('discountDisplay').textContent = totals.discount > 0 ? '-' + formatRupiah(totals.discount) : formatRupiah(0);

        document.getElementById('serviceRow').classList.toggle('d-none', charges.service_charge_percent <= 0);
        document.getElementById('serviceLabel').textContent = 'Service charge (' + formatPercent(charges.service_charge_percent) + '%)';
        document.getElementById('serviceDisplay').textContent = formatRupiah(totals.serviceCharge);

        document.getElementById('taxRow').classList.toggle('d-none', charges.tax_percent <= 0);
        document.getElementById('taxLabel').textContent = 'Pajak (' + formatPercent(charges.tax_percent) + '%)';
        document.getElementById('taxDisplay').textContent = formatRupiah(totals.tax);

        document.getElementById('grandTotalDisplay').textContent = formatRupiah(totals.payable);

        renderCashSection(totals);
        renderPaymentHint();
    }

    const discountInput = document.getElementById('discountInput');
    discountInput.addEventListener('input', function () {
        const digits = discountInput.value.replace(/[^\d]/g, '');
        const mode = selectedValue('discountMode', 'rp');
        discountInput.value = digits === '' ? '' : (mode === 'pct' ? digits : MitraForm.formatRupiah(Number(digits)));
        renderCart();
    });

    document.querySelectorAll('input[name="discountMode"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            discountInput.value = '';
            renderCart();
        });
    });

    document.querySelectorAll('input[name="paymentMethod"]').forEach(function (radio) {
        radio.addEventListener('change', renderCart);
    });

    document.getElementById('cashReceivedInput').addEventListener('input', renderCart);
    document.getElementById('exactCashBtn').addEventListener('click', function () {
        const input = document.getElementById('cashReceivedInput');
        input.value = MitraForm.formatRupiah(computeTotals().payable);
        renderCart();
    });

    document.querySelectorAll('.product-card').forEach(function (card) {
        card.addEventListener('click', function () {
            addToCart(parseInt(card.dataset.id, 10), card.dataset.name, parseFloat(card.dataset.price));
        });
        card.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (cart.length === 0) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ---- Stock badge refresh ----
    function refreshStockBadges() {
        fetch(productsUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (body) {
                if (!body || body.success !== true || !Array.isArray(body.data)) return;

                body.data.forEach(function (p) {
                    productStock[p.id] = { current_stock: p.current_stock, low_stock: !!p.low_stock };

                    const badge = document.querySelector('.stock-badge[data-product-id="' + p.id + '"]');
                    if (!badge) return;

                    badge.classList.remove('bg-label-secondary', 'bg-label-success', 'bg-label-warning', 'bg-label-danger');

                    if (p.current_stock === null) {
                        badge.textContent = 'Stok tak terbatas';
                        badge.classList.add('bg-label-secondary');
                    } else if (p.current_stock <= 0) {
                        badge.textContent = 'Stok habis';
                        badge.classList.add('bg-label-danger');
                    } else if (p.low_stock) {
                        badge.textContent = 'Stok: ' + p.current_stock + ' (menipis)';
                        badge.classList.add('bg-label-warning');
                    } else {
                        badge.textContent = 'Stok: ' + p.current_stock;
                        badge.classList.add('bg-label-success');
                    }
                });

                renderCart();
            })
            .catch(function (err) {
                console.error('Gagal memuat stok produk:', err);
            });
    }

    // ---- Checkout ----
    function buildReceiptHtml(transaction) {
        let rows = '';
        (transaction.items || []).forEach(function (item) {
            rows += '<tr><td class="text-start">' + escapeHtml(item.product_name) + '</td><td>' + escapeHtml(item.qty) + '</td><td class="text-end">' + formatRupiah(item.line_total) + '</td></tr>';
        });

        const receiptUrl = receiptUrlTemplate.replace('__TRANSACTION_NO__', transaction.transaction_no);

        let extraRows = '';
        if (Number(transaction.service_charge) > 0) {
            extraRows += '<p class="mb-1 d-flex justify-content-between"><span>Service Charge</span><span>' + formatRupiah(transaction.service_charge) + '</span></p>';
        }
        if (Number(transaction.tax) > 0) {
            extraRows += '<p class="mb-1 d-flex justify-content-between"><span>Pajak</span><span>' + formatRupiah(transaction.tax) + '</span></p>';
        }

        return `
            <div class="text-start small">
                <p class="mb-1"><strong>No. Transaksi:</strong> ${escapeHtml(transaction.transaction_no)}</p>
                <table class="table table-sm mb-2">
                    <thead><tr><th class="text-start">Produk</th><th>Qty</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                ${extraRows}
                <p class="mb-2 text-end"><strong>Total: ${formatRupiah(transaction.grand_total)}</strong></p>
                <p class="mb-0 text-center">
                    <a href="${escapeHtml(receiptUrl)}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-printer-line me-1"></i> Cetak Struk
                    </a>
                </p>
            </div>
        `;
    }

    function formatQty(n) {
        return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    }

    function buildWarningsHtml(warnings) {
        let items = '';
        warnings.forEach(function (w) {
            const unit = w.unit ? ' ' + escapeHtml(w.unit) : '';
            items += '<li>Bahan ' + escapeHtml(w.material_name) + ' kurang: butuh ' + formatQty(w.required) + unit + ', tersedia ' + formatQty(w.available) + unit + '</li>';
        });
        return '<div class="alert alert-warning text-start small mt-3 mb-0"><strong>Stok bahan tidak cukup. Segera isi ulang bahan berikut:</strong><ul class="mb-0">' + items + '</ul></div>';
    }

    function resolveErrorMessage(status, body) {
        if (body && body.errors && typeof body.errors === 'object') {
            const messages = [];
            Object.keys(body.errors).forEach(function (field) {
                (body.errors[field] || []).forEach(function (m) { messages.push(escapeHtml(m)); });
            });
            if (messages.length > 0) return messages.join('<br>');
        }
        if (body && body.message) return escapeHtml(body.message);
        return 'Terjadi kesalahan saat checkout (kode ' + status + '). Transaksi belum tersimpan.';
    }

    function showError(html) {
        Swal.fire({
            icon: 'error',
            title: 'Checkout gagal',
            html: html,
            customClass: { confirmButton: 'btn btn-primary' },
            buttonsStyling: false
        });
    }

    function buildSummaryLines(totals, method) {
        const lines = cart.map(function (c) {
            return c.name + ' x ' + c.qty + ' = ' + formatRupiah(c.price * c.qty);
        });
        lines.push('Subtotal: ' + formatRupiah(totals.subtotal));
        if (totals.discount > 0) lines.push('Diskon: -' + formatRupiah(totals.discount));
        if (charges.service_charge_percent > 0) lines.push('Service charge: ' + formatRupiah(totals.serviceCharge));
        if (charges.tax_percent > 0) lines.push('Pajak: ' + formatRupiah(totals.tax));
        lines.push('TOTAL BAYAR: ' + formatRupiah(totals.payable));
        lines.push('Metode bayar: ' + (PAYMENT_LABELS[method] || method));

        if (method === 'cash') {
            const received = MitraForm.toRupiahNumber(document.getElementById('cashReceivedInput').value) || 0;
            lines.push('Uang diterima: ' + formatRupiah(received));
            lines.push('Kembalian: ' + formatRupiah(Math.max(0, received - totals.payable)));
        }
        return lines;
    }

    function sendCheckout(payload) {
        const checkoutBtn = document.getElementById('checkoutBtn');
        isBusy = true;
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        fetch(checkoutUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.json()
                    .catch(function () { return null; })
                    .then(function (body) { return { ok: res.ok, status: res.status, body: body }; });
            })
            .then(function (result) {
                if (result.ok && result.body && result.body.success === true) {
                    const data = result.body.data || {};
                    const transaction = data.transaction || {};
                    const warnings = data.stock_warnings || [];

                    Swal.fire({
                        icon: 'success',
                        title: 'Transaksi berhasil',
                        html: buildReceiptHtml(transaction) + (warnings.length > 0 ? buildWarningsHtml(warnings) : ''),
                        customClass: { confirmButton: 'btn btn-primary' },
                        buttonsStyling: false
                    });

                    cart = [];
                    clientToken = newClientToken();
                    discountInput.value = '';
                    document.getElementById('cashReceivedInput').value = '';
                    renderCart();
                    refreshStockBadges();
                } else {
                    showError(resolveErrorMessage(result.status, result.body));
                }
            })
            .catch(function (err) {
                showError('Koneksi terputus. Transaksi MUNGKIN sudah tersimpan — cek Riwayat Transaksi sebelum mengulang. Kalau Anda menekan Bayar lagi, sistem tidak akan mencatat dobel.');
                console.error(err);
            })
            .finally(function () {
                isBusy = false;
                checkoutBtn.innerHTML = '<i class="ri-shopping-cart-2-line me-1"></i> Checkout';
                checkoutBtn.disabled = cart.length === 0;
            });
    }

    document.getElementById('checkoutBtn').addEventListener('click', function () {
        if (isBusy) return;

        if (cart.length === 0) {
            document.getElementById('cartValidationMsg').classList.remove('d-none');
            return;
        }

        const method = selectedValue('paymentMethod', 'cash');
        const totals = computeTotals();

        if (method === 'cash') {
            const received = MitraForm.toRupiahNumber(document.getElementById('cashReceivedInput').value);
            if (received === null || received < totals.payable) {
                showError('Uang diterima belum cukup. Isi "Uang diterima" minimal ' + formatRupiah(totals.payable) + ', atau tekan "Uang pas".');
                return;
            }
        }

        // Lock the button while the dialog is open so a second click cannot stack another one.
        isBusy = true;
        this.disabled = true;

        const payload = {
            items: cart.map(function (c) { return { mitra_product_id: c.mitra_product_id, qty: c.qty }; }),
            discount: totals.discount,
            sales_mode: selectedValue('salesMode', 'dine_in'),
            payment_method: method,
            client_token: clientToken
        };

        MitraForm.confirm({
            title: 'Proses pembayaran?',
            lines: buildSummaryLines(totals, method),
            confirmText: 'Ya, proses'
        }).then(function (ok) {
            if (!ok) {
                isBusy = false;
                renderCart();
                return;
            }
            sendCheckout(payload);
        });
    });

    // Initial state
    renderCart();
    refreshStockBadges();
});
</script>
@endsection
