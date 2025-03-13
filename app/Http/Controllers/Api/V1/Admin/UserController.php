<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController
{
    public function index(Request $request)
    {
        if (!$request->user()->can('view_users')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $users = User::all();
        return response()->json(['users' => $users], 200);
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->can('view_users')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = User::find($id);
        if ($user) {
            return response()->json(['user' => $user], 200);
        }
        return response()->json(['error' => 'User not found'], 404);
    }

    public function store(Request $request)
    {
        if (!$request->user()->can('create_users')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name'
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->assignRole($request->role);
        return response()->json(['user' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->can('edit_users')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|min:8',
            'role' => 'required|string|exists:roles,name'
        ]);
        $user->syncRoles([$validatedData['role']]);
        if (!empty($validatedData['name'])) {
            $user->fill([
                'name' => $validatedData['name'],
            ]);
        }
        if (!empty($validatedData['email'])) {
            $user->fill([
                'email' => $validatedData['email'],
            ]);
        }

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        return response()->json(['user' => $user], 200);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete_users')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return response()->json(['message' => 'User deleted'], 200);
        }
        return response()->json(['error' => 'User not found'], 404);
    }
}
