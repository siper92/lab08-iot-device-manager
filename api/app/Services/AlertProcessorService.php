<?php

namespace App\Services;

use App\Contracts\AlertRule;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class AlertProcessorService
{
    /**
     * @var array<AlertRule>
     */
    private array $rules;

    /**
     * Create a new alert processor service.
     *
     * @param array<AlertRule> $rules
     */
    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * Add a rule to the processor.
     *
     * @param AlertRule $rule
     * @return void
     */
    public function addRule(AlertRule $rule): void
    {
        $this->rules[] = $rule;
    }

    public function processMeasurement(DeviceMeasurement $measurement): void
    {
        $deviceId = $measurement->device_id;
        $data = $measurement->data;

        Log::debug("[AlertProcessorService] Measurement $deviceId: " . json_encode($measurement));

        $this->checkAndCreateAlerts($deviceId, $data ?? [], $measurement->id);
    }

    /**
     * Check and create alerts for a device measurement.
     *
     * @param int $deviceId
     * @param array $data The measurement data
     * @param int|null $measurementId
     * @return void
     */
    public function checkAndCreateAlerts(int $deviceId, array $data, ?int $measurementId = null): void
    {
        $userDevices = UserDevice::where('device_id', $deviceId)
            ->whereNull('detached_at')
            ->get();

        if ($userDevices->isEmpty()) {
            Log::info("No active users found for device {$deviceId}");
            return;
        }

        // Check each alert rule
        foreach ($this->rules as $rule) {
            if ($rule->isTriggered($data)) {
                // Create alert for each user who owns the device
                foreach ($userDevices as $userDevice) {
                    $this->createAlert(
                        $userDevice->user_id,
                        $deviceId,
                        $rule,
                        $data,
                        $measurementId
                    );
                }
            }
        }
    }

    /**
     * Create an alert.
     *
     * @param int $userId
     * @param int $deviceId
     * @param AlertRule $rule
     * @param array $data
     * @param int|null $measurementId
     * @return Alert
     */
    private function createAlert(
        int $userId,
        int $deviceId,
        AlertRule $rule,
        array $data,
        ?int $measurementId = null
    ): Alert {
        $alert = Alert::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'alert_type' => $rule->getAlertType(),
            'message' => $rule->getAlertMessage($data),
            'measurement_id' => $measurementId,
            'severity' => $rule->getSeverity()->value,
            'triggered_at' => now(),
            'is_read' => false,
        ]);

        Log::info("Alert created: {$alert->id} for user {$userId}, device {$deviceId}");

        // Here you could dispatch notifications to various channels
        // foreach ($rule->getChannels() as $channel) {
        //     $this->notifyChannel($channel, $alert);
        // }

        return $alert;
    }
}
