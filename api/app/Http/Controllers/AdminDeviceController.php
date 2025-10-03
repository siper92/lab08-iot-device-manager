<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class AdminDeviceController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'device_identifier' => 'required|string|unique:devices,device_identifier',
            'manufacturer' => 'nullable|string',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $device = Device::create($request->all());

        return response()->json($device, 201);
    }

    public function delete($id)
    {
        $device = Device::findOrFail($id);

        $device->measurements()->delete();
        $device->delete();

        return response()->json(['message' => 'Device deleted successfully'], 200);
    }

    public function attach($deviceId, Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $device = Device::findOrFail($deviceId);
        $user = User::findOrFail($request->user_id);

        $existingAttachment = $device->currentUserDevice;
        if ($existingAttachment) {
            return response()->json(['error' => 'Device is already attached to a user'], 400);
        }

        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        return response()->json($userDevice, 201);
    }

    public function detach($deviceId)
    {
        $device = Device::findOrFail($deviceId);

        $userDevice = $device->currentUserDevice;
        if (!$userDevice) {
            return response()->json(['error' => 'Device is not attached to any user'], 400);
        }

        $userDevice->detach();

        return response()->json(['message' => 'Device detached successfully'], 200);
    }
}
