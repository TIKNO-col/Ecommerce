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

## 🔧 Comandos Útiles

### Desarrollo
```bash
# Iniciar servidor de desarrollo
php artisan serve

# Compilar assets en modo desarrollo
npm run dev

# Compilar assets en modo watch
npm run watch

# Compilar assets para producción
npm run build
```

### Base de Datos
```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar migraciones con seeders
php artisan migrate --seed

# Rollback de migraciones
php artisan migrate:rollback

# Refrescar base de datos
php artisan migrate:fresh --seed
```

### Cache
```bash
# Limpiar todas las cachés
php artisan optimize:clear

# Limpiar caché específica
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan optimize
```

## 🖼️ Optimización de Imágenes

### Instalación de herramientas de optimización

#### En Windows:
```powershell
# Instalar Chocolatey (si no está instalado)
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Instalar herramientas de optimización
choco install imagemagick
choco install jpegoptim
choco install optipng
choco install gifsicle

# Alternativa con npm
npm install -g imagemin-cli
npm install -g imagemin-mozjpeg
npm install -g imagemin-pngquant
npm install -g imagemin-gifsicle
```

#### En Linux/macOS:
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install imagemagick jpegoptim optipng gifsicle webp

# macOS con Homebrew
brew install imagemagick jpegoptim optipng gifsicle webp

# CentOS/RHEL
sudo yum install ImageMagick jpegoptim optipng gifsicle libwebp-tools
```

### Comandos de optimización

#### Optimizar imágenes JPEG:
```bash
# Optimización básica
jpegoptim --max=85 --strip-all storage/app/public/products/*.jpg

# Optimización agresiva
jpegoptim --max=75 --strip-all --all-progressive storage/app/public/products/*.jpg

# Con ImageMagick
magick mogrify -quality 85 -strip storage/app/public/products/*.jpg
```

#### Optimizar imágenes PNG:
```bash
# Con optipng
optipng -o7 storage/app/public/products/*.png

# Con ImageMagick
magick mogrify -quality 85 -strip storage/app/public/products/*.png

# Convertir PNG a WebP
for file in storage/app/public/products/*.png; do
    cwebp -q 85 "$file" -o "${file%.png}.webp"
done
```

#### Optimizar imágenes GIF:
```bash
# Con gifsicle
gifsicle --optimize=3 --batch storage/app/public/products/*.gif

# Convertir GIF a WebP (solo primer frame)
for file in storage/app/public/products/*.gif; do
    cwebp -q 85 "$file" -o "${file%.gif}.webp"
done
```

#### Conversión masiva a WebP:
```bash
# Convertir todas las imágenes a WebP
find storage/app/public/products -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" | while read file; do
    cwebp -q 85 "$file" -o "${file%.*}.webp"
done
```

#### Redimensionar imágenes:
```bash
# Redimensionar manteniendo proporción (ancho máximo 800px)
magick mogrify -resize 800x800> storage/app/public/products/*.jpg

# Crear thumbnails (200x200px)
mkdir -p storage/app/public/products/thumbnails
for file in storage/app/public/products/*.jpg; do
    magick "$file" -resize 200x200^ -gravity center -extent 200x200 "storage/app/public/products/thumbnails/$(basename "$file")"
done
```

#### Script de optimización completa:
```bash
#!/bin/bash
# optimize-images.sh

IMAGE_DIR="storage/app/public/products"

echo "Optimizando imágenes en $IMAGE_DIR..."

# Optimizar JPEG
echo "Optimizando archivos JPEG..."
jpegoptim --max=85 --strip-all "$IMAGE_DIR"/*.jpg 2>/dev/null
jpegoptim --max=85 --strip-all "$IMAGE_DIR"/*.jpeg 2>/dev/null

# Optimizar PNG
echo "Optimizando archivos PNG..."
optipng -o7 "$IMAGE_DIR"/*.png 2>/dev/null

# Optimizar GIF
echo "Optimizando archivos GIF..."
gifsicle --optimize=3 --batch "$IMAGE_DIR"/*.gif 2>/dev/null

# Convertir a WebP
echo "Convirtiendo a WebP..."
find "$IMAGE_DIR" -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" | while read file; do
    if [ ! -f "${file%.*}.webp" ]; then
        cwebp -q 85 "$file" -o "${file%.*}.webp"
    fi
done

echo "Optimización completada!"
```

### Comandos Artisan personalizados

#### Crear comando para optimización automática:
```bash
php artisan make:command OptimizeImages
```

#### Uso del comando personalizado:
```bash
# Optimizar todas las imágenes
php artisan images:optimize

# Optimizar solo imágenes nuevas
php artisan images:optimize --new-only

# Generar WebP para todas las imágenes
php artisan images:webp

# Generar thumbnails
php artisan images:thumbnails
```

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
