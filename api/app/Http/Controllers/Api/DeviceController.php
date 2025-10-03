<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    /**
     * Create a new device.
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $device = Device::create($validated);

        return response()->json([
            'message' => 'Device created successfully',
            'data' => [
                'id' => $device->id,
                'device_identifier' => $device->device_identifier,
                'name' => $device->name,
                'manufacturer' => $device->manufacturer,
                'description' => $device->description,
                'created_at' => $device->created_at,
            ],
        ], 201);
    }

    /**
     * Delete a device.
     */
    public function destroy(int $id): JsonResponse
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json([
                'message' => 'Device not found',
            ], 404);
        }

        // Soft delete the device (will cascade to measurements due to foreign key)
        $device->delete();

        return response()->json([
            'message' => 'Device deleted successfully',
        ], 200);
    }

    /**
     * Attach a device to a user.
     */
    public function attachToUser(int $userId, int $deviceId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $device = Device::find($deviceId);
        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        // Check if device is already attached to this user
        $existingAttachment = UserDevice::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->whereNull('detached_at')
            ->first();

        if ($existingAttachment) {
            return response()->json([
                'message' => 'Device is already attached to this user',
                'data' => [
                    'access_token' => $existingAttachment->access_token,
                ],
            ], 200);
        }

        // Create new user-device relationship
        $userDevice = UserDevice::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
        ]);

        return response()->json([
            'message' => 'Device attached to user successfully',
            'data' => [
                'user_device_id' => $userDevice->id,
                'access_token' => $userDevice->access_token,
                'attached_at' => $userDevice->attached_at,
            ],
        ], 201);
    }

    /**
     * Detach a device from a user.
     */
    public function detachFromUser(int $userId, int $deviceId): JsonResponse
    {
        $userDevice = UserDevice::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->whereNull('detached_at')
            ->first();

        if (!$userDevice) {
            return response()->json([
                'message' => 'Device is not attached to this user',
            ], 404);
        }

        $userDevice->detach();

        return response()->json([
            'message' => 'Device detached from user successfully',
            'data' => [
                'detached_at' => $userDevice->detached_at,
            ],
        ], 200);
    }

    /**
     * Get a device by ID.
     */
    public function show(int $id): JsonResponse
    {
        $device = Device::with(['users'])->find($id);

        if (!$device) {
            return response()->json([
                'message' => 'Device not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $device->id,
                'device_identifier' => $device->device_identifier,
                'name' => $device->name,
                'manufacturer' => $device->manufacturer,
                'description' => $device->description,
                'current_owners' => $device->users->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
                'created_at' => $device->created_at,
            ],
        ], 200);
    }

    /**
     * Get all devices.
     */
    public function index(): JsonResponse
    {
        $devices = Device::with(['users'])->paginate(15);

        return response()->json($devices, 200);
    }
}
