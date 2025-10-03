<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Create a new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /**
     * Delete a user.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Detach all devices before deleting
        foreach ($user->userDevices()->whereNull('detached_at')->get() as $userDevice) {
            $userDevice->detach();
        }

        // Soft delete the user
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ], 200);
    }

    /**
     * Get a user by ID.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['devices', 'alerts'])->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'devices_count' => $user->devices->count(),
                'unread_alerts_count' => $user->alerts()->unread()->count(),
                'created_at' => $user->created_at,
            ],
        ], 200);
    }

    /**
     * Get all users.
     */
    public function index(): JsonResponse
    {
        $users = User::withCount(['devices', 'alerts'])->paginate(15);

        return response()->json($users, 200);
    }
}
