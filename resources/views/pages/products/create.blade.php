@extends('layouts/layoutMaster')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Produk /</span> Tambah Produk
        </h4>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Form Tambah Produk</h5>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="cover">Cover Produk</label>
                                <input type="file" class="form-control @error('cover') is-invalid @enderror" id="cover" name="cover" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG, GIF. Max: 2MB</small>
                                @error('cover')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="preview-container" class="mt-2 d-none">
                                    <img id="preview-image" src="#" alt="Preview" class="img-thumbnail" style="max-height: 200px">
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="name">Nama Produk</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Laptop Gaming Razer">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="price">Harga (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" placeholder="0">
                                </div>
                                @error('price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="quantity">Stok / Jumlah</label>
                                <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity') }}" placeholder="0">
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="description">Deskripsi Produk</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Jelaskan detail produk...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktifkan Produk</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2">Simpan Produk</button>
                            <button type="reset" class="btn btn-label-secondary">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.getElementById('cover').onchange = evt => {
        const [file] = evt.target.files;
        if (file) {
            const preview = document.getElementById('preview-image');
            const container = document.getElementById('preview-container');
            preview.src = URL.createObjectURL(file);
            container.classList.remove('d-none');
        }
    }
</script>
@endsection
