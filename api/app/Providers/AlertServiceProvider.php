<?php

namespace App\Providers;

use App\Services\AlertProcessorService;
use Illuminate\Support\ServiceProvider;

class AlertServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AlertProcessorService::class, function ($app) {
            $service = new AlertProcessorService();

            // Load alert rules from config
            $rulesConfig = config('alert_rules.rules', []);

            foreach ($rulesConfig as $ruleConfig) {
                $class = $ruleConfig['class'];
                $params = $ruleConfig['params'] ?? [];

                // Instantiate the rule with parameters
                $rule = new $class(...array_values($params));
                $service->addRule($rule);
            }

            return $service;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
