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
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            $cart = Cart::firstOrCreate([
                'user_id' => $user->id
            ]);

            $sessionCart = request()->session()->get('cart', []);

            //fusionner le panier du session avec le panier de la base de données

            if (!empty($sessionCart)) {
                foreach ($sessionCart as $productId => $item) {
                    $cartItem = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($cartItem) {
                        $cartItem->quantity += $item['quantity'];
                        $cartItem->save();
                    } else {
                        CartItem::create([
                            'cart_id' => $cart->id,
                            'product_id' => $productId,
                            'quantity' => $item['quantity']
                        ]);
                    }
                }
                // vider le panier de la session
                request()->session()->forget('cart');
            }

            $cartItems = CartItem::where('cart_id', $cart->id)->with('product')->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cart_id' => $cart->id,
                    'cart_items' => $cartItems
                ]
            ]);
        } else {
            // Utilisateur invité - retourner le panier en session
            $sessionCart = session()->get('cart', []);
            $cartItems = [];

            // Récupérer les détails des produits pour les articles en session
            if (!empty($sessionCart)) {
                foreach ($sessionCart as $productId => $item) {
                    $product = Product::find($productId);
                    if ($product) {
                        $item['product'] = $product;
                        $cartItems[] = $item;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Panier invité récupéré',
                'data' => [
                    'items' => $cartItems,
                    'total_items' => count($cartItems)
                ]
            ]);
        }
    }
}
