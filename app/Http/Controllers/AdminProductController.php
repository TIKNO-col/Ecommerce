<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'categories', 'images']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Brand filter
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'featured') {
                $query->where('is_featured', true);
            } elseif ($request->status === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::active()->get();
        $brands = Brand::active()->get();

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        
        return view('admin.products.create', compact('categories', 'brands'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sku' => 'nullable|string|unique:products,sku',
            'stock_quantity' => 'required|integer|min:0',
            'brand_id' => 'required|exists:brands,id',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'manage_stock' => 'boolean',
        ]);

        // Generate SKU if not provided
        if (empty($validated['sku'])) {
            $validated['sku'] = 'PRD-' . strtoupper(Str::random(8));
        }

        // Generate slug
        $validated['slug'] = Str::slug($validated['name']);
        
        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Product::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Handle stock management
        $validated['in_stock'] = $validated['manage_stock'] ? $validated['stock_quantity'] > 0 : true;

        // Handle image uploads
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imageUrls[] = $path;
            }
        }
        $validated['images'] = $imageUrls;

        // Create product
        $product = Product::create($validated);

        // Attach categories
        $product->categories()->attach($validated['categories']);

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['brand', 'categories', 'images']);
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $product->load(['categories']);
        
        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'stock_quantity' => 'required|integer|min:0',
            'brand_id' => 'required|exists:brands,id',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'manage_stock' => 'boolean',
        ]);

        // Update slug if name changed
        if ($product->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Ensure unique slug
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Handle stock management
        $validated['in_stock'] = $validated['manage_stock'] ? $validated['stock_quantity'] > 0 : true;

        // Handle new image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($product->images) {
                foreach ($product->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            // Upload new images
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imageUrls[] = $path;
            }
            $validated['images'] = $imageUrls;
        }

        // Update product
        $product->update($validated);

        // Sync categories
        $product->categories()->sync($validated['categories']);

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete associated images
        if ($product->images) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        // Delete product
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Toggle product status (active/inactive).
     */
    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        
        $status = $product->is_active ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "Producto {$status} exitosamente.",
            'is_active' => $product->is_active
        ]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => !$product->is_featured]);
        
        $status = $product->is_featured ? 'destacado' : 'removido de destacados';
        return response()->json([
            'success' => true,
            'message' => "Producto {$status} exitosamente.",
            'is_featured' => $product->is_featured
        ]);
    }

    /**
     * Bulk actions for products.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,feature,unfeature,delete',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id'
        ]);

        $products = Product::whereIn('id', $request->products);
        $count = $products->count();

        switch ($request->action) {
            case 'activate':
                $products->update(['is_active' => true]);
                $message = "{$count} productos activados exitosamente.";
                break;
            case 'deactivate':
                $products->update(['is_active' => false]);
                $message = "{$count} productos desactivados exitosamente.";
                break;
            case 'feature':
                $products->update(['is_featured' => true]);
                $message = "{$count} productos destacados exitosamente.";
                break;
            case 'unfeature':
                $products->update(['is_featured' => false]);
                $message = "{$count} productos removidos de destacados exitosamente.";
                break;
            case 'delete':
                // Delete associated images
                foreach ($products->get() as $product) {
                    if ($product->images) {
                        foreach ($product->images as $image) {
                            Storage::disk('public')->delete($image);
                        }
                    }
                }
                $products->delete();
                $message = "{$count} productos eliminados exitosamente.";
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}