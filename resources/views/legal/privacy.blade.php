@extends('layouts.app')

@section('title', 'Política de Privacidad')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="text-center mb-5">Política de Privacidad</h1>
                    
                    <div class="mb-4">
                        <h3>1. Información que Recopilamos</h3>
                        <p>Recopilamos información que usted nos proporciona directamente, como cuando crea una cuenta, realiza una compra o se comunica con nosotros. Esto puede incluir:</p>
                        <ul>
                            <li>Nombre y información de contacto</li>
                            <li>Información de facturación y envío</li>
                            <li>Historial de compras</li>
                            <li>Preferencias de comunicación</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h3>2. Cómo Utilizamos su Información</h3>
                        <p>Utilizamos la información recopilada para:</p>
                        <ul>
                            <li>Procesar y completar sus pedidos</li>
                            <li>Proporcionar atención al cliente</li>
                            <li>Enviar comunicaciones relacionadas con su cuenta</li>
                            <li>Mejorar nuestros productos y servicios</li>
                            <li>Cumplir con obligaciones legales</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h3>3. Compartir Información</h3>
                        <p>No vendemos, intercambiamos ni transferimos su información personal a terceros sin su consentimiento, excepto en los siguientes casos:</p>
                        <ul>
                            <li>Proveedores de servicios que nos ayudan a operar nuestro sitio web</li>
                            <li>Cuando sea requerido por ley</li>
                            <li>Para proteger nuestros derechos o la seguridad de otros</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h3>4. Seguridad de Datos</h3>
                        <p>Implementamos medidas de seguridad apropiadas para proteger su información personal contra acceso no autorizado, alteración, divulgación o destrucción.</p>
                    </div>

                    <div class="mb-4">
                        <h3>5. Cookies</h3>
                        <p>Utilizamos cookies para mejorar su experiencia en nuestro sitio web. Puede configurar su navegador para rechazar cookies, aunque esto puede afectar la funcionalidad del sitio.</p>
                    </div>

                    <div class="mb-4">
                        <h3>6. Sus Derechos</h3>
                        <p>Usted tiene derecho a:</p>
                        <ul>
                            <li>Acceder a su información personal</li>
                            <li>Corregir información inexacta</li>
                            <li>Solicitar la eliminación de su información</li>
                            <li>Oponerse al procesamiento de su información</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h3>7. Cambios a esta Política</h3>
                        <p>Podemos actualizar esta política de privacidad ocasionalmente. Le notificaremos sobre cambios significativos publicando la nueva política en esta página.</p>
                    </div>

                    <div class="mb-4">
                        <h3>8. Contacto</h3>
                        <p>Si tiene preguntas sobre esta política de privacidad, puede contactarnos a través de:</p>
                        <ul>
                            <li>Email: privacy@ecommerce.com</li>
                            <li>Teléfono: +1 (555) 123-4567</li>
                            <li>Dirección: 123 Calle Principal, Ciudad, País</li>
                        </ul>
                    </div>

                    <div class="text-muted">
                        <small>Última actualización: {{ date('d/m/Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection