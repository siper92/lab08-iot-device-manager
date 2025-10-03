<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_device(): void
    {
        $deviceData = [
            'device_identifier' => 'DEV-001',
            'name' => 'Temperature Sensor 1',
            'manufacturer' => 'SensorCorp',
            'description' => 'High-precision temperature sensor',
        ];

        $response = $this->postJson('/api/devices', $deviceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'device_identifier', 'name', 'manufacturer'],
            ]);

        $this->assertDatabaseHas('devices', [
            'device_identifier' => 'DEV-001',
        ]);
    }

    public function test_cannot_create_device_with_duplicate_identifier(): void
    {
        Device::factory()->create(['device_identifier' => 'DEV-001']);

        $deviceData = [
            'device_identifier' => 'DEV-001',
            'name' => 'Temperature Sensor 2',
        ];

        $response = $this->postJson('/api/devices', $deviceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_identifier']);
    }

    public function test_can_attach_device_to_user(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();

        $response = $this->postJson("/api/users/{$user->id}/devices/{$device->id}/attach");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['user_device_id', 'access_token', 'attached_at'],
            ]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);
    }

    public function test_can_detach_device_from_user(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $response = $this->deleteJson("/api/users/{$user->id}/devices/{$device->id}/detach");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Device detached from user successfully',
            ]);

        $this->assertDatabaseHas('user_devices', [
            'id' => $userDevice->id,
        ]);
        $this->assertDatabaseMissing('user_devices', [
            'id' => $userDevice->id,
            'detached_at' => null,
        ]);
    }

    public function test_cannot_attach_device_to_nonexistent_user(): void
    {
        $device = Device::factory()->create();

        $response = $this->postJson("/api/users/999/devices/{$device->id}/attach");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found',
            ]);
    }

    public function test_can_delete_device(): void
    {
        $device = Device::factory()->create();

        $response = $this->deleteJson("/api/devices/{$device->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Device deleted successfully',
            ]);

        $this->assertSoftDeleted('devices', [
            'id' => $device->id,
        ]);
    }

    public function test_attaching_already_attached_device_returns_existing_token(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $response = $this->postJson("/api/users/{$user->id}/devices/{$device->id}/attach");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Device is already attached to this user',
                'data' => [
                    'access_token' => $userDevice->access_token,
                ],
            ]);
    }
}
