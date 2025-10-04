<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected string $userToken;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();

        // Get user token for authenticated requests
        $response = $this->postJson('/login', [
            'email' => self::DEFAULT_USER_EMAIL,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $this->userToken = $response->json('token');
        $this->user = User::where('email', self::DEFAULT_USER_EMAIL)->first();
    }

    public function test_user_can_attach_device_with_valid_data(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create([
            'device_identifier' => 'NEW-DEVICE-001',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/{$this->user->id}/devices/{$device->id}/attach", [
                'device_identifier' => 'NEW-DEVICE-001',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'user_device' => [
                    'user_id',
                    'device_id',
                ],
            ])
            ->assertJsonFragment([
                'user_id' => $this->user->id,
                'device_id' => $device->id,
            ]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'detached_at' => null,
        ]);
    }

    public function test_attach_device_returns_valid_jwt_token(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create([
            'device_identifier' => 'TOKEN-TEST-DEVICE',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/{$this->user->id}/devices/{$device->id}/attach", [
                'device_identifier' => 'TOKEN-TEST-DEVICE',
            ]);

        $response->assertStatus(201);

        $accessToken = $response->json('access_token');
        $this->assertIsString($accessToken);

        $service = app('App\Services\JwtService');
        $this->assertTrue($service->validateToken($accessToken));

        $payload = $service->getPayload($accessToken);
        $this->assertEquals('device', $payload->type);
        $this->assertEquals($device->id, $payload->sub);
    }

    public function test_attach_device_requires_device_identifier(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/{$this->user->id}/devices/{$device->id}/attach", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_identifier']);
    }

    public function test_attach_device_requires_matching_device_identifier(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create([
            'device_identifier' => 'CORRECT-ID',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/{$this->user->id}/devices/{$device->id}/attach", [
                'device_identifier' => 'WRONG-ID',
            ]);

        $response->assertStatus(404);
    }

    public function test_attach_device_fails_with_invalid_device_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/{$this->user->id}/devices/99999/attach", [
                'device_identifier' => 'SOME-ID',
            ]);

        $response->assertStatus(404);
    }

    public function test_attach_device_fails_with_invalid_user_id(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create([
            'device_identifier' => 'TEST-DEVICE',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/99999/devices/{$device->id}/attach", [
                'device_identifier' => 'TEST-DEVICE',
            ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'forbidden...']);
    }

    public function test_cannot_attach_device_that_is_already_attached(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create([
            'device_identifier' => 'ATTACHED-DEVICE',
        ]);

        // First attachment
        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
        ]);

        // Try to attach again
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->postJson("/users/{$this->user->id}/devices/{$device->id}/attach", [
                'device_identifier' => 'ATTACHED-DEVICE',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Device is already attached to a user']);
    }

    public function test_user_can_detach_device(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->deleteJson("/users/{$this->user->id}/devices/{$device->id}/detach");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Device detached successfully']);

        /** @var UserDevice $userDevice */
        $userDevice = UserDevice::where('device_id', $device->id)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($userDevice->detached_at);
    }

    public function test_cannot_detach_device_that_is_not_attached_to_user(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->deleteJson("/users/{$this->user->id}/devices/{$device->id}/detach");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Device is not attached to this user']);
    }

    public function test_detach_device_fails_with_invalid_device_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->deleteJson("/users/{$this->user->id}/devices/99999/detach");

        $response->assertStatus(404);
    }

    public function test_detach_device_fails_with_invalid_user_id(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->deleteJson("/users/99999/devices/{$device->id}/detach");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'forbidden...']);
    }

    public function test_cannot_detach_device_that_was_already_detached(): void
    {
        /** @var Device $device */
        $device = Device::factory()->create();

        /** @var UserDevice $userDevice */
        $userDevice = UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
        ]);

        // Detach the device
        $userDevice->detach();

        // Try to detach again
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->deleteJson("/users/{$this->user->id}/devices/{$device->id}/detach");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Device is not attached to this user']);
    }
}
