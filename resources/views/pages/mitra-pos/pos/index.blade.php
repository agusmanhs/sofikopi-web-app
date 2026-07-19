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
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Kasir</h4>

    <div class="row g-4">
        <!-- Product grid -->
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
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
                        <div class="btn-group w-100" role="group" aria-label="payment method toggle">
                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_cash" value="cash" checked>
                            <label class="btn btn-outline-success" for="pay_cash">Cash</label>

                            <input type="radio" class="btn-check" name="paymentMethod" id="pay_qris" value="qris">
                            <label class="btn btn-outline-success" for="pay_qris">QRIS</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="discountInput">Diskon (Rp)</label>
                        <input type="number" min="0" step="1" class="form-control" id="discountInput" value="0">
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Subtotal</span>
                        <span id="subtotalDisplay">Rp0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Diskon</span>
                        <span id="discountDisplay">Rp0</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold fs-5">Total</span>
                        <span class="fw-bold fs-5" id="grandTotalDisplay">Rp0</span>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name=csrf-token]').content;
    const productsUrl = "{{ route('pos.products') }}";
    const checkoutUrl = "{{ route('pos.store') }}";

    let cart = [];

    function formatRupiah(n) {
        return 'Rp' + Math.round(n || 0).toLocaleString('id-ID');
    }

    // ---- Category filter ----
    const categoryTabs = document.querySelectorAll('.category-tab');
    categoryTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            categoryTabs.forEach(function (t) {
                t.classList.remove('btn-primary');
                t.classList.add('btn-outline-primary');
            });
            tab.classList.remove('btn-outline-primary');
            tab.classList.add('btn-primary');

            const category = tab.dataset.category;
            document.querySelectorAll('.product-card-col').forEach(function (col) {
                if (category === 'all' || col.dataset.category === category) {
                    col.classList.remove('d-none');
                } else {
                    col.classList.add('d-none');
                }
            });
        });
    });
    // Default active tab = "Semua"
    document.querySelector('.category-tab[data-category="all"]').classList.remove('btn-outline-primary');
    document.querySelector('.category-tab[data-category="all"]').classList.add('btn-primary');

    // ---- Cart logic ----
    function addToCart(id, name, price) {
        const existing = cart.find(function (c) { return c.mitra_product_id === id; });
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ mitra_product_id: id, name: name, price: price, qty: 1 });
        }
        renderCart();
    }

    function changeQty(id, delta) {
        const item = cart.find(function (c) { return c.mitra_product_id === id; });
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(function (c) { return c.mitra_product_id !== id; });
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(function (c) { return c.mitra_product_id !== id; });
        renderCart();
    }

    function computeTotals() {
        const subtotal = cart.reduce(function (sum, c) { return sum + (c.price * c.qty); }, 0);
        const discount = Math.max(0, Number(document.getElementById('discountInput').value) || 0);
        const grandTotal = Math.max(0, subtotal - discount);
        return { subtotal: subtotal, discount: discount, grandTotal: grandTotal };
    }

    function renderCart() {
        const cartList = document.getElementById('cartList');
        const cartEmpty = document.getElementById('cartEmpty');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const validationMsg = document.getElementById('cartValidationMsg');

        cartList.innerHTML = '';

        if (cart.length === 0) {
            cartEmpty.classList.remove('d-none');
            checkoutBtn.disabled = true;
        } else {
            cartEmpty.classList.add('d-none');
            checkoutBtn.disabled = false;

            cart.forEach(function (item) {
                const line = document.createElement('div');
                line.className = 'd-flex align-items-center justify-content-between border-bottom py-2 cart-line';
                line.innerHTML = `
                    <div class="flex-grow-1 me-2">
                        <div class="fw-medium">${item.name}</div>
                        <div class="text-muted small">${formatRupiah(item.price)} x ${item.qty} = ${formatRupiah(item.price * item.qty)}</div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon qty-minus"><i class="ri-subtract-line"></i></button>
                        <span class="mx-1">${item.qty}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon qty-plus"><i class="ri-add-line"></i></button>
                        <button type="button" class="btn btn-sm btn-text-danger btn-icon remove-line ms-1"><i class="ri-delete-bin-line"></i></button>
                    </div>
                `;
                line.querySelector('.qty-minus').addEventListener('click', function () { changeQty(item.mitra_product_id, -1); });
                line.querySelector('.qty-plus').addEventListener('click', function () { changeQty(item.mitra_product_id, 1); });
                line.querySelector('.remove-line').addEventListener('click', function () { removeFromCart(item.mitra_product_id); });
                cartList.appendChild(line);
            });
        }

        validationMsg.classList.add('d-none');

        const totals = computeTotals();
        document.getElementById('subtotalDisplay').textContent = formatRupiah(totals.subtotal);
        document.getElementById('discountDisplay').textContent = formatRupiah(totals.discount);
        document.getElementById('grandTotalDisplay').textContent = formatRupiah(totals.grandTotal);
    }

    document.getElementById('discountInput').addEventListener('input', renderCart);

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
            })
            .catch(function (err) {
                console.error('Gagal memuat stok produk:', err);
            });
    }

    // ---- Checkout ----
    function buildReceiptHtml(transaction) {
        let rows = '';
        (transaction.items || []).forEach(function (item) {
            rows += '<tr><td class="text-start">' + item.product_name + '</td><td>' + item.qty + '</td><td class="text-end">' + formatRupiah(item.line_total) + '</td></tr>';
        });

        return `
            <div class="text-start small">
                <p class="mb-1"><strong>No. Transaksi:</strong> ${transaction.transaction_no}</p>
                <table class="table table-sm mb-2">
                    <thead><tr><th class="text-start">Produk</th><th>Qty</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                <p class="mb-0 text-end"><strong>Total: ${formatRupiah(transaction.grand_total)}</strong></p>
            </div>
        `;
    }

    function buildWarningsHtml(warnings) {
        let items = '';
        warnings.forEach(function (w) {
            items += '<li>' + w.material_name + ': butuh ' + w.required + ', tersedia ' + w.available + '</li>';
        });
        return '<div class="alert alert-warning text-start small mt-3 mb-0"><strong>Peringatan stok bahan baku menipis/negatif:</strong><ul class="mb-0">' + items + '</ul></div>';
    }

    function resolveErrorMessage(status, body) {
        if (body && body.errors && typeof body.errors === 'object') {
            const messages = [];
            Object.keys(body.errors).forEach(function (field) {
                (body.errors[field] || []).forEach(function (m) { messages.push(m); });
            });
            if (messages.length > 0) return messages.join('<br>');
        }
        if (body && body.message) return body.message;
        return 'Terjadi kesalahan saat checkout (HTTP ' + status + ').';
    }

    document.getElementById('checkoutBtn').addEventListener('click', function () {
        if (cart.length === 0) {
            document.getElementById('cartValidationMsg').classList.remove('d-none');
            return;
        }

        const salesModeInput = document.querySelector('input[name="salesMode"]:checked');
        const paymentMethodInput = document.querySelector('input[name="paymentMethod"]:checked');

        const payload = {
            items: cart.map(function (c) { return { mitra_product_id: c.mitra_product_id, qty: c.qty }; }),
            discount: Number(document.getElementById('discountInput').value) || 0,
            sales_mode: salesModeInput ? salesModeInput.value : 'dine_in',
            payment_method: paymentMethodInput ? paymentMethodInput.value : 'cash'
        };

        const checkoutBtn = document.getElementById('checkoutBtn');
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
                return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; });
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
                    document.getElementById('discountInput').value = 0;
                    renderCart();
                    refreshStockBadges();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Checkout gagal',
                        html: resolveErrorMessage(result.status, result.body),
                        customClass: { confirmButton: 'btn btn-primary' },
                        buttonsStyling: false
                    });
                }
            })
            .catch(function (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Checkout gagal',
                    text: 'Tidak dapat menghubungi server. Periksa koneksi Anda.',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
                console.error(err);
            })
            .finally(function () {
                checkoutBtn.disabled = cart.length === 0;
                checkoutBtn.innerHTML = '<i class="ri-shopping-cart-2-line me-1"></i> Checkout';
            });
    });

    // Initial state
    renderCart();
    refreshStockBadges();
});
</script>
@endsection
