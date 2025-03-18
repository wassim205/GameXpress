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
  

     
    // This is how the function should be called on method index
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
