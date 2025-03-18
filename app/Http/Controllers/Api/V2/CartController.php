<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Admin\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
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
        if($product->stock < $request->quantity)
        {
            return response()->json([
                'Quanity Error' => 'Quantity Less Than Stock'
            ], 400);
        }
        //create expired time
        $expiresAt = Carbon::now()->addHour(48);

        //check if Authntified
        if(Auth::check())
        {
            //if the product allready existe
            $existingCartItem = CartItem::where('user_id', Auth::user()->id)
            ->where('product_id', $request->product_id)->first();

            if($existingCartItem)
            {
                //update The quantity
                $existingCartItem->expires_at = $expiresAt;
                $existingCartItem->user_id = Auth::user()->id;
                // Make sure we're explicitly adding to the existing quantity
                $existingCartItem->quantity = $existingCartItem->quantity + $request->quantity;
                $existingCartItem->save();
                $cartItem = $existingCartItem;
                
            }else{
                //create now cart
                $cartItem = CartItem::create([
                    'user_id' => Auth::user()->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'expires_at' => $expiresAt,
                ]);
            }
        }else {
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
            
            // Return with cookie
            return response()->json([
                'message' => 'Product Added to Cart',
                'cart_item' => $cartItem,
                'session_id' => $sessionId
            ])->cookie('cart_session_id', $sessionId, 60*48); // 48 hours
        }
        
        // This return is only for authenticated users
        return response()->json([
            'message' => 'Product Added to Cart',
            'cart_item' => $cartItem,
        ]);
    }
  
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

    private function getSessionId(Request $request)
    {
        return $request->session_id;
    }
}
