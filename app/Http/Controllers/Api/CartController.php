<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Album;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Cart Controller
 * Handles shopping cart operations
 */
class CartController extends Controller
{
    /**
     * Get user's cart
     */
    public function show(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        
        $cart->load([
            'items.product.primaryImage',
            'items.product.category',
            'items.variant',
            'items.album'
        ]);

        return response()->json([
            'cart' => $cart
        ]);
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_type' => 'required|in:product,album',
            'product_id' => 'required_if:item_type,product|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'album_id' => 'required_if:item_type,album|exists:albums,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $cart = $this->getOrCreateCart($request);

        if ($request->item_type === 'product') {
            $product = Product::findOrFail($request->product_id);
            $variant = $request->variant_id ? ProductVariant::findOrFail($request->variant_id) : null;
            
            // Check stock
            if ($variant) {
                if (!$variant->isInStock()) {
                    return response()->json([
                        'message' => 'This variant is out of stock'
                    ], 400);
                }
            } else {
                if (!$product->isInStock()) {
                    return response()->json([
                        'message' => 'This product is out of stock'
                    ], 400);
                }
            }
            
            $cart->addProduct($product, $variant, $request->quantity);
        } else {
            $album = Album::findOrFail($request->album_id);
            
            if (!$album->isInStock()) {
                return response()->json([
                    'message' => 'Some products in this album are out of stock'
                ], 400);
            }
            
            $cart->addAlbum($album, $request->quantity);
        }

        $cart->load([
            'items.product.primaryImage',
            'items.variant',
            'items.album'
        ]);

        return response()->json([
            'message' => 'Item added to cart',
            'cart' => $cart
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(Request $request, CartItem $item)
    {
        // Ensure item belongs to user's cart
        $cart = $this->getOrCreateCart($request);
        
        if ($item->cart_id !== $cart->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $item->updateQuantity($request->quantity);

        $cart->load([
            'items.product.primaryImage',
            'items.variant',
            'items.album'
        ]);

        return response()->json([
            'message' => 'Cart updated',
            'cart' => $cart
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, CartItem $item)
    {
        $cart = $this->getOrCreateCart($request);
        
        if ($item->cart_id !== $cart->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $cart->removeItem($item);

        $cart->load([
            'items.product.primaryImage',
            'items.variant',
            'items.album'
        ]);

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => $cart
        ]);
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $cart = $this->getOrCreateCart($request);
        
        $coupon = \App\Models\Coupon::where('code', $request->coupon_code)->first();
        
        if (!$coupon) {
            return response()->json([
                'message' => 'Invalid coupon code'
            ], 404);
        }

        if (!$coupon->isValid($cart->subtotal, $request->user())) {
            return response()->json([
                'message' => 'Coupon is not valid or has expired'
            ], 400);
        }

        $cart->coupon_code = $request->coupon_code;
        $cart->calculateTotals();

        return response()->json([
            'message' => 'Coupon applied successfully',
            'cart' => $cart
        ]);
    }

    /**
     * Remove coupon from cart
     */
    public function removeCoupon(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->coupon_code = null;
        $cart->calculateTotals();

        return response()->json([
            'message' => 'Coupon removed',
            'cart' => $cart
        ]);
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->clear();

        return response()->json([
            'message' => 'Cart cleared'
        ]);
    }

    /**
     * Sync guest cart items to user's cart after login
     */
    public function syncGuestCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.item_type' => 'required|in:product,album',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.album_id' => 'nullable|exists:albums,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $cart = $this->getOrCreateCart($request);
        $addedItems = 0;
        $errors = [];

        foreach ($request->items as $item) {
            try {
                if ($item['item_type'] === 'product' && !empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product && $product->isInStock()) {
                        $variant = !empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;
                        $cart->addProduct($product, $variant, $item['quantity']);
                        $addedItems++;
                    }
                } elseif ($item['item_type'] === 'album' && !empty($item['album_id'])) {
                    $album = Album::find($item['album_id']);
                    if ($album && $album->isInStock()) {
                        $cart->addAlbum($album, $item['quantity']);
                        $addedItems++;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to add item: " . $e->getMessage();
            }
        }

        $cart->load([
            'items.product.primaryImage',
            'items.variant',
            'items.album'
        ]);

        return response()->json([
            'message' => "Synced {$addedItems} items to cart",
            'cart' => $cart,
            'errors' => $errors
        ]);
    }

    /**
     * Get or create cart for user
     */
    protected function getOrCreateCart(Request $request)
    {
        $user = $request->user();
        
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
            ]);
        }
        
        return $cart;
    }
}
