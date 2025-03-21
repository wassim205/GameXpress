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


            // return redirect($session->url);
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
            // dd($orderId);
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
            $query->where('user_id', auth()->id());
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
}
