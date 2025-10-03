<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Services\AlertProcessorService;
use Illuminate\Http\Request;

class DeviceMeasurementController extends Controller
{
    protected AlertProcessorService $alertProcessor;

    public function __construct(AlertProcessorService $alertProcessor)
    {
        $this->alertProcessor = $alertProcessor;
    }

    public function submit($deviceId, Request $request)
    {
        $request->validate([
            'measure_type' => 'required|string',
            'f_measure' => 'nullable|numeric',
            's_measure' => 'nullable|string',
            'i_measure' => 'nullable|integer',
            'recorded_at' => 'nullable|date',
        ]);

        $device = Device::findOrFail($deviceId);

        $measurement = DeviceMeasurement::create([
            'device_id' => $device->id,
            'measure_type' => $request->measure_type,
            'f_measure' => $request->f_measure,
            's_measure' => $request->s_measure,
            'i_measure' => $request->i_measure,
            'recorded_at' => $request->recorded_at ?? now(),
        ]);

        $this->alertProcessor->processMeasurement($measurement);

        return response()->json($measurement, 201);
    }
}
