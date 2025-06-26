@extends('layouts.app')

@section('title', $product->name)
@section('description', $product->meta_description ?: Str::limit(strip_tags($product->description), 160))
@section('keywords', $product->meta_keywords ?: $product->name . ', producto, comprar')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Productos</a></li>
            @if($product->categories->first())
                <li class="breadcrumb-item"><a href="{{ route('categories.show', $product->categories->first()->slug) }}">{{ $product->categories->first()->name }}</a></li>
            @endif
            <li class="breadcrumb-item active">{{ $product->name }}</li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6 mb-4">
            <div class="product-images">
                <!-- Main Image -->
                <div class="main-image mb-3">
                    <img id="mainImage" src="{{ $product->getMainImageUrl() }}" class="img-fluid rounded shadow" alt="{{ $product->name }}" style="width: 100%; height: 400px; object-fit: cover;">
                </div>
                
                <!-- Thumbnail Images -->
                @if($product->getAllImageUrls()->count() > 1)
                <div class="thumbnail-images">
                    <div class="row g-2">
                        @foreach($product->getAllImageUrls() as $index => $imageUrl)
                        <div class="col-3">
                            <img src="{{ $imageUrl }}" class="img-fluid rounded thumbnail-img {{ $index === 0 ? 'active' : '' }}" alt="{{ $product->name }}" style="height: 80px; object-fit: cover; cursor: pointer;" onclick="changeMainImage('{{ $imageUrl }}', this)">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="product-info">
                <!-- Product Title -->
                <h1 class="fw-bold mb-3">{{ $product->name }}</h1>
                
                <!-- SKU -->
                @if($product->sku)
                <p class="text-muted mb-2"><strong>SKU:</strong> {{ $product->sku }}</p>
                @endif
                
                <!-- Rating -->
                @if($product->average_rating > 0)
                <div class="rating mb-3">
                    <div class="d-flex align-items-center">
                        <div class="stars me-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $product->average_rating)
                                    <i class="fas fa-star text-warning"></i>
                                @else
                                    <i class="far fa-star text-warning"></i>
                                @endif
                            @endfor
                        </div>
                        <span class="me-2">{{ number_format($product->average_rating, 1) }}</span>
                        <a href="#reviews" class="text-decoration-none">({{ $product->reviews_count }} {{ $product->reviews_count === 1 ? 'reseña' : 'reseñas' }})</a>
                    </div>
                </div>
                @endif
                
                <!-- Price -->
                <div class="price mb-4">
                    @if($product->isOnSale())
                        <div class="d-flex align-items-center gap-3">
                            <span class="h3 text-danger mb-0">${{ number_format($product->getCurrentPrice(), 2) }}</span>
                            <span class="h5 text-muted text-decoration-line-through mb-0">${{ number_format($product->price, 2) }}</span>
                            <span class="badge bg-danger">-{{ $product->getDiscountPercentage() }}%</span>
                        </div>
                        <div class="text-success mt-1">
                            <i class="fas fa-piggy-bank me-1"></i>Ahorras ${{ number_format($product->price - $product->getCurrentPrice(), 2) }}
                        </div>
                    @else
                        <span class="h3 text-primary">${{ number_format($product->price, 2) }}</span>
                    @endif
                </div>
                
                <!-- Stock Status -->
                <div class="stock-status mb-4">
                    @if($product->stock > 0)
                        @if($product->stock <= 5)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>¡Últimas {{ $product->stock }} unidades disponibles!</strong>
                            </div>
                        @else
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>En stock</strong> - {{ $product->stock }} unidades disponibles
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>Producto agotado</strong>
                        </div>
                    @endif
                </div>
                
                <!-- Add to Cart Form -->
                @if($product->stock > 0)
                <form id="addToCartForm" class="mb-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Cantidad:</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="quantity" id="quantity" class="form-control text-center" value="1" min="1" max="{{ $product->stock }}">
                                <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @endif
                
                <!-- Action Buttons -->
                <div class="action-buttons mb-4">
                    <div class="row g-2">
                        @auth
                        <div class="col-6">
                            <button class="btn btn-outline-danger w-100" onclick="addToWishlist({{ $product->id }})">
                                <i class="fas fa-heart me-2"></i>Lista de Deseos
                            </button>
                        </div>
                        @endauth
                        <div class="col-{{ auth()->check() ? '6' : '12' }}">
                            <button class="btn btn-outline-secondary w-100" onclick="shareProduct()">
                                <i class="fas fa-share-alt me-2"></i>Compartir
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Product Features -->
                <div class="product-features">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <i class="fas fa-shipping-fast text-primary fa-2x mb-2"></i>
                            <div class="small">Envío Rápido</div>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-shield-alt text-success fa-2x mb-2"></i>
                            <div class="small">Compra Segura</div>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-undo text-info fa-2x mb-2"></i>
                            <div class="small">Devoluciones</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Details Tabs -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Descripción
                    </button>
                </li>
                @if($product->attributes && is_array($product->attributes) && count($product->attributes) > 0)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>Especificaciones
                    </button>
                </li>
                @endif
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                        <i class="fas fa-star me-2"></i>Reseñas ({{ $product->reviews_count }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab">
                        <i class="fas fa-truck me-2"></i>Envío
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="productTabsContent">
                <!-- Description Tab -->
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <div class="p-4">
                        @if($product->description)
                            {!! nl2br(e($product->description)) !!}
                        @else
                            <p class="text-muted">No hay descripción disponible para este producto.</p>
                        @endif
                    </div>
                </div>
                
                <!-- Specifications Tab -->
                @if($product->attributes && is_array($product->attributes) && count($product->attributes) > 0)
                <div class="tab-pane fade" id="specifications" role="tabpanel">
                    <div class="p-4">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <tbody>
                                    @foreach($product->attributes as $key => $value)
                                    <tr>
                                        <td class="fw-bold" style="width: 30%;">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                        <td>{{ $value }}</td>
                                    </tr>
                                    @endforeach
                                    @if($product->weight)
                                    <tr>
                                        <td class="fw-bold">Peso</td>
                                        <td>{{ $product->weight }} kg</td>
                                    </tr>
                                    @endif
                                    @if($product->dimensions)
                                    <tr>
                                        <td class="fw-bold">Dimensiones</td>
                                        <td>{{ $product->dimensions }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Reviews Tab -->
                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <div class="p-4">
                        @if($product->reviews->count() > 0)
                            <!-- Reviews Summary -->
                            <div class="row mb-4">
                                <div class="col-md-4 text-center">
                                    <div class="display-4 fw-bold text-warning">{{ number_format($product->average_rating, 1) }}</div>
                                    <div class="rating mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $product->average_rating)
                                                <i class="fas fa-star text-warning"></i>
                                            @else
                                                <i class="far fa-star text-warning"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <div class="text-muted">{{ $product->reviews_count }} {{ $product->reviews_count === 1 ? 'reseña' : 'reseñas' }}</div>
                                </div>
                                <div class="col-md-8">
                                    <!-- Rating Breakdown -->
                                    @for($i = 5; $i >= 1; $i--)
                                        @php
                                            $count = $product->reviews->where('rating', $i)->count();
                                            $percentage = $product->reviews_count > 0 ? ($count / $product->reviews_count) * 100 : 0;
                                        @endphp
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="me-2">{{ $i }}</span>
                                            <i class="fas fa-star text-warning me-2"></i>
                                            <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                <div class="progress-bar bg-warning" style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span class="text-muted small">{{ $count }}</span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            
                            <!-- Individual Reviews -->
                            <div class="reviews-list">
                                @foreach($product->reviews->take(5) as $review)
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong>{{ $review->user->name }}</strong>
                                            <div class="rating">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <i class="fas fa-star text-warning"></i>
                                                    @else
                                                        <i class="far fa-star text-warning"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                    @if($review->title)
                                        <h6>{{ $review->title }}</h6>
                                    @endif
                                    <p class="mb-0">{{ $review->comment }}</p>
                                </div>
                                @endforeach
                                
                                @if($product->reviews->count() > 5)
                                <div class="text-center">
                                    <a href="{{ route('reviews.product', $product->slug) }}" class="btn btn-outline-primary">
                                        Ver todas las reseñas
                                    </a>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <h5>No hay reseñas aún</h5>
                                <p class="text-muted">Sé el primero en reseñar este producto</p>
                            </div>
                        @endif
                        
                        <!-- Add Review Button -->
                        @auth
                            @if($product->canUserReview(auth()->user()))
                            <div class="text-center mt-4">
                                <a href="{{ route('reviews.create', $product->slug) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Escribir Reseña
                                </a>
                            </div>
                            @endif
                        @else
                            <div class="text-center mt-4">
                                <p class="text-muted">Debes <a href="{{ route('auth.login') }}">iniciar sesión</a> para escribir una reseña</p>
                            </div>
                        @endauth
                    </div>
                </div>
                
                <!-- Shipping Tab -->
                <div class="tab-pane fade" id="shipping" role="tabpanel">
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-truck me-2"></i>Opciones de Envío</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Envío estándar (3-5 días hábiles): $5.99</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Envío express (1-2 días hábiles): $12.99</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Envío gratis en pedidos superiores a $50</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-undo me-2"></i>Política de Devoluciones</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>30 días para devoluciones</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Producto en condiciones originales</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Reembolso completo garantizado</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    @if($relatedProducts && $relatedProducts->count() > 0)
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="fw-bold mb-4">Productos Relacionados</h3>
            <div class="row g-4">
                @foreach($relatedProducts as $relatedProduct)
                <div class="col-md-6 col-lg-3">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="position-relative">
                            <img src="{{ $relatedProduct->getMainImageUrl() }}" class="card-img-top product-image" alt="{{ $relatedProduct->name }}">
                            @if($relatedProduct->isOnSale())
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                    -{{ $relatedProduct->getDiscountPercentage() }}%
                                </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">{{ Str::limit($relatedProduct->name, 50) }}</h6>
                            <div class="price mb-3">
                                @if($relatedProduct->isOnSale())
                                    <span class="price-old me-2">${{ number_format($relatedProduct->price, 2) }}</span>
                                    <span class="h6">${{ number_format($relatedProduct->getCurrentPrice(), 2) }}</span>
                                @else
                                    <span class="h6">${{ number_format($relatedProduct->price, 2) }}</span>
                                @endif
                            </div>
                            <div class="mt-auto">
                                <a href="{{ route('products.show', $relatedProduct->slug) }}" class="btn btn-outline-primary btn-sm w-100">
                                    Ver Producto
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Add to cart form submission
        $('#addToCartForm').on('submit', function(e) {
            e.preventDefault();
            
            const productId = $('input[name="product_id"]').val();
            const quantity = parseInt($('#quantity').val());
            
            addToCart(productId, quantity);
        });
    });
    
    function changeMainImage(imageUrl, thumbnail) {
        $('#mainImage').attr('src', imageUrl);
        $('.thumbnail-img').removeClass('active');
        $(thumbnail).addClass('active');
    }
    
    function changeQuantity(change) {
        const quantityInput = $('#quantity');
        const currentQuantity = parseInt(quantityInput.val());
        const maxQuantity = parseInt(quantityInput.attr('max'));
        const minQuantity = parseInt(quantityInput.attr('min'));
        
        let newQuantity = currentQuantity + change;
        
        if (newQuantity < minQuantity) {
            newQuantity = minQuantity;
        } else if (newQuantity > maxQuantity) {
            newQuantity = maxQuantity;
            showAlert('warning', `Solo hay ${maxQuantity} unidades disponibles`);
        }
        
        quantityInput.val(newQuantity);
    }
    
    function shareProduct() {
        if (navigator.share) {
            navigator.share({
                title: '{{ $product->name }}',
                text: '{{ Str::limit($product->description, 100) }}',
                url: window.location.href
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(function() {
                showAlert('success', 'Enlace copiado al portapapeles');
            });
        }
    }
</script>

<style>
    .thumbnail-img {
        border: 2px solid transparent;
        transition: border-color 0.2s;
    }
    
    .thumbnail-img.active,
    .thumbnail-img:hover {
        border-color: var(--primary-color);
    }
    
    .product-images .main-image {
        position: relative;
        overflow: hidden;
    }
    
    .product-images .main-image img {
        transition: transform 0.3s ease;
    }
    
    .product-images .main-image:hover img {
        transform: scale(1.05);
    }
    
    .rating .fas.fa-star,
    .rating .far.fa-star {
        font-size: 1rem;
    }
    
    .progress {
        background-color: #e9ecef;
    }
    
    .review-item:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
</style>
@endpush