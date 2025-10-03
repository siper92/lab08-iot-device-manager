<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_temperature_measurement(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $measurementData = [
            'access_token' => $userDevice->access_token,
            'measure_type' => 'temperature',
            'value' => 25.5,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'device_id', 'measure_type', 'value'],
            ]);

        $this->assertDatabaseHas('device_measurements', [
            'device_id' => $device->id,
            'measure_type' => 'temperature',
            'f_measure' => 25.5,
        ]);
    }

    public function test_cannot_submit_measurement_with_invalid_token(): void
    {
        $measurementData = [
            'access_token' => 'invalid-token',
            'measure_type' => 'temperature',
            'value' => 25.5,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['access_token']);
    }

    public function test_cannot_submit_measurement_with_invalid_measure_type(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $measurementData = [
            'access_token' => $userDevice->access_token,
            'measure_type' => 'invalid_type',
            'value' => 25.5,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['measure_type']);
    }

    public function test_can_get_user_measurements(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        DeviceMeasurement::create([
            'device_id' => $device->id,
            'measure_type' => 'temperature',
            'f_measure' => 25.5,
            'recorded_at' => now(),
        ]);

        $response = $this->getJson("/api/users/{$user->id}/measurements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_id', 'measure_type', 'recorded_at'],
                ],
            ]);
    }

    public function test_measurements_are_paginated(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        // Create 60 measurements
        for ($i = 0; $i < 60; $i++) {
            DeviceMeasurement::create([
                'device_id' => $device->id,
                'measure_type' => 'temperature',
                'f_measure' => 20 + $i,
                'recorded_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->getJson("/api/users/{$user->id}/measurements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_cannot_submit_measurement_for_detached_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);
        $userDevice->detach();

        $measurementData = [
            'access_token' => $userDevice->access_token,
            'measure_type' => 'temperature',
            'value' => 25.5,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid access token or device is not attached',
            ]);
    }
}
