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
            ]);
        } else {
            $categories = Category::all();
            if ($categories->isNotEmpty()) {
                return response()->json([
                    'categories' => $categories,
                ]);
            } else {
                return response()->json([
                    'message' => 'No categories found',
                ]);
            }
        }
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->can('view_categories')) {
            return response()->json([
                'message' => 'You do not have permission to view categories',
            ]);
        } else {
            $categories = Category::find($id);
            if ($categories) {
                return response()->json([
                    'categories' => $categories,
                ]);
            } else {
                return response()->json([
                    'message' => 'No categories found',
                ]);
            }
        }
    }

    public function store(Request $request)
    {
        if (!$request->user()->can('create_categories')) {
            return response()->json([
                'message' => 'You do not have permission to create categories',
            ]);
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
            ]);
        }
    }


    public function update(Request $request, $id)
    {
        if (!$request->user()->can('edit_categories')) {
            return response()->json([
                'message' => 'You do not have permission to update categories',
            ]);
        } else {
            $category = Category::find($id);
            if ($category) {
                $category->update($request->all());
                return response()->json([
                    'category' => $category,
                ]);
            } else {
                return response()->json([
                    'message' => 'No category found',
                ]);
            }
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_categories')) {
            return response()->json([
                'message' => 'You do not have permission to delete categories',
            ]);
        } else {
            $category = Category::find($id);
            if ($category) {
                $category->delete();
                return response()->json([
                    'message' => 'Category deleted successfully',
                ]);
            } else {
                return response()->json([
                    'message' => 'No category found',
                ]);
            }
        }
    }
}
