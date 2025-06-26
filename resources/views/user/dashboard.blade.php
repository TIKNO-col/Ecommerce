@extends('layouts.app')

@section('title', 'Mi Cuenta')
@section('description', 'Gestiona tu cuenta, pedidos y preferencias.')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Mi Cuenta</li>
        </ol>
    </nav>
    
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">¡Hola, {{ auth()->user()->first_name }}!</h2>
                            <p class="mb-0 opacity-75">Bienvenido de vuelta a tu cuenta</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="me-3">
                                    <small class="opacity-75">Miembro desde</small><br>
                                    <strong>{{ auth()->user()->created_at->format('M Y') }}</strong>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-primary mb-2">
                        <i class="fas fa-shopping-bag fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">{{ $totalOrders ?? 0 }}</h4>
                    <p class="text-muted mb-0">Pedidos Totales</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-success mb-2">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">${{ number_format($totalSpent ?? 0, 2) }}</h4>
                    <p class="text-muted mb-0">Total Gastado</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-warning mb-2">
                        <i class="fas fa-heart fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">{{ $wishlistCount ?? 0 }}</h4>
                    <p class="text-muted mb-0">Lista de Deseos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-info mb-2">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">{{ $reviewsCount ?? 0 }}</h4>
                    <p class="text-muted mb-0">Reseñas Escritas</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>Mi Cuenta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('user.dashboard') }}" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="{{ route('user.profile') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i>Perfil
                    </a>
                    <a href="{{ route('user.orders') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag me-2"></i>Mis Pedidos
                        @if($pendingOrders > 0)
                            <span class="badge bg-primary ms-auto">{{ $pendingOrders }}</span>
                        @endif
                    </a>
                    <a href="{{ route('user.addresses') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-map-marker-alt me-2"></i>Direcciones
                    </a>
                    <a href="{{ route('wishlist.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2"></i>Lista de Deseos
                        @if($wishlistCount > 0)
                            <span class="badge bg-danger ms-auto">{{ $wishlistCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('user.reviews') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-star me-2"></i>Mis Reseñas
                    </a>
                    <a href="{{ route('user.preferences') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i>Preferencias
                    </a>
                    <a href="{{ route('user.security') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-shield-alt me-2"></i>Seguridad
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Recent Orders -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pedidos Recientes</h5>
                    <a href="{{ route('user.orders') }}" class="btn btn-outline-primary btn-sm">Ver Todos</a>
                </div>
                <div class="card-body">
                    @if($recentOrders && $recentOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <strong>#{{ $order->order_number }}</strong><br>
                                            <small class="text-muted">{{ $order->items_count }} {{ $order->items_count === 1 ? 'producto' : 'productos' }}</small>
                                        </td>
                                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'shipped' => 'primary',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $statusTexts = [
                                                    'pending' => 'Pendiente',
                                                    'processing' => 'Procesando',
                                                    'shipped' => 'Enviado',
                                                    'delivered' => 'Entregado',
                                                    'cancelled' => 'Cancelado'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                                {{ $statusTexts[$order->status] ?? ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td><strong>${{ number_format($order->total, 2) }}</strong></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-primary" title="Ver Detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($order->status === 'delivered')
                                                    <a href="{{ route('orders.invoice', $order->id) }}" class="btn btn-outline-secondary" title="Descargar Factura">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                @if(in_array($order->status, ['pending', 'processing']))
                                                    <button class="btn btn-outline-danger" onclick="cancelOrder({{ $order->id }})" title="Cancelar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h5>No tienes pedidos aún</h5>
                            <p class="text-muted">¡Explora nuestros productos y haz tu primer pedido!</p>
                            <a href="{{ route('products.index') }}" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Explorar Productos
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('user.profile') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-user fa-2x mb-2"></i>
                                <span>Editar Perfil</span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('user.addresses') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                                <span>Gestionar Direcciones</span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('user.preferences') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-cog fa-2x mb-2"></i>
                                <span>Preferencias</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Actividad Reciente</h5>
                </div>
                <div class="card-body">
                    @if($recentActivity && $recentActivity->count() > 0)
                        <div class="timeline">
                            @foreach($recentActivity as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    @php
                                        $icons = [
                                            'order_placed' => 'fas fa-shopping-cart text-primary',
                                            'order_shipped' => 'fas fa-truck text-info',
                                            'order_delivered' => 'fas fa-check-circle text-success',
                                            'review_posted' => 'fas fa-star text-warning',
                                            'wishlist_added' => 'fas fa-heart text-danger',
                                            'profile_updated' => 'fas fa-user text-secondary'
                                        ];
                                    @endphp
                                    <i class="{{ $icons[$activity->type] ?? 'fas fa-circle text-muted' }}"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">{{ $activity->title }}</h6>
                                    <p class="text-muted mb-1">{{ $activity->description }}</p>
                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('user.activity') }}" class="btn btn-outline-primary btn-sm">
                                Ver Toda la Actividad
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-history fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No hay actividad reciente</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Recommendations -->
            @if($recommendedProducts && $recommendedProducts->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-thumbs-up me-2"></i>Recomendado para Ti</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($recommendedProducts as $product)
                        <div class="col-md-4">
                            <div class="card h-100">
                                <img src="{{ $product->getMainImageUrl() }}" class="card-img-top" alt="{{ $product->name }}" style="height: 150px; object-fit: cover;">
                                <div class="card-body p-3">
                                    <h6 class="card-title">{{ Str::limit($product->name, 40) }}</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary fw-bold">${{ number_format($product->getCurrentPrice(), 2) }}</span>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-danger" onclick="addToWishlist({{ $product->id }})" title="Agregar a lista de deseos">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                            <button class="btn btn-primary" onclick="addToCart({{ $product->id }})" title="Agregar al carrito">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
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
    </div>
</div>
@endsection

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .timeline-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        font-size: 10px;
    }
    
    .timeline-content h6 {
        font-size: 14px;
        font-weight: 600;
    }
    
    .timeline-content p {
        font-size: 13px;
        margin-bottom: 5px;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .list-group-item.active {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    @media (max-width: 768px) {
        .timeline {
            padding-left: 20px;
        }
        
        .timeline-marker {
            left: -20px;
            width: 15px;
            height: 15px;
            font-size: 8px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function cancelOrder(orderId) {
        if (confirm('¿Estás seguro de que quieres cancelar este pedido?')) {
            $.post('{{ route("orders.cancel", "") }}/' + orderId, {
                _token: '{{ csrf_token() }}',
                _method: 'PATCH'
            }, function(data) {
                if (data.success) {
                    showAlert('success', data.message || 'Pedido cancelado exitosamente');
                    location.reload();
                } else {
                    showAlert('error', data.message || 'Error al cancelar el pedido');
                }
            }).fail(function() {
                showAlert('error', 'Error al cancelar el pedido');
            });
        }
    }
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        // Only refresh if user is active (has interacted in last 5 minutes)
        if (Date.now() - lastActivity < 300000) {
            $.get('{{ route("user.dashboard.refresh") }}', function(data) {
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(function(notification) {
                        showAlert(notification.type, notification.message);
                    });
                }
            });
        }
    }, 300000); // 5 minutes
    
    let lastActivity = Date.now();
    
    // Track user activity
    $(document).on('click keypress scroll', function() {
        lastActivity = Date.now();
    });
</script>
@endpush