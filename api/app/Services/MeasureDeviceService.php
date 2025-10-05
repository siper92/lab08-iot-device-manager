<?php

namespace App\Services;

use App\Models\DeviceMeasurement;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class MeasureDeviceService
{
    protected AlertProcessorService $alertProcessor;

    /**
     * Create a new measure device service.
     *
     * @param AlertProcessorService $alertProcessor
     */
    public function __construct(AlertProcessorService $alertProcessor)
    {
        $this->alertProcessor = $alertProcessor;
    }

    /**
     * Submit a device measurement.
     *
     * @param int $deviceId
     * @param array $data
     * @return DeviceMeasurement
     * @throws \Exception
     */
    public function submitMeasurement(int $deviceId, array $data): DeviceMeasurement
    {
        try {
            $measurement = DeviceMeasurement::create([
                'device_id' => $deviceId,
                'measure_type' => $data['measure_type'],
                'f_measure' => $data['f_measure'] ?? null,
                's_measure' => $data['s_measure'] ?? null,
                'i_measure' => $data['i_measure'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? now(),
            ]);
        } catch (QueryException $e) {
            Log::error($e);
            throw new \Exception('Invalid device data');
        }

        $this->alertProcessor->processMeasurement($measurement);

        return $measurement;
    }
}
