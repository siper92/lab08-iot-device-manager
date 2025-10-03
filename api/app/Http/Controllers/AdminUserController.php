<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json($user, 201);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        foreach ($user->userDevices as $userDevice) {
            if ($userDevice->isAttached()) {
                $userDevice->detach();
            }
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
