<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display a listing of products for admin.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['categories', 'brand']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20);
        $categories = Category::where('parent_id', null)->get();
        $brands = Brand::all();

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::all();
        $brands = Brand::all();
        
        return view('admin.products.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sku' => 'required|string|unique:products,sku',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create product
            $product = new Product();
            $product->name = $request->name;
            $product->slug = Str::slug($request->name);
            $product->description = $request->description;
            $product->short_description = $request->short_description;
            $product->price = $request->price;
            $product->sale_price = $request->sale_price;
            $product->sku = $request->sku;
            $product->stock_quantity = $request->stock_quantity;
            $product->weight = $request->weight;
            $product->dimensions = $request->dimensions;
            $product->brand_id = $request->brand_id;
            $product->is_active = $request->boolean('is_active', true);
            $product->is_featured = $request->boolean('is_featured', false);
            $product->meta_title = $request->meta_title;
            $product->meta_description = $request->meta_description;

            // Handle images
            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('products', $filename, 'public');
                    $images[] = Storage::url($path);
                }
            }
            $product->images = $images;
            $product->image_url = $images[0] ?? null;

            $product->save();

            // Attach categories
            $product->categories()->attach($request->categories);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'product' => $product->load(['categories', 'brand'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $product->load(['categories', 'brand', 'reviews.user']);
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $categories = Category::all();
        $brands = Brand::all();
        $product->load(['categories', 'brand']);
        
        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'new_images' => 'nullable|array|max:5',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'existing_images' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update product
            $product->name = $request->name;
            $product->slug = Str::slug($request->name);
            $product->description = $request->description;
            $product->short_description = $request->short_description;
            $product->price = $request->price;
            $product->sale_price = $request->sale_price;
            $product->sku = $request->sku;
            $product->stock_quantity = $request->stock_quantity;
            $product->weight = $request->weight;
            $product->dimensions = $request->dimensions;
            $product->brand_id = $request->brand_id;
            $product->is_active = $request->boolean('is_active', true);
            $product->is_featured = $request->boolean('is_featured', false);
            $product->meta_title = $request->meta_title;
            $product->meta_description = $request->meta_description;

            // Handle images
            $images = $request->existing_images ?? [];
            
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $index => $image) {
                    $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('products', $filename, 'public');
                    $images[] = Storage::url($path);
                }
            }
            
            $product->images = $images;
            $product->image_url = $images[0] ?? null;

            $product->save();

            // Sync categories
            $product->categories()->sync($request->categories);

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'product' => $product->load(['categories', 'brand'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Delete associated images
            if ($product->images) {
                foreach ($product->images as $image) {
                    $imagePath = str_replace('/storage/', '', $image);
                    Storage::disk('public')->delete($imagePath);
                }
            }

            // Detach categories
            $product->categories()->detach();

            // Delete product
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update products.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,feature,unfeature,delete',
        ]);

        try {
            $products = Product::whereIn('id', $request->product_ids);
            $count = $products->count();

            switch ($request->action) {
                case 'activate':
                    $products->update(['is_active' => true]);
                    $message = "Se activaron {$count} productos";
                    break;
                case 'deactivate':
                    $products->update(['is_active' => false]);
                    $message = "Se desactivaron {$count} productos";
                    break;
                case 'feature':
                    $products->update(['is_featured' => true]);
                    $message = "Se destacaron {$count} productos";
                    break;
                case 'unfeature':
                    $products->update(['is_featured' => false]);
                    $message = "Se quitaron de destacados {$count} productos";
                    break;
                case 'delete':
                    $products->delete();
                    $message = "Se eliminaron {$count} productos";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la operación masiva: ' . $e->getMessage()
            ], 500);
        }
    }
}