<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@ecommerce.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create test user
        User::create([
            'name' => 'Usuario Test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create categories
        $categories = [
            ['name' => 'Electrónicos', 'slug' => 'electronicos', 'description' => 'Dispositivos electrónicos y gadgets'],
            ['name' => 'Ropa y Moda', 'slug' => 'ropa-moda', 'description' => 'Ropa y accesorios de moda'],
            ['name' => 'Hogar y Jardín', 'slug' => 'hogar-jardin', 'description' => 'Artículos para el hogar y jardín'],
            ['name' => 'Deportes', 'slug' => 'deportes', 'description' => 'Equipos y ropa deportiva'],
            ['name' => 'Libros', 'slug' => 'libros', 'description' => 'Libros y material de lectura'],
            ['name' => 'Belleza', 'slug' => 'belleza', 'description' => 'Productos de belleza y cuidado personal'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Run brand seeder
        $this->call(BrandSeeder::class);

        // Create additional brands if needed
        $additionalBrands = [
            ['name' => 'Zara', 'slug' => 'zara', 'description' => 'Moda contemporánea', 'is_active' => true, 'sort_order' => 6],
            ['name' => 'IKEA', 'slug' => 'ikea', 'description' => 'Muebles y decoración', 'is_active' => true, 'sort_order' => 7],
        ];

        foreach ($additionalBrands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }

        // Create products
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'El iPhone más avanzado con chip A17 Pro y cámara profesional',
                'short_description' => 'iPhone 15 Pro con chip A17 Pro',
                'sku' => 'IPH-15-PRO-001',
                'price' => 999.99,
                'stock_quantity' => 50,
                'category_id' => 1,
                'brand_id' => 1,
                'is_featured' => true,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/007bff/ffffff?text=iPhone+15+Pro']),
                'gallery' => json_encode([
                    'https://via.placeholder.com/400x400/007bff/ffffff?text=iPhone+15+Pro+1',
                    'https://via.placeholder.com/400x400/28a745/ffffff?text=iPhone+15+Pro+2',
                    'https://via.placeholder.com/400x400/dc3545/ffffff?text=iPhone+15+Pro+3'
                ]),
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'slug' => 'samsung-galaxy-s24',
                'description' => 'Smartphone Android premium con IA integrada',
                'short_description' => 'Galaxy S24 con IA integrada',
                'sku' => 'SAM-S24-001',
                'price' => 899.99,
                'stock_quantity' => 30,
                'category_id' => 1,
                'brand_id' => 2,
                'is_featured' => true,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/6f42c1/ffffff?text=Galaxy+S24']),
                'gallery' => json_encode([
                    'https://via.placeholder.com/400x400/6f42c1/ffffff?text=Galaxy+S24+1',
                    'https://via.placeholder.com/400x400/20c997/ffffff?text=Galaxy+S24+2'
                ]),
            ],
            [
                'name' => 'Nike Air Max 270',
                'slug' => 'nike-air-max-270',
                'description' => 'Zapatillas deportivas con máxima comodidad',
                'short_description' => 'Air Max 270 - Máxima comodidad',
                'sku' => 'NIK-AM270-001',
                'price' => 149.99,
                'stock_quantity' => 100,
                'category_id' => 4,
                'brand_id' => 3,
                'is_featured' => true,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/fd7e14/ffffff?text=Nike+Air+Max']),
                'gallery' => json_encode([
                    'https://via.placeholder.com/400x400/fd7e14/ffffff?text=Nike+Air+Max+1',
                    'https://via.placeholder.com/400x400/e83e8c/ffffff?text=Nike+Air+Max+2'
                ]),
            ],
            [
                'name' => 'Chaqueta Adidas Originals',
                'slug' => 'chaqueta-adidas-originals',
                'description' => 'Chaqueta deportiva clásica de Adidas',
                'short_description' => 'Chaqueta Adidas clásica',
                'sku' => 'ADI-CHQ-001',
                'price' => 79.99,
                'stock_quantity' => 75,
                'category_id' => 2,
                'brand_id' => 4,
                'is_featured' => false,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/343a40/ffffff?text=Adidas+Jacket']),
            ],
            [
                'name' => 'Vestido Zara Elegante',
                'slug' => 'vestido-zara-elegante',
                'description' => 'Vestido elegante para ocasiones especiales',
                'short_description' => 'Vestido elegante Zara',
                'sku' => 'ZAR-VES-001',
                'price' => 59.99,
                'stock_quantity' => 40,
                'category_id' => 2,
                'brand_id' => 5,
                'is_featured' => true,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/e83e8c/ffffff?text=Zara+Dress']),
            ],
            [
                'name' => 'Mesa IKEA Moderna',
                'slug' => 'mesa-ikea-moderna',
                'description' => 'Mesa de comedor moderna y funcional',
                'short_description' => 'Mesa IKEA moderna',
                'sku' => 'IKE-MES-001',
                'price' => 199.99,
                'stock_quantity' => 20,
                'category_id' => 3,
                'brand_id' => 6,
                'is_featured' => false,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/17a2b8/ffffff?text=IKEA+Table']),
            ],
            [
                'name' => 'MacBook Pro 14"',
                'slug' => 'macbook-pro-14',
                'description' => 'Laptop profesional con chip M3 Pro',
                'short_description' => 'MacBook Pro 14" M3 Pro',
                'sku' => 'APL-MBP14-001',
                'price' => 1999.99,
                'stock_quantity' => 15,
                'category_id' => 1,
                'brand_id' => 1,
                'is_featured' => true,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/6c757d/ffffff?text=MacBook+Pro']),
            ],
            [
                'name' => 'Auriculares Samsung Galaxy Buds',
                'slug' => 'samsung-galaxy-buds',
                'description' => 'Auriculares inalámbricos con cancelación de ruido',
                'short_description' => 'Galaxy Buds inalámbricos',
                'sku' => 'SAM-GBD-001',
                'price' => 129.99,
                'stock_quantity' => 80,
                'category_id' => 1,
                'brand_id' => 2,
                'is_featured' => false,
                'is_active' => true,
                'images' => json_encode(['https://via.placeholder.com/400x400/ffc107/000000?text=Galaxy+Buds']),
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
