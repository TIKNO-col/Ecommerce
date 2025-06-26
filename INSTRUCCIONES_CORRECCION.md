# Instrucciones para Corregir los Errores

## Errores Corregidos

### 1. Error de Variable `$newProducts` No Definida
- **Problema**: El controlador `HomeController` pasaba `$newArrivals` pero la vista esperaba `$newProducts`
- **Solución**: Se corrigió el controlador para pasar la variable con el nombre correcto

### 2. Errores en AuthController (Campos Faltantes)
- **Problema**: El `AuthController` hacía referencia a campos que no existían en la tabla `users` y modelo `Brand`
- **Soluciones aplicadas**:
  - Se creó migración para agregar campos faltantes a la tabla `users`
  - Se creó migración para la tabla `brands`
  - Se creó migración para agregar `brand_id` a la tabla `products`
  - Se creó el modelo `Brand`
  - Se actualizó el modelo `User` con los nuevos campos
  - Se actualizó el modelo `Product` para incluir la relación con `Brand`

## Comandos a Ejecutar

Para aplicar todas las correcciones, ejecuta los siguientes comandos en la terminal:

```bash
# 1. Ejecutar las migraciones
php artisan migrate

# 2. Ejecutar los seeders (opcional, para datos de prueba)
php artisan db:seed

# 3. Limpiar caché de configuración
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 4. Iniciar el servidor
php artisan serve
```

## Archivos Modificados/Creados

### Archivos Modificados:
1. `app/Http/Controllers/HomeController.php` - Corregida variable `$newProducts`
2. `app/Models/User.php` - Agregados nuevos campos
3. `app/Models/Product.php` - Agregada relación con Brand
4. `database/seeders/DatabaseSeeder.php` - Incluido BrandSeeder

### Archivos Creados:
1. `database/migrations/2024_01_01_000009_add_fields_to_users_table.php`
2. `database/migrations/2024_01_01_000010_create_brands_table.php`
3. `database/migrations/2024_01_01_000011_add_brand_id_to_products_table.php`
4. `app/Models/Brand.php`
5. `database/seeders/BrandSeeder.php`

## Verificación

Después de ejecutar los comandos:
1. Visita `http://127.0.0.1:8000` para verificar que no hay errores
2. El error de `$newProducts` debería estar resuelto
3. Los errores del `AuthController` relacionados con campos faltantes deberían estar corregidos

## Notas Importantes

- Las migraciones agregarán los campos necesarios sin afectar datos existentes
- El modelo `Brand` incluye funcionalidades completas para manejo de marcas
- Los seeders crearán datos de prueba para las marcas
- Todos los cambios son compatibles con la estructura existente del proyecto