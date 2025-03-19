<?php

namespace App\Http\Controllers\Api\V1\Admin;


use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of all payments
     */
    public function index(Request $request)
    {
        // if (!$request->user()->can('view_payments')) {
        //     return response()->json(['message' => 'Accès interdit'], 403);
        // }
        $payments = Payment::with('order')->latest()->get();

        return response()->json([
            'payments' => $payments
        ]);
    }

   
}
