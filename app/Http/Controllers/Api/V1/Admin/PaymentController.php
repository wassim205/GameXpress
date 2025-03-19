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
    $payments = Payment::with('order')->whereHas('order', function ($query) {
        $query->where('user_id', auth()->id());
    })->latest()->get();

    return response()->json([
        'payments' => $payments,
        'message' => $payments->isNotEmpty() ? 'Payments found' : 'No payments found'
    ], $payments->isNotEmpty() ? 200 : 404);
}
}
