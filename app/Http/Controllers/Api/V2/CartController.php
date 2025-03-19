<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController
{
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

    public function test(Request $request)
    {

        return $this->calculeTotal(
            $request,
            $request->tva,
            $request->livraison,
            $request->reduction
        );
    }
    public function calculeTotal($request, $tva, $livraison, $reduction)
    {
        if ($request->tva < 0 || $request->tva > 100) {
            return response()->json([
                'message' => 'TVA doit être compris entre 0 et 100',
            ]);
        }
        if ($livraison < 0 || $livraison > 50) {
            return response()->json([
                'message' => 'Livraison doit être compris entre 0 et 50',
            ]);
        }
        if ($reduction < 0 || $reduction > 100) {
            return response()->json([
                'message' => 'Réduction doit être compris entre 0 et 100',
            ]);
        }

        if (Auth::check()) {
            $cart = CartItem::where('user_id', Auth::id())->get();
        } else {
            $sessionId = $this->getSessionId($request);
            $cart = CartItem::where('session_id', $sessionId)->get();
        }
        $subTotal = 0;
        foreach ($cart as $item) {
            $subTotal += $item->product->price * $item->quantity;
        }
        $TVACost = $subTotal * $tva / 100;
        $ReductionCost = $subTotal * $reduction / 100;
        $total = $subTotal - $ReductionCost + $TVACost + $livraison;

        return response()->json([
            'subTotal' => $subTotal,
            'livraison' => $livraison,
            'taux de tva' => $tva . '%',
            'le prix va ajoute avec tva' => $TVACost,
            'taux de réduction' => $reduction ? $reduction . '%' : '0%',
            'le prix va soustrait avec reduction' => $ReductionCost,
            'total' => $total,
        ]);
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
                $cartItem->expires_at = now()->addHours(48);
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
                $cartItem->expires_at = now()->addHours(48);
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
