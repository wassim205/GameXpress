<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\User;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->can('view_products')) {
            return response()->json(['message' => 'Accès interdit'], 403);
        } else {
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

}
