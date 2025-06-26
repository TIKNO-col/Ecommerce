@extends('layouts.app')

@section('title', 'Crear Cuenta')
@section('description', 'Crea tu cuenta para disfrutar de todas las funcionalidades de nuestra tienda.')

@section('content')
<div class="auth-container" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2340&q=80') center/cover; min-height: 100vh; display: flex; align-items: center;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg border-0" style="backdrop-filter: blur(10px); background: rgba(255,255,255,0.95);">
                    <div class="card-body p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Crear Cuenta</h2>
                        <p class="text-muted">Únete a nuestra comunidad</p>
                    </div>
                    

                    
                    <!-- Registration Form -->
                    <form method="POST" action="{{ route('auth.register.submit') }}" id="registerForm">
                        @csrf
                        
                        <!-- Name Fields -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Nombre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                           id="first_name" name="first_name" value="{{ old('first_name') }}" 
                                           placeholder="Tu nombre" required autocomplete="given-name">
                                </div>
                                @error('first_name')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Apellido</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" name="last_name" value="{{ old('last_name') }}" 
                                           placeholder="Tu apellido" required autocomplete="family-name">
                                </div>
                                @error('last_name')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" 
                                       placeholder="tu@email.com" required autocomplete="email">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">Usaremos este correo para enviarte confirmaciones de pedidos.</div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono <span class="text-muted">(Opcional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone') }}" 
                                       placeholder="+1 234 567 8900" autocomplete="tel">
                            </div>
                            @error('phone')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" placeholder="Mínimo 8 caracteres" 
                                       required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                            
                            <!-- Password Strength Indicator -->
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="passwordStrengthText">Ingresa una contraseña</small>
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation" 
                                       placeholder="Repite tu contraseña" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                    <i class="fas fa-eye" id="togglePasswordConfirmIcon"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordMatchError" style="display: none;">
                                Las contraseñas no coinciden
                            </div>
                        </div>
                        
                        <!-- Date of Birth -->
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Fecha de Nacimiento <span class="text-muted">(Opcional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}" 
                                       max="{{ date('Y-m-d', strtotime('-13 years')) }}">
                            </div>
                            @error('date_of_birth')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">Debe ser mayor de 13 años para registrarse.</div>
                        </div>
                        
                        <!-- Gender -->
                        <div class="mb-3">
                            <label class="form-label">Género <span class="text-muted">(Opcional)</span></label>
                            <div class="row">
                                <div class="col-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male" value="male" {{ old('gender') == 'male' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="male">Masculino</label>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female" value="female" {{ old('gender') == 'female' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="female">Femenino</label>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="other" value="other" {{ old('gender') == 'other' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="other">Otro</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Newsletter Subscription -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter" 
                                       value="1" {{ old('newsletter') ? 'checked' : '' }}>
                                <label class="form-check-label" for="newsletter">
                                    Quiero recibir ofertas especiales y novedades por correo electrónico
                                </label>
                            </div>
                        </div>
                        
                        <!-- Terms and Privacy -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror" 
                                       type="checkbox" name="terms" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Acepto los <a href="{{ route('terms') }}" target="_blank" class="text-decoration-none">Términos y Condiciones</a> 
                                    y la <a href="{{ route('privacy') }}" target="_blank" class="text-decoration-none">Política de Privacidad</a>
                                </label>
                            </div>
                            @error('terms')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="registerBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="registerSpinner"></span>
                                <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                            </button>
                        </div>
                        
                        <!-- Login Link -->
                        <div class="text-center">
                            <p class="mb-0">¿Ya tienes una cuenta? 
                                <a href="{{ route('login') }}" class="text-decoration-none fw-bold">Inicia sesión aquí</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Guest Actions -->
            <div class="text-center mt-4">
                <p class="text-muted mb-2">¿Solo quieres echar un vistazo?</p>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-shopping-bag me-2"></i>Continuar como invitado
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay d-none" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-3 text-white">Creando tu cuenta...</div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        border-radius: 15px;
    }
    
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .input-group-text {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    
    .progress-bar {
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .social-btn {
        transition: all 0.3s ease;
    }
    
    .social-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 2rem !important;
        }
        
        .row.mb-3 .col-md-6:first-child {
            margin-bottom: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
            togglePasswordField('#password', '#togglePasswordIcon');
        });
        
        $('#togglePasswordConfirm').click(function() {
            togglePasswordField('#password_confirmation', '#togglePasswordConfirmIcon');
        });
        
        // Password strength checker
        $('#password').on('input', function() {
            checkPasswordStrength($(this).val());
            checkPasswordMatch();
        });
        
        // Password confirmation checker
        $('#password_confirmation').on('input', function() {
            checkPasswordMatch();
        });
        
        // Phone number formatting
        $('#phone').on('input', function() {
            formatPhoneNumber(this);
        });
        
        // Form validation
        $('#registerForm').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            showLoadingState();
        });
        
        // Auto-focus on first name field
        $('#first_name').focus();
        
        // Handle social registration clicks
        $('.social-btn').click(function() {
            showLoadingState();
        });
    });
    
    function togglePasswordField(fieldSelector, iconSelector) {
        const passwordField = $(fieldSelector);
        const passwordIcon = $(iconSelector);
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            passwordIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            passwordIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    }
    
    function checkPasswordStrength(password) {
        const strengthBar = $('#passwordStrength');
        const strengthText = $('#passwordStrengthText');
        
        let strength = 0;
        let text = '';
        let color = '';
        
        if (password.length === 0) {
            text = 'Ingresa una contraseña';
            color = 'bg-secondary';
        } else if (password.length < 6) {
            strength = 20;
            text = 'Muy débil';
            color = 'bg-danger';
        } else if (password.length < 8) {
            strength = 40;
            text = 'Débil';
            color = 'bg-warning';
        } else {
            strength = 60;
            text = 'Buena';
            color = 'bg-info';
            
            // Check for additional criteria
            if (/[A-Z]/.test(password)) strength += 10;
            if (/[0-9]/.test(password)) strength += 10;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            if (strength >= 90) {
                text = 'Muy fuerte';
                color = 'bg-success';
            } else if (strength >= 80) {
                text = 'Fuerte';
                color = 'bg-success';
            }
        }
        
        strengthBar.removeClass('bg-danger bg-warning bg-info bg-success bg-secondary')
                  .addClass(color)
                  .css('width', strength + '%');
        strengthText.text(text);
    }
    
    function checkPasswordMatch() {
        const password = $('#password').val();
        const confirmPassword = $('#password_confirmation').val();
        const errorDiv = $('#passwordMatchError');
        
        if (confirmPassword.length > 0) {
            if (password !== confirmPassword) {
                $('#password_confirmation').addClass('is-invalid');
                errorDiv.show();
                return false;
            } else {
                $('#password_confirmation').removeClass('is-invalid');
                errorDiv.hide();
                return true;
            }
        } else {
            $('#password_confirmation').removeClass('is-invalid');
            errorDiv.hide();
            return true;
        }
    }
    
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length >= 10) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        } else if (value.length >= 6) {
            value = value.replace(/(\d{3})(\d{3})/, '($1) $2');
        } else if (value.length >= 3) {
            value = value.replace(/(\d{3})/, '($1)');
        }
        
        input.value = value;
    }
    
    function validateForm() {
        let isValid = true;
        
        // Required fields
        const requiredFields = ['first_name', 'last_name', 'email', 'password', 'password_confirmation'];
        
        requiredFields.forEach(function(fieldName) {
            const field = $(`#${fieldName}`);
            if (!field.val().trim()) {
                field.addClass('is-invalid');
                isValid = false;
            } else {
                field.removeClass('is-invalid');
            }
        });
        
        // Email validation
        const email = $('#email').val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
            showAlert('error', 'Por favor ingresa un correo electrónico válido');
            isValid = false;
        }
        
        // Password validation
        const password = $('#password').val();
        if (password.length < 8) {
            $('#password').addClass('is-invalid');
            showAlert('error', 'La contraseña debe tener al menos 8 caracteres');
            isValid = false;
        }
        
        // Password match validation
        if (!checkPasswordMatch()) {
            isValid = false;
        }
        
        // Terms acceptance
        if (!$('#terms').is(':checked')) {
            $('#terms').addClass('is-invalid');
            showAlert('error', 'Debes aceptar los términos y condiciones');
            isValid = false;
        }
        
        // Age validation
        const dateOfBirth = $('#date_of_birth').val();
        if (dateOfBirth) {
            const birthDate = new Date(dateOfBirth);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            
            if (age < 13) {
                $('#date_of_birth').addClass('is-invalid');
                showAlert('error', 'Debes ser mayor de 13 años para registrarte');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    function showLoadingState() {
        $('#registerBtn').prop('disabled', true);
        $('#registerSpinner').removeClass('d-none');
        $('#loadingOverlay').removeClass('d-none');
    }
    
    function hideLoadingState() {
        $('#registerBtn').prop('disabled', false);
        $('#registerSpinner').addClass('d-none');
        $('#loadingOverlay').addClass('d-none');
    }
    
    // Handle form errors
    @if($errors->any())
        $(document).ready(function() {
            hideLoadingState();
            @foreach($errors->all() as $error)
                showAlert('error', '{{ $error }}');
            @endforeach
        });
    @endif
    
    // Handle success messages
    @if(session('success'))
        $(document).ready(function() {
            showAlert('success', '{{ session("success") }}');
        });
    @endif
    
    // Auto-hide loading on page load
    $(window).on('load', function() {
        hideLoadingState();
    });
</script>
@endpush