@extends('layouts.app')

@section('title', 'Contacto')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold">Contáctanos</h1>
                <p class="lead text-muted">¿Tienes alguna pregunta? Nos encantaría escucharte.</p>
            </div>

            <div class="row g-4">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="card-title mb-4">Envíanos un mensaje</h3>
                            
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form action="{{ route('contact.submit') }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nombre *</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Asunto *</label>
                                        <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                               id="subject" name="subject" value="{{ old('subject') }}" required>
                                        @error('subject')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Mensaje *</label>
                                        <textarea class="form-control @error('message') is-invalid @enderror" 
                                                  id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-4">
                            <h3 class="card-title mb-4">Información de Contacto</h3>
                            
                            <div class="contact-info">
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-map-marker-alt text-primary me-3"></i>
                                        <h6 class="mb-0">Dirección</h6>
                                    </div>
                                    <p class="text-muted ms-4 mb-0">
                                        Calle Principal 123<br>
                                        Ciudad, Estado 12345
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-phone text-primary me-3"></i>
                                        <h6 class="mb-0">Teléfono</h6>
                                    </div>
                                    <p class="text-muted ms-4 mb-0">
                                        <a href="tel:+1234567890" class="text-decoration-none">+1 (234) 567-890</a>
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-envelope text-primary me-3"></i>
                                        <h6 class="mb-0">Email</h6>
                                    </div>
                                    <p class="text-muted ms-4 mb-0">
                                        <a href="mailto:info@ecommerce.com" class="text-decoration-none">info@ecommerce.com</a>
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock text-primary me-3"></i>
                                        <h6 class="mb-0">Horarios</h6>
                                    </div>
                                    <p class="text-muted ms-4 mb-0">
                                        Lun - Vie: 9:00 AM - 6:00 PM<br>
                                        Sáb: 10:00 AM - 4:00 PM<br>
                                        Dom: Cerrado
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection