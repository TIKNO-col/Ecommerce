# 🛒 Laravel E-commerce Platform

Una plataforma de comercio electrónico completa construida con Laravel 12, que incluye gestión de productos, carrito de compras, sistema de pedidos, lista de deseos, reseñas y panel de administración.

## ✨ Características Principales

### 🏪 Funcionalidades del Frontend
- **Catálogo de Productos**: Navegación por categorías, filtros avanzados, búsqueda
- **Carrito de Compras**: Gestión completa del carrito con persistencia
- **Lista de Deseos**: Guardar productos favoritos
- **Sistema de Reseñas**: Calificaciones y comentarios de productos
- **Checkout Completo**: Proceso de compra con múltiples métodos de pago
- **Autenticación**: Registro, login, recuperación de contraseña
- **Panel de Usuario**: Dashboard personal con historial de pedidos

### 🔧 Funcionalidades del Backend
- **Gestión de Productos**: CRUD completo con imágenes y variantes
- **Gestión de Categorías**: Estructura jerárquica de categorías
- **Gestión de Pedidos**: Seguimiento completo del estado de pedidos
- **Panel de Administración**: Dashboard con estadísticas y reportes
- **Sistema de Cupones**: Descuentos y promociones
- **Gestión de Inventario**: Control de stock automático

### 🎨 Características Técnicas
- **Responsive Design**: Compatible con dispositivos móviles
- **SEO Optimizado**: Meta tags, URLs amigables, sitemap
- **API REST**: Endpoints para integración con aplicaciones móviles
- **Seguridad**: Protección CSRF, validación de datos, sanitización
- **Performance**: Cache de consultas, optimización de imágenes

## 🚀 Instalación

### Requisitos Previos
- PHP 8.2 o superior
- Composer
- Node.js y NPM
- PostgreSQL (Supabase) o MySQL
- Extensiones PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd Ecommerce
```

2. **Instalar dependencias de PHP**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos en .env**
```env
DB_CONNECTION=supabase
DB_HOST=your-supabase-host
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=your-username
DB_PASSWORD=your-password

SUPABASE_URL=your-supabase-url
```

5. **Ejecutar migraciones**
```bash
php artisan migrate
```

6. **Sembrar datos de prueba (opcional)**
```bash
php artisan db:seed
```

7. **Instalar dependencias de frontend**
```bash
npm install
npm run build
```

8. **Configurar storage**
```bash
php artisan storage:link
```

9. **Iniciar servidor de desarrollo**
```bash
php artisan serve
```

## 📁 Estructura del Proyecto

```
Ecommerce/
├── app/
│   ├── Http/Controllers/     # Controladores
│   ├── Models/              # Modelos Eloquent
│   └── Services/            # Servicios de negocio
├── database/
│   ├── migrations/          # Migraciones de BD
│   └── seeders/            # Seeders de datos
├── resources/
│   └── views/              # Vistas Blade
│       ├── auth/           # Autenticación
│       ├── cart/           # Carrito
│       ├── checkout/       # Checkout
│       ├── layouts/        # Layouts
│       ├── orders/         # Pedidos
│       ├── products/       # Productos
│       ├── user/           # Panel usuario
│       └── wishlist/       # Lista deseos
├── routes/
│   └── web.php             # Rutas web
└── public/                 # Archivos públicos
```

## 🗄️ Modelos y Relaciones

### Modelos Principales
- **User**: Usuarios del sistema
- **Product**: Productos del catálogo
- **Category**: Categorías de productos
- **Cart**: Carrito de compras
- **Order**: Pedidos
- **OrderItem**: Items de pedidos
- **Wishlist**: Lista de deseos
- **Review**: Reseñas de productos

### Relaciones Clave
```php
// Product
belongsToMany(Category::class)
hasMany(CartItem::class)
hasMany(OrderItem::class)
hasMany(Review::class)
hasMany(Wishlist::class)

// User
hasMany(Cart::class)
hasMany(Order::class)
hasMany(Review::class)
hasMany(Wishlist::class)

// Order
belongsTo(User::class)
hasMany(OrderItem::class)
```

## 🛣️ Rutas Principales

### Rutas Públicas
- `GET /` - Página de inicio
- `GET /products` - Catálogo de productos
- `GET /products/{slug}` - Detalle de producto
- `GET /categories/{slug}` - Productos por categoría
- `GET /cart` - Carrito de compras

### Rutas de Autenticación
- `GET /auth/login` - Formulario de login
- `POST /auth/login` - Procesar login
- `GET /auth/register` - Formulario de registro
- `POST /auth/register` - Procesar registro

### Rutas Autenticadas
- `GET /user/dashboard` - Dashboard del usuario
- `GET /orders` - Historial de pedidos
- `GET /wishlist` - Lista de deseos
- `GET /orders/checkout` - Proceso de checkout

### API Routes
- `GET /api/products/search` - Búsqueda de productos
- `POST /api/cart/add` - Añadir al carrito
- `GET /api/cart` - Obtener carrito

## 🎨 Vistas y Componentes

### Layout Principal
- **app.blade.php**: Layout base con navegación, footer y scripts

### Vistas Principales
- **home.blade.php**: Página de inicio con productos destacados
- **products/index.blade.php**: Listado de productos con filtros
- **products/show.blade.php**: Detalle de producto
- **cart/index.blade.php**: Carrito de compras
- **checkout/index.blade.php**: Proceso de checkout
- **user/dashboard.blade.php**: Panel del usuario

## 🔧 Configuración Adicional

### Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue (Opcional)
```bash
php artisan queue:work
```

### Programador de Tareas
```bash
# Añadir al crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔒 Seguridad

- Validación de datos en todos los formularios
- Protección CSRF en formularios
- Sanitización de entradas
- Autenticación y autorización
- Rate limiting en APIs
- Encriptación de contraseñas

## 📊 Performance

- Eager loading de relaciones
- Cache de consultas frecuentes
- Optimización de imágenes
- Compresión de assets
- CDN para archivos estáticos

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Tests con coverage
php artisan test --coverage
```

## 📝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🆘 Soporte

Si encuentras algún problema o tienes preguntas:

1. Revisa la documentación
2. Busca en los issues existentes
3. Crea un nuevo issue con detalles del problema

## 🚀 Próximas Características

- [ ] Sistema de cupones avanzado
- [ ] Integración con pasarelas de pago
- [ ] Sistema de afiliados
- [ ] Chat en vivo
- [ ] Notificaciones push
- [ ] App móvil
- [ ] Marketplace multi-vendor
- [ ] Sistema de puntos y recompensas

---

**Desarrollado con ❤️ usando Laravel 12**
