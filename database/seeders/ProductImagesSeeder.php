<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Rutas de las imágenes originales
        $sourceImages = [
            'imagen1.jpg',
            'imagen2.jpg', 
            'imagen3.jpeg',
            'imagen4.jpg'
        ];
        
        $sourcePath = resource_path('Images');
        $destinationPath = public_path('storage/products');
        
        // Verificar que el directorio de destino existe
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }
        
        // Obtener todos los productos
        $products = Product::all();
        
        if ($products->isEmpty()) {
            $this->command->info('No hay productos en la base de datos.');
            return;
        }
        
        // Limpiar imágenes existentes
        ProductImage::truncate();
        
        $imageIndex = 0;
        
        foreach ($products as $product) {
            // Asignar 1-3 imágenes por producto de forma cíclica
            $numImages = rand(1, 3);
            
            for ($i = 0; $i < $numImages; $i++) {
                $sourceImage = $sourceImages[$imageIndex % count($sourceImages)];
                $sourceFile = $sourcePath . DIRECTORY_SEPARATOR . $sourceImage;
                
                if (File::exists($sourceFile)) {
                    // Generar nombre único para la imagen
                    $extension = pathinfo($sourceImage, PATHINFO_EXTENSION);
                    $newFileName = 'product_' . $product->id . '_' . ($i + 1) . '_' . time() . '.' . $extension;
                    $destinationFile = $destinationPath . DIRECTORY_SEPARATOR . $newFileName;
                    
                    // Copiar la imagen
                    File::copy($sourceFile, $destinationFile);
                    
                    // Crear registro en la base de datos
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => 'products/' . $newFileName,
                        'alt_text' => $product->name . ' - Imagen ' . ($i + 1),
                        'sort_order' => $i + 1,
                        'is_primary' => $i === 0 // La primera imagen es la principal
                    ]);
                    
                    $this->command->info("Imagen {$newFileName} asignada al producto: {$product->name}");
                }
                
                $imageIndex++;
            }
        }
        
        $this->command->info('Imágenes de productos asignadas exitosamente.');
    }
}