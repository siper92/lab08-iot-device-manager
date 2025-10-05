<?php

namespace App\Services;

use App\Models\DeviceMeasurement;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class MeasurementsService
{
    private bool $useKafka = false;

    public function __construct(
        protected AlertProcessorService $alertProcessor
    )
    {
        $this->useKafka = (env('APP_USE_KAFKA_FOR_MEASUREMENTS', 'false') == 'true');
    }

    public function processMeasurement(int $deviceId, array $data): array
    {
        try {
            $recordedAt = $data['recorded_at'] ?? now();
            $body = [
                'device_id' => (string)$deviceId,
                'measure_type' => $data['measure_type'] ?? 'unknown',
                'f_measure' => $data['f_measure'] ?? null,
                's_measure' => $data['s_measure'] ?? null,
                'i_measure' => $data['i_measure'] ?? null,
                'recorded_at' => is_string($recordedAt) ? $recordedAt : $recordedAt->toIso8601String(),
            ];

            if ($this->useKafka) {
                return $this->_processWithKafka($deviceId, $body);
            } else {
                $measurement = $this->storeMeasure($body);
                $this->alertProcessor->processMeasurement($measurement);
                return $body;
            }
        } catch (\Exception $e) {
            Log::error("Failed to publish measurement to Kafka: " . $e->getMessage());
            throw new \Exception('Failed to submit measurement');
        }
    }

    /**
     * @throws \Exception
     */
    public function storeMeasure(array $data): DeviceMeasurement
    {
        $this->_validateData($data);

        $measurement = DeviceMeasurement::create([
            'device_id' => (int) $data['device_id'],
            'measure_type' => $data['measure_type'],
            'f_measure' => $data['f_measure'] ?? null,
            's_measure' => $data['s_measure'] ?? null,
            'i_measure' => $data['i_measure'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        Log::info("Measurement stored with ID: {$measurement->id}");

        return $measurement;
    }

    /**
     * @throws \Exception
     */
    private function _processWithKafka($deviceId, array $data)
    {
        $this->_validateData($data);

        $message = new Message(body: $data);
        $producer = Kafka::publish('kafka')
            ->onTopic('lab08_measurements')
            ->withMessage($message);

        $producer->send();

        Log::info("Measurement published to Kafka for device {$deviceId}");

        return $message->getBody();
    }

    private function _validateData(array $data): bool
    {
        if (!isset($data['device_id'], $data['measure_type'])) {
            Log::error("Invalid message structure", ['body' => $data]);
            throw new \Exception("invalid message structure");
        }

        return true;
    }
}
