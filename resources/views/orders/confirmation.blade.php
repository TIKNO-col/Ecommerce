@extends('layouts.app')

@section('title', 'Pedido Confirmado - #' . $order->order_number)
@section('description', 'Tu pedido ha sido confirmado exitosamente.')

@section('content')
<div class="container py-4">
    <!-- Success Message -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8 text-center">
            <div class="card border-success">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h1 class="text-success mb-3">¡Pedido Confirmado!</h1>
                    <p class="lead mb-4">Gracias por tu compra. Tu pedido ha sido procesado exitosamente.</p>
                    
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <i class="fas fa-receipt fa-2x text-primary mb-2"></i>
                                <h5>Número de Pedido</h5>
                                <p class="fw-bold text-primary">#{{ $order->order_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                                <h5>Fecha del Pedido</h5>
                                <p class="fw-bold">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                                <h5>Total Pagado</h5>
                                <p class="fw-bold text-success">${{ number_format($order->total, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-eye me-2"></i>Ver Detalles del Pedido
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Seguir Comprando
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Summary -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Productos Pedidos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $item->product->getMainImageUrl() }}" alt="{{ $item->product_name }}" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="{{ route('products.show', $item->product->slug) }}" class="text-decoration-none">
                                                        {{ $item->product_name }}
                                                    </a>
                                                </h6>
                                                <small class="text-muted">SKU: {{ $item->product_sku }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="fw-bold">${{ number_format($item->price, 2) }}</span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-secondary">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="fw-bold">${{ number_format($item->price * $item->quantity, 2) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Información de Envío</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Dirección de Envío:</h6>
                            <address class="mb-0">
                                <strong>{{ $order->shipping_name }}</strong><br>
                                {{ $order->shipping_address_line_1 }}<br>
                                @if($order->shipping_address_line_2)
                                    {{ $order->shipping_address_line_2 }}<br>
                                @endif
                                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                                {{ $order->shipping_country }}
                                @if($order->shipping_phone)
                                    <br>Tel: {{ $order->shipping_phone }}
                                @endif
                            </address>
                        </div>
                        <div class="col-md-6">
                            <h6>Método de Envío:</h6>
                            <p class="mb-2">
                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $order->shipping_method)) }}</span>
                            </p>
                            <p class="mb-0">
                                <strong>Tiempo estimado de entrega:</strong><br>
                                @switch($order->shipping_method)
                                    @case('free')
                                        5-7 días hábiles
                                        @break
                                    @case('standard')
                                        3-5 días hábiles
                                        @break
                                    @case('express')
                                        1-2 días hábiles
                                        @break
                                    @case('overnight')
                                        1 día hábil
                                        @break
                                    @default
                                        Consultar con soporte
                                @endswitch
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Información de Pago</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Método de Pago:</h6>
                            <p class="mb-0">
                                @if($order->payment_method === 'credit_card')
                                    <i class="fas fa-credit-card me-2"></i>Tarjeta de Crédito/Débito
                                    @if($order->payment_details && isset($order->payment_details['last_four']))
                                        <br><small class="text-muted">**** **** **** {{ $order->payment_details['last_four'] }}</small>
                                    @endif
                                @elseif($order->payment_method === 'paypal')
                                    <i class="fab fa-paypal me-2"></i>PayPal
                                @else
                                    {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Estado del Pago:</h6>
                            <span class="badge bg-success">{{ ucfirst($order->payment_status) }}</span>
                            @if($order->payment_details && isset($order->payment_details['transaction_id']))
                                <br><small class="text-muted">ID: {{ $order->payment_details['transaction_id'] }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary Sidebar -->
        <div class="col-lg-4">
            <!-- Order Total -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Resumen del Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Envío:</span>
                        <span>
                            @if($order->shipping_cost > 0)
                                ${{ number_format($order->shipping_cost, 2) }}
                            @else
                                <span class="text-success">Gratis</span>
                            @endif
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Impuestos:</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    
                    @if($order->discount_amount > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Descuento:</span>
                        <span>-${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary">${{ number_format($order->total, 2) }}</strong>
                    </div>
                </div>
            </div>
            
            <!-- Order Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Estado del Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="order-status">
                        <div class="status-item {{ $order->status === 'pending' ? 'active' : ($order->status !== 'pending' ? 'completed' : '') }}">
                            <div class="status-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="status-content">
                                <h6>Pedido Recibido</h6>
                                <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        
                        <div class="status-item {{ in_array($order->status, ['processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                            <div class="status-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="status-content">
                                <h6>Procesando</h6>
                                <small class="text-muted">Preparando tu pedido</small>
                            </div>
                        </div>
                        
                        <div class="status-item {{ in_array($order->status, ['shipped', 'delivered']) ? 'active' : '' }}">
                            <div class="status-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="status-content">
                                <h6>Enviado</h6>
                                <small class="text-muted">En camino</small>
                            </div>
                        </div>
                        
                        <div class="status-item {{ $order->status === 'delivered' ? 'active' : '' }}">
                            <div class="status-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="status-content">
                                <h6>Entregado</h6>
                                <small class="text-muted">Pedido completado</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('orders.track', $order->order_number) }}" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Rastrear Pedido
                        </a>
                        
                        @if($order->status === 'delivered')
                        <a href="{{ route('orders.invoice', $order->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-pdf me-2"></i>Descargar Factura
                        </a>
                        @endif
                        
                        @if(in_array($order->status, ['pending', 'processing']))
                        <button class="btn btn-outline-danger" onclick="cancelOrder({{ $order->id }})">
                            <i class="fas fa-times me-2"></i>Cancelar Pedido
                        </button>
                        @endif
                        
                        <a href="{{ route('orders.reorder', $order->id) }}" class="btn btn-outline-success">
                            <i class="fas fa-redo me-2"></i>Volver a Pedir
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Support -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">¿Necesitas Ayuda?</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos.</p>
                    
                    <div class="d-grid gap-2">
                        <a href="mailto:soporte@tienda.com?subject=Consulta sobre pedido #{{ $order->order_number }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-envelope me-2"></i>Enviar Email
                        </a>
                        
                        <a href="tel:+1234567890" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-phone me-2"></i>Llamar Soporte
                        </a>
                        
                        <a href="#" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-comments me-2"></i>Chat en Vivo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Email Confirmation Notice -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-envelope me-2"></i>
                <strong>Confirmación por Email:</strong> Hemos enviado un email de confirmación a <strong>{{ $order->billing_email }}</strong> con todos los detalles de tu pedido.
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .order-status {
        position: relative;
    }
    
    .status-item {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        position: relative;
    }
    
    .status-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 20px;
        top: 40px;
        width: 2px;
        height: 20px;
        background-color: #e9ecef;
    }
    
    .status-item.active::after,
    .status-item.completed::after {
        background-color: #28a745;
    }
    
    .status-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        position: relative;
        z-index: 1;
    }
    
    .status-item.active .status-icon,
    .status-item.completed .status-icon {
        background-color: #28a745;
        color: white;
    }
    
    .status-content h6 {
        margin-bottom: 2px;
        font-size: 14px;
    }
    
    .status-content small {
        font-size: 12px;
    }
</style>
@endpush

@push('scripts')
<script>
    function cancelOrder(orderId) {
        if (confirm('¿Estás seguro de que quieres cancelar este pedido?')) {
            $.post('{{ route("orders.cancel", ":id") }}'.replace(':id', orderId), {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    showAlert('success', data.message || 'Pedido cancelado exitosamente');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert('error', data.message || 'Error al cancelar el pedido');
                }
            }).fail(function() {
                showAlert('error', 'Error al cancelar el pedido');
            });
        }
    }
    
    $(document).ready(function() {
        // Auto-scroll to order summary on mobile
        if (window.innerWidth < 768) {
            setTimeout(() => {
                $('html, body').animate({
                    scrollTop: $('.card').first().offset().top - 20
                }, 1000);
            }, 500);
        }
    });
</script>
@endpush