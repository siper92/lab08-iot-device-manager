<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceMeasurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserMeasurementController extends Controller
{
    public function getMeasurements($userId, Request $request): JsonResponse
    {
        $user = User::findOrFail($userId);

        $measurements = DeviceMeasurement::whereIn('device_id', $user->devicesIDs())
            ->with('device')
            ->orderBy('recorded_at', 'desc')
            ->paginate(
                perPage: $this->getPageLimit($request),
                page: $this->getPage($request)
            );

        return response()->json($measurements);
    }

    public function getAlerts($userId, Request $request): JsonResponse
    {
        $user = User::findOrFail($userId);

        $alerts = $user->alerts()
            ->with('device')
            ->orderBy('created_at', 'desc')
            ->paginate(
                perPage: $this->getPageLimit($request),
                page: $this->getPage($request)
            );

        return response()->json($alerts);
    }
}
