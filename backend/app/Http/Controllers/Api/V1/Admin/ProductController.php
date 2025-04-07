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

        return response()->json([
            'message' => 'Accès autorisé',
            'data' => $products,
            'count' => $products->count(),
        ], 200);
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

    public function show(Request $request, $id)
    {
        if (!$request->user()->can('view_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        return response()->json($product);
    }

    public function store(Request $request)
    {
        if (!$request->user()->can('create_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'slug' => 'required|string|max:255|unique:products',
            'status' => 'required',
            'category_id' => 'required|exists:categories,id',
            'images' => 'array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = Product::create($request->all());

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'image_url' => $path,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return response()->json(['message' => 'Produit ajouté avec succès', 'product' => $product->load('images')], 201);
    }

    public function update(Request $request, $id)
    {

        if (!$request->user()->can('edit_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $product->update($request->except('images'));

        if ($request->hasFile('images')) {

            $product->images()->delete();

            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'image_url' => $path,
                    'is_primary' => $index === 0,
                ]);
            }
        }
        return response()->json(['message' => 'Produit mis à jour', 'product' => $product->load('images')], 200);
    }


    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Produit supprimé'], 200);
    }
}
