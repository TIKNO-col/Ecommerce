@extends('layouts.app')

@section('title', $product->name . ' - Panel de Administración')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">{{ $product->name }}</h1>
                    <p class="text-muted mb-0">SKU: {{ $product->sku }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Product Images -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Imágenes del Producto</h5>
                        </div>
                        <div class="card-body">
                            @if($product->images->count() > 0)
                                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        @foreach($product->images as $index => $image)
                                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                                <img src="{{ asset('storage/' . $image->image_path) }}" 
                                                     class="d-block w-100" 
                                                     alt="{{ $product->name }}"
                                                     style="height: 400px; object-fit: contain;">
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($product->images->count() > 1)
                                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon"></span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon"></span>
                                        </button>
                                    @endif
                                </div>
                                
                                <!-- Thumbnails -->
                                @if($product->images->count() > 1)
                                    <div class="row mt-3">
                                        @foreach($product->images as $index => $image)
                                            <div class="col-3">
                                                <img src="{{ asset('storage/' . $image->image_path) }}" 
                                                     class="img-thumbnail cursor-pointer" 
                                                     onclick="$('#productCarousel').carousel({{ $index }})"
                                                     style="height: 80px; object-fit: cover;">
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay imágenes disponibles</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="col-lg-6">
                    <!-- Basic Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Nombre:</strong></div>
                                <div class="col-sm-8">{{ $product->name }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>SKU:</strong></div>
                                <div class="col-sm-8"><code>{{ $product->sku }}</code></div>
                            </div>
                            @if($product->brand)
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Marca:</strong></div>
                                    <div class="col-sm-8">{{ $product->brand->name }}</div>
                                </div>
                            @endif
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Categorías:</strong></div>
                                <div class="col-sm-8">
                                    @foreach($product->categories as $category)
                                        <span class="badge bg-secondary me-1">{{ $category->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Estado:</strong></div>
                                <div class="col-sm-8">
                                    @if($product->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                    @if($product->is_featured)
                                        <span class="badge bg-warning text-dark ms-1">Destacado</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Creado:</strong></div>
                                <div class="col-sm-8">{{ $product->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Actualizado:</strong></div>
                                <div class="col-sm-8">{{ $product->updated_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Inventory -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Precio e Inventario</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Precio Regular:</strong></div>
                                <div class="col-sm-8">
                                    <span class="h5 mb-0">${{ number_format($product->price, 2) }}</span>
                                </div>
                            </div>
                            @if($product->sale_price)
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Precio de Oferta:</strong></div>
                                    <div class="col-sm-8">
                                        <span class="h5 mb-0 text-success">${{ number_format($product->sale_price, 2) }}</span>
                                        <small class="text-muted ms-2">
                                            ({{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% descuento)
                                        </small>
                                    </div>
                                </div>
                            @endif
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Stock:</strong></div>
                                <div class="col-sm-8">
                                    @if($product->stock_quantity <= 5)
                                        <span class="badge bg-danger fs-6">{{ $product->stock_quantity }} unidades</span>
                                        <small class="text-danger ms-2">Stock bajo</small>
                                    @elseif($product->stock_quantity <= 20)
                                        <span class="badge bg-warning text-dark fs-6">{{ $product->stock_quantity }} unidades</span>
                                        <small class="text-warning ms-2">Stock medio</small>
                                    @else
                                        <span class="badge bg-success fs-6">{{ $product->stock_quantity }} unidades</span>
                                        <small class="text-success ms-2">Stock alto</small>
                                    @endif
                                </div>
                            </div>
                            @if($product->weight)
                                <div class="row mb-3">
                                    <div class="col-sm-4"><strong>Peso:</strong></div>
                                    <div class="col-sm-8">{{ $product->weight }} kg</div>
                                </div>
                            @endif
                            @if($product->dimensions)
                                <div class="row">
                                    <div class="col-sm-4"><strong>Dimensiones:</strong></div>
                                    <div class="col-sm-8">{{ $product->dimensions }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Descripción</h5>
                        </div>
                        <div class="card-body">
                            @if($product->short_description)
                                <div class="mb-3">
                                    <h6>Descripción Corta:</h6>
                                    <p class="text-muted">{{ $product->short_description }}</p>
                                </div>
                            @endif
                            <div>
                                <h6>Descripción Completa:</h6>
                                <div>{!! nl2br(e($product->description)) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Info -->
            @if($product->meta_title || $product->meta_description)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Información SEO</h5>
                            </div>
                            <div class="card-body">
                                @if($product->meta_title)
                                    <div class="mb-3">
                                        <strong>Meta Título:</strong>
                                        <p class="mb-0">{{ $product->meta_title }}</p>
                                    </div>
                                @endif
                                @if($product->meta_description)
                                    <div>
                                        <strong>Meta Descripción:</strong>
                                        <p class="mb-0">{{ $product->meta_description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group me-2">
                                @if($product->is_active)
                                    <button type="button" class="btn btn-warning" onclick="toggleStatus({{ $product->id }}, 'deactivate')">
                                        <i class="fas fa-pause me-2"></i>Desactivar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-success" onclick="toggleStatus({{ $product->id }}, 'activate')">
                                        <i class="fas fa-play me-2"></i>Activar
                                    </button>
                                @endif
                            </div>
                            
                            <div class="btn-group me-2">
                                @if($product->is_featured)
                                    <button type="button" class="btn btn-outline-warning" onclick="toggleFeatured({{ $product->id }}, false)">
                                        <i class="fas fa-star me-2"></i>Quitar de Destacados
                                    </button>
                                @else
                                    <button type="button" class="btn btn-warning" onclick="toggleFeatured({{ $product->id }}, true)">
                                        <i class="fas fa-star me-2"></i>Destacar
                                    </button>
                                @endif
                            </div>
                            
                            <div class="btn-group">
                                <button type="button" class="btn btn-danger" onclick="deleteProduct({{ $product->id }})">
                                    <i class="fas fa-trash me-2"></i>Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleStatus(productId, action) {
    const actionText = action === 'activate' ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de que quieres ${actionText} este producto?`)) {
        $.ajax({
            url: '{{ route("admin.products.bulk-update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                product_ids: [productId]
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al cambiar el estado del producto');
            }
        });
    }
}

function toggleFeatured(productId, featured) {
    const actionText = featured ? 'destacar' : 'quitar de destacados';
    
    if (confirm(`¿Estás seguro de que quieres ${actionText} este producto?`)) {
        $.ajax({
            url: '{{ route("admin.products.bulk-update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action: featured ? 'feature' : 'unfeature',
                product_ids: [productId]
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al cambiar el estado destacado del producto');
            }
        });
    }
}

function deleteProduct(productId) {
    if (confirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.')) {
        $.ajax({
            url: `/admin/products/${productId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '{{ route("admin.products.index") }}';
                } else {
                    alert('Error al eliminar el producto');
                }
            },
            error: function() {
                alert('Error al eliminar el producto');
            }
        });
    }
}
</script>
@endpush