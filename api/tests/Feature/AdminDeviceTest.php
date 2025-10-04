<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();

        // Get admin token for authenticated requests
        $response = $this->postJson('/admin/login', [
            'email' => self::DEFAULT_ADMIN_EMAIL,
            'password' => self::DEFAULT_ADMIN_PASSWORD,
        ]);

        $this->adminToken = $response->json('token');
    }

    public function test_admin_can_create_device_with_valid_data(): void
    {
        $deviceData = [
            'device_identifier' => 'TEST-DEVICE-001',
            'manufacturer' => 'Test Manufacturer',
            'name' => 'Test Device',
            'description' => 'A test device',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/devices', $deviceData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'device_identifier' => 'TEST-DEVICE-001',
                'manufacturer' => 'Test Manufacturer',
                'name' => 'Test Device',
                'description' => 'A test device',
            ]);

        $this->assertDatabaseHas('devices', [
            'device_identifier' => 'TEST-DEVICE-001',
            'manufacturer' => 'Test Manufacturer',
        ]);
    }

    public function test_create_device_requires_device_identifier(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/devices', [
                'manufacturer' => 'Test Manufacturer',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_identifier']);
    }

    public function test_device_identifier_must_be_unique(): void
    {
        Device::factory()->create(['device_identifier' => 'DUPLICATE-ID']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/devices', [
                'device_identifier' => 'DUPLICATE-ID',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_identifier']);
    }

    public function test_admin_can_delete_device(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/devices/{$device->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Device deleted successfully']);

        $this->assertSoftDeleted('devices', ['id' => $device->id]);
    }

    public function test_delete_device_fails_with_invalid_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/admin/devices/99999');

        $response->assertStatus(404);
    }

    public function test_admin_can_attach_device_to_user(): void
    {
        /** @var User $user */
        $user = User::where('email', self::DEFAULT_USER_EMAIL)->first();
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/admin/devices/{$device->id}/attach", [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'user_id' => $user->id,
                'device_id' => $device->id,
            ]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'detached_at' => null,
        ]);
    }

    public function test_attach_device_requires_user_id(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/admin/devices/{$device->id}/attach", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_attach_device_requires_existing_user(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/admin/devices/{$device->id}/attach", [
                'user_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_cannot_attach_device_that_is_already_attached(): void
    {
        /** @var User $user */
        $user = User::where('email', self::DEFAULT_USER_EMAIL)->first();
        /** @var Device $device */
        $device = Device::factory()->create();

        // First attachment
        UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        // Try to attach again
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/admin/devices/{$device->id}/attach", [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Device is already attached to a user']);
    }

    public function test_attach_device_fails_with_invalid_device_id(): void
    {
        /** @var User $user */
        $user = User::where('email', self::DEFAULT_USER_EMAIL)->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/devices/99999/attach', [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(404);
    }

    public function test_admin_can_detach_device_from_user(): void
    {
        /** @var User $user */
        $user = User::where('email', self::DEFAULT_USER_EMAIL)->first();
        /** @var Device $device */
        $device = Device::factory()->create();

        UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/devices/{$device->id}/detach");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Device detached successfully']);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        /** @var UserDevice $userDevice */
        $userDevice = UserDevice::where('device_id', $device->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($userDevice->detached_at);
    }

    public function test_cannot_detach_device_that_is_not_attached(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/devices/{$device->id}/detach");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Device is not attached to any user']);
    }

    public function test_detach_device_fails_with_invalid_device_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/admin/devices/99999/detach');

        $response->assertStatus(404);
    }
}
