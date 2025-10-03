<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_created_for_temperature_below_threshold(): void
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
            'value' => -5,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('alerts', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
        ]);
    }

    public function test_alert_is_created_for_temperature_above_threshold(): void
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
            'value' => 35,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('alerts', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
        ]);
    }

    public function test_no_alert_is_created_for_normal_temperature(): void
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
            'value' => 22,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(201);

        $this->assertDatabaseMissing('alerts', [
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);
    }

    public function test_can_get_user_alerts(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();

        Alert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Temperature alert',
            'severity' => 'high',
            'triggered_at' => now(),
        ]);

        $response = $this->getJson("/api/users/{$user->id}/alerts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'device_id', 'alert_type', 'message', 'severity'],
                ],
            ]);
    }

    public function test_can_mark_alert_as_read(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();

        $alert = Alert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Temperature alert',
            'severity' => 'high',
            'triggered_at' => now(),
            'is_read' => false,
        ]);

        $response = $this->postJson("/api/alerts/{$alert->id}/mark-read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'is_read' => true,
        ]);
    }

    public function test_can_filter_unread_alerts(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();

        Alert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Unread alert',
            'severity' => 'high',
            'triggered_at' => now(),
            'is_read' => false,
        ]);

        Alert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Read alert',
            'severity' => 'high',
            'triggered_at' => now(),
            'is_read' => true,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/alerts?unread_only=1");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Unread alert', $data[0]['message']);
    }

    public function test_can_get_alert_statistics(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();

        Alert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Alert 1',
            'severity' => 'high',
            'triggered_at' => now(),
            'is_read' => false,
        ]);

        Alert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Alert 2',
            'severity' => 'medium',
            'triggered_at' => now(),
            'is_read' => true,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/alerts/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total', 'unread', 'by_severity', 'by_type'],
            ])
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.unread', 1);
    }
}
