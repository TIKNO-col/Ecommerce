@extends('layouts.app')

@section('title', 'Inicio')
@section('description', 'Descubre los mejores productos en nuestra tienda online. Ofertas especiales, productos destacados y las últimas novedades.')
@section('keywords', 'ecommerce, productos, ofertas, destacados, novedades, tienda online')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Bienvenido a {{ config('app.name') }}</h1>
                <p class="lead mb-4">Descubre los mejores productos con ofertas increíbles. Calidad garantizada y envío rápido a todo el país.</p>
                <div class="d-flex gap-3">
                    <a href="{{ route('products.index') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Ver Productos
                    </a>
                    <a href="{{ route('products.sale') }}" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-tags me-2"></i>Ofertas
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://via.placeholder.com/500x400/007bff/ffffff?text=E-Commerce" alt="Hero Image" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Featured Categories -->
@if($featuredCategories->count() > 0)
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold">Categorías Destacadas</h2>
                <p class="text-muted">Explora nuestras categorías más populares</p>
            </div>
        </div>
        <div class="row g-4">
            @foreach($featuredCategories as $category)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('categories.show', $category->slug) }}" class="category-card text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            @if($category->icon)
                                <i class="{{ $category->icon }} fa-3x text-primary mb-3"></i>
                            @else
                                <i class="fas fa-folder fa-3x text-primary mb-3"></i>
                            @endif
                            <h5 class="card-title">{{ $category->name }}</h5>
                            <p class="card-text text-muted small">{{ $category->products_count }} productos</p>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Featured Products -->
@if($featuredProducts->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold">Productos Destacados</h2>
                <p class="text-muted">Los productos más populares de nuestra tienda</p>
            </div>
        </div>
        <div class="row g-4">
            @foreach($featuredProducts as $product)
            <div class="col-md-6 col-lg-3">
                <div class="card product-card h-100 border-0 shadow-sm">
                    <div class="position-relative">
                        <img src="{{ $product->getMainImageUrl() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                        @if($product->isOnSale())
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                -{{ $product->getDiscountPercentage() }}%
                            </span>
                        @endif
                        @auth
                        <button class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2" onclick="addToWishlist({{ $product->id }})">
                            <i class="fas fa-heart"></i>
                        </button>
                        @endauth
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ Str::limit($product->name, 50) }}</h6>
                        <div class="mb-2">
                            @if($product->average_rating > 0)
                                <div class="rating mb-1">
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
                        <div class="price mb-3">
                            @if($product->isOnSale())
                                <span class="price-old me-2">${{ number_format($product->price, 2) }}</span>
                                <span class="h5">${{ number_format($product->getCurrentPrice(), 2) }}</span>
                            @else
                                <span class="h5">${{ number_format($product->price, 2) }}</span>
                            @endif
                        </div>
                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="addToCart({{ $product->id }})">
                                    <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                                </button>
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
        <div class="text-center mt-4">
            <a href="{{ route('products.featured') }}" class="btn btn-primary btn-lg">
                Ver Todos los Destacados <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
@endif

<!-- New Products -->
@if($newProducts->count() > 0)
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold">Nuevos Productos</h2>
                <p class="text-muted">Las últimas incorporaciones a nuestro catálogo</p>
            </div>
        </div>
        <div class="row g-4">
            @foreach($newProducts as $product)
            <div class="col-md-6 col-lg-3">
                <div class="card product-card h-100 border-0 shadow-sm">
                    <div class="position-relative">
                        <img src="{{ $product->getMainImageUrl() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                        <span class="badge bg-success position-absolute top-0 start-0 m-2">
                            Nuevo
                        </span>
                        @if($product->isOnSale())
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2" style="margin-top: 2.5rem !important;">
                                -{{ $product->getDiscountPercentage() }}%
                            </span>
                        @endif
                        @auth
                        <button class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2" onclick="addToWishlist({{ $product->id }})">
                            <i class="fas fa-heart"></i>
                        </button>
                        @endauth
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ Str::limit($product->name, 50) }}</h6>
                        <div class="mb-2">
                            @if($product->average_rating > 0)
                                <div class="rating mb-1">
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
                        <div class="price mb-3">
                            @if($product->isOnSale())
                                <span class="price-old me-2">${{ number_format($product->price, 2) }}</span>
                                <span class="h5">${{ number_format($product->getCurrentPrice(), 2) }}</span>
                            @else
                                <span class="h5">${{ number_format($product->price, 2) }}</span>
                            @endif
                        </div>
                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="addToCart({{ $product->id }})">
                                    <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                                </button>
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
        <div class="text-center mt-4">
            <a href="{{ route('products.new') }}" class="btn btn-success btn-lg">
                Ver Todos los Nuevos <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
@endif

<!-- Sale Products -->
@if($saleProducts->count() > 0)
<section class="py-5 bg-warning bg-opacity-10">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-bold text-warning">
                    <i class="fas fa-fire me-2"></i>¡Ofertas Especiales!
                </h2>
                <p class="text-muted">Aprovecha estos descuentos por tiempo limitado</p>
            </div>
        </div>
        <div class="row g-4">
            @foreach($saleProducts as $product)
            <div class="col-md-6 col-lg-3">
                <div class="card product-card h-100 border-warning shadow-sm">
                    <div class="position-relative">
                        <img src="{{ $product->getMainImageUrl() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                            -{{ $product->getDiscountPercentage() }}%
                        </span>
                        @auth
                        <button class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2" onclick="addToWishlist({{ $product->id }})">
                            <i class="fas fa-heart"></i>
                        </button>
                        @endauth
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ Str::limit($product->name, 50) }}</h6>
                        <div class="mb-2">
                            @if($product->average_rating > 0)
                                <div class="rating mb-1">
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
                        <div class="price mb-3">
                            <span class="price-old me-2">${{ number_format($product->price, 2) }}</span>
                            <span class="h5 text-danger">${{ number_format($product->getCurrentPrice(), 2) }}</span>
                            <div class="small text-success">
                                <i class="fas fa-piggy-bank me-1"></i>Ahorras ${{ number_format($product->price - $product->getCurrentPrice(), 2) }}
                            </div>
                        </div>
                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning" onclick="addToCart({{ $product->id }})">
                                    <i class="fas fa-cart-plus me-2"></i>¡Aprovechar Oferta!
                                </button>
                                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-warning btn-sm">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('products.sale') }}" class="btn btn-warning btn-lg">
                Ver Todas las Ofertas <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
@endif

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <i class="fas fa-shipping-fast fa-3x text-primary"></i>
                </div>
                <h5>Envío Rápido</h5>
                <p class="text-muted">Entrega en 24-48 horas en toda la ciudad</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <i class="fas fa-shield-alt fa-3x text-success"></i>
                </div>
                <h5>Compra Segura</h5>
                <p class="text-muted">Tus datos y pagos están protegidos</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <i class="fas fa-undo fa-3x text-info"></i>
                </div>
                <h5>Devoluciones</h5>
                <p class="text-muted">30 días para devolver tu compra</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <i class="fas fa-headset fa-3x text-warning"></i>
                </div>
                <h5>Soporte 24/7</h5>
                <p class="text-muted">Estamos aquí para ayudarte siempre</p>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-2">¡Suscríbete a nuestro boletín!</h3>
                <p class="mb-0">Recibe ofertas exclusivas y novedades directamente en tu email</p>
            </div>
            <div class="col-lg-6">
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <input type="email" name="email" class="form-control" placeholder="Tu email" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Suscribirse
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Newsletter subscription
        $('form[action*="newsletter.subscribe"]').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const email = form.find('input[name="email"]').val();
            
            $.post(form.attr('action'), {
                email: email,
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    showAlert('success', data.message || '¡Suscripción exitosa!');
                    form[0].reset();
                } else {
                    showAlert('error', data.message || 'Error en la suscripción');
                }
            }).fail(function() {
                showAlert('error', 'Error al procesar la suscripción');
            });
        });
    });
</script>
@endpush