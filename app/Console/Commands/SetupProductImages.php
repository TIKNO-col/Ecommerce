<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\File;

class SetupProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:setup-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup product images from resources/Images folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up product images...');
        
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
            $this->info('Created directory: ' . $destinationPath);
        }
        
        // Copiar imagenlogo.jpg a public
        $logoSource = $sourcePath . DIRECTORY_SEPARATOR . 'imagenlogo.jpg';
        $logoDestination = public_path('imagenlogo.jpg');
        
        if (File::exists($logoSource)) {
            File::copy($logoSource, $logoDestination);
            $this->info('Logo image copied to public folder');
        }
        
        // Obtener todos los productos
        $products = Product::all();
        
        if ($products->isEmpty()) {
            $this->warn('No products found in database.');
            return;
        }
        
        // Limpiar imágenes existentes
        ProductImage::truncate();
        $this->info('Cleared existing product images');
        
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
                    $newFileName = 'product_' . $product->id . '_' . ($i + 1) . '_' . time() . rand(100, 999) . '.' . $extension;
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
                    
                    $this->info("Image {$newFileName} assigned to product: {$product->name}");
                } else {
                    $this->warn("Source image not found: {$sourceFile}");
                }
                
                $imageIndex++;
            }
        }
        
        $this->info('Product images setup completed successfully!');
        
        return 0;
    }
}