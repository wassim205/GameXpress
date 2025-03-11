<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Product;
use App\Models\User;
use App\Notifications\StockLowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ProductController extends Controller
{
    public function index(Request $request, Product $product)
    {
        if (!$request->user()->can('view_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        } else {
            $this->sendEmail();
            return response()->json(['message' => 'Accès autorisé'], 200);
        }
    }

    public function permissions(Request $request)
    {
        return response()->json([
            'roles' => $request->user()->getRoleNames(),
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }


    public function sendEmail()
    {
        $products = Product::where('stock', '<', 25)->get();
        if ($products) {
            $admin = User::role('super_admin')->first();
            foreach ($products as $product) {
                Notification::send($admin, new StockLowNotification($product));
            }
        }
    }
    
}
