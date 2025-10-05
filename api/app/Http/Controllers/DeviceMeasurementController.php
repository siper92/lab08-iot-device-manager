<?php

namespace App\Http\Controllers;

use App\Services\MeasurementsService;
use Illuminate\Http\Request;

class DeviceMeasurementController extends Controller
{
    protected MeasurementsService $measureDeviceService;

    public function __construct(MeasurementsService $measureDeviceService)
    {
        $this->measureDeviceService = $measureDeviceService;
    }

    public function submit($deviceId, Request $request)
    {
        $validatedData = $request->validate([
            'measure_type' => 'required|string',
            'f_measure' => 'nullable|numeric',
            's_measure' => 'nullable|string',
            'i_measure' => 'nullable|integer',
            'recorded_at' => 'nullable|date',
        ]);

        try {
            $messageBody = $this->measureDeviceService->processMeasurement($deviceId, $validatedData);
            \Log::debug('Submitted measurement', ['body' => $messageBody]);

            return response()->json([
                "success" => true,
                "timestamp" => now()->toIso8601String(),
                'message' => 'submitted successfully',
            ], 202);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
