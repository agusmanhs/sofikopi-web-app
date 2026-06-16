@extends('layouts/layoutMaster')

@section('title', 'Edit Sales Order')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Penjualan / Sales Order /</span> Edit Draft
    </h4>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('sales-order.update', $data->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label d-block">Tipe Customer</label>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="customer_type" id="type_mitra" value="mitra" {{ $data->mitra_id ? 'checked' : '' }}>
                                    <label class="form-check-label" for="type_mitra">Pilih dari Mitra</label>
                                </div>
                                <div class="form-check form-check-inline mt-2">
                                    <input class="form-check-input" type="radio" name="customer_type" id="type_manual" value="manual" {{ !$data->mitra_id ? 'checked' : '' }}>
                                    <label class="form-check-label" for="type_manual">Ketik Manual</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Pengiriman</label>
                                <select name="delivery_type" class="form-select" required>
                                    <option value="delivery" {{ $data->delivery_type == 'delivery' ? 'selected' : '' }}>Diantar (Delivery)</option>
                                    <option value="self_pickup" {{ $data->delivery_type == 'self_pickup' ? 'selected' : '' }}>Ambil Sendiri (Self Pickup)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 {{ $data->mitra_id ? '' : 'd-none' }}" id="mitra_container">
                                <label class="form-label">Nama Mitra</label>
                                <select name="mitra_id" class="form-select select2" id="mitra_select">
                                    <option value="">-- Pilih Mitra --</option>
                                    @foreach($mitras as $mitra)
                                        <option value="{{ $mitra->id }}" {{ $data->mitra_id == $mitra->id ? 'selected' : '' }}>{{ $mitra->code }} - {{ $mitra->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 {{ !$data->mitra_id ? '' : 'd-none' }}" id="manual_container">
                                <label class="form-label">Nama Customer</label>
                                <input type="text" name="customer_name" id="manual_input" class="form-control" placeholder="Contoh: Budi Susanto" value="{{ !$data->mitra_id ? $data->customer_name : '' }}">
                            </div>
                        </div>

                        <!-- Dynamic Item Repeater -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5>Daftar Produk</h5>
                                <table class="table table-bordered" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th width="150">Quantity</th>
                                            <th width="100">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($data->items as $index => $item)
                                        <tr>
                                            <td>
                                                <select name="items[{{ $index }}][product_id]" class="form-select select2" required>
                                                    <option value="">-- Pilih Produk --</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item->quantity }}" min="1" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="ri-delete-bin-line"></i></button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td>
                                                <select name="items[0][product_id]" class="form-select select2" required>
                                                    <option value="">-- Pilih Produk --</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="ri-delete-bin-line"></i></button>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addRow"><i class="ri-add-line"></i> Tambah Produk</button>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Catatan Tambahan</label>
                                <textarea name="notes" class="form-control" rows="3">{{ $data->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end border-top pt-3">
                        <a href="{{ route('sales-order.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        
        if (jq) {
            jq('.select2').select2({
                placeholder: '-- Pilih --',
                allowClear: true
            });

            let itemIndex = {{ max(count($data->items), 1) }};
            jq('#addRow').on('click', function() {
                let tbody = jq('#itemsTable tbody');
                let tr = jq('<tr></tr>');
                tr.html(`
                    <td>
                        <select name="items[${itemIndex}][product_id]" class="form-select select2" required>
                            <option value="">-- Pilih Produk --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="1" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="ri-delete-bin-line"></i></button>
                    </td>
                `);
                tbody.append(tr);
                
                tr.find('.select2').select2({
                    placeholder: '-- Pilih Produk --',
                    allowClear: true
                });
                
                itemIndex++;
            });

            jq('#itemsTable').on('click', '.remove-row', function() {
                let tbody = jq('#itemsTable tbody');
                if (tbody.children().length > 1) {
                    jq(this).closest('tr').remove();
                } else {
                    alert('Minimal harus ada 1 produk.');
                }
            });

            jq('input[name="customer_type"]').on('change', function() {
                if (jq(this).val() === 'mitra') {
                    jq('#mitra_container').removeClass('d-none');
                    jq('#mitra_select').prop('required', true);
                    
                    jq('#manual_container').addClass('d-none');
                    jq('#manual_input').prop('required', false).val('');
                } else {
                    jq('#manual_container').removeClass('d-none');
                    jq('#manual_input').prop('required', true);
                    
                    jq('#mitra_container').addClass('d-none');
                    jq('#mitra_select').prop('required', false).val('').trigger('change');
                }
            });
            
            // Set initial required state
            if (jq('input[name="customer_type"]:checked').val() === 'mitra') {
                jq('#mitra_select').prop('required', true);
                jq('#manual_input').prop('required', false);
            } else {
                jq('#mitra_select').prop('required', false);
                jq('#manual_input').prop('required', true);
            }
        } else {
            console.error('jQuery tidak ditemukan!');
        }
    });
</script>
@endsection
