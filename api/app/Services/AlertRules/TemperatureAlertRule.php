<?php

namespace App\Services\AlertRules;

use App\Contracts\AlertRule;
use App\Enums\Channel;
use App\Enums\Severity;

class TemperatureAlertRule implements AlertRule
{
    private float $minTemperature;
    private float $maxTemperature;

    public function __construct(float $minTemperature = 0, float $maxTemperature = 30)
    {
        $this->minTemperature = $minTemperature;
        $this->maxTemperature = $maxTemperature;
    }

    public function isTriggered(array $data): bool
    {
        if (!isset($data['measure_type']) || $data['measure_type'] !== 'temperature') {
            return false;
        }

        $temperature = $data['value'] ?? null;

        if ($temperature === null) {
            return false;
        }

        return $temperature < $this->minTemperature || $temperature > $this->maxTemperature;
    }

    public function getAlertMessage(array $data): string
    {
        $temperature = $data['value'];

        if ($temperature < $this->minTemperature) {
            return "Temperature alert: {$temperature}°C is below the minimum threshold of {$this->minTemperature}°C";
        }

        return "Temperature alert: {$temperature}°C exceeds the maximum threshold of {$this->maxTemperature}°C";
    }

    public function getAlertType(): string
    {
        return 'temperature_threshold';
    }

    public function getSeverity(): Severity
    {
        return Severity::HIGH;
    }

    public function getChannels(): array
    {
        return [Channel::DATABASE];
    }
}
