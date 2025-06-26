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
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'is_admin' => true,
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
        $productsData = [
            [
                'product' => [
                    'name' => 'iPhone 15 Pro',
                    'slug' => 'iphone-15-pro',
                    'description' => 'El iPhone más avanzado con chip A17 Pro y cámara profesional',
                    'short_description' => 'iPhone 15 Pro con chip A17 Pro',
                    'sku' => 'IPH-15-PRO-001',
                    'price' => 999.99,
                    'stock_quantity' => 50,
                    'brand_id' => 1,
                    'is_featured' => true,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=400&h=400&fit=crop']),
                    'gallery' => json_encode([
                        'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=400&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=400&h=400&fit=crop'
                    ]),
                ],
                'categories' => [1]
            ],
            [
                'product' => [
                    'name' => 'Samsung Galaxy S24',
                    'slug' => 'samsung-galaxy-s24',
                    'description' => 'Smartphone Android premium con IA integrada',
                    'short_description' => 'Galaxy S24 con IA integrada',
                    'sku' => 'SAM-S24-001',
                    'price' => 899.99,
                    'stock_quantity' => 30,
                    'brand_id' => 2,
                    'is_featured' => true,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400&h=400&fit=crop']),
                    'gallery' => json_encode([
                        'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1565849904461-04a58ad377e0?w=400&h=400&fit=crop'
                    ]),
                ],
                'categories' => [1]
            ],
            [
                'product' => [
                    'name' => 'Nike Air Max 270',
                    'slug' => 'nike-air-max-270',
                    'description' => 'Zapatillas deportivas con máxima comodidad',
                    'short_description' => 'Air Max 270 - Máxima comodidad',
                    'sku' => 'NIK-AM270-001',
                    'price' => 149.99,
                    'stock_quantity' => 100,
                    'brand_id' => 3,
                    'is_featured' => true,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=400&fit=crop']),
                    'gallery' => json_encode([
                        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&h=400&fit=crop'
                    ]),
                ],
                'categories' => [4]
            ],
            [
                'product' => [
                    'name' => 'Chaqueta Adidas Originals',
                    'slug' => 'chaqueta-adidas-originals',
                    'description' => 'Chaqueta deportiva clásica de Adidas',
                    'short_description' => 'Chaqueta Adidas clásica',
                    'sku' => 'ADI-CHQ-001',
                    'price' => 79.99,
                    'stock_quantity' => 75,
                    'brand_id' => 4,
                    'is_featured' => false,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=400&h=400&fit=crop']),
                ],
                'categories' => [2]
            ],
            [
                'product' => [
                    'name' => 'Vestido Zara Elegante',
                    'slug' => 'vestido-zara-elegante',
                    'description' => 'Vestido elegante para ocasiones especiales',
                    'short_description' => 'Vestido elegante Zara',
                    'sku' => 'ZAR-VES-001',
                    'price' => 59.99,
                    'stock_quantity' => 40,
                    'brand_id' => 5,
                    'is_featured' => true,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&h=400&fit=crop']),
                ],
                'categories' => [2]
            ],
            [
                'product' => [
                    'name' => 'Mesa IKEA Moderna',
                    'slug' => 'mesa-ikea-moderna',
                    'description' => 'Mesa de comedor moderna y funcional',
                    'short_description' => 'Mesa IKEA moderna',
                    'sku' => 'IKE-MES-001',
                    'price' => 199.99,
                    'stock_quantity' => 20,
                    'brand_id' => 6,
                    'is_featured' => false,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=400&fit=crop']),
                ],
                'categories' => [3]
            ],
            [
                'product' => [
                    'name' => 'MacBook Pro 14"',
                    'slug' => 'macbook-pro-14',
                    'description' => 'Laptop profesional con chip M3 Pro',
                    'short_description' => 'MacBook Pro 14" M3 Pro',
                    'sku' => 'APL-MBP14-001',
                    'price' => 1999.99,
                    'stock_quantity' => 15,
                    'brand_id' => 1,
                    'is_featured' => true,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&h=400&fit=crop']),
                ],
                'categories' => [1]
            ],
            [
                'product' => [
                    'name' => 'Auriculares Samsung Galaxy Buds',
                    'slug' => 'samsung-galaxy-buds',
                    'description' => 'Auriculares inalámbricos con cancelación de ruido',
                    'short_description' => 'Galaxy Buds inalámbricos',
                    'sku' => 'SAM-GBD-001',
                    'price' => 129.99,
                    'stock_quantity' => 80,
                    'brand_id' => 2,
                    'is_featured' => false,
                    'is_active' => true,
                    'images' => json_encode(['https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=400&h=400&fit=crop']),
                ],
                'categories' => [1]
            ],
        ];

        foreach ($productsData as $productData) {
            $product = Product::create($productData['product']);
            $product->categories()->attach($productData['categories']);
        }
    }
}
