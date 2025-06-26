@extends('layouts.app')

@section('title', 'Mi Lista de Deseos')
@section('description', 'Guarda tus productos favoritos en tu lista de deseos.')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Lista de Deseos</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold"><i class="fas fa-heart me-2 text-danger"></i>Mi Lista de Deseos</h1>
            @if($wishlistItems && $wishlistItems->count() > 0)
                <p class="text-muted">Tienes {{ $wishlistItems->count() }} {{ $wishlistItems->count() === 1 ? 'producto' : 'productos' }} en tu lista de deseos</p>
            @endif
        </div>
        <div class="col-md-4 text-end">
            @if($wishlistItems && $wishlistItems->count() > 0)
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="addAllToCart()">
                        <i class="fas fa-cart-plus me-2"></i>Agregar Todo al Carrito
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="clearWishlist()">
                        <i class="fas fa-trash me-2"></i>Limpiar Lista
                    </button>
                </div>
            @endif
        </div>
    </div>
    
    @if($wishlistItems && $wishlistItems->count() > 0)
        <!-- Filters and Sort -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchWishlist" placeholder="Buscar en tu lista de deseos...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterAvailability">
                    <option value="">Todos los productos</option>
                    <option value="in_stock">En stock</option>
                    <option value="out_of_stock">Agotados</option>
                    <option value="on_sale">En oferta</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="sortWishlist">
                    <option value="newest">Más recientes</option>
                    <option value="oldest">Más antiguos</option>
                    <option value="price_low">Precio: menor a mayor</option>
                    <option value="price_high">Precio: mayor a menor</option>
                    <option value="name">Nombre A-Z</option>
                </select>
            </div>
        </div>
        
        <!-- Wishlist Items -->
        <div id="wishlistContent">
            <div class="row g-4">
                @foreach($wishlistItems as $item)
                <div class="col-lg-3 col-md-4 col-sm-6 wishlist-item" data-product-id="{{ $item->product->id }}" data-name="{{ strtolower($item->product->name) }}" data-price="{{ $item->product->getCurrentPrice() }}" data-stock="{{ $item->product->stock > 0 ? 'in_stock' : 'out_of_stock' }}" data-sale="{{ $item->product->isOnSale() ? 'on_sale' : 'regular' }}" data-date="{{ $item->created_at->timestamp }}">
                    <div class="card h-100 product-card">
                        <!-- Product Image -->
                        <div class="position-relative">
                            <a href="{{ route('products.show', $item->product->slug) }}">
                                <img src="{{ $item->product->getMainImageUrl() }}" class="card-img-top" alt="{{ $item->product->name }}" style="height: 250px; object-fit: cover;">
                            </a>
                            
                            <!-- Badges -->
                            <div class="position-absolute top-0 start-0 p-2">
                                @if($item->product->isOnSale())
                                    <span class="badge bg-danger">-{{ $item->product->getDiscountPercentage() }}%</span>
                                @endif
                                @if($item->product->stock <= 0)
                                    <span class="badge bg-secondary">Agotado</span>
                                @elseif($item->product->stock <= 5)
                                    <span class="badge bg-warning text-dark">Últimas {{ $item->product->stock }}</span>
                                @endif
                            </div>
                            
                            <!-- Wishlist Actions -->
                            <div class="position-absolute top-0 end-0 p-2">
                                <button class="btn btn-sm btn-light rounded-circle" onclick="removeFromWishlist({{ $item->product->id }})" title="Eliminar de la lista">
                                    <i class="fas fa-times text-danger"></i>
                                </button>
                            </div>
                            
                            <!-- Quick View -->
                            <div class="position-absolute bottom-0 start-0 end-0 p-2 quick-actions" style="background: linear-gradient(transparent, rgba(0,0,0,0.7)); opacity: 0; transition: opacity 0.3s;">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-light flex-fill" onclick="quickView({{ $item->product->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($item->product->stock > 0)
                                        <button class="btn btn-sm btn-primary flex-fill" onclick="addToCart({{ $item->product->id }})">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Info -->
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title">
                                <a href="{{ route('products.show', $item->product->slug) }}" class="text-decoration-none text-dark">
                                    {{ Str::limit($item->product->name, 50) }}
                                </a>
                            </h6>
                            
                            <!-- Rating -->
                            @if($item->product->reviews_count > 0)
                                <div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="stars me-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $item->product->rating)
                                                    <i class="fas fa-star text-warning"></i>
                                                @else
                                                    <i class="far fa-star text-muted"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <small class="text-muted">({{ $item->product->reviews_count }})</small>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Price -->
                            <div class="mb-3">
                                @if($item->product->isOnSale())
                                    <div class="d-flex align-items-center">
                                        <span class="h5 text-danger mb-0 me-2">${{ number_format($item->product->getCurrentPrice(), 2) }}</span>
                                        <span class="text-muted text-decoration-line-through small">${{ number_format($item->product->price, 2) }}</span>
                                    </div>
                                @else
                                    <span class="h5 text-primary mb-0">${{ number_format($item->product->price, 2) }}</span>
                                @endif
                            </div>
                            
                            <!-- Stock Status -->
                            <div class="mb-3">
                                @if($item->product->stock > 0)
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>En stock ({{ $item->product->stock }} disponibles)
                                    </small>
                                @else
                                    <small class="text-danger">
                                        <i class="fas fa-times-circle me-1"></i>Producto agotado
                                    </small>
                                @endif
                            </div>
                            
                            <!-- Added Date -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>Agregado el {{ $item->created_at->format('d/m/Y') }}
                                </small>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-auto">
                                @if($item->product->stock > 0)
                                    <button class="btn btn-primary w-100 mb-2" onclick="addToCart({{ $item->product->id }})">
                                        <i class="fas fa-cart-plus me-2"></i>Agregar al Carrito
                                    </button>
                                @else
                                    <button class="btn btn-outline-secondary w-100 mb-2" onclick="notifyWhenAvailable({{ $item->product->id }})">
                                        <i class="fas fa-bell me-2"></i>Notificar Disponibilidad
                                    </button>
                                @endif
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary flex-fill" onclick="shareProduct({{ $item->product->id }})">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    <button class="btn btn-outline-danger flex-fill" onclick="removeFromWishlist({{ $item->product->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Pagination -->
        @if($wishlistItems->hasPages())
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-center">
                    {{ $wishlistItems->links() }}
                </div>
            </div>
        @endif
        
    @else
        <!-- Empty Wishlist -->
        <div class="text-center py-5">
            <i class="fas fa-heart fa-4x text-muted mb-4"></i>
            <h3>Tu lista de deseos está vacía</h3>
            <p class="text-muted mb-4">¡Agrega productos que te gusten para encontrarlos fácilmente más tarde!</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i>Explorar Productos
            </a>
            
            <!-- Recently Viewed Products -->
            @if($recentlyViewed && $recentlyViewed->count() > 0)
            <div class="mt-5">
                <h5>Productos que has visto recientemente</h5>
                <div class="row g-3 mt-2">
                    @foreach($recentlyViewed->take(4) as $product)
                    <div class="col-md-3">
                        <div class="card">
                            <img src="{{ $product->getMainImageUrl() }}" class="card-img-top" alt="{{ $product->name }}" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <h6 class="card-title small">{{ Str::limit($product->name, 30) }}</h6>
                                <div class="small text-primary fw-bold mb-2">${{ number_format($product->getCurrentPrice(), 2) }}</div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-danger btn-sm" onclick="addToWishlist({{ $product->id }})">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    @if($product->stock > 0)
                                        <button class="btn btn-primary btn-sm flex-fill" onclick="addToCart({{ $product->id }})">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    @endif
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Compartir Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="shareOn('facebook')">
                        <i class="fab fa-facebook-f me-2"></i>Compartir en Facebook
                    </button>
                    <button class="btn btn-info" onclick="shareOn('twitter')">
                        <i class="fab fa-twitter me-2"></i>Compartir en Twitter
                    </button>
                    <button class="btn btn-success" onclick="shareOn('whatsapp')">
                        <i class="fab fa-whatsapp me-2"></i>Compartir en WhatsApp
                    </button>
                    <button class="btn btn-secondary" onclick="copyLink()">
                        <i class="fas fa-copy me-2"></i>Copiar Enlace
                    </button>
                </div>
                <div class="mt-3">
                    <input type="text" class="form-control" id="shareLink" readonly>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .product-card:hover .quick-actions {
        opacity: 1;
    }
    
    .stars i {
        font-size: 14px;
    }
    
    .wishlist-item {
        transition: opacity 0.3s ease;
    }
    
    .wishlist-item.hidden {
        opacity: 0;
        pointer-events: none;
    }
</style>
@endpush

@push('scripts')
<script>
    let currentShareUrl = '';
    
    $(document).ready(function() {
        // Search functionality
        $('#searchWishlist').on('input', function() {
            filterWishlist();
        });
        
        // Filter functionality
        $('#filterAvailability').on('change', function() {
            filterWishlist();
        });
        
        // Sort functionality
        $('#sortWishlist').on('change', function() {
            sortWishlist();
        });
    });
    
    function filterWishlist() {
        const searchTerm = $('#searchWishlist').val().toLowerCase();
        const availabilityFilter = $('#filterAvailability').val();
        
        $('.wishlist-item').each(function() {
            const $item = $(this);
            const name = $item.data('name');
            const stock = $item.data('stock');
            const sale = $item.data('sale');
            
            let showItem = true;
            
            // Search filter
            if (searchTerm && !name.includes(searchTerm)) {
                showItem = false;
            }
            
            // Availability filter
            if (availabilityFilter) {
                if (availabilityFilter === 'in_stock' && stock !== 'in_stock') {
                    showItem = false;
                } else if (availabilityFilter === 'out_of_stock' && stock !== 'out_of_stock') {
                    showItem = false;
                } else if (availabilityFilter === 'on_sale' && sale !== 'on_sale') {
                    showItem = false;
                }
            }
            
            if (showItem) {
                $item.removeClass('hidden');
            } else {
                $item.addClass('hidden');
            }
        });
    }
    
    function sortWishlist() {
        const sortBy = $('#sortWishlist').val();
        const $container = $('.row.g-4');
        const $items = $('.wishlist-item').detach();
        
        $items.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);
            
            switch (sortBy) {
                case 'newest':
                    return $b.data('date') - $a.data('date');
                case 'oldest':
                    return $a.data('date') - $b.data('date');
                case 'price_low':
                    return $a.data('price') - $b.data('price');
                case 'price_high':
                    return $b.data('price') - $a.data('price');
                case 'name':
                    return $a.data('name').localeCompare($b.data('name'));
                default:
                    return 0;
            }
        });
        
        $container.append($items);
    }
    
    function removeFromWishlist(productId) {
        $.post('{{ route("wishlist.remove") }}', {
            product_id: productId,
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                $(`.wishlist-item[data-product-id="${productId}"]`).fadeOut(300, function() {
                    $(this).remove();
                    updateWishlistCount();
                    
                    // Check if wishlist is empty
                    if ($('.wishlist-item').length === 0) {
                        location.reload();
                    }
                });
                showAlert('success', data.message || 'Producto eliminado de la lista de deseos');
            } else {
                showAlert('error', data.message || 'Error al eliminar producto');
            }
        }).fail(function() {
            showAlert('error', 'Error al eliminar producto de la lista de deseos');
        });
    }
    
    function addAllToCart() {
        if (confirm('¿Agregar todos los productos disponibles al carrito?')) {
            const availableProducts = [];
            
            $('.wishlist-item').each(function() {
                if ($(this).data('stock') === 'in_stock') {
                    availableProducts.push($(this).data('product-id'));
                }
            });
            
            if (availableProducts.length === 0) {
                showAlert('warning', 'No hay productos disponibles para agregar al carrito');
                return;
            }
            
            $.post('{{ route("wishlist.add-all-to-cart") }}', {
                product_ids: availableProducts,
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    updateCartCount();
                    showAlert('success', data.message || `${availableProducts.length} productos agregados al carrito`);
                } else {
                    showAlert('error', data.message || 'Error al agregar productos al carrito');
                }
            }).fail(function() {
                showAlert('error', 'Error al agregar productos al carrito');
            });
        }
    }
    
    function clearWishlist() {
        if (confirm('¿Estás seguro de que quieres limpiar toda tu lista de deseos?')) {
            $.post('{{ route("wishlist.clear") }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert('error', data.message || 'Error al limpiar la lista de deseos');
                }
            }).fail(function() {
                showAlert('error', 'Error al limpiar la lista de deseos');
            });
        }
    }
    
    function notifyWhenAvailable(productId) {
        $.post('{{ route("products.notify-availability") }}', {
            product_id: productId,
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                showAlert('success', data.message || 'Te notificaremos cuando el producto esté disponible');
            } else {
                showAlert('error', data.message || 'Error al configurar la notificación');
            }
        }).fail(function() {
            showAlert('error', 'Error al configurar la notificación');
        });
    }
    
    function shareProduct(productId) {
        // Get product URL
        const productCard = $(`.wishlist-item[data-product-id="${productId}"]`);
        const productLink = productCard.find('.card-title a').attr('href');
        currentShareUrl = window.location.origin + productLink;
        
        $('#shareLink').val(currentShareUrl);
        $('#shareModal').modal('show');
    }
    
    function shareOn(platform) {
        const url = encodeURIComponent(currentShareUrl);
        const text = encodeURIComponent('¡Mira este producto increíble!');
        
        let shareUrl = '';
        
        switch (platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${text}%20${url}`;
                break;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    }
    
    function copyLink() {
        const shareLink = document.getElementById('shareLink');
        shareLink.select();
        shareLink.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            showAlert('success', 'Enlace copiado al portapapeles');
        } catch (err) {
            showAlert('error', 'Error al copiar el enlace');
        }
    }
    
    function quickView(productId) {
        // Implement quick view functionality
        showAlert('info', 'Función de vista rápida próximamente');
    }
</script>
@endpush