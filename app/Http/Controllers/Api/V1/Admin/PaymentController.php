<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\CartItem;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of all payments
     */

    public function checkout(Request $request)
    {

        try {

            Stripe::setApiKey(config('services.stripe.secret'));

            if(auth()->check()) {
                $products = CartItem::where('user_id', auth()->id())->with('product')->get();
            } else {
                $products = CartItem::where('session_id', $request->session_id)->with('product')->get();
            }

            $orderId = 'ORD-' . strtoupper(Str::random(10));


            $lineItems = [];
            $totalAmount = 0;

            foreach ($products as $product) {

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $product->product->price * 100,
                        'product_data' => [
                            'name' => $product->product->name,
                        ],
                    ],
                    'quantity' => $product->quantity,
                ];

                $totalAmount += $product->product->price * $product->quantity;
            }

            // Create Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => url('/api/payment/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/api/checkout'),
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $request->user_id ?? 'guest',
                ],
            ]);


            // create order & order items in database


            // create payment in database


            // return redirect($session->url);
            return response()->json(['id' => $session->id, 'url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }

    public function index(Request $request)
    {
        $payments = Payment::with('order')->whereHas('order', function ($query) {
            $query->where('user_id', auth()->id);
        })->latest()->get();

        return response()->json([
            'payments' => $payments,
            'message' => $payments->isNotEmpty() ? 'Payments found' : 'No payments found'
        ], $payments->isNotEmpty() ? 200 : 404);
    }

    /**
     * Display the specified payment
     */
    public function show(Request $request, string $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        return response()->json([
            'payment' => $payment
        ]);
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        try {
            $sessionId = $request->session_id;
            
            if (!$sessionId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session ID is required'
                ], 400);
            }
            
            // Verify the session with Stripe
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = Session::retrieve($sessionId);
            
            // Check if payment was successful
            if ($session->payment_status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment not completed'
                ], 400);
            }
            
            // Get metadata from the session
            $orderId = $session->metadata->order_id ?? null;
            $userId = $session->metadata->user_id ?? 'guest';
            
            // Find cart items
            if ($userId !== 'guest') {
                $cartItems = CartItem::where('user_id', $userId)->with('product')->get();
            } else {
                // For guest users, we need the session_id from the request
                $cartItems = CartItem::where('session_id', $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session'))
                    ->with('product')
                    ->get();
            }
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No items found in cart'
                ], 400);
            }
            
            // Calculate total amount
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item->product->price * $item->quantity;
            }
            
            // Create order
            $order = Order::create([
                'reference' => $orderId,
                'user_id' => $userId !== 'guest' ? $userId : null,
                'session_id' => $userId === 'guest' ? $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session') : null,
                'total_price' => $totalAmount,
                'status' => 'processing',
                'shipping_address' => $request->shipping_address ?? null,
                'payment_method' => 'stripe',
            ]);
            
            // Create order items
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
                
                // Update product stock
                $product = $item->product;
                $product->stock -= $item->quantity;
                $product->save();
            }
            
            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => 'stripe',
                'transaction_id' => $session->payment_intent,
                'status' => 'completed',
            ]);
            
            // Clear cart
            if ($userId !== 'guest') {
                CartItem::where('user_id', $userId)->delete();
            } else {
                CartItem::where('session_id', $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session'))->delete();
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'order' => $order->load('items.product')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
