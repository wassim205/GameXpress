<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController
{
    public function index(Request $request)
    {

        if (!$request->user()->can('view_categories')) {
            return response()->json([
                'message' => 'You do not have permission to view categories',
            ], 403);
        } else {
            $categories = Category::all();
            if ($categories->isNotEmpty()) {
                return response()->json([
                    'categories' => $categories,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No categories found',
                ], 404);
            }
        }
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->can('view_categories')) {
            return response()->json([
                'message' => 'You do not have permission to view categories',
            ], 403);
        } else {
            $categorie = Category::find($id);
            if ($categorie) {
                return response()->json([
                    'category' => $categorie,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No categories found',
                ], 404);
            }
        }
    }

    public function store(Request $request)
    {
        if (!$request->user()->can('create_categories')) {
            return response()->json([
                'message' => 'You do not have permission to create categories',
            ], 403);
        } else {
            $request->validate(
                [
                    'name' => 'required',
                    'slug' => 'required',
                ],
            );
            $category = Category::create($request->all());
            return response()->json([
                'category' => $category,
            ], 201);
        }
    }


    public function update(Request $request, $id)
    {
        if (!$request->user()->can('edit_categories')) {
            return response()->json([
                'message' => 'You do not have permission to update categories',
            ], 403);
        } else {
            $category = Category::find($id);
            if ($category) {
                $category->update($request->all());
                return response()->json([
                    'category' => $category,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No category found',
                ], 404);
            }
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_categories')) {
            return response()->json([
                'message' => 'You do not have permission to delete categories',
            ], 403);
        } else {
            $category = Category::find($id);
            if ($category) {
                $category->delete();
                return response()->json([
                    'message' => 'Category deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No category found',
                ], 404);
            }
        }
    }
}
