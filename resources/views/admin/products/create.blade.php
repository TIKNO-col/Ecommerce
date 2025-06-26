@extends('layouts.app')

@section('title', 'Agregar Producto - Panel de Administración')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Agregar Nuevo Producto</h1>
                    <p class="text-muted mb-0">Completa la información del producto</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver a Productos
                </a>
            </div>

            <!-- Product Form -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
                        @csrf
                        
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-lg-8">
                                <h5 class="mb-3">Información Básica</h5>
                                
                                <!-- Product Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre del Producto *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- SKU -->
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku') }}" placeholder="Se generará automáticamente si se deja vacío">
                                    <small class="text-muted">Código único del producto</small>
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descripción *</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Short Description -->
                                <div class="mb-3">
                                    <label for="short_description" class="form-label">Descripción Corta</label>
                                    <textarea class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" rows="2" maxlength="500">{{ old('short_description') }}</textarea>
                                    <small class="text-muted">Máximo 500 caracteres</small>
                                    @error('short_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Pricing -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Precio Regular *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0" required>
                                            </div>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sale_price" class="form-label">Precio de Oferta</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price" value="{{ old('sale_price') }}" step="0.01" min="0">
                                            </div>
                                            <small class="text-muted">Debe ser menor al precio regular</small>
                                            @error('sale_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Inventory -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock_quantity" class="form-label">Cantidad en Stock *</label>
                                            <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0" required>
                                            @error('stock_quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="weight" class="form-label">Peso (kg)</label>
                                            <input type="number" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" value="{{ old('weight') }}" step="0.01" min="0">
                                            @error('weight')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Dimensions -->
                                <div class="mb-3">
                                    <label for="dimensions" class="form-label">Dimensiones</label>
                                    <input type="text" class="form-control @error('dimensions') is-invalid @enderror" id="dimensions" name="dimensions" value="{{ old('dimensions') }}" placeholder="Largo x Ancho x Alto (cm)">
                                    @error('dimensions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- SEO -->
                                <h5 class="mb-3 mt-4">SEO</h5>
                                <div class="mb-3">
                                    <label for="meta_title" class="form-label">Meta Título</label>
                                    <input type="text" class="form-control @error('meta_title') is-invalid @enderror" id="meta_title" name="meta_title" value="{{ old('meta_title') }}" maxlength="255">
                                    @error('meta_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Meta Descripción</label>
                                    <textarea class="form-control @error('meta_description') is-invalid @enderror" id="meta_description" name="meta_description" rows="2" maxlength="500">{{ old('meta_description') }}</textarea>
                                    @error('meta_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-lg-4">
                                <!-- Categories -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Categorías *</h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach($categories as $category)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category->id }}" id="category_{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="category_{{ $category->id }}">
                                                    {{ $category->name }}
                                                </label>
                                            </div>
                                            @if($category->children->count() > 0)
                                                @foreach($category->children as $subcategory)
                                                    <div class="form-check ms-3">
                                                        <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $subcategory->id }}" id="category_{{ $subcategory->id }}" {{ in_array($subcategory->id, old('categories', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="category_{{ $subcategory->id }}">
                                                            {{ $subcategory->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        @endforeach
                                        @error('categories')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Brand -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Marca</h6>
                                    </div>
                                    <div class="card-body">
                                        <select class="form-select @error('brand_id') is-invalid @enderror" name="brand_id" required>
                                            <option value="">Seleccionar marca</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('brand_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Product Images -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Imágenes del Producto</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                                            <small class="text-muted">Máximo 5 imágenes. Formatos: JPG, PNG, GIF, WebP</small>
                                        </div>
                                        <div id="imagePreview" class="row"></div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Estado</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                            <label class="form-check-label" for="is_active">
                                                Producto Activo
                                            </label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                            <label class="form-check-label" for="is_featured">
                                                Producto Destacado
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner"></span>
                                        <i class="fas fa-save me-2"></i>Guardar Producto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Image preview
    $('#images').on('change', function() {
        const files = this.files;
        const preview = $('#imagePreview');
        preview.empty();
        
        if (files.length > 5) {
            alert('Máximo 5 imágenes permitidas');
            this.value = '';
            return;
        }
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.append(`
                    <div class="col-6 mb-2">
                        <img src="${e.target.result}" class="img-fluid rounded" style="height: 100px; object-fit: cover;">
                    </div>
                `);
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Auto-generate SKU from name
    $('#name').on('input', function() {
        const name = $(this).val();
        const sku = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10) + '-' + Date.now().toString().slice(-4);
        $('#sku').val(sku);
    });
    
    // Form submission validation
    $('#productForm').on('submit', function(e) {
        // Validate categories
        if ($('input[name="categories[]"]:checked').length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una categoría');
            return false;
        }
        
        // Allow normal form submission
        return true;
    });
});
</script>
@endpush