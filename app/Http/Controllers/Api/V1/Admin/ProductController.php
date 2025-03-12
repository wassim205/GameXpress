<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Product;
use App\Models\User;
use App\Notifications\StockLowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->can('view_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        } else {
            $this->sendEmail();
            $products = Product::all();
            return response()->json(['message' => 'Accès autorisé', $products], 200);
        }
    }

    // public function permissions(Request $request)
    // {
    //     return response()->json([
    //         'roles' => $request->user()->getRoleNames(),
    //         'permissions' => $request->user()->getAllPermissions()->pluck('name'),
    //     ]);
    // }


    public function sendEmail()
    {
        $products = Product::where('stock', '<', 25)->get();
        if ($products->isNotEmpty()) {
            $admin = User::role('super_admin')->first();
            Notification::send($admin, new StockLowNotification($products));
        }
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        return response()->json(['message' => 'Product found', $product], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'required|exists:categories,id'
        ]);

        $product = Product::create($request->all());
        return response()->json(['message' => 'Product created', $product], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $product->update($request->all());
        return response()->json(['message' => 'Product updated', $product], 200);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted'], 200);
    }
}
