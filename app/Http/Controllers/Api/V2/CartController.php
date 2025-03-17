<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Admin\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //check for Authntification of User
        if(Auth::check()){
            $cartItems = CartItem::with('product')->where('user_id', Auth::user()->id)->get();

        }else{
            // For Guest Client
            $sessionId = $this->getSessionId($request);
            $cartItems = CartItem::with('product')->where('session_id', $sessionId)->get();
        }

        //Calculate The Total Price
        $subtotal = 0;
        $items = $cartItems->map(function($item) use(&$subtotal) {
                $price = $item->product->price;
                $itemTotal = $price * $item->quantity;
                $subtotal += $itemTotal;

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'total' => $itemTotal,
                    'image' => $item->product->image,
                    'expires_at' => $item->expires_at,
                ];
            });

            $tax = $subtotal * 0.2;
            $total = $subtotal + $tax;
            return response()->json([
                'items' => $items,
                'summary' => [
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]
            ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        // Ceck if Product Existe
        // $product = Product::where('id', $request->product_id)->first();
        $product = Product::findOrFail($request->product_id);
        if($product->stock < $request->quantity){
            return response()->json([
                'message' => 'Product Out of Stock',
            ], 400);
        }
        // Set Expired Date
        $expiresAt = Carbon::now()->addHours(48);
        //Check if User is Authenticated
        if(Auth::check()){
            // Check if CartItem Existe
            $cartItem = CartItem::updateOrCreate(
                [
                    'user_id' => Auth::user()->id,
                    'product_id' => $request->product_id
                ],
                [
                    'quantity' => $request->quantity,
                    'expires_at' => $expiresAt,
                ]
            );
        }else {
            // For Guest
            $sessionId = $this->getSessionId($request);
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
        }
        return response()->json([
            'message' => 'Product Added to Cart',
            'cart_item' => $cartItem,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::findOrFail($id);

        // Check if the cart item belongs to the current user or session
        if (!$this->authorizeCartAction($request, $cartItem)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if product has enough stock
        $product = Product::findOrFail($cartItem->product_id);
        if ($product->stock < $request->quantity) {
            return response()->json([
                'message' => 'Not enough stock available'
            ], 422);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => $cartItem
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $cartItem = CartItem::findOrFail($id);

        // Check if the cart item belongs to the current user or session
        if (!$this->authorizeCartAction($request, $cartItem)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart'
        ]);
    }

    public function mergeCart(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $sessionId = $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session');
        
        if (!$sessionId) {
            return response()->json(['message' => 'No session cart to merge'], 200);
        }

        // Get all items from the session cart
        $sessionCartItems = CartItem::where('session_id', $sessionId)->get();

        foreach ($sessionCartItems as $sessionItem) {
            // Check if the user already has this product in their cart
            $userItem = CartItem::where('user_id', Auth::id())
                ->where('product_id', $sessionItem->product_id)
                ->first();

            if ($userItem) {
                // Update quantity if the item already exists
                $userItem->quantity += $sessionItem->quantity;
                $userItem->save();
                $sessionItem->delete();
            } else {
                // Transfer the item to the user's cart
                $sessionItem->user_id = Auth::id();
                $sessionItem->session_id = null;
                $sessionItem->save();
            }
        }

        return response()->json([
            'message' => 'Cart merged successfully'
        ]);
    }

    /**
     * Get or create a session ID for guest users
     */
    private function getSessionId(Request $request)
    {
        $sessionId = $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session');
        
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
        }
        
        return $sessionId;
    }

    /**
     * Check if the current user or session is authorized to modify a cart item
     */
    private function authorizeCartAction(Request $request, CartItem $cartItem)
    {
        if (Auth::check()) {
            return $cartItem->user_id === Auth::id();
        } else {
            $sessionId = $this->getSessionId($request);
            return $cartItem->session_id === $sessionId;
        }
    }
}
