@extends('layouts.app')

@section('title', 'Productos')
@section('description', 'Explora nuestro catálogo completo de productos. Encuentra lo que buscas con nuestros filtros avanzados.')
@section('keywords', 'productos, catálogo, tienda, comprar, filtros')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Productos</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Productos</h1>
            <p class="text-muted">Descubre nuestro catálogo completo de productos</p>
        </div>
    </div>
    
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" method="GET">
                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Buscar</label>
                            <input type="text" name="search" class="form-control" placeholder="Buscar productos..." value="{{ request('search') }}">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Categoría</label>
                            <select name="category" class="form-select">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rango de Precio</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" placeholder="Mín" value="{{ request('min_price') }}" min="0" step="0.01">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" placeholder="Máx" value="{{ request('max_price') }}" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rating Filter -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Calificación Mínima</label>
                            <select name="rating" class="form-select">
                                <option value="">Cualquier calificación</option>
                                <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4+ estrellas</option>
                                <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3+ estrellas</option>
                                <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2+ estrellas</option>
                                <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1+ estrellas</option>
                            </select>
                        </div>
                        
                        <!-- Availability -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="inStock" {{ request('in_stock') ? 'checked' : '' }}>
                                <label class="form-check-label" for="inStock">
                                    Solo productos en stock
                                </label>
                            </div>
                        </div>
                        
                        <!-- On Sale -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="on_sale" value="1" id="onSale" {{ request('on_sale') ? 'checked' : '' }}>
                                <label class="form-check-label" for="onSale">
                                    Solo productos en oferta
                                </label>
                            </div>
                        </div>
                        
                        <!-- Filter Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Aplicar Filtros
                            </button>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar Filtros
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Sort and View Options -->
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        Mostrando {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} de {{ $products->total() }} productos
                    </p>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex justify-content-end align-items-center gap-2">
                        @foreach(request()->except(['sort', 'per_page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        
                        <label class="form-label mb-0 me-2">Ordenar por:</label>
                        <select name="sort" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nombre A-Z</option>
                            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nombre Z-A</option>
                            <option value="price" {{ request('sort') == 'price' ? 'selected' : '' }}>Precio: Menor a Mayor</option>
                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Precio: Mayor a Menor</option>
                            <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Mejor Calificados</option>
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Más Nuevos</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Más Populares</option>
                        </select>
                        
                        <select name="per_page" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <option value="12" {{ request('per_page') == '12' ? 'selected' : '' }}>12 por página</option>
                            <option value="24" {{ request('per_page') == '24' ? 'selected' : '' }}>24 por página</option>
                            <option value="48" {{ request('per_page') == '48' ? 'selected' : '' }}>48 por página</option>
                        </select>
                    </form>
                </div>
            </div>
            
            @if($products->count() > 0)
                <!-- Products Grid -->
                <div class="row g-4">
                    @foreach($products as $product)
                    <div class="col-md-6 col-xl-4">
                        <div class="card product-card h-100 border-0 shadow-sm">
                            <div class="position-relative">
                                <img src="{{ $product->getMainImageUrl() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                                
                                <!-- Badges -->
                                @if($product->isOnSale())
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                        -{{ $product->getDiscountPercentage() }}%
                                    </span>
                                @endif
                                
                                @if($product->created_at->diffInDays() <= 7)
                                    <span class="badge bg-success position-absolute top-0 start-0 m-2" style="{{ $product->isOnSale() ? 'margin-top: 2.5rem !important;' : '' }}">
                                        Nuevo
                                    </span>
                                @endif
                                
                                @if($product->stock <= 5 && $product->stock > 0)
                                    <span class="badge bg-warning position-absolute top-0 start-0 m-2" style="margin-top: {{ $product->isOnSale() && $product->created_at->diffInDays() <= 7 ? '5rem' : ($product->isOnSale() || $product->created_at->diffInDays() <= 7 ? '2.5rem' : '0') }} !important;">
                                        ¡Últimas {{ $product->stock }}!
                                    </span>
                                @endif
                                
                                @if($product->stock <= 0)
                                    <span class="badge bg-secondary position-absolute top-0 start-0 m-2">
                                        Agotado
                                    </span>
                                @endif
                                
                                <!-- Wishlist Button -->
                                @auth
                                <button class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2" onclick="addToWishlist({{ $product->id }})">
                                    <i class="fas fa-heart"></i>
                                </button>
                                @endauth
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title">{{ Str::limit($product->name, 60) }}</h6>
                                
                                <!-- Rating -->
                                @if($product->average_rating > 0)
                                    <div class="rating mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $product->average_rating)
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                        <small class="text-muted ms-1">({{ $product->reviews_count }})</small>
                                    </div>
                                @endif
                                
                                <!-- Price -->
                                <div class="price mb-3">
                                    @if($product->isOnSale())
                                        <span class="price-old me-2">${{ number_format($product->price, 2) }}</span>
                                        <span class="h5">${{ number_format($product->getCurrentPrice(), 2) }}</span>
                                        <div class="small text-success">
                                            <i class="fas fa-piggy-bank me-1"></i>Ahorras ${{ number_format($product->price - $product->getCurrentPrice(), 2) }}
                                        </div>
                                    @else
                                        <span class="h5">${{ number_format($product->price, 2) }}</span>
                                    @endif
                                </div>
                                
                                <!-- Stock Status -->
                                <div class="mb-3">
                                    @if($product->stock > 0)
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>En stock ({{ $product->stock }} disponibles)
                                        </small>
                                    @else
                                        <small class="text-danger">
                                            <i class="fas fa-times-circle me-1"></i>Agotado
                                        </small>
                                    @endif
                                </div>
                                
                                <!-- Actions -->
                                <div class="mt-auto">
                                    <div class="d-grid gap-2">
                                        @if($product->stock > 0)
                                            <button class="btn btn-primary" onclick="addToCart({{ $product->id }})">
                                                <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                                            </button>
                                        @else
                                            <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-times me-2"></i>No Disponible
                                            </button>
                                        @endif
                                        <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-primary btn-sm">
                                            Ver Detalles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            @else
                <!-- No Products Found -->
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No se encontraron productos</h4>
                    <p class="text-muted">Intenta ajustar tus filtros de búsqueda o explora nuestras categorías.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Ver Todos los Productos
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-submit form when filters change
        $('#filterForm input[type="checkbox"]').on('change', function() {
            $('#filterForm').submit();
        });
        
        // Price range validation
        $('input[name="min_price"], input[name="max_price"]').on('blur', function() {
            const minPrice = parseFloat($('input[name="min_price"]').val()) || 0;
            const maxPrice = parseFloat($('input[name="max_price"]').val()) || 0;
            
            if (maxPrice > 0 && minPrice > maxPrice) {
                showAlert('warning', 'El precio mínimo no puede ser mayor al precio máximo');
                $(this).focus();
            }
        });
        
        // Clear individual filters
        $('.clear-filter').on('click', function(e) {
            e.preventDefault();
            const filterName = $(this).data('filter');
            $(`[name="${filterName}"]`).val('').prop('checked', false);
            $('#filterForm').submit();
        });
    });
</script>
@endpush