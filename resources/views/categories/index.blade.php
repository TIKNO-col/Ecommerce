@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold">Nuestras Categorías</h1>
                <p class="lead text-muted">Explora nuestra amplia gama de productos organizados por categorías</p>
            </div>

            @if($categories->count() > 0)
                <div class="row g-4">
                    @foreach($categories as $category)
                        <div class="col-lg-4 col-md-6">
                            <div class="card category-card h-100 shadow-sm border-0">
                                <div class="card-body text-center p-4">
                                    @if($category->image)
                                        <div class="category-image mb-3">
                                            <img src="{{ $category->image }}" alt="{{ $category->name }}" 
                                                 class="img-fluid rounded" style="max-height: 150px; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="category-icon mb-3">
                                            <i class="fas fa-folder-open fa-4x text-primary"></i>
                                        </div>
                                    @endif
                                    
                                    <h4 class="card-title mb-3">{{ $category->name }}</h4>
                                    
                                    @if($category->description)
                                        <p class="card-text text-muted mb-3">{{ Str::limit($category->description, 100) }}</p>
                                    @endif
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-light text-dark">
                                            {{ $category->products_count ?? 0 }} productos
                                        </span>
                                    </div>
                                    
                                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" 
                                       class="btn btn-primary btn-lg w-100">
                                        Ver Productos
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Subcategories Section -->
                @if($categories->where('children_count', '>', 0)->count() > 0)
                    <div class="mt-5">
                        <h3 class="text-center mb-4">Subcategorías</h3>
                        <div class="row g-3">
                            @foreach($categories as $category)
                                @if($category->children && $category->children->count() > 0)
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">{{ $category->name }}</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-2">
                                                    @foreach($category->children as $subcategory)
                                                        <div class="col-md-3 col-sm-6">
                                                            <a href="{{ route('products.index', ['category' => $subcategory->slug]) }}" 
                                                               class="btn btn-outline-secondary btn-sm w-100">
                                                                {{ $subcategory->name }}
                                                                <span class="badge bg-secondary ms-1">
                                                                    {{ $subcategory->products_count ?? 0 }}
                                                                </span>
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-5x text-muted mb-4"></i>
                    <h3 class="text-muted">No hay categorías disponibles</h3>
                    <p class="text-muted">Vuelve pronto para ver nuestras categorías de productos.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.category-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

.category-icon {
    transition: color 0.3s ease;
}

.category-card:hover .category-icon i {
    color: var(--bs-secondary) !important;
}
</style>
@endsection