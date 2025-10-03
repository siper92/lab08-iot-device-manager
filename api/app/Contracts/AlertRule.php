<?php

namespace App\Contracts;

use App\Enums\Severity;
use App\Enums\Channel;

interface AlertRule
{
    /**
     * Check if the alert rule is triggered.
     *
     * @param array $data The measurement data
     * @return bool
     */
    public function isTriggered(array $data): bool;

    /**
     * Get the alert message.
     *
     * @param array $data The measurement data
     * @return string
     */
    public function getAlertMessage(array $data): string;

    /**
     * Get the alert type.
     *
     * @return string
     */
    public function getAlertType(): string;

    /**
     * Get the severity level.
     *
     * @return Severity
     */
    public function getSeverity(): Severity;

    /**
     * Get the notification channels.
     *
     * @return array<Channel>
     */
    public function getChannels(): array;
}
