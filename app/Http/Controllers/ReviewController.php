<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display reviews for a product.
     */
    public function index(Request $request, string $productSlug): View|JsonResponse
    {
        $product = Product::where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $rating = $request->get('rating');
        $sort = $request->get('sort', 'newest');
        $verified = $request->get('verified');
        $withImages = $request->get('with_images');
        $perPage = $request->get('per_page', 10);

        $reviewsQuery = $product->reviews()
            ->approved()
            ->with(['user']);

        // Apply filters
        if ($rating) {
            $reviewsQuery->byRating($rating);
        }

        if ($verified) {
            $reviewsQuery->verified();
        }

        if ($withImages) {
            $reviewsQuery->withImages();
        }

        // Apply sorting
        switch ($sort) {
            case 'oldest':
                $reviewsQuery->oldest();
                break;
            case 'helpful':
                $reviewsQuery->mostHelpful();
                break;
            case 'rating_high':
                $reviewsQuery->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $reviewsQuery->orderBy('rating', 'asc');
                break;
            default: // newest
                $reviewsQuery->newest();
                break;
        }

        $reviews = $reviewsQuery->paginate($perPage);
        $reviews->appends($request->query());

        // Get review summary
        $reviewSummary = Review::getSummary($product->id);

        if ($request->expectsJson()) {
            return response()->json([
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
                'summary' => $reviewSummary,
            ]);
        }

        return view('reviews.index', compact('product', 'reviews', 'reviewSummary', 'rating', 'sort', 'verified', 'withImages'));
    }

    /**
     * Show the form for creating a new review.
     */
    public function create(Request $request, string $productSlug): View
    {
        $product = Product::where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $orderItemId = $request->get('order_item_id');
        $orderItem = null;

        if ($orderItemId) {
            $orderItem = OrderItem::where('id', $orderItemId)
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) {
                    $query->where('user_id', Auth::id())
                          ->where('status', 'delivered');
                })
                ->first();
        }

        // Check if user has already reviewed this product
        $existingReview = Review::where('product_id', $product->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReview) {
            return redirect()->route('products.show', $product->slug)
                ->with('error', 'Ya has reseñado este producto.');
        }

        return view('reviews.create', compact('product', 'orderItem'));
    }

    /**
     * Store a newly created review.
     */
    public function store(Request $request, string $productSlug): JsonResponse
    {
        $product = Product::where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|max:2000',
            'order_item_id' => 'sometimes|exists:order_items,id',
            'images' => 'sometimes|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Check if user has already reviewed this product
            $existingReview = Review::where('product_id', $product->id)
                ->where('user_id', Auth::id())
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has reseñado este producto.'
                ], 400);
            }

            $orderItem = null;
            if ($request->order_item_id) {
                $orderItem = OrderItem::where('id', $request->order_item_id)
                    ->where('product_id', $product->id)
                    ->whereHas('order', function ($query) {
                        $query->where('user_id', Auth::id())
                              ->where('status', 'delivered');
                    })
                    ->first();
            }

            // Handle image uploads
            $imageUrls = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('reviews', 'public');
                    $imageUrls[] = Storage::url($path);
                }
            }

            // Create review
            $review = Review::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'order_id' => $orderItem?->order_id,
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'images' => $imageUrls,
                'is_verified' => $orderItem ? true : false,
                'is_approved' => true, // Auto-approve for now
            ]);

            // Update product rating
            $product->updateRating();

            return response()->json([
                'success' => true,
                'message' => 'Reseña enviada exitosamente.',
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'is_verified' => $review->is_verified,
                    'created_at' => $review->created_at->diffForHumans(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la reseña.'
            ], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show(int $reviewId): View
    {
        $review = Review::where('id', $reviewId)
            ->approved()
            ->with(['product', 'user'])
            ->firstOrFail();

        // Get related reviews
        $relatedReviews = Review::where('product_id', $review->product_id)
            ->where('id', '!=', $review->id)
            ->approved()
            ->with(['user'])
            ->latest()
            ->limit(5)
            ->get();

        return view('reviews.show', compact('review', 'relatedReviews'));
    }

    /**
     * Show the form for editing the review.
     */
    public function edit(int $reviewId): View
    {
        $review = Review::where('id', $reviewId)
            ->where('user_id', Auth::id())
            ->with(['product'])
            ->firstOrFail();

        if (!$review->canEdit()) {
            abort(403, 'No puedes editar esta reseña.');
        }

        return view('reviews.edit', compact('review'));
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, int $reviewId): JsonResponse
    {
        $review = Review::where('id', $reviewId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (!$review->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes editar esta reseña.'
            ], 403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|max:2000',
            'images' => 'sometimes|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_images' => 'sometimes|array',
            'remove_images.*' => 'string',
        ]);

        try {
            $imageUrls = $review->images ?? [];

            // Remove specified images
            if ($request->remove_images) {
                foreach ($request->remove_images as $imageUrl) {
                    if (($key = array_search($imageUrl, $imageUrls)) !== false) {
                        unset($imageUrls[$key]);
                        // Delete file from storage
                        $path = str_replace('/storage/', '', $imageUrl);
                        Storage::disk('public')->delete($path);
                    }
                }
                $imageUrls = array_values($imageUrls);
            }

            // Add new images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if (count($imageUrls) < 5) {
                        $path = $image->store('reviews', 'public');
                        $imageUrls[] = Storage::url($path);
                    }
                }
            }

            // Update review
            $review->update([
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'images' => $imageUrls,
                'is_approved' => true, // Re-approve after edit
            ]);

            // Update product rating
            $review->product->updateRating();

            return response()->json([
                'success' => true,
                'message' => 'Reseña actualizada exitosamente.',
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'images' => $review->image_urls,
                    'updated_at' => $review->updated_at->diffForHumans(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la reseña.'
            ], 500);
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy(int $reviewId): JsonResponse
    {
        try {
            $review = Review::where('id', $reviewId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            if (!$review->canDelete()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar esta reseña.'
                ], 403);
            }

            // Delete review images
            if ($review->images) {
                foreach ($review->images as $imageUrl) {
                    $path = str_replace('/storage/', '', $imageUrl);
                    Storage::disk('public')->delete($path);
                }
            }

            $product = $review->product;
            $review->delete();

            // Update product rating
            $product->updateRating();

            return response()->json([
                'success' => true,
                'message' => 'Reseña eliminada exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la reseña.'
            ], 500);
        }
    }

    /**
     * Mark review as helpful.
     */
    public function markHelpful(int $reviewId): JsonResponse
    {
        try {
            $review = Review::findOrFail($reviewId);
            
            if ($review->user_id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes marcar tu propia reseña como útil.'
                ], 400);
            }

            $review->markAsHelpful();

            return response()->json([
                'success' => true,
                'message' => 'Reseña marcada como útil.',
                'helpful_count' => $review->helpful_count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar la reseña como útil.'
            ], 500);
        }
    }

    /**
     * Get review summary for a product.
     */
    public function summary(string $productSlug): JsonResponse
    {
        $product = Product::where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $summary = Review::getSummary($product->id);

        return response()->json($summary);
    }

    /**
     * Get user's reviews.
     */
    public function userReviews(Request $request): View
    {
        $status = $request->get('status', 'all');
        $sort = $request->get('sort', 'newest');
        $perPage = $request->get('per_page', 10);

        $reviewsQuery = Auth::user()->reviews()
            ->with(['product']);

        // Apply filters
        if ($status === 'approved') {
            $reviewsQuery->approved();
        } elseif ($status === 'pending') {
            $reviewsQuery->where('is_approved', false);
        }

        // Apply sorting
        switch ($sort) {
            case 'oldest':
                $reviewsQuery->oldest();
                break;
            case 'rating_high':
                $reviewsQuery->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $reviewsQuery->orderBy('rating', 'asc');
                break;
            case 'helpful':
                $reviewsQuery->orderBy('helpful_count', 'desc');
                break;
            default: // newest
                $reviewsQuery->latest();
                break;
        }

        $reviews = $reviewsQuery->paginate($perPage);
        $reviews->appends($request->query());

        // Get user review statistics
        $stats = [
            'total_reviews' => Auth::user()->reviews()->count(),
            'approved_reviews' => Auth::user()->reviews()->approved()->count(),
            'pending_reviews' => Auth::user()->reviews()->where('is_approved', false)->count(),
            'average_rating' => Auth::user()->reviews()->avg('rating'),
            'total_helpful' => Auth::user()->reviews()->sum('helpful_count'),
        ];

        return view('reviews.user-reviews', compact('reviews', 'stats', 'status', 'sort'));
    }

    /**
     * Get products that can be reviewed by the user.
     */
    public function reviewableProducts(): JsonResponse
    {
        $products = OrderItem::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id())
                      ->where('status', 'delivered');
            })
            ->with(['product'])
            ->get()
            ->filter(function ($item) {
                return $item->canReview();
            })
            ->map(function ($item) {
                return [
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_slug' => $item->product->slug ?? null,
                    'product_image' => $item->product_image_url,
                    'order_number' => $item->order->order_number,
                    'delivered_at' => $item->order->updated_at->format('d/m/Y'),
                ];
            })
            ->values();

        return response()->json($products);
    }
}