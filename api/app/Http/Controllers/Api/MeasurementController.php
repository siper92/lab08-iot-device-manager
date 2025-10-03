<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeasurementRequest;
use App\Models\DeviceMeasurement;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\AlertProcessorService;
use Illuminate\Http\JsonResponse;

class MeasurementController extends Controller
{
    private AlertProcessorService $alertProcessor;

    public function __construct(AlertProcessorService $alertProcessor)
    {
        $this->alertProcessor = $alertProcessor;
    }

    /**
     * Store a new measurement from a device.
     */
    public function store(StoreMeasurementRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find the device by access token
        $userDevice = UserDevice::where('access_token', $validated['access_token'])
            ->whereNull('detached_at')
            ->first();

        if (!$userDevice) {
            return response()->json([
                'message' => 'Invalid access token or device is not attached',
            ], 403);
        }

        // Determine which field to use based on measure_type
        $measureData = [
            'device_id' => $userDevice->device_id,
            'measure_type' => $validated['measure_type'],
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ];

        // Store value in appropriate column based on type
        if (in_array($validated['measure_type'], ['temperature', 'humidity', 'pressure'])) {
            $measureData['f_measure'] = $validated['value'];
        } elseif ($validated['measure_type'] === 'battery') {
            $measureData['i_measure'] = (int) $validated['value'];
        } else {
            $measureData['f_measure'] = $validated['value'];
        }

        // Create the measurement
        $measurement = DeviceMeasurement::create($measureData);

        // Check for alerts
        $alertData = [
            'measure_type' => $validated['measure_type'],
            'value' => $validated['value'],
        ];

        $this->alertProcessor->checkAndCreateAlerts(
            $userDevice->device_id,
            $alertData,
            $measurement->id
        );

        return response()->json([
            'message' => 'Measurement recorded successfully',
            'data' => [
                'id' => $measurement->id,
                'device_id' => $measurement->device_id,
                'measure_type' => $measurement->measure_type,
                'value' => $validated['value'],
                'recorded_at' => $measurement->recorded_at,
            ],
        ], 201);
    }

    /**
     * Get measurements for a user's devices.
     */
    public function getUserMeasurements(int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Get all device IDs for the user
        $deviceIds = $user->devices()->pluck('devices.id');

        // Get measurements for those devices
        $measurements = DeviceMeasurement::whereIn('device_id', $deviceIds)
            ->with('device:id,device_identifier,name')
            ->orderBy('recorded_at', 'desc')
            ->paginate(50);

        return response()->json($measurements, 200);
    }

    /**
     * Get measurements for a specific device.
     */
    public function getDeviceMeasurements(int $deviceId): JsonResponse
    {
        $measurements = DeviceMeasurement::where('device_id', $deviceId)
            ->with('device:id,device_identifier,name')
            ->orderBy('recorded_at', 'desc')
            ->paginate(50);

        return response()->json($measurements, 200);
    }
}
