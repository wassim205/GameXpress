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
        }
        $emailResponse = $this->sendEmail();
        if ($emailResponse instanceof \Illuminate\Http\JsonResponse) {
            return $emailResponse;
        }


        $products = Product::all();
        return response()->json(['message' => 'Accès autorisé', $products], 200);
    }

    public function sendEmail()
    {
        $products = Product::where('stock', '<', 10)->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Aucun produit en stock'], 200);
        }

        $admin = User::role('super_admin')->first();
        if (!$admin) {
            return response()->json(['message' => 'Aucun administrateur super'], 404);
        }

        Notification::send($admin, new StockLowNotification($products));
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'slug' => 'required|string|max:255|unique:products',
            'status' => 'required',
            'category_id' => 'required|exists:categories,id'
        ]);

        $product = Product::create($request->all());
        return response()->json(['message' => 'Produit ajouté avec succès', 'product' => $product], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $product->update($request->all());
        return response()->json(['message' => 'Produit mis à jour', 'product' => $product], 200);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Produit supprimé'], 200);
    }
}
