<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert Rules Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file defines the alert rules that will be used
    | to monitor device measurements and trigger alerts.
    |
    */

    'rules' => [
        // Temperature alert rule
        [
            'class' => \App\Services\AlertRules\TemperatureAlertRule::class,
            'params' => [
                'min_temperature' => env('ALERT_MIN_TEMPERATURE', 0),
                'max_temperature' => env('ALERT_MAX_TEMPERATURE', 30),
            ],
        ],

        // Add more alert rules here as needed
        // Example:
        // [
        //     'class' => \App\Services\AlertRules\HumidityAlertRule::class,
        //     'params' => ['min' => 20, 'max' => 80],
        // ],
    ],
];
