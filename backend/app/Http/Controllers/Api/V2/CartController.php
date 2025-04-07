<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Admin\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Jobs\DeleteProductJob;

class CartController
{
    /**
     * Display a listing of the resource.
     */

    public function store(Request $request)
    {
        //validate
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        //check if product existe
        $product = Product::findOrFail($request->product_id);
        //check if less or heigher than stock
        if ($product->stock < $request->quantity) {
            return response()->json([
                'Quanity Error' => 'Quantity Less Than Stock'
            ], 400);
        }
        //create expired time
        $expiresAt = Carbon::now()->addHour(48);
        // DeleteProductJob::dispatch($cart->id)->delay(Carbon::now()->addSeconds(10));


        //check if Authntified
        if (Auth::check()) {
            //if the product allready existe
            $existingCartItem = CartItem::where('user_id', Auth::user()->id)
                ->where('product_id', $request->product_id)->first();

            if ($existingCartItem) {
                //update The quantity
                $existingCartItem->expires_at = $expiresAt;
                $existingCartItem->user_id = Auth::user()->id;
                // Make sure we're explicitly adding to the existing quantity
                $existingCartItem->quantity = $existingCartItem->quantity + $request->quantity;
                $existingCartItem->save();
                $cartItem = $existingCartItem;
                
                // Schedule deletion after expiration
                DeleteProductJob::dispatch($cartItem->id)->delay($expiresAt);
            } else {
                //create now cart
                $cartItem = CartItem::create([
                    'user_id' => Auth::user()->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'expires_at' => $expiresAt,
                ]);
                
                // Schedule deletion after expiration
                DeleteProductJob::dispatch($cartItem->id)->delay($expiresAt);
            }
        } else {
            // Get session ID from cookie or header
            $sessionId = $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session');

            if (!$sessionId) {
                $sessionId = Str::uuid()->toString();
            }

            // Create cart item in database with session_id
            $cartItem = CartItem::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'product_id' => $request->product_id
                ],
                [
                    'quantity' => $request->quantity,
                    'expires_at' => $expiresAt,
                ]
            );
            
            // Schedule deletion after expiration
            DeleteProductJob::dispatch($cartItem->id)->delay($expiresAt);

            // Return with cookie
            return response()->json([
                'message' => 'Product Added to Cart',
                'cart_item' => $cartItem,
                'session_id' => $sessionId
            ])->cookie('cart_session_id', $sessionId, 60 * 48); // 48 hours
        }

        // This return is only for authenticated users
        return response()->json([
            'message' => 'Product Added to Cart',
            'cart_item' => $cartItem,
        ]);
    }

    public function index(Request $request)
    {
        // Get session ID for guest users
        $sessionId = $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session');

        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            // If there are guest cart items with this session ID, merge them with the user's cart
            if ($sessionId) {
                $sessionCartItems = CartItem::where('session_id', $sessionId)->get();

                foreach ($sessionCartItems as $sessionItem) {
                    $cartItem = CartItem::where('user_id', $user->id)
                        ->where('product_id', $sessionItem->product_id)
                        ->first();
                    $product = Product::find($sessionItem->product_id);

                    if ($product) {
                        if ($cartItem && ($cartItem->quantity + $sessionItem->quantity) <= $product->stock) {
                            $cartItem->quantity += $sessionItem->quantity;
                            $cartItem->save();
                        } elseif (!$cartItem && $sessionItem->quantity <= $product->stock) {
                            CartItem::create([
                                'user_id' => $user->id,
                                'product_id' => $sessionItem->product_id,
                                'quantity' => $sessionItem->quantity,
                                'expires_at' => now()->addHours(48)
                            ]);
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Quantité supérieure au stock disponible'
                            ]);
                        }
                    }
                    // Delete the guest cart item after merging
                    $sessionItem->delete();
                }
            }

            $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cart_items' => $cartItems,
                    'total_items' => $cartItems->count()
                ]
            ]);
        } else {
            // Guest user - retrieve cart items using session_id from database
            $cartItems = CartItem::where('session_id', $sessionId)
                ->with('product')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'items' => $cartItems,
                    'total_items' => $cartItems->count()
                ]
            ]);
        }
    }

    public function update(Request $request)
    {
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produit non trouvé'
            ], 404);
        }

        if ($request->quantity <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'La quantité doit être supérieure à zéro'
            ], 400);
        }

        if ($request->quantity > $product->stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quantité supérieure au stock disponible'
            ], 400);
        }

        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            $cartItem = CartItem::where('user_id', $user->id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($cartItem) {
                $cartItem->quantity = $request->quantity;
                $cartItem->save();
            } else {
                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'expires_at' => now()->addHours(48)
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Panier mis à jour'
            ]);
        } else {
            // Get session ID for guest user
            $sessionId = $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session');

            if (!$sessionId) {
                // Generate a new session ID if none exists
                $sessionId = uniqid('cart_', true);
            }

            // Find or create cart item with session_id
            $cartItem = CartItem::where('session_id', $sessionId)
                ->where('product_id', $request->product_id)
                ->first();

            if ($cartItem) {
                $cartItem->quantity = $request->quantity;
                $cartItem->save();
            } else {
                CartItem::create([
                    'session_id' => $sessionId,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'expires_at' => now()->addHours(48)
                ]);
            }

            // Add a cookie to the response with the session ID
            $response = response()->json([
                'status' => 'success',
                'message' => 'Panier mis à jour',
                'session_id' => $sessionId
            ]);

            if (!$request->cookie('cart_session_id')) {
                $response->cookie('cart_session_id', $sessionId, 60 * 24 * 30); // 30 days
            }

            return $response;
        }
    }

    public function delete(Request $request)
    {
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            $deleted = CartItem::where('user_id', $user->id)
                ->where('product_id', $request->product_id)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Produit supprimé du panier'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produit non trouvé dans le panier'
                ], 404);
            }
        } else {
            // Get session ID for guest user
            $sessionId = $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session');

            if (!$sessionId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session panier non trouvée'
                ], 404);
            }

            $deleted = CartItem::where('session_id', $sessionId)
                ->where('product_id', $request->product_id)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Produit supprimé du panier'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produit non trouvé dans le panier'
                ], 404);
            }
        }
    }
}
