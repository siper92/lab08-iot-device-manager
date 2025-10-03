<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_be_transferred_between_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device = Device::factory()->create();

        // Attach device to user1
        $userDevice1 = UserDevice::create([
            'user_id' => $user1->id,
            'device_id' => $device->id,
        ]);

        // Detach from user1
        $this->deleteJson("/api/users/{$user1->id}/devices/{$device->id}/detach");

        // Attach to user2
        $response = $this->postJson("/api/users/{$user2->id}/devices/{$device->id}/attach");

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user2->id,
            'device_id' => $device->id,
            'detached_at' => null,
        ]);
    }

    public function test_user_can_only_see_their_own_measurements(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device1 = Device::factory()->create();
        $device2 = Device::factory()->create();

        UserDevice::create(['user_id' => $user1->id, 'device_id' => $device1->id]);
        UserDevice::create(['user_id' => $user2->id, 'device_id' => $device2->id]);

        DeviceMeasurement::create([
            'device_id' => $device1->id,
            'measure_type' => 'temperature',
            'f_measure' => 25,
            'recorded_at' => now(),
        ]);

        DeviceMeasurement::create([
            'device_id' => $device2->id,
            'measure_type' => 'temperature',
            'f_measure' => 30,
            'recorded_at' => now(),
        ]);

        $response = $this->getJson("/api/users/{$user1->id}/measurements");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($device1->id, $data[0]['device_id']);
    }

    public function test_user_can_only_see_their_own_alerts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device1 = Device::factory()->create();
        $device2 = Device::factory()->create();

        Alert::create([
            'user_id' => $user1->id,
            'device_id' => $device1->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Alert for user 1',
            'severity' => 'high',
            'triggered_at' => now(),
        ]);

        Alert::create([
            'user_id' => $user2->id,
            'device_id' => $device2->id,
            'alert_type' => 'temperature_threshold',
            'message' => 'Alert for user 2',
            'severity' => 'high',
            'triggered_at' => now(),
        ]);

        $response = $this->getJson("/api/users/{$user1->id}/alerts");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Alert for user 1', $data[0]['message']);
    }

    public function test_alerts_are_created_for_all_current_device_owners(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device = Device::factory()->create();

        // Both users have the device attached (edge case: shared device)
        $userDevice1 = UserDevice::create(['user_id' => $user1->id, 'device_id' => $device->id]);
        $userDevice2 = UserDevice::create(['user_id' => $user2->id, 'device_id' => $device->id]);

        // Submit measurement that triggers alert
        $measurementData = [
            'access_token' => $userDevice1->access_token,
            'measure_type' => 'temperature',
            'value' => 40,
        ];

        $this->postJson('/api/measurements', $measurementData);

        // Both users should receive alerts
        $this->assertDatabaseHas('alerts', [
            'user_id' => $user1->id,
            'device_id' => $device->id,
        ]);

        $this->assertDatabaseHas('alerts', [
            'user_id' => $user2->id,
            'device_id' => $device->id,
        ]);
    }

    public function test_device_history_is_maintained_after_transfer(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device = Device::factory()->create();

        // Attach device to user1
        UserDevice::create([
            'user_id' => $user1->id,
            'device_id' => $device->id,
        ]);

        // Detach from user1
        $this->deleteJson("/api/users/{$user1->id}/devices/{$device->id}/detach");

        // Attach to user2
        $this->postJson("/api/users/{$user2->id}/devices/{$device->id}/attach");

        // Check that history is maintained
        $history = UserDevice::where('device_id', $device->id)->get();
        $this->assertCount(2, $history);
        $this->assertNotNull($history[0]->detached_at);
        $this->assertNull($history[1]->detached_at);
    }

    public function test_measurements_submitted_after_detachment_fail(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $token = $userDevice->access_token;

        // Detach device
        $this->deleteJson("/api/users/{$user->id}/devices/{$device->id}/detach");

        // Try to submit measurement with old token
        $measurementData = [
            'access_token' => $token,
            'measure_type' => 'temperature',
            'value' => 25,
        ];

        $response = $this->postJson('/api/measurements', $measurementData);

        $response->assertStatus(403);
    }
}
