<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NewsletterController;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'contact'])->name('contact.submit');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/search/suggestions', [HomeController::class, 'searchSuggestions'])->name('search.suggestions');

// Newsletter Routes
Route::prefix('newsletter')->name('newsletter.')->group(function () {
    Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->name('subscribe');
    Route::get('/unsubscribe', [NewsletterController::class, 'showUnsubscribe'])->name('unsubscribe.form');
    Route::get('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe.submit');
});

// Product Routes
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/featured', [ProductController::class, 'featured'])->name('featured');
    Route::get('/new', [ProductController::class, 'newProducts'])->name('new');
    Route::get('/sale', [ProductController::class, 'onSale'])->name('sale');
    Route::get('/compare', [ProductController::class, 'compare'])->name('compare');
    Route::get('/{product:slug}', [ProductController::class, 'show'])->name('show');
    Route::get('/{product:slug}/quick-view', [ProductController::class, 'quickView'])->name('quick-view');
});

// Category Routes
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/popular', [CategoryController::class, 'popular'])->name('popular');
    Route::get('/navigation', [CategoryController::class, 'navigation'])->name('navigation');
    Route::get('/filters', [CategoryController::class, 'filters'])->name('filters');
    Route::get('/search', [CategoryController::class, 'search'])->name('search');
    Route::get('/{category:slug}', [CategoryController::class, 'show'])->name('show');
    Route::get('/{category:slug}/breadcrumbs', [CategoryController::class, 'breadcrumbs'])->name('breadcrumbs');
});

// Cart Routes (Guest + Auth)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/summary', [CartController::class, 'summary'])->name('summary');
    Route::get('/count', [CartController::class, 'count'])->name('count');
    Route::get('/mini-cart', [CartController::class, 'miniCart'])->name('mini-cart');
    Route::post('/validate', [CartController::class, 'validateCart'])->name('validate');
    Route::post('/apply-coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
    Route::delete('/remove-coupon', [CartController::class, 'removeCoupon'])->name('remove-coupon');
    Route::post('/estimate-shipping', [CartController::class, 'estimateShipping'])->name('estimate-shipping');
    Route::post('/save-for-later', [CartController::class, 'saveForLater'])->name('save-for-later');
    Route::post('/restore', [CartController::class, 'restoreCart'])->name('restore');
    Route::post('/transfer-guest', [CartController::class, 'transferGuestCart'])->name('transfer-guest');
});

// Authentication Routes
Route::prefix('auth')->name('auth.')->group(function () {
    // Guest only routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
        Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password.submit');
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password.submit');
        
        // Social Authentication
        Route::get('/social/{provider}', [AuthController::class, 'redirectToProvider'])->name('social.redirect');
        Route::get('/social/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('social.callback');
        

    });
    
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/status', [AuthController::class, 'status'])->name('status');
    Route::get('/guest-id', [AuthController::class, 'getGuestId'])->name('guest-id');
    
    // Email verification
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/email/resend', [AuthController::class, 'resendVerification'])->name('verification.resend');
    
    // Social account linking (authenticated users)
    Route::middleware('auth')->group(function () {
        Route::post('/social/{provider}/link', [AuthController::class, 'linkSocialAccount'])->name('social.link');
        Route::delete('/social/{provider}/unlink', [AuthController::class, 'unlinkSocialAccount'])->name('social.unlink');
    });
});

// Authenticated User Routes
Route::middleware('auth')->group(function () {
    // User Dashboard & Profile
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [UserController::class, 'profile'])->name('profile');
        Route::patch('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
        Route::patch('/password', [UserController::class, 'changePassword'])->name('password.change');
        Route::get('/addresses', [UserController::class, 'addresses'])->name('addresses');
        Route::get('/statistics', [UserController::class, 'statistics'])->name('statistics');
        Route::get('/activity', [UserController::class, 'activity'])->name('activity');
        Route::get('/preferences', [UserController::class, 'preferences'])->name('preferences');
        Route::patch('/preferences', [UserController::class, 'updatePreferences'])->name('preferences.update');
        Route::delete('/account', [UserController::class, 'deleteAccount'])->name('account.delete');
        Route::get('/export-data', [UserController::class, 'exportData'])->name('export-data');
    });
    
    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
        Route::post('/checkout', [OrderController::class, 'store'])->name('store');
        Route::get('/{order:order_number}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order:order_number}/confirmation', [OrderController::class, 'confirmation'])->name('confirmation');
        Route::patch('/{order:order_number}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{order:order_number}/reorder', [OrderController::class, 'reorder'])->name('reorder');
        Route::get('/{order:order_number}/invoice', [OrderController::class, 'invoice'])->name('invoice');
        Route::get('/{order:order_number}/status-updates', [OrderController::class, 'statusUpdates'])->name('status-updates');
    });
    
    // Wishlist
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/add', [WishlistController::class, 'add'])->name('add');
        Route::delete('/remove', [WishlistController::class, 'remove'])->name('remove');
        Route::post('/toggle', [WishlistController::class, 'toggle'])->name('toggle');
        Route::delete('/clear', [WishlistController::class, 'clear'])->name('clear');
        Route::post('/move-to-cart', [WishlistController::class, 'moveToCart'])->name('move-to-cart');
        Route::post('/move-all-to-cart', [WishlistController::class, 'moveAllToCart'])->name('move-all-to-cart');
        Route::get('/count', [WishlistController::class, 'count'])->name('count');
        Route::get('/summary', [WishlistController::class, 'summary'])->name('summary');
        Route::get('/recent', [WishlistController::class, 'recent'])->name('recent');
        Route::get('/back-in-stock', [WishlistController::class, 'backInStock'])->name('back-in-stock');
        Route::get('/on-sale', [WishlistController::class, 'onSale'])->name('on-sale');
        Route::get('/check', [WishlistController::class, 'check'])->name('check');
        Route::get('/share', [WishlistController::class, 'share'])->name('share');
    });
    
    // Reviews
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/create', [ReviewController::class, 'create'])->name('create');
        Route::post('/', [ReviewController::class, 'store'])->name('store');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
        Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
        Route::patch('/{review}', [ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
        Route::post('/{review}/helpful', [ReviewController::class, 'markHelpful'])->name('helpful');
        Route::get('/user/reviews', [ReviewController::class, 'userReviews'])->name('user.index');
        Route::get('/user/reviewable-products', [ReviewController::class, 'reviewableProducts'])->name('user.reviewable');
    });
});

// Public Review Routes
Route::prefix('products/{product:slug}/reviews')->name('products.reviews.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index');
    Route::get('/summary', [ReviewController::class, 'summary'])->name('summary');
});

// Public Order Tracking
Route::get('/track-order', [OrderController::class, 'track'])->name('orders.track');
Route::post('/track-order', [OrderController::class, 'track'])->name('orders.track.submit');

// Public Wishlist Sharing
Route::get('/wishlist/public/{token}', [WishlistController::class, 'public'])->name('wishlist.public');

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/system-stats', [AdminController::class, 'systemStats'])->name('system-stats');
    Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/health-check', [AdminController::class, 'healthCheck'])->name('health-check');
    
    // Data Management
    Route::post('/export-data', [AdminController::class, 'exportData'])->name('export-data');
    Route::get('/download-export/{filename}', [AdminController::class, 'downloadExport'])->name('download-export');
    Route::post('/clear-cache', [AdminController::class, 'clearCache'])->name('clear-cache');
    
    // Alerts & Moderation
    Route::get('/low-stock-alerts', [AdminController::class, 'lowStockAlerts'])->name('low-stock-alerts');
    Route::get('/pending-reviews', [AdminController::class, 'pendingReviews'])->name('pending-reviews');
    Route::post('/bulk-approve-reviews', [AdminController::class, 'bulkApproveReviews'])->name('bulk-approve-reviews');
    
    // Order Management
    Route::patch('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.update-status');
    
    // You can add more admin routes here for managing products, categories, users, etc.
    // Route::resource('products', AdminProductController::class);
    // Route::resource('categories', AdminCategoryController::class);
    // Route::resource('users', AdminUserController::class);
    // Route::resource('orders', AdminOrderController::class);
    // Route::resource('reviews', AdminReviewController::class);
});

// API Routes for AJAX calls
Route::prefix('api')->name('api.')->group(function () {
    // Public API routes
    Route::get('/products/search', [ProductController::class, 'apiSearch'])->name('products.search');
    Route::get('/products/new', [ProductController::class, 'newProducts'])->name('products.new');
    Route::get('/categories/tree', [CategoryController::class, 'apiTree'])->name('categories.tree');
    
    // Cart API (works for both guest and auth)
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'apiIndex'])->name('api.index');
        Route::post('/add', [CartController::class, 'apiAdd'])->name('api.add');
        Route::patch('/update', [CartController::class, 'apiUpdate'])->name('api.update');
        Route::delete('/remove', [CartController::class, 'apiRemove'])->name('api.remove');
    });
    
    // Auth API
    Route::middleware('auth')->group(function () {
        Route::get('/user', [UserController::class, 'apiUser'])->name('user');
        Route::get('/wishlist', [WishlistController::class, 'apiIndex'])->name('wishlist.api.index');
    });
});
