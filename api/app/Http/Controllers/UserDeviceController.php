<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\JwtService;
use Illuminate\Http\Request;

class UserDeviceController extends Controller
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function attach($userId, $deviceId, Request $request)
    {
        $request->validate([
            'device_identifier' => 'required|string',
        ]);

        $user = User::findOrFail($userId);
        $device = Device::where('id', $deviceId)
            ->where('device_identifier', $request->device_identifier)
            ->firstOrFail();

        $existingAttachment = $device->currentUserDevice;
        if ($existingAttachment) {
            return response()->json(['error' => 'Device is already attached to a user'], 400);
        }

        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $deviceToken = $this->jwtService->generateToken($device->id, 'device');

        return response()->json([
            'access_token' => $deviceToken,
            'user_device' => $userDevice
        ], 201);
    }

    public function detach($userId, $deviceId)
    {
        $user = User::findOrFail($userId);
        $device = Device::findOrFail($deviceId);

        $userDevice = UserDevice::where('user_id', $user->id)
            ->where('device_id', $device->id)
            ->whereNull('detached_at')
            ->first();

        if (!$userDevice) {
            return response()->json(['error' => 'Device is not attached to this user'], 400);
        }

        $userDevice->detach();

        return response()->json(['message' => 'Device detached successfully'], 200);
    }
}
