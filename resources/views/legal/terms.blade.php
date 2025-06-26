@extends('layouts.app')

@section('title', 'Términos y Condiciones')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-file-contract me-2"></i>
                        Términos y Condiciones
                    </h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-muted mb-4">
                                <strong>Última actualización:</strong> {{ date('d/m/Y') }}
                            </p>
                            
                            <div class="terms-content">
                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">1. Aceptación de los Términos</h2>
                                    <p>Al acceder y utilizar este sitio web de comercio electrónico, usted acepta estar sujeto a estos términos y condiciones de uso. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestro sitio web.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">2. Uso del Sitio Web</h2>
                                    <p>Usted se compromete a utilizar este sitio web únicamente para fines legales y de manera que no infrinja los derechos de terceros o restrinja o inhiba el uso y disfrute del sitio web por parte de cualquier tercero.</p>
                                    <ul>
                                        <li>No debe usar el sitio para actividades ilegales o no autorizadas</li>
                                        <li>Debe proporcionar información precisa y actualizada</li>
                                        <li>Es responsable de mantener la confidencialidad de su cuenta</li>
                                    </ul>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">3. Productos y Servicios</h2>
                                    <p>Nos esforzamos por mostrar los colores y las imágenes de nuestros productos con la mayor precisión posible. Sin embargo, no podemos garantizar que la visualización de cualquier color en su monitor sea precisa.</p>
                                    <p>Nos reservamos el derecho de limitar las cantidades de cualquier producto o servicio que ofrecemos y de descontinuar cualquier producto en cualquier momento.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">4. Precios y Pagos</h2>
                                    <p>Todos los precios están sujetos a cambios sin previo aviso. Nos reservamos el derecho de modificar o descontinuar cualquier producto sin previo aviso.</p>
                                    <p>Los pagos deben realizarse en el momento de la compra utilizando los métodos de pago aceptados en nuestro sitio web.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">5. Envío y Entrega</h2>
                                    <p>Los tiempos de entrega son estimados y pueden variar según la ubicación y disponibilidad del producto. No somos responsables de retrasos causados por circunstancias fuera de nuestro control.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">6. Devoluciones y Reembolsos</h2>
                                    <p>Aceptamos devoluciones dentro de los 30 días posteriores a la compra, siempre que los productos estén en su estado original y sin usar. Los gastos de envío de devolución corren por cuenta del cliente, excepto en casos de productos defectuosos.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">7. Privacidad</h2>
                                    <p>Su privacidad es importante para nosotros. Consulte nuestra Política de Privacidad para obtener información sobre cómo recopilamos, utilizamos y protegemos su información personal.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">8. Limitación de Responsabilidad</h2>
                                    <p>En ningún caso seremos responsables de daños indirectos, incidentales, especiales o consecuentes que resulten del uso o la imposibilidad de usar nuestros productos o servicios.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">9. Modificaciones</h2>
                                    <p>Nos reservamos el derecho de modificar estos términos y condiciones en cualquier momento. Las modificaciones entrarán en vigor inmediatamente después de su publicación en el sitio web.</p>
                                </section>

                                <section class="mb-5">
                                    <h2 class="h4 text-primary mb-3">10. Contacto</h2>
                                    <p>Si tiene alguna pregunta sobre estos términos y condiciones, puede contactarnos a través de:</p>
                                    <ul>
                                        <li>Email: info@ecommerce.com</li>
                                        <li>Teléfono: +1 (555) 123-4567</li>
                                        <li>Dirección: 123 Calle Principal, Ciudad, País</li>
                                    </ul>
                                </section>
                            </div>
                            
                            <div class="text-center mt-5">
                                <button type="button" class="btn btn-secondary" onclick="window.close()">
                                    <i class="fas fa-times me-2"></i>
                                    Cerrar
                                </button>
                                <a href="{{ route('home') }}" class="btn btn-primary ms-2">
                                    <i class="fas fa-home me-2"></i>
                                    Volver al Inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.terms-content {
    line-height: 1.6;
}

.terms-content h2 {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.terms-content ul {
    padding-left: 1.5rem;
}

.terms-content li {
    margin-bottom: 0.5rem;
}
</style>
@endpush