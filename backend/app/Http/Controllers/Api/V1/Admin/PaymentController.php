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
        // dd($request->all());

        try {

            Stripe::setApiKey(config('services.stripe.secret'));

            if (auth()->check()) {
                $products = CartItem::where('user_id', auth()->id())->with('product')->get();
                $user = auth()->user();
            } else {
                $products = CartItem::where('session_id', $request->session_id)->with('product')->get();
                // $session_user = CartItem::where('session_id', $request->session_id)->first();
            }

            // $orderId = 'ORD-' . strtoupper(Str::random(10));


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
            // dd($request->session_id);
            $order = Order::create([
                'user_id' => auth()->check() ? auth()->id() : null,
                'session_id' => $request->session_id ? $request->session_id : null,
                'total_price' => $totalAmount,
                'status' => 'en cours',
            ]);
            // dd($order);

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
                    'order_id' => $order->id,
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

            // Check if payment was successful
            if ($session->payment_status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment not completed'
                ], 400);
            }

            // Get metadata from the session
            if (auth()->check()) {
                $orderId = Order::where('user_id', auth()->id())->first();
            } else {
                $orderId = Order::where('session_id', $request->x_cart_session)->first();
            }
            // dd($session->metadata);
            $userId = $session->metadata->user_id ?? 'guest';

            // Find cart items
            if ($userId !== 'guest') {
                $cartItems = CartItem::where('user_id', $userId)->with('product')->get();
            } else {
                // For guest users, we need the session_id from the request
                $cartItems = CartItem::where('session_id', $request->x_cart_session)
                    ->with('product')
                    ->get();
            }

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No items found in cart'
                ], 400);
            }

            // dd($request->all());
            Payment::where('order_id', $orderId->id)->update([
                'status' => 'reussi',
                'transaction_id' => $session->payment_intent,
            ]);

            // Clear cart
            if ($userId !== 'guest') {
                CartItem::where('user_id', $userId)->delete();
            } else {
                CartItem::where('session_id', $request->cookie('cart_session_id') ?? $request->header('X-Cart-Session'))->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
