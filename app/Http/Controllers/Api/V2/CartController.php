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
    public function destroy(string $id)
    {
        //
    }
}
