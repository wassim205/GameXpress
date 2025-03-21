<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\CartItem;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
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

            if (auth()->check()) {
                $products = CartItem::where('user_id', auth()->id())->with('product')->get();
                $user = auth()->user();
            } else {
                $products = CartItem::where('session_id', $request->session_id)->with('product')->get();
                // $session_user = CartItem::where('session_id', $request->session_id)->first();
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

            $order = Order::create([
                'user_id' => $user->id ?? null,
                'total_price' => $totalAmount,
                'status' => 'en cours',
            ]);

            foreach ($products as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->product->id,
                    'quantity' => $product->quantity,
                    'price' => $product->product->price,
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_type' => 'carte bancaire',
                'status' => 'en attente',
            ]);

            // Create Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => url('/api/payment/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/api/cancel'),
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $request->user_id ?? 'guest',
                ],
            ]);


            return response()->json(['id' => $session->id, 'url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    // cancel payment
    public function cancel(Request $request)
    {
        try {
            $orderId = $request->order_id ?? null;
            $order = Order::where('id', $orderId)->first();

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found.',
                ], 404);
            }

            $order->items()->delete();

            $order->payment()->delete();

            $order->delete();

            return response()->json([
                'status' => 'canceled',
                'message' => 'The payment was canceled, and the order has been removed.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel the payment.',
                'error' => $e->getMessage(),
            ], 500);
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
            
            // For testing purposes, you can log the session details
            Log::info('Stripe Session', ['session' => $session]);
            
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
            
            // Try multiple ways to find cart items
            $cartItems = null;
            
            // 1. Try with user_id if authenticated
            if ($userId !== 'guest') {
                $cartItems = CartItem::where('user_id', $userId)->with('product')->get();
            }
            
            // 2. Try with session_id from request
            if (empty($cartItems) || $cartItems->isEmpty()) {
                $cartItems = CartItem::where('session_id', $request->session_id)->with('product')->get();
            }
            
            // 3. Try with X-Cart-Session header
            if (empty($cartItems) || $cartItems->isEmpty()) {
                $cartItems = CartItem::where('session_id', $request->header('X-Cart-Session'))->with('product')->get();
            }
            
            // 4. Try with cookie
            if (empty($cartItems) || $cartItems->isEmpty()) {
                $cartItems = CartItem::where('session_id', $request->cookie('cart_session_id'))->with('product')->get();
            }
            
            // Check if this payment was already processed
            $existingPayment = Payment::where('transaction_id', $session->payment_intent)->first();
            if ($existingPayment) {
                $order = Order::find($existingPayment->order_id);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment was already processed',
                    'order' => $order->load('items.product')
                ]);
            }
            
            // For testing in Postman, if no cart items found, create a dummy order
            if (empty($cartItems) || $cartItems->isEmpty()) {
                // Create a dummy order for testing purposes
                $order = Order::create([
                    'reference' => $orderId,
                    'user_id' => $userId !== 'guest' ? $userId : null,
                    'session_id' => $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session'),
                    'total_price' => 100.00, // Dummy price
                    'status' => 'en cours',
                    // 'payment_method' => 'stripe', // This field doesn't exist in orders table
                ]);
                
                // Create a dummy order item
                $order->items()->create([
                    'product_id' => 1, // Assuming product ID 1 exists
                    'quantity' => 1,
                    'price' => 100.00,
                ]);
                
                // Create payment record
                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => 'carte bancaire', // Changed from 'payment_method' to 'payment_type'
                    'transaction_id' => $session->payment_intent,
                    'status' => 'reussi', // Changed from 'completed' to 'reussi' to match enum values
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Test payment processed successfully',
                    'order' => $order->load('items'),
                    'note' => 'This is a test order created because no cart items were found'
                ]);
            }
            
            // Continue with normal processing if cart items were found
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
                'status' => 'en cours', // Changed from 'processing' to match your database enum
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
