@extends('layouts.admin')

@section('title', 'Editar Producto')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Editar Producto</h3>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" id="productForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Información Básica -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Información Básica</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="name" class="form-label">Nombre del Producto *</label>
                                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                           id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                                    @error('name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="sku" class="form-label">SKU</label>
                                                    <input type="text" class="form-control @error('sku') is-invalid @enderror" 
                                                           id="sku" name="sku" value="{{ old('sku', $product->sku) }}" 
                                                           placeholder="Se generará automáticamente si se deja vacío">
                                                    @error('sku')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="short_description" class="form-label">Descripción Corta</label>
                                            <textarea class="form-control @error('short_description') is-invalid @enderror" 
                                                      id="short_description" name="short_description" rows="2" 
                                                      maxlength="500">{{ old('short_description', $product->short_description) }}</textarea>
                                            @error('short_description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="description" class="form-label">Descripción Completa *</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                                      id="description" name="description" rows="6" required>{{ old('description', $product->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Precios e Inventario -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Precios e Inventario</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="price" class="form-label">Precio Regular *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" class="form-control @error('price') is-invalid @enderror" 
                                                               id="price" name="price" value="{{ old('price', $product->price) }}" 
                                                               step="0.01" min="0" required>
                                                        @error('price')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="sale_price" class="form-label">Precio de Oferta</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" class="form-control @error('sale_price') is-invalid @enderror" 
                                                               id="sale_price" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" 
                                                               step="0.01" min="0">
                                                        @error('sale_price')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="manage_stock" 
                                                               name="manage_stock" value="1" 
                                                               {{ old('manage_stock', $product->manage_stock) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="manage_stock">
                                                            Gestionar inventario
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3" id="stock_quantity_group">
                                                    <label for="stock_quantity" class="form-label">Cantidad en Stock *</label>
                                                    <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror" 
                                                           id="stock_quantity" name="stock_quantity" 
                                                           value="{{ old('stock_quantity', $product->stock_quantity) }}" 
                                                           min="0" required>
                                                    @error('stock_quantity')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dimensiones -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Dimensiones y Peso</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="weight" class="form-label">Peso (kg)</label>
                                                    <input type="number" class="form-control @error('weight') is-invalid @enderror" 
                                                           id="weight" name="weight" value="{{ old('weight', $product->weight) }}" 
                                                           step="0.01" min="0">
                                                    @error('weight')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="dimensions" class="form-label">Dimensiones</label>
                                                    <input type="text" class="form-control @error('dimensions') is-invalid @enderror" 
                                                           id="dimensions" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" 
                                                           placeholder="Ej: 20x15x10 cm">
                                                    @error('dimensions')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">SEO</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-3">
                                            <label for="meta_title" class="form-label">Meta Título</label>
                                            <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                                                   id="meta_title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" 
                                                   maxlength="255">
                                            @error('meta_title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="meta_description" class="form-label">Meta Descripción</label>
                                            <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                                      id="meta_description" name="meta_description" rows="3" 
                                                      maxlength="500">{{ old('meta_description', $product->meta_description) }}</textarea>
                                            @error('meta_description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-md-4">
                                <!-- Categorías y Marca -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Categorías y Marca</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-3">
                                            <label for="brand_id" class="form-label">Marca *</label>
                                            <select class="form-select @error('brand_id') is-invalid @enderror" 
                                                    id="brand_id" name="brand_id" required>
                                                <option value="">Seleccionar marca</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}" 
                                                            {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                                        {{ $brand->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('brand_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label">Categorías *</label>
                                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($categories as $category)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="category_{{ $category->id }}" name="categories[]" 
                                                               value="{{ $category->id }}"
                                                               {{ in_array($category->id, old('categories', $product->categories->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="category_{{ $category->id }}">
                                                            {{ $category->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @error('categories')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Imágenes -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Imágenes</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Imágenes actuales -->
                                        @if($product->images && $product->images->count() > 0)
                                            <div class="mb-3">
                                                <label class="form-label">Imágenes Actuales</label>
                                                <div class="row">
                                                    @foreach($product->images as $image)
                                                        <div class="col-6 mb-2">
                                                            <img src="{{ $image->getImageUrl() }}" 
                                                                 class="img-thumbnail" style="width: 100%; height: 80px; object-fit: cover;">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="form-group mb-3">
                                            <label for="images" class="form-label">Nuevas Imágenes</label>
                                            <input type="file" class="form-control @error('images') is-invalid @enderror" 
                                                   id="images" name="images[]" multiple accept="image/*">
                                            <small class="form-text text-muted">
                                                Selecciona nuevas imágenes para reemplazar las actuales. Formatos: JPG, PNG, GIF, WebP. Máximo 2MB por imagen.
                                            </small>
                                            @error('images')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Preview de nuevas imágenes -->
                                        <div id="imagePreview" class="row"></div>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Estado</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="is_active" 
                                                   name="is_active" value="1" 
                                                   {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Producto Activo
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_featured" 
                                                   name="is_featured" value="1" 
                                                   {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_featured">
                                                Producto Destacado
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary w-100 mb-2">
                                            <i class="fas fa-save"></i> Actualizar Producto
                                        </button>
                                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-info w-100 mb-2">
                                            <i class="fas fa-eye"></i> Ver Producto
                                        </a>
                                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary w-100">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
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
    // Preview de imágenes
    $('#images').on('change', function() {
        const files = this.files;
        const preview = $('#imagePreview');
        preview.empty();
        
        if (files.length > 0) {
            Array.from(files).forEach(function(file, index) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.append(`
                            <div class="col-6 mb-2">
                                <img src="${e.target.result}" class="img-thumbnail" 
                                     style="width: 100%; height: 80px; object-fit: cover;">
                            </div>
                        `);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // Gestión de inventario
    $('#manage_stock').on('change', function() {
        const stockGroup = $('#stock_quantity_group');
        if (this.checked) {
            stockGroup.show();
            $('#stock_quantity').prop('required', true);
        } else {
            stockGroup.hide();
            $('#stock_quantity').prop('required', false);
        }
    }).trigger('change');

    // Validación del formulario
    $('#productForm').on('submit', function(e) {
        const categories = $('input[name="categories[]"]:checked').length;
        if (categories === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos una categoría.');
            return false;
        }
        
        // Mostrar loading
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
    });
});
</script>
@endpush