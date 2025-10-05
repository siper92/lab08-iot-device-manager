<?php

namespace App\Http\Controllers;

use App\Services\MeasureDeviceService;
use Illuminate\Http\Request;

class DeviceMeasurementController extends Controller
{
    protected MeasureDeviceService $measureDeviceService;

    public function __construct(MeasureDeviceService $measureDeviceService)
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
            $measurement = $this->measureDeviceService->submitMeasurement($deviceId, $validatedData);
            return response()->json($measurement, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
