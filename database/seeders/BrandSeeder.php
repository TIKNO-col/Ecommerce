<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'Innovative technology products',
                'website' => 'https://www.apple.com',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'description' => 'Electronics and technology solutions',
                'website' => 'https://www.samsung.com',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Athletic footwear and apparel',
                'website' => 'https://www.nike.com',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Sports clothing and accessories',
                'website' => 'https://www.adidas.com',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'description' => 'Electronics and entertainment',
                'website' => 'https://www.sony.com',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::updateOrCreate(
                ['slug' => $brandData['slug']],
                $brandData
            );
        }
    }
}