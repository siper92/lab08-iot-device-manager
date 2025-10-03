<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceMeasurement;
use Illuminate\Http\Request;

class UserMeasurementController extends Controller
{
    public function getMeasurements($userId)
    {
        $user = User::findOrFail($userId);

        $deviceIds = $user->devices()->pluck('devices.id');

        $measurements = DeviceMeasurement::whereIn('device_id', $deviceIds)
            ->with('device')
            ->orderBy('recorded_at', 'desc')
            ->get();

        return response()->json($measurements);
    }

    public function getAlerts($userId)
    {
        $user = User::findOrFail($userId);

        $alerts = $user->alerts()
            ->with('device')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($alerts);
    }
}
