<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;

class DashboardController
{
    public function index(Request $request)
    {
        if (!$request->user()->can('view_dashboard')) {
            return response()->json([
                'total_products' => 100,
                'total_users' => 50,
                'total_orders' => 30,
            ]);
        }
        else {
            return response()->json([
                'message' => 'You do not have permission to view the dashboard',
                ]);
        }
    }
}
