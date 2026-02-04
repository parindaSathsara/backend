<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminAlbumController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminInventoryController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminCustomerController;
use App\Http\Controllers\Api\Admin\AdminBannerController;
use App\Http\Controllers\Api\Admin\AdminCouponController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Admin\AdminVariationController;
use App\Http\Controllers\Api\Admin\AdminVariationTypeController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/**
 * Public routes (no authentication required)
 */
// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Public product browsing
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/trending', [ProductController::class, 'trending']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/albums', [AlbumController::class, 'index']);
Route::get('/albums/featured', [AlbumController::class, 'featured']);
Route::get('/albums/{slug}', [AlbumController::class, 'show']);

Route::get('/banners', [BannerController::class, 'index']);

// Shipping settings (public)
Route::get('/shipping-settings', function () {
    return response()->json([
        'shipping_rate_per_kg' => \App\Models\Setting::get('shipping_rate_per_kg', 500),
        'free_shipping_threshold' => \App\Models\Setting::get('free_shipping_threshold', 0),
        'default_weight' => \App\Models\Setting::get('default_weight', 0.5),
    ]);
});

// Bank details (public - for checkout)
Route::get('/payment/bank-details', function () {
    return response()->json([
        'bank_name' => \App\Models\Setting::get('bank_name', 'Bank of Ceylon'),
        'account_number' => \App\Models\Setting::get('bank_account_number', ''),
        'account_name' => \App\Models\Setting::get('bank_account_name', 'SH Womens Fashion (Pvt) Ltd'),
        'branch' => \App\Models\Setting::get('bank_branch', ''),
        'branch_code' => \App\Models\Setting::get('bank_branch_code', ''),
        'swift_code' => \App\Models\Setting::get('bank_swift_code', ''),
    ]);
});

// Product reviews (public viewing)
Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);

/**
 * Protected routes (authentication required)
 */
Route::middleware('auth:sanctum')->group(function () {
    // Auth user routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    // Cart management
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
    Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon']);
    Route::delete('/cart/remove-coupon', [CartController::class, 'removeCoupon']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::post('/cart/sync', [CartController::class, 'syncGuestCart']);

    // Order management
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel']);

    // Payment - Bank transfer
    Route::get('/payment/bank-details', [PaymentController::class, 'getBankDetails']);
    Route::post('/payment/{orderNumber}/upload-slip', [PaymentController::class, 'uploadSlip']);
    Route::get('/payment/{orderNumber}/status', [PaymentController::class, 'getPaymentStatus']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'add']);
    Route::delete('/wishlist/{product}', [WishlistController::class, 'remove']);

    // Reviews (creating)
    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Coupon validation
    Route::post('/coupons/validate', [CouponController::class, 'validate']);
});

/**
 * Admin routes (admin authentication required)
 */
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/dashboard/recent-orders', [AdminDashboardController::class, 'recentOrders']);
    Route::get('/dashboard/low-stock', [AdminDashboardController::class, 'lowStock']);

    // Category management
    Route::apiResource('categories', AdminCategoryController::class);
    Route::post('categories/{category}/toggle-status', [AdminCategoryController::class, 'toggleStatus']);

    // Product management
    Route::apiResource('products', AdminProductController::class);
    Route::post('products/{product}/toggle-status', [AdminProductController::class, 'toggleStatus']);
    Route::post('products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured']);
    Route::post('products/{product}/toggle-trending', [AdminProductController::class, 'toggleTrending']);
    Route::post('products/{product}/images', [AdminProductController::class, 'uploadImages']);
    Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'deleteImage']);
    
    // Product variants
    Route::post('products/{product}/variants', [AdminProductController::class, 'addVariant']);
    Route::put('products/{product}/variants/{variant}', [AdminProductController::class, 'updateVariant']);
    Route::delete('products/{product}/variants/{variant}', [AdminProductController::class, 'deleteVariant']);

    // Album management
    Route::apiResource('albums', AdminAlbumController::class);
    Route::post('albums/{album}/toggle-status', [AdminAlbumController::class, 'toggleStatus']);
    Route::post('albums/{album}/toggle-featured', [AdminAlbumController::class, 'toggleFeatured']);
    Route::post('albums/{album}/products', [AdminAlbumController::class, 'addProduct']);
    Route::delete('albums/{album}/products/{product}', [AdminAlbumController::class, 'removeProduct']);

    // Inventory management
    Route::get('inventory', [AdminInventoryController::class, 'index']);
    Route::get('inventory/low-stock', [AdminInventoryController::class, 'lowStock']);
    Route::get('inventory/out-of-stock', [AdminInventoryController::class, 'outOfStock']);
    Route::put('inventory/{inventory}', [AdminInventoryController::class, 'update']);
    Route::post('inventory/{inventory}/adjust', [AdminInventoryController::class, 'adjust']);

    // Order management
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);
    Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    Route::put('orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
    Route::post('orders/{order}/tracking', [AdminOrderController::class, 'addTracking']);

    // Customer management
    Route::get('customers', [AdminCustomerController::class, 'index']);
    Route::get('customers/{user}', [AdminCustomerController::class, 'show']);
    Route::post('customers/{user}/toggle-status', [AdminCustomerController::class, 'toggleStatus']);

    // Banner management
    Route::apiResource('banners', AdminBannerController::class);
    Route::post('banners/{banner}/toggle-status', [AdminBannerController::class, 'toggleStatus']);

    // Coupon management
    Route::apiResource('coupons', AdminCouponController::class);
    Route::post('coupons/{coupon}/toggle-status', [AdminCouponController::class, 'toggleStatus']);

    // Review management
    Route::get('reviews', [AdminReviewController::class, 'index']);
    Route::post('reviews/{review}/approve', [AdminReviewController::class, 'approve']);
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);

    // Variation types management (color, size, material, gold_type, etc.)
    Route::get('variation-types', [AdminVariationTypeController::class, 'index']);
    Route::post('variation-types', [AdminVariationTypeController::class, 'store']);
    Route::get('variation-types/{variationType}', [AdminVariationTypeController::class, 'show']);
    Route::put('variation-types/{variationType}', [AdminVariationTypeController::class, 'update']);
    Route::delete('variation-types/{variationType}', [AdminVariationTypeController::class, 'destroy']);
    Route::post('variation-types/{variationType}/toggle-status', [AdminVariationTypeController::class, 'toggleStatus']);
    Route::post('variation-types/reorder', [AdminVariationTypeController::class, 'reorder']);
    
    // Variation options management (colors, sizes, materials)
    Route::get('variations', [AdminVariationController::class, 'index']);
    Route::get('variations/type/{typeSlug}', [AdminVariationController::class, 'byType']);
    Route::post('variations', [AdminVariationController::class, 'store']);
    Route::put('variations/{variation}', [AdminVariationController::class, 'update']);
    Route::delete('variations/{variation}', [AdminVariationController::class, 'destroy']);
    Route::post('variations/{variation}/toggle-status', [AdminVariationController::class, 'toggleStatus']);
    Route::post('variations/reorder', [AdminVariationController::class, 'reorder']);

    // Payment management
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payments/pending-verification', [AdminPaymentController::class, 'pendingVerification']);
    Route::get('payments/stats', [AdminPaymentController::class, 'stats']);
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show']);
    Route::get('payments/{payment}/slip', [AdminPaymentController::class, 'viewSlip']);
    Route::post('payments/{payment}/verify', [AdminPaymentController::class, 'verify']);
    Route::post('payments/{payment}/reject', [AdminPaymentController::class, 'reject']);

    // Settings management
    Route::get('settings', [AdminSettingsController::class, 'index']);
    Route::get('settings/shipping', [AdminSettingsController::class, 'getShippingSettings']);
    Route::put('settings/shipping', [AdminSettingsController::class, 'updateShippingSettings']);
    Route::get('settings/bank', [AdminSettingsController::class, 'getBankSettings']);
    Route::put('settings/bank', [AdminSettingsController::class, 'updateBankSettings']);
    Route::put('settings/{key}', [AdminSettingsController::class, 'update']);
});
