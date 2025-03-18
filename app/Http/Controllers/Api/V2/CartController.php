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
}
