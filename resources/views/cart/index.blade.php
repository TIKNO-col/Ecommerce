@extends('layouts.app')

@section('title', 'Carrito de Compras')
@section('description', 'Revisa los productos en tu carrito de compras antes de proceder al checkout.')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Carrito de Compras</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold"><i class="fas fa-shopping-cart me-2"></i>Carrito de Compras</h1>
        </div>
    </div>
    
    <div id="cartContent">
        @if($cartItems && $cartItems->count() > 0)
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Productos en tu carrito ({{ $cartItems->count() }})</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-center">Precio</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Subtotal</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cartItems as $item)
                                        <tr id="cart-item-{{ $item->id }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ $item->product->getMainImageUrl() }}" alt="{{ $item->product->name }}" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <a href="{{ route('products.show', $item->product->slug) }}" class="text-decoration-none">
                                                                {{ $item->product->name }}
                                                            </a>
                                                        </h6>
                                                        <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                                        @if($item->product->stock <= 0)
                                                            <div class="small text-danger">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Producto agotado
                                                            </div>
                                                        @elseif($item->quantity > $item->product->stock)
                                                            <div class="small text-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Solo {{ $item->product->stock }} disponibles
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                @if($item->product->isOnSale())
                                                    <div>
                                                        <span class="text-muted text-decoration-line-through small">${{ number_format($item->product->price, 2) }}</span>
                                                        <div class="fw-bold text-danger">${{ number_format($item->product->getCurrentPrice(), 2) }}</div>
                                                    </div>
                                                @else
                                                    <span class="fw-bold">${{ number_format($item->product->price, 2) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="input-group" style="width: 120px; margin: 0 auto;">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})" {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="form-control form-control-sm text-center" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock }}" onchange="updateQuantity({{ $item->id }}, this.value)">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})" {{ $item->quantity >= $item->product->stock ? 'disabled' : '' }}>
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="fw-bold">${{ number_format($item->product->getCurrentPrice() * $item->quantity, 2) }}</span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="btn-group">
                                                    @auth
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="moveToWishlist({{ $item->id }})" title="Mover a lista de deseos">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                    @endauth
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFromCart({{ $item->id }})" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                                </a>
                                <button type="button" class="btn btn-outline-danger" onclick="clearCart()">
                                    <i class="fas fa-trash me-2"></i>Vaciar Carrito
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-header">
                            <h5 class="mb-0">Resumen del Pedido</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">${{ number_format($subtotal, 2) }}</span>
                            </div>
                            
                            @if($discount > 0)
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Descuento:</span>
                                <span>-${{ number_format($discount, 2) }}</span>
                            </div>
                            @endif
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span id="shipping">
                                    @if($subtotal >= 50)
                                        <span class="text-success">Gratis</span>
                                    @else
                                        $5.99
                                    @endif
                                </span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Impuestos:</span>
                                <span id="taxes">${{ number_format($taxes, 2) }}</span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-primary" id="total">${{ number_format($total, 2) }}</strong>
                            </div>
                            
                            <!-- Coupon Code -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Código de cupón" id="couponCode">
                                    <button class="btn btn-outline-secondary" type="button" onclick="applyCoupon()">
                                        Aplicar
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <div class="d-grid">
                                @auth
                                    <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg">
                                        <i class="fas fa-credit-card me-2"></i>Proceder al Checkout
                                    </a>
                                @else
                                    <a href="{{ route('auth.login', ['redirect' => route('checkout.index')]) }}" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión para Continuar
                                    </a>
                                @endauth
                            </div>
                            
                            <!-- Security Badges -->
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>Compra 100% segura
                                </small>
                                <div class="mt-2">
                                    <img src="https://via.placeholder.com/40x25/007bff/ffffff?text=VISA" alt="Visa" class="me-1">
                                    <img src="https://via.placeholder.com/40x25/ff5722/ffffff?text=MC" alt="Mastercard" class="me-1">
                                    <img src="https://via.placeholder.com/40x25/009688/ffffff?text=PP" alt="PayPal">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommended Products -->
                    @if($recommendedProducts && $recommendedProducts->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">También te puede interesar</h6>
                        </div>
                        <div class="card-body">
                            @foreach($recommendedProducts->take(3) as $product)
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ $product->getMainImageUrl() }}" alt="{{ $product->name }}" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">
                                        <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none">
                                            {{ Str::limit($product->name, 30) }}
                                        </a>
                                    </h6>
                                    <div class="small text-primary fw-bold">${{ number_format($product->getCurrentPrice(), 2) }}</div>
                                </div>
                                <button class="btn btn-outline-primary btn-sm" onclick="addToCart({{ $product->id }})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Empty Cart -->
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Tu carrito está vacío</h3>
                <p class="text-muted mb-4">¡Agrega algunos productos increíbles a tu carrito!</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Explorar Productos
                </a>
                
                @auth
                    @if($wishlistItems && $wishlistItems->count() > 0)
                    <div class="mt-5">
                        <h5>Productos en tu lista de deseos</h5>
                        <div class="row g-3 mt-2">
                            @foreach($wishlistItems->take(4) as $item)
                            <div class="col-md-3">
                                <div class="card">
                                    <img src="{{ $item->product->getMainImageUrl() }}" class="card-img-top" alt="{{ $item->product->name }}" style="height: 150px; object-fit: cover;">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small">{{ Str::limit($item->product->name, 30) }}</h6>
                                        <div class="small text-primary fw-bold mb-2">${{ number_format($item->product->getCurrentPrice(), 2) }}</div>
                                        <button class="btn btn-primary btn-sm w-100" onclick="addToCart({{ $item->product->id }})">
                                            <i class="fas fa-cart-plus me-1"></i>Agregar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endauth
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateQuantity(itemId, newQuantity) {
        if (newQuantity < 1) return;
        
        $.post('{{ route("cart.update") }}', {
            item_id: itemId,
            quantity: newQuantity,
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                location.reload();
            } else {
                showAlert('error', data.message || 'Error al actualizar cantidad');
            }
        }).fail(function() {
            showAlert('error', 'Error al actualizar el carrito');
        });
    }
    
    function removeFromCart(itemId) {
        if (confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
            $.post('{{ route("cart.remove") }}', {
                item_id: itemId,
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    $('#cart-item-' + itemId).fadeOut(300, function() {
                        $(this).remove();
                        updateCartCount();
                        
                        // Check if cart is empty
                        if ($('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                    showAlert('success', data.message || 'Producto eliminado del carrito');
                } else {
                    showAlert('error', data.message || 'Error al eliminar producto');
                }
            }).fail(function() {
                showAlert('error', 'Error al eliminar producto del carrito');
            });
        }
    }
    
    @auth
    function moveToWishlist(itemId) {
        $.post('{{ route("cart.move-to-wishlist") }}', {
            item_id: itemId,
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                $('#cart-item-' + itemId).fadeOut(300, function() {
                    $(this).remove();
                    updateCartCount();
                    updateWishlistCount();
                    
                    // Check if cart is empty
                    if ($('tbody tr').length === 0) {
                        location.reload();
                    }
                });
                showAlert('success', data.message || 'Producto movido a la lista de deseos');
            } else {
                showAlert('error', data.message || 'Error al mover producto');
            }
        }).fail(function() {
            showAlert('error', 'Error al mover producto a la lista de deseos');
        });
    }
    @endauth
    
    function clearCart() {
        if (confirm('¿Estás seguro de que quieres vaciar todo el carrito?')) {
            $.post('{{ route("cart.clear") }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert('error', data.message || 'Error al vaciar carrito');
                }
            }).fail(function() {
                showAlert('error', 'Error al vaciar el carrito');
            });
        }
    }
    
    function applyCoupon() {
        const couponCode = $('#couponCode').val().trim();
        
        if (!couponCode) {
            showAlert('warning', 'Por favor ingresa un código de cupón');
            return;
        }
        
        $.post('{{ route("cart.apply-coupon") }}', {
            coupon_code: couponCode,
            _token: '{{ csrf_token() }}'
        }, function(data) {
            if (data.success) {
                location.reload();
            } else {
                showAlert('error', data.message || 'Código de cupón inválido');
            }
        }).fail(function() {
            showAlert('error', 'Error al aplicar el cupón');
        });
    }
    
    $(document).ready(function() {
        // Auto-save cart on quantity change
        $('input[type="number"]').on('change', function() {
            const itemId = $(this).closest('tr').attr('id').replace('cart-item-', '');
            const newQuantity = parseInt($(this).val());
            updateQuantity(itemId, newQuantity);
        });
        
        // Apply coupon on Enter key
        $('#couponCode').on('keypress', function(e) {
            if (e.which === 13) {
                applyCoupon();
            }
        });
    });
</script>
@endpush