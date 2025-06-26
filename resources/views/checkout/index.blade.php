@extends('layouts.app')

@section('title', 'Checkout - Finalizar Compra')
@section('description', 'Completa tu compra de forma segura y rápida.')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Carrito</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold"><i class="fas fa-credit-card me-2"></i>Finalizar Compra</h1>
        </div>
    </div>
    
    <!-- Checkout Steps -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="step-title">Carrito</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="step-title">Dirección</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="step-title">Pago</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="step">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-title">Confirmación</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <form id="checkoutForm" action="{{ route('checkout.process') }}" method="POST">
        @csrf
        
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <!-- Shipping Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Dirección de Envío</h5>
                    </div>
                    <div class="card-body">
                        @if($userAddresses && $userAddresses->count() > 0)
                            <div class="mb-3">
                                <label class="form-label">Seleccionar dirección guardada:</label>
                                <div class="row">
                                    @foreach($userAddresses as $address)
                                    <div class="col-md-6 mb-3">
                                        <div class="card address-card" data-address-id="{{ $address->id }}">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="shipping_address_id" value="{{ $address->id }}" id="address{{ $address->id }}" {{ $address->is_default ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="address{{ $address->id }}">
                                                        <strong>{{ $address->name }}</strong>
                                                        @if($address->is_default)
                                                            <span class="badge bg-primary ms-2">Principal</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                <div class="mt-2 small text-muted">
                                                    {{ $address->address_line_1 }}<br>
                                                    @if($address->address_line_2)
                                                        {{ $address->address_line_2 }}<br>
                                                    @endif
                                                    {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>
                                                    {{ $address->country }}
                                                    @if($address->phone)
                                                        <br>Tel: {{ $address->phone }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="shipping_address_id" value="new" id="newAddress">
                                <label class="form-check-label" for="newAddress">
                                    <strong>Usar nueva dirección</strong>
                                </label>
                            </div>
                        @endif
                        
                        <div id="newAddressForm" class="{{ $userAddresses && $userAddresses->count() > 0 ? 'd-none' : '' }}">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', auth()->user()->first_name ?? '') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', auth()->user()->last_name ?? '') }}" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address_line_1" class="form-label">Dirección *</label>
                                <input type="text" class="form-control" id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}" placeholder="Calle, número" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address_line_2" class="form-label">Dirección 2 (Opcional)</label>
                                <input type="text" class="form-control" id="address_line_2" name="address_line_2" value="{{ old('address_line_2') }}" placeholder="Apartamento, suite, etc.">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">Ciudad *</label>
                                    <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="state" class="form-label">Estado *</label>
                                    <input type="text" class="form-control" id="state" name="state" value="{{ old('state') }}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="postal_code" class="form-label">Código Postal *</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="country" class="form-label">País *</label>
                                <select class="form-select" id="country" name="country" required>
                                    <option value="">Seleccionar país</option>
                                    <option value="MX" {{ old('country') == 'MX' ? 'selected' : '' }}>México</option>
                                    <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>Estados Unidos</option>
                                    <option value="CA" {{ old('country') == 'CA' ? 'selected' : '' }}>Canadá</option>
                                    <option value="ES" {{ old('country') == 'ES' ? 'selected' : '' }}>España</option>
                                    <option value="AR" {{ old('country') == 'AR' ? 'selected' : '' }}>Argentina</option>
                                    <option value="CO" {{ old('country') == 'CO' ? 'selected' : '' }}>Colombia</option>
                                    <option value="PE" {{ old('country') == 'PE' ? 'selected' : '' }}>Perú</option>
                                    <option value="CL" {{ old('country') == 'CL' ? 'selected' : '' }}>Chile</option>
                                </select>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="save_address" name="save_address" value="1" checked>
                                <label class="form-check-label" for="save_address">
                                    Guardar esta dirección para futuras compras
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Método de Envío</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card shipping-method" data-price="0">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="shipping_method" value="free" id="freeShipping" {{ $subtotal >= 50 ? 'checked' : ($subtotal < 50 ? 'disabled' : '') }}>
                                            <label class="form-check-label" for="freeShipping">
                                                <strong>Envío Gratis</strong>
                                                <span class="text-success ms-2">$0.00</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">
                                            @if($subtotal >= 50)
                                                Entrega en 5-7 días hábiles
                                            @else
                                                Disponible en compras mayores a $50.00
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card shipping-method" data-price="5.99">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="shipping_method" value="standard" id="standardShipping" {{ $subtotal < 50 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="standardShipping">
                                                <strong>Envío Estándar</strong>
                                                <span class="text-primary ms-2">$5.99</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">Entrega en 3-5 días hábiles</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card shipping-method" data-price="12.99">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="shipping_method" value="express" id="expressShipping">
                                            <label class="form-check-label" for="expressShipping">
                                                <strong>Envío Express</strong>
                                                <span class="text-warning ms-2">$12.99</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">Entrega en 1-2 días hábiles</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card shipping-method" data-price="19.99">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="shipping_method" value="overnight" id="overnightShipping">
                                            <label class="form-check-label" for="overnightShipping">
                                                <strong>Envío Nocturno</strong>
                                                <span class="text-danger ms-2">$19.99</span>
                                            </label>
                                        </div>
                                        <small class="text-muted">Entrega al siguiente día hábil</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Método de Pago</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card payment-method">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="credit_card" id="creditCard" checked>
                                            <label class="form-check-label" for="creditCard">
                                                <i class="fas fa-credit-card me-2"></i>
                                                <strong>Tarjeta de Crédito/Débito</strong>
                                            </label>
                                        </div>
                                        <div class="mt-2">
                                            <img src="https://via.placeholder.com/40x25/007bff/ffffff?text=VISA" alt="Visa" class="me-1">
                                            <img src="https://via.placeholder.com/40x25/ff5722/ffffff?text=MC" alt="Mastercard" class="me-1">
                                            <img src="https://via.placeholder.com/40x25/4caf50/ffffff?text=AE" alt="American Express">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card payment-method">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="paypal" id="paypal">
                                            <label class="form-check-label" for="paypal">
                                                <i class="fab fa-paypal me-2"></i>
                                                <strong>PayPal</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Paga de forma segura con tu cuenta PayPal</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Credit Card Form -->
                        <div id="creditCardForm">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="card_number" class="form-label">Número de Tarjeta *</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="card_name" class="form-label">Nombre en la Tarjeta *</label>
                                    <input type="text" class="form-control" id="card_name" name="card_name" placeholder="Como aparece en la tarjeta">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="card_expiry" class="form-label">Fecha de Vencimiento *</label>
                                    <input type="text" class="form-control" id="card_expiry" name="card_expiry" placeholder="MM/AA" maxlength="5">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="card_cvv" class="form-label">CVV *</label>
                                    <input type="text" class="form-control" id="card_cvv" name="card_cvv" placeholder="123" maxlength="4">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notas del Pedido (Opcional)</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="order_notes" rows="3" placeholder="Instrucciones especiales para la entrega, comentarios adicionales...">{{ old('order_notes') }}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">Resumen del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <!-- Cart Items -->
                        <div class="mb-3">
                            @foreach($cartItems as $item)
                            <div class="d-flex align-items-center mb-2">
                                <img src="{{ $item->product->getMainImageUrl() }}" alt="{{ $item->product->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="small fw-bold">{{ Str::limit($item->product->name, 25) }}</div>
                                    <div class="small text-muted">Cantidad: {{ $item->quantity }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="small fw-bold">${{ number_format($item->product->getCurrentPrice() * $item->quantity, 2) }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <hr>
                        
                        <!-- Price Breakdown -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="orderSubtotal">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío:</span>
                            <span id="orderShipping">
                                @if($subtotal >= 50)
                                    <span class="text-success">Gratis</span>
                                @else
                                    $5.99
                                @endif
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Impuestos:</span>
                            <span id="orderTaxes">${{ number_format($taxes, 2) }}</span>
                        </div>
                        
                        @if($discount > 0)
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Descuento:</span>
                            <span>-${{ number_format($discount, 2) }}</span>
                        </div>
                        @endif
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary" id="orderTotal">${{ number_format($total, 2) }}</strong>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label small" for="terms">
                                Acepto los <a href="#" target="_blank">términos y condiciones</a> y la <a href="#" target="_blank">política de privacidad</a>
                            </label>
                        </div>
                        
                        <!-- Place Order Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="placeOrderBtn">
                                <i class="fas fa-lock me-2"></i>Realizar Pedido
                            </button>
                        </div>
                        
                        <!-- Security Info -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>Transacción 100% segura
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .step {
        position: relative;
        padding: 20px 0;
    }
    
    .step-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 20px;
        transition: all 0.3s ease;
    }
    
    .step.active .step-icon {
        background-color: #007bff;
        color: white;
    }
    
    .step-title {
        font-size: 14px;
        font-weight: 500;
        color: #6c757d;
    }
    
    .step.active .step-title {
        color: #007bff;
        font-weight: 600;
    }
    
    .address-card:hover,
    .shipping-method:hover,
    .payment-method:hover {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        cursor: pointer;
    }
    
    .address-card.selected,
    .shipping-method.selected,
    .payment-method.selected {
        border-color: #007bff;
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Address selection
        $('input[name="shipping_address_id"]').on('change', function() {
            if ($(this).val() === 'new') {
                $('#newAddressForm').removeClass('d-none');
            } else {
                $('#newAddressForm').addClass('d-none');
            }
            
            $('.address-card').removeClass('selected');
            if ($(this).val() !== 'new') {
                $(this).closest('.address-card').addClass('selected');
            }
        });
        
        // Shipping method selection
        $('input[name="shipping_method"]').on('change', function() {
            $('.shipping-method').removeClass('selected');
            $(this).closest('.shipping-method').addClass('selected');
            updateOrderTotal();
        });
        
        // Payment method selection
        $('input[name="payment_method"]').on('change', function() {
            $('.payment-method').removeClass('selected');
            $(this).closest('.payment-method').addClass('selected');
            
            if ($(this).val() === 'credit_card') {
                $('#creditCardForm').show();
            } else {
                $('#creditCardForm').hide();
            }
        });
        
        // Card number formatting
        $('#card_number').on('input', function() {
            let value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            $(this).val(formattedValue);
        });
        
        // Card expiry formatting
        $('#card_expiry').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            $(this).val(value);
        });
        
        // CVV validation
        $('#card_cvv').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            $(this).val(value);
        });
        
        // Form validation
        $('#checkoutForm').on('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return false;
            }
            
            $('#placeOrderBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Procesando...');
            
            // Submit form
            this.submit();
        });
        
        // Initialize selected states
        $('input[name="shipping_address_id"]:checked').trigger('change');
        $('input[name="shipping_method"]:checked').trigger('change');
        $('input[name="payment_method"]:checked').trigger('change');
    });
    
    function updateOrderTotal() {
        const shippingPrice = parseFloat($('input[name="shipping_method"]:checked').closest('.shipping-method').data('price')) || 0;
        const subtotal = {{ $subtotal }};
        const taxes = {{ $taxes }};
        const discount = {{ $discount }};
        
        const total = subtotal + shippingPrice + taxes - discount;
        
        $('#orderShipping').text(shippingPrice === 0 ? 'Gratis' : '$' + shippingPrice.toFixed(2));
        $('#orderTotal').text('$' + total.toFixed(2));
    }
    
    function validateForm() {
        let isValid = true;
        
        // Validate shipping address
        if ($('input[name="shipping_address_id"]:checked').val() === 'new') {
            const requiredFields = ['first_name', 'last_name', 'email', 'address_line_1', 'city', 'state', 'postal_code', 'country'];
            
            requiredFields.forEach(function(field) {
                const input = $(`#${field}`);
                if (!input.val().trim()) {
                    input.addClass('is-invalid');
                    isValid = false;
                } else {
                    input.removeClass('is-invalid');
                }
            });
        }
        
        // Validate payment method
        if ($('input[name="payment_method"]:checked').val() === 'credit_card') {
            const cardFields = ['card_number', 'card_name', 'card_expiry', 'card_cvv'];
            
            cardFields.forEach(function(field) {
                const input = $(`#${field}`);
                if (!input.val().trim()) {
                    input.addClass('is-invalid');
                    isValid = false;
                } else {
                    input.removeClass('is-invalid');
                }
            });
            
            // Validate card number length
            const cardNumber = $('#card_number').val().replace(/\s/g, '');
            if (cardNumber.length < 13 || cardNumber.length > 19) {
                $('#card_number').addClass('is-invalid');
                isValid = false;
            }
            
            // Validate expiry date
            const expiry = $('#card_expiry').val();
            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                $('#card_expiry').addClass('is-invalid');
                isValid = false;
            }
            
            // Validate CVV
            const cvv = $('#card_cvv').val();
            if (cvv.length < 3 || cvv.length > 4) {
                $('#card_cvv').addClass('is-invalid');
                isValid = false;
            }
        }
        
        // Validate terms
        if (!$('#terms').is(':checked')) {
            showAlert('warning', 'Debes aceptar los términos y condiciones');
            isValid = false;
        }
        
        if (!isValid) {
            showAlert('error', 'Por favor completa todos los campos requeridos');
        }
        
        return isValid;
    }
</script>
@endpush