@extends('layouts.app')

@section('title', 'Desuscribirse del Newsletter')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                        <h2 class="h4 mb-2">Desuscribirse del Newsletter</h2>
                        <p class="text-muted">Lamentamos verte partir. Confirma tu email para desuscribirte.</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('newsletter.unsubscribe.submit') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', request('email')) }}" 
                                   required 
                                   placeholder="tu@email.com">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-unlink me-2"></i>
                                Confirmar Desuscripción
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver al Inicio
                            </a>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            ¿Cambiaste de opinión? 
                            <a href="{{ route('home') }}#newsletter" class="text-decoration-none">
                                Mantener suscripción
                            </a>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-comment-dots me-2"></i>
                        ¿Por qué te desuscribes?
                    </h5>
                    <p class="card-text text-muted mb-3">
                        Tu opinión nos ayuda a mejorar. Selecciona una razón (opcional):
                    </p>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reason1">
                                <label class="form-check-label" for="reason1">
                                    Demasiados emails
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reason2">
                                <label class="form-check-label" for="reason2">
                                    Contenido no relevante
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reason3">
                                <label class="form-check-label" for="reason3">
                                    Ya no me interesa
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reason4">
                                <label class="form-check-label" for="reason4">
                                    Otro motivo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.btn {
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 500;
}

.form-control {
    border-radius: 8px;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.alert {
    border-radius: 10px;
    border: none;
}
</style>
@endsection