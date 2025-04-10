<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController
{
    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('view_dashboard')) {
                return response()->json([
                    'message' => 'You do not have permission to view the dashboard',
                ], 403);
            } else {
                return response()->json([
                    'products'   => Product::count(),
                    'categories' => Category::count(),
                    'users'      => User::count(),
                    'orders'     => Order::count(),
                    'revenue'    => Order::sum('total_price'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
