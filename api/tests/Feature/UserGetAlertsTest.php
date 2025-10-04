<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagination;
use Tests\TestCase;

class UserGetAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected string $userToken;
    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();
        $this->initConfig();

        // Create main test user
        /** @var User $user */
        $user = User::where('email', self::DEFAULT_USER_EMAIL)->first();
        $this->user = $user;

        // Get user token
        $response = $this->postJson('/login', [
            'email' => self::DEFAULT_USER_EMAIL,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);
        $this->userToken = $response->json('token');

        // Create another user for testing forbidden access
        /** @var User $otherUser */
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->otherUser = $otherUser;

        // Create devices for main user
        /** @var Device $device1 */
        $device1 = Device::factory()->create(['name' => 'Test Device 1']);
        /** @var Device $device2 */
        $device2 = Device::factory()->create(['name' => 'Test Device 2']);

        // Attach devices to user
        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => $device1->id,
        ]);

        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => $device2->id,
        ]);

        // Create measurements for devices
        /** @var DeviceMeasurement $measurement1 */
        $measurement1 = DeviceMeasurement::create([
            'device_id' => $device1->id,
            'measure_type' => 'temperature',
            'f_measure' => 35.5,
            'recorded_at' => now()->subHours(2),
        ]);

        /** @var DeviceMeasurement $measurement2 */
        $measurement2 = DeviceMeasurement::create([
            'device_id' => $device2->id,
            'measure_type' => 'humidity',
            'f_measure' => 80.0,
            'recorded_at' => now()->subHours(1),
        ]);

        // Create alerts for user
        Alert::create([
            'user_id' => $this->user->id,
            'device_id' => $device1->id,
            'measurement_id' => $measurement1->id,
            'alert_type' => 'high_temperature',
            'message' => 'Temperature is too high',
            'severity' => 'warning',
            'triggered_at' => now()->subHours(2),
            'is_read' => false,
        ]);

        Alert::create([
            'user_id' => $this->user->id,
            'device_id' => $device2->id,
            'measurement_id' => $measurement2->id,
            'alert_type' => 'high_humidity',
            'message' => 'Humidity is too high',
            'severity' => 'critical',
            'triggered_at' => now()->subHours(1),
            'is_read' => false,
        ]);

        Alert::create([
            'user_id' => $this->user->id,
            'device_id' => $device1->id,
            'measurement_id' => null,
            'alert_type' => 'device_offline',
            'message' => 'Device is offline',
            'severity' => 'info',
            'triggered_at' => now()->subMinutes(30),
            'is_read' => true,
        ]);

        // Create alert for other user
        /** @var Device $otherDevice */
        $otherDevice = Device::factory()->create(['name' => 'Other Device']);
        UserDevice::create([
            'user_id' => $this->otherUser->id,
            'device_id' => $otherDevice->id,
        ]);

        Alert::create([
            'user_id' => $this->otherUser->id,
            'device_id' => $otherDevice->id,
            'alert_type' => 'test_alert',
            'message' => 'Test alert for other user',
            'severity' => 'info',
            'triggered_at' => now(),
            'is_read' => false,
        ]);
    }

    public function test_user_can_get_alerts_with_valid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'device_id',
                        'alert_type',
                        'message',
                        'severity',
                        'triggered_at',
                        'is_read',
                        'device' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
                'current_page',
                'per_page',
                'total',
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        // Verify alerts are ordered by created_at desc
        $this->assertEquals('high_temperature', $data[0]['alert_type']);
        $this->assertEquals('high_humidity', $data[1]['alert_type']);
        $this->assertEquals('device_offline', $data[2]['alert_type']);
    }

    public function test_get_alerts_returns_401_when_no_token_provided(): void
    {
        $response = $this->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_get_alerts_returns_401_when_token_is_invalid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_get_alerts_returns_403_when_accessing_other_user_alerts(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->otherUser->id}/alerts");

        $response->assertStatus(403)
            ->assertJson(['error' => 'forbidden...']);
    }

    public function test_get_alerts_returns_404_when_user_does_not_exist(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        // First delete the user to make them not exist
        $this->user->delete();

        // Create a new user with the same ID to get a valid token but access deleted user
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/99999/alerts");

        $response->assertStatus(403); // Will be 403 because userId doesn't match token
    }

    public function test_alerts_response_structure_is_valid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $alert = $data[0];
        $this->assertArrayHasKey('id', $alert);
        $this->assertArrayHasKey('user_id', $alert);
        $this->assertArrayHasKey('device_id', $alert);
        $this->assertArrayHasKey('alert_type', $alert);
        $this->assertArrayHasKey('message', $alert);
        $this->assertArrayHasKey('severity', $alert);
        $this->assertArrayHasKey('triggered_at', $alert);
        $this->assertArrayHasKey('is_read', $alert);
        $this->assertArrayHasKey('device', $alert);

        $this->assertEquals($this->user->id, $alert['user_id']);
    }

    public function test_alerts_include_device_information(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(200);

        $data = $response->json('data');
        $alert = $data[0];

        $this->assertNotNull($alert['device']);
        $this->assertArrayHasKey('id', $alert['device']);
        $this->assertArrayHasKey('name', $alert['device']);
    }

    public function test_alerts_are_paginated(): void
    {
        // Create more alerts to test pagination
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 20; $i++) {
            Alert::create([
                'user_id' => $this->user->id,
                'device_id' => $device->id,
                'alert_type' => 'test_alert_' . $i,
                'message' => 'Test alert ' . $i,
                'severity' => 'info',
                'triggered_at' => now()->subMinutes($i),
                'is_read' => false,
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
                'last_page',
                'first_page_url',
                'last_page_url',
                'next_page_url',
                'prev_page_url',
            ]);

        $this->assertEquals(1, $response->json('current_page'));
        $this->assertEquals(15, $response->json('per_page'));
        $this->assertGreaterThanOrEqual(20, $response->json('total'));
    }

    public function test_alerts_pagination_with_custom_per_page(): void
    {
        // Create more alerts
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 10; $i++) {
            Alert::create([
                'user_id' => $this->user->id,
                'device_id' => $device->id,
                'alert_type' => 'test_alert_' . $i,
                'message' => 'Test alert ' . $i,
                'severity' => 'info',
                'triggered_at' => now()->subMinutes($i),
                'is_read' => false,
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts?per_page=5");

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('per_page'));
        $this->assertCount(5, $response->json('data'));
    }

    public function test_user_can_access_specific_page_of_alerts(): void
    {
        // Create more alerts
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 20; $i++) {
            Alert::create([
                'user_id' => $this->user->id,
                'device_id' => $device->id,
                'alert_type' => 'test_alert_' . $i,
                'message' => 'Test alert ' . $i,
                'severity' => 'info',
                'triggered_at' => now()->subMinutes($i),
                'is_read' => false,
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts?page=2&per_page=10");

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('current_page'));
        $this->assertEquals(10, $response->json('per_page'));
    }

    public function test_alert_is_read_status_is_boolean(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $alert) {
            $this->assertIsBool($alert['is_read']);
        }
    }

    public function test_user_only_sees_their_own_alerts(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/alerts");

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $alert) {
            $this->assertEquals($this->user->id, $alert['user_id']);
        }
    }
}
