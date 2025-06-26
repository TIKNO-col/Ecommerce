<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'E-Commerce') - {{ config('app.name') }}</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="@yield('description', 'Tu tienda online de confianza')">
    <meta name="keywords" content="@yield('keywords', 'ecommerce, tienda, productos, compras')">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            line-height: 1.6;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        .product-card {
            transition: transform 0.2s ease-in-out;
            border: 1px solid #e9ecef;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 250px;
            object-fit: cover;
        }
        
        .price {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .price-old {
            text-decoration: line-through;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .rating {
            color: #ffc107;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            margin-top: auto;
        }
        
        .cart-count {
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            position: absolute;
            top: -8px;
            right: -8px;
        }
        
        .wishlist-count {
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            position: absolute;
            top: -8px;
            right: -8px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 4rem 0;
        }
        
        .category-card {
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        
        .category-card:hover {
            color: inherit;
            transform: scale(1.05);
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .search-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .search-suggestion:hover {
            background-color: #f8f9fa;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .btn {
            border-radius: 6px;
        }
        
        .card {
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .form-control {
            border-radius: 6px;
        }
        
        .badge {
            border-radius: 4px;
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: block;
        }
    </style>
    
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-store me-2"></i>{{ config('app.name', 'E-Commerce') }}
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Bar -->
                <div class="mx-auto" style="width: 400px;">
                    <form action="{{ route('search') }}" method="GET" class="position-relative">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Buscar productos..." value="{{ request('q') }}" id="searchInput">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div id="searchSuggestions" class="search-suggestions d-none"></div>
                    </form>
                </div>
                
                <!-- Right Side Navigation -->
                <ul class="navbar-nav ms-auto">
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" id="cartCount">0</span>
                        </a>
                    </li>
                    
                    @auth
                        <!-- Wishlist -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('wishlist.index') }}">
                                <i class="fas fa-heart"></i>
                                <span class="wishlist-count" id="wishlistCount">0</span>
                            </a>
                        </li>
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('user.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ route('user.profile') }}"><i class="fas fa-user me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="fas fa-box me-2"></i>Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="{{ route('wishlist.index') }}"><i class="fas fa-heart me-2"></i>Lista de Deseos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('auth.login') }}"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('auth.register') }}"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Categories Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('home') }}">Inicio</a>
                <a class="nav-link" href="{{ route('products.index') }}">Productos</a>
                <a class="nav-link" href="{{ route('categories.index') }}">Categorías</a>
                <a class="nav-link" href="{{ route('products.featured') }}">Destacados</a>
                <a class="nav-link" href="{{ route('products.new') }}">Nuevos</a>
                <a class="nav-link" href="{{ route('products.sale') }}">Ofertas</a>
                <a class="nav-link" href="{{ route('contact') }}">Contacto</a>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <!-- Main Content -->
    <main class="flex-grow-1">
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="footer mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <h5>{{ config('app.name') }}</h5>
                    <p class="text-muted">Tu tienda online de confianza con los mejores productos y precios.</p>
                </div>
                <div class="col-md-3">
                    <h6>Enlaces Rápidos</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('home') }}" class="text-muted text-decoration-none">Inicio</a></li>
                        <li><a href="{{ route('products.index') }}" class="text-muted text-decoration-none">Productos</a></li>
                        <li><a href="{{ route('categories.index') }}" class="text-muted text-decoration-none">Categorías</a></li>
                        <li><a href="{{ route('contact') }}" class="text-muted text-decoration-none">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Mi Cuenta</h6>
                    <ul class="list-unstyled">
                        @auth
                            <li><a href="{{ route('user.dashboard') }}" class="text-muted text-decoration-none">Dashboard</a></li>
                            <li><a href="{{ route('orders.index') }}" class="text-muted text-decoration-none">Mis Pedidos</a></li>
                            <li><a href="{{ route('wishlist.index') }}" class="text-muted text-decoration-none">Lista de Deseos</a></li>
                        @else
                            <li><a href="{{ route('auth.login') }}" class="text-muted text-decoration-none">Iniciar Sesión</a></li>
                            <li><a href="{{ route('auth.register') }}" class="text-muted text-decoration-none">Registrarse</a></li>
                        @endauth
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contacto</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-envelope me-2"></i>info@ecommerce.com</li>
                        <li><i class="fas fa-phone me-2"></i>+1 234 567 8900</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>123 Calle Principal</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="#" class="text-muted me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Update cart count on page load
            updateCartCount();
            
            @auth
            // Update wishlist count on page load
            updateWishlistCount();
            @endauth
            
            // Search suggestions
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        fetchSearchSuggestions(query);
                    }, 300);
                } else {
                    $('#searchSuggestions').addClass('d-none');
                }
            });
            
            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('#searchSuggestions').addClass('d-none');
                }
            });
        });
        
        function updateCartCount() {
            $.get('{{ route("cart.count") }}', function(data) {
                $('#cartCount').text(data.count || 0);
            }).fail(function() {
                $('#cartCount').text('0');
            });
        }
        
        @auth
        function updateWishlistCount() {
            $.get('{{ route("wishlist.count") }}', function(data) {
                $('#wishlistCount').text(data.count || 0);
            }).fail(function() {
                $('#wishlistCount').text('0');
            });
        }
        @endauth
        
        function fetchSearchSuggestions(query) {
            $.get('{{ route("search.suggestions") }}', { q: query }, function(data) {
                const suggestions = $('#searchSuggestions');
                suggestions.empty();
                
                if (data.length > 0) {
                    data.forEach(function(item) {
                        const suggestion = $('<div class="search-suggestion"></div>')
                            .text(item.name)
                            .on('click', function() {
                                $('#searchInput').val(item.name);
                                suggestions.addClass('d-none');
                                window.location.href = item.url;
                            });
                        suggestions.append(suggestion);
                    });
                    suggestions.removeClass('d-none');
                } else {
                    suggestions.addClass('d-none');
                }
            });
        }
        
        // Add to cart function
        function addToCart(productId, quantity = 1) {
            $.post('{{ route("cart.add") }}', {
                product_id: productId,
                quantity: quantity,
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    updateCartCount();
                    showAlert('success', data.message || 'Producto agregado al carrito');
                } else {
                    showAlert('error', data.message || 'Error al agregar producto');
                }
            }).fail(function() {
                showAlert('error', 'Error al agregar producto al carrito');
            });
        }
        
        @auth
        // Add to wishlist function
        function addToWishlist(productId) {
            $.post('{{ route("wishlist.add") }}', {
                product_id: productId,
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    updateWishlistCount();
                    showAlert('success', data.message || 'Producto agregado a la lista de deseos');
                } else {
                    showAlert('error', data.message || 'Error al agregar a la lista de deseos');
                }
            }).fail(function() {
                showAlert('error', 'Error al agregar a la lista de deseos');
            });
        }
        @endauth
        
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const alert = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    <i class="fas ${icon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').append(alert);
            
            setTimeout(() => {
                alert.alert('close');
            }, 5000);
        }
    </script>
    
    @stack('scripts')
</body>
</html>