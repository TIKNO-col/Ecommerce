@extends('layouts.app')

@section('title', 'Iniciar Sesión')
@section('description', 'Inicia sesión en tu cuenta para acceder a todas las funcionalidades.')

@section('content')
<div class="auth-container" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') center/cover; min-height: 100vh; display: flex; align-items: center;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0" style="backdrop-filter: blur(10px); background: rgba(255,255,255,0.95);">
                    <div class="card-body p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Bienvenido de vuelta</h2>
                        <p class="text-muted">Inicia sesión en tu cuenta</p>
                    </div>
                    

                    
                    <!-- Login Form -->
                    <form method="POST" action="{{ route('auth.login.submit') }}" id="loginForm">
                        @csrf
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" 
                                       placeholder="tu@email.com" required autocomplete="email" autofocus>
                            </div>
                            @error('email')
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
                                       id="password" name="password" placeholder="Tu contraseña" 
                                       required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                                       {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Recordarme
                                </label>
                            </div>
                            <a href="{{ route('password.request') }}" class="text-decoration-none">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="loginSpinner"></span>
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </div>
                        
                        <!-- Register Link -->
                        <div class="text-center">
                            <p class="mb-0">¿No tienes una cuenta? 
                                <a href="{{ route('register') }}" class="text-decoration-none fw-bold">Regístrate aquí</a>
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
                </a></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay d-none" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-3 text-white">Iniciando sesión...</div>
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
    
    .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
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
    
    @media (max-width: 576px) {
        .card-body {
            padding: 2rem !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordField = $('#password');
            const passwordIcon = $('#togglePasswordIcon');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                passwordIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                passwordIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Form validation
        $('#loginForm').on('submit', function(e) {
            const email = $('#email').val().trim();
            const password = $('#password').val();
            
            // Basic validation
            if (!email || !password) {
                e.preventDefault();
                showAlert('error', 'Por favor completa todos los campos');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showAlert('error', 'Por favor ingresa un correo electrónico válido');
                return false;
            }
            
            // Show loading state
            showLoadingState();
        });
        
        // Auto-focus on email field if empty
        if (!$('#email').val()) {
            $('#email').focus();
        }
        
        // Handle social login clicks
        $('.social-btn').click(function() {
            showLoadingState();
        });
    });
    
    function showLoadingState() {
        $('#loginBtn').prop('disabled', true);
        $('#loginSpinner').removeClass('d-none');
        $('#loadingOverlay').removeClass('d-none');
    }
    
    function hideLoadingState() {
        $('#loginBtn').prop('disabled', false);
        $('#loginSpinner').addClass('d-none');
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
    
    // Handle info messages
    @if(session('info'))
        $(document).ready(function() {
            showAlert('info', '{{ session("info") }}');
        });
    @endif
    
    // Auto-hide loading on page load
    $(window).on('load', function() {
        hideLoadingState();
    });
</script>
@endpush