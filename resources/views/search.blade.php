@extends('layouts.app')

@section('title', 'Resultados de búsqueda')
@section('description', 'Resultados de búsqueda para productos en nuestra tienda online.')
@section('keywords', 'búsqueda, productos, resultados, tienda')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Búsqueda</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            @if($query)
                <h1 class="fw-bold">Resultados para "{{ $query }}"</h1>
                <p class="text-muted">{{ $results->total() }} producto(s) encontrado(s)</p>
            @else
                <h1 class="fw-bold">Búsqueda de Productos</h1>
                <p class="text-muted">Utiliza los filtros para encontrar productos</p>
            @endif
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
                    <form id="searchForm" method="GET" action="{{ route('search') }}">
                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Buscar</label>
                            <input type="text" name="q" class="form-control" placeholder="Buscar productos..." value="{{ $query }}">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Categoría</label>
                            <select name="category" class="form-select">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $category == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        @if($priceRange && $priceRange->min_price && $priceRange->max_price)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rango de Precio</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" placeholder="Mín" value="{{ $minPrice }}" min="{{ $priceRange->min_price }}" max="{{ $priceRange->max_price }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" placeholder="Máx" value="{{ $maxPrice }}" min="{{ $priceRange->min_price }}" max="{{ $priceRange->max_price }}">
                                </div>
                            </div>
                            <small class="text-muted">Rango: ${{ number_format($priceRange->min_price) }} - ${{ number_format($priceRange->max_price) }}</small>
                        </div>
                        @endif
                        
                        <!-- Sort -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ordenar por</label>
                            <select name="sort" class="form-select">
                                <option value="relevance" {{ $sort == 'relevance' ? 'selected' : '' }}>Relevancia</option>
                                <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>Más recientes</option>
                                <option value="price_low" {{ $sort == 'price_low' ? 'selected' : '' }}>Precio: menor a mayor</option>
                                <option value="price_high" {{ $sort == 'price_high' ? 'selected' : '' }}>Precio: mayor a menor</option>
                                <option value="rating" {{ $sort == 'rating' ? 'selected' : '' }}>Mejor valorados</option>
                                <option value="popular" {{ $sort == 'popular' ? 'selected' : '' }}>Más populares</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        
                        <a href="{{ route('search') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Limpiar filtros
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="col-lg-9">
            @if($results->count() > 0)
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Mostrando {{ $results->firstItem() }}-{{ $results->lastItem() }} de {{ $results->total() }} resultados</span>
                    <div class="d-flex align-items-center">
                        <label class="form-label me-2 mb-0">Por página:</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                            <option value="12" {{ request('per_page', 12) == 12 ? 'selected' : '' }}>12</option>
                            <option value="24" {{ request('per_page') == 24 ? 'selected' : '' }}>24</option>
                            <option value="48" {{ request('per_page') == 48 ? 'selected' : '' }}>48</option>
                        </select>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="row">
                    @foreach($results as $product)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 product-card">
                                <div class="position-relative">
                                    @if($product->images && $product->images->count() > 0)
                                        <img src="{{ asset('storage/' . $product->images->first()->image_path) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 250px; object-fit: cover;">
                                    @else
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 250px;">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    @endif
                                    
                                    @if($product->isOnSale())
                                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">Oferta</span>
                                    @endif
                                    
                                    @if($product->stock <= 5 && $product->stock > 0)
                                        <span class="badge bg-warning position-absolute top-0 end-0 m-2">Últimas unidades</span>
                                    @elseif($product->stock == 0)
                                        <span class="badge bg-secondary position-absolute top-0 end-0 m-2">Agotado</span>
                                    @endif
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">{{ $product->name }}</h5>
                                    <p class="card-text text-muted small flex-grow-1">{{ Str::limit($product->description, 100) }}</p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                @if($product->isOnSale())
                                                    <span class="h5 text-danger mb-0">${{ number_format($product->getCurrentPrice()) }}</span>
                                                    <small class="text-muted text-decoration-line-through ms-1">${{ number_format($product->price) }}</small>
                                                @else
                                                    <span class="h5 text-primary mb-0">${{ number_format($product->getCurrentPrice()) }}</span>
                                                @endif
                                            </div>
                                            
                                            @if($product->average_rating > 0)
                                                <div class="text-warning">
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
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>Ver detalles
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
                    {{ $results->links() }}
                </div>
            @else
                <!-- No Results -->
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h3>No se encontraron productos</h3>
                    @if($query)
                        <p class="text-muted">No encontramos productos que coincidan con "{{ $query }}"</p>
                        <p class="text-muted">Intenta con otros términos de búsqueda o ajusta los filtros.</p>
                    @else
                        <p class="text-muted">Utiliza los filtros para buscar productos.</p>
                    @endif
                    <a href="{{ route('products.index') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Ver todos los productos
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('searchForm');
    const selects = form.querySelectorAll('select');
    const inputs = form.querySelectorAll('input[type="number"]');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            form.submit();
        });
    });
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            form.submit();
        });
    });
});
</script>
@endsection