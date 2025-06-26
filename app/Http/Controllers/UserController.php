<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Request;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display user dashboard.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        
        // Get recent orders
        $recentOrders = $user->orders()
            ->with(['items'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent reviews
        $recentReviews = $user->reviews()
            ->with(['product'])
            ->approved()
            ->latest()
            ->limit(3)
            ->get();

        // Get wishlist count
        $wishlistCount = Wishlist::getItemsCount();

        // Get user statistics
        $stats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->where('status', Order::STATUS_PENDING)->count(),
            'completed_orders' => $user->orders()->where('status', Order::STATUS_DELIVERED)->count(),
            'total_spent' => $user->orders()
                ->where('payment_status', Order::PAYMENT_PAID)
                ->sum('total_amount'),
            'total_reviews' => $user->reviews()->approved()->count(),
            'wishlist_items' => $wishlistCount,
        ];

        // Get products that can be reviewed
        $reviewableProducts = $user->orders()
            ->where('status', Order::STATUS_DELIVERED)
            ->with(['items.product'])
            ->get()
            ->pluck('items')
            ->flatten()
            ->filter(function ($item) {
                return $item->canReview();
            })
            ->take(3);

        // Get recent activity
        $recentActivity = collect()
            ->merge($recentOrders->map(function ($order) {
                return [
                    'type' => 'order',
                    'title' => 'Pedido #' . $order->order_number,
                    'description' => 'Estado: ' . $order->status_text,
                    'date' => $order->created_at,
                    'url' => route('orders.show', $order->order_number),
                ];
            }))
            ->merge($recentReviews->map(function ($review) {
                return [
                    'type' => 'review',
                    'title' => 'Reseña de ' . $review->product->name,
                    'description' => $review->rating . ' estrellas',
                    'date' => $review->created_at,
                    'url' => route('products.show', $review->product->slug),
                ];
            }))
            ->sortByDesc('date')
            ->take(5)
            ->values();

        return view('user.dashboard', compact(
            'user',
            'recentOrders',
            'recentReviews',
            'stats',
            'reviewableProducts',
            'recentActivity'
        ));
    }

    /**
     * Show user profile.
     */
    public function profile(): View
    {
        $user = Auth::user();
        
        return view('user.profile', compact('user'));
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $data = $request->only(['name', 'email', 'phone', 'date_of_birth', 'gender']);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar
                if ($user->avatar) {
                    $oldPath = str_replace('/storage/', '', $user->avatar);
                    Storage::disk('public')->delete($oldPath);
                }

                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = Storage::url($path);
            }

            $user->update($data);

            return redirect()->route('user.profile')
                ->with('success', 'Perfil actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el perfil.')
                ->withInput();
        }
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'La contraseña actual es incorrecta.'])
                ->withInput();
        }

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return redirect()->route('user.profile')
                ->with('success', 'Contraseña cambiada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al cambiar la contraseña.')
                ->withInput();
        }
    }

    /**
     * Show user addresses.
     */
    public function addresses(): View
    {
        $user = Auth::user();
        
        // Get unique addresses from user's orders
        $addresses = $user->orders()
            ->whereNotNull('billing_address')
            ->get()
            ->map(function ($order) {
                return [
                    'billing' => json_decode($order->billing_address, true),
                    'shipping' => json_decode($order->shipping_address, true),
                ];
            })
            ->unique()
            ->values();

        return view('user.addresses', compact('addresses'));
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        
        $stats = [
            'orders' => [
                'total' => $user->orders()->count(),
                'pending' => $user->orders()->where('status', Order::STATUS_PENDING)->count(),
                'processing' => $user->orders()->where('status', Order::STATUS_PROCESSING)->count(),
                'shipped' => $user->orders()->where('status', Order::STATUS_SHIPPED)->count(),
                'delivered' => $user->orders()->where('status', Order::STATUS_DELIVERED)->count(),
                'cancelled' => $user->orders()->where('status', Order::STATUS_CANCELLED)->count(),
            ],
            'spending' => [
                'total' => $user->orders()
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->sum('total_amount'),
                'this_year' => $user->orders()
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount'),
                'this_month' => $user->orders()
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount'),
                'average_order' => $user->orders()
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->avg('total_amount'),
            ],
            'reviews' => [
                'total' => $user->reviews()->count(),
                'approved' => $user->reviews()->approved()->count(),
                'pending' => $user->reviews()->where('is_approved', false)->count(),
                'average_rating' => $user->reviews()->avg('rating'),
                'total_helpful' => $user->reviews()->sum('helpful_count'),
            ],
            'wishlist' => [
                'total_items' => Wishlist::getItemsCount(),
                'in_stock_items' => Wishlist::withInStockProducts()->forCurrentUser()->count(),
                'on_sale_items' => Wishlist::getOnSaleItems()->count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Get user activity timeline.
     */
    public function activity(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->get('limit', 20);
        $type = $request->get('type'); // orders, reviews, all

        $activities = collect();

        if (!$type || $type === 'orders' || $type === 'all') {
            $orders = $user->orders()
                ->with(['items'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'type' => 'order',
                        'id' => $order->id,
                        'title' => 'Pedido #' . $order->order_number,
                        'description' => 'Total: ' . $order->formatted_total_amount . ' - Estado: ' . $order->status_text,
                        'date' => $order->created_at,
                        'url' => route('orders.show', $order->order_number),
                        'icon' => 'shopping-bag',
                        'color' => $this->getOrderStatusColor($order->status),
                    ];
                });
            
            $activities = $activities->merge($orders);
        }

        if (!$type || $type === 'reviews' || $type === 'all') {
            $reviews = $user->reviews()
                ->with(['product'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($review) {
                    return [
                        'type' => 'review',
                        'id' => $review->id,
                        'title' => 'Reseña de ' . $review->product->name,
                        'description' => $review->rating . ' estrellas - "' . Str::limit($review->title, 50) . '"',
                        'date' => $review->created_at,
                        'url' => route('products.show', $review->product->slug),
                        'icon' => 'star',
                        'color' => 'yellow',
                    ];
                });
            
            $activities = $activities->merge($reviews);
        }

        $activities = $activities
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        return response()->json($activities);
    }

    /**
     * Get user preferences.
     */
    public function preferences(): View
    {
        $user = Auth::user();
        
        return view('user.preferences', compact('user'));
    }

    /**
     * Update user preferences.
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'order_updates' => 'boolean',
            'review_reminders' => 'boolean',
            'wishlist_alerts' => 'boolean',
            'language' => 'sometimes|string|in:es,en',
            'currency' => 'sometimes|string|in:USD,EUR,MXN',
            'timezone' => 'sometimes|string',
        ]);

        try {
            $user = Auth::user();
            
            // Update user preferences (you might want to create a separate preferences table)
            $preferences = $request->only([
                'email_notifications',
                'sms_notifications', 
                'marketing_emails',
                'order_updates',
                'review_reminders',
                'wishlist_alerts',
                'language',
                'currency',
                'timezone'
            ]);

            // For now, store in user table or create a preferences relationship
            $user->update(['preferences' => $preferences]);

            return redirect()->route('user.preferences')
                ->with('success', 'Preferencias actualizadas exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar las preferencias.')
                ->withInput();
        }
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'confirmation' => 'required|in:DELETE',
        ]);

        $user = Auth::user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withErrors(['password' => 'Contraseña incorrecta.'])
                ->withInput();
        }

        try {
            // Check for pending orders
            $pendingOrders = $user->orders()
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED])
                ->count();

            if ($pendingOrders > 0) {
                return redirect()->back()
                    ->with('error', 'No puedes eliminar tu cuenta mientras tengas pedidos pendientes.');
            }

            // Delete user avatar
            if ($user->avatar) {
                $path = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($path);
            }

            // Delete user data (this should be done carefully considering GDPR)
            $user->reviews()->delete();
            $user->wishlists()->delete();
            $user->cart()->delete();
            
            // Anonymize orders instead of deleting them
            $user->orders()->update([
                'billing_address' => json_encode(['anonymized' => true]),
                'shipping_address' => json_encode(['anonymized' => true]),
            ]);

            // Delete user account
            $user->delete();

            // Logout and redirect to home
            Auth::logout();
            return redirect()->route('home')
                ->with('success', 'Cuenta eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar la cuenta.')
                ->withInput();
        }
    }

    /**
     * Export user data.
     */
    public function exportData()
    {
        try {
            $user = Auth::user();
            
            $data = [
                'user' => $user->only(['name', 'email', 'phone', 'date_of_birth', 'gender', 'created_at']),
                'orders' => $user->orders()->with(['items'])->get(),
                'reviews' => $user->reviews()->with(['product'])->get(),
                'wishlist' => $user->wishlists()->with(['product'])->get(),
            ];

            $fileName = 'user_data_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
            
            return response()->streamDownload(function () use ($data) {
                echo json_encode($data, JSON_PRETTY_PRINT);
            }, $fileName, [
                'Content-Type' => 'application/json',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al exportar los datos.');
        }
    }

    /**
     * Get order status color for activity timeline.
     */
    private function getOrderStatusColor(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'orange',
            Order::STATUS_PROCESSING => 'blue',
            Order::STATUS_SHIPPED => 'purple',
            Order::STATUS_DELIVERED => 'green',
            Order::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }
}