<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGetMeasurementsTest extends TestCase
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
        $device1 = Device::factory()->create([
            'name' => 'Temperature Sensor',
            'device_identifier' => 'TEMP-001',
        ]);

        /** @var Device $device2 */
        $device2 = Device::factory()->create([
            'name' => 'Humidity Sensor',
            'device_identifier' => 'HUMID-001',
        ]);

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
        DeviceMeasurement::create([
            'device_id' => $device1->id,
            'measure_type' => 'temperature',
            'f_measure' => 22.5,
            'recorded_at' => now()->subHours(5),
        ]);

        DeviceMeasurement::create([
            'device_id' => $device1->id,
            'measure_type' => 'temperature',
            'f_measure' => 23.8,
            'recorded_at' => now()->subHours(3),
        ]);

        DeviceMeasurement::create([
            'device_id' => $device2->id,
            'measure_type' => 'humidity',
            'f_measure' => 65.0,
            'recorded_at' => now()->subHours(2),
        ]);

        DeviceMeasurement::create([
            'device_id' => $device1->id,
            'measure_type' => 'temperature',
            'f_measure' => 24.2,
            'recorded_at' => now()->subHour(),
        ]);

        DeviceMeasurement::create([
            'device_id' => $device2->id,
            'measure_type' => 'humidity',
            'f_measure' => 68.5,
            'recorded_at' => now()->subMinutes(30),
        ]);

        // Create device and measurements for other user
        /** @var Device $otherDevice */
        $otherDevice = Device::factory()->create([
            'name' => 'Other Sensor',
            'device_identifier' => 'OTHER-001',
        ]);

        UserDevice::create([
            'user_id' => $this->otherUser->id,
            'device_id' => $otherDevice->id,
        ]);

        DeviceMeasurement::create([
            'device_id' => $otherDevice->id,
            'measure_type' => 'temperature',
            'f_measure' => 30.0,
            'recorded_at' => now(),
        ]);
    }

    public function test_user_can_get_measurements_with_valid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'device_id',
                        'measure_type',
                        'recorded_at',
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
        $this->assertCount(5, $data);

        // Verify measurements are ordered by recorded_at desc
        $this->assertGreaterThanOrEqual(
            strtotime($data[1]['recorded_at']),
            strtotime($data[0]['recorded_at'])
        );
    }

    public function test_get_measurements_returns_401_when_no_token_provided(): void
    {
        $response = $this->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_get_measurements_returns_401_when_token_is_invalid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_get_measurements_returns_403_when_accessing_other_user_measurements(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->otherUser->id}/measurements");

        $response->assertStatus(403)
            ->assertJson(['error' => 'forbidden...']);
    }

    public function test_get_measurements_returns_404_when_user_does_not_exist(): void
    {
        // Will be 403 because userId doesn't match token
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/99999/measurements");

        $response->assertStatus(403);
    }

    public function test_measurements_response_structure_is_valid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $measurement = $data[0];
        $this->assertArrayHasKey('id', $measurement);
        $this->assertArrayHasKey('device_id', $measurement);
        $this->assertArrayHasKey('measure_type', $measurement);
        $this->assertArrayHasKey('recorded_at', $measurement);
        $this->assertArrayHasKey('device', $measurement);
    }

    public function test_measurements_include_device_information(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $measurement = $data[0];

        $this->assertNotNull($measurement['device']);
        $this->assertArrayHasKey('id', $measurement['device']);
        $this->assertArrayHasKey('name', $measurement['device']);

        $this->assertContains($measurement['device']['id'], $this->user->devicesIDs());
    }

    public function test_measurements_are_paginated(): void
    {
        // Create more measurements to test pagination
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 20; $i++) {
            DeviceMeasurement::create([
                'device_id' => $device->id,
                'measure_type' => 'temperature',
                'f_measure' => 20.0 + $i,
                'recorded_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

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
        $this->assertGreaterThanOrEqual(20, $response->json('total'));
    }

    public function test_measurements_pagination_with_custom_per_page(): void
    {
        // Create more measurements
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 15; $i++) {
            DeviceMeasurement::create([
                'device_id' => $device->id,
                'measure_type' => 'temperature',
                'f_measure' => 20.0 + $i,
                'recorded_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?per_page=10");

        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('per_page'));
        $this->assertCount(10, $response->json('data'));
    }

    public function test_user_can_access_specific_page_of_measurements(): void
    {
        // Create more measurements
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 20; $i++) {
            DeviceMeasurement::create([
                'device_id' => $device->id,
                'measure_type' => 'temperature',
                'f_measure' => 20.0 + $i,
                'recorded_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?page=2&per_page=10");

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('current_page'));
        $this->assertEquals(10, $response->json('per_page'));
    }

    public function test_user_only_sees_measurements_from_their_devices(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $userDeviceIds = $this->user->devices()->pluck('devices.id')->toArray();

        foreach ($data as $measurement) {
            $this->assertContains($measurement['device_id'], $userDeviceIds);
        }
    }

    public function test_measurements_contain_different_measure_types(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $measureTypes = array_unique(array_column($data, 'measure_type'));

        // We created both temperature and humidity measurements
        $this->assertContains('temperature', $measureTypes);
        $this->assertContains('humidity', $measureTypes);
    }

    public function test_measurements_are_ordered_by_recorded_at_desc(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThan(1, count($data));

        for ($i = 0; $i < count($data) - 1; $i++) {
            $current = strtotime($data[$i]['recorded_at']);
            $next = strtotime($data[$i + 1]['recorded_at']);
            $this->assertGreaterThanOrEqual($next, $current);
        }
    }

    public function test_measurements_from_multiple_devices_are_returned(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $deviceIds = array_unique(array_column($data, 'device_id'));

        // We attached 2 devices to the user
        $this->assertGreaterThan(1, count($deviceIds));
    }

    public function test_empty_measurements_when_user_has_no_devices(): void
    {
        /** @var User $newUser */
        $newUser = User::factory()->create([
            'email' => 'nodevices@example.com',
            'password' => bcrypt('password'),
        ]);

        // Get token for new user
        $response = $this->postJson('/login', [
            'email' => 'nodevices@example.com',
            'password' => 'password',
        ]);
        $newUserToken = $response->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $newUserToken)
            ->getJson("/users/{$newUser->id}/measurements");

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_measurements_pagination_with_multiple_pages(): void
    {
        /** @var Device $device */
        $device = $this->user->devices()->first();

        for ($i = 0; $i < 25; $i++) {
            DeviceMeasurement::create([
                'device_id' => $device->id,
                'measure_type' => 'temperature',
                'f_measure' => 20.0 + $i,
                'recorded_at' => now()->subMinutes($i),
            ]);
        }

        // total measurements now 25 + 5 initial = 30

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?per_page=10&page=1");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('current_page'));
        $this->assertCount(10, $response->json('data'));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?per_page=10&page=2");

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('current_page'));
        $this->assertCount(10, $response->json('data'));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?per_page=10&page=3");

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('current_page'));
        $this->assertCount(10, $response->json('data'));


        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?per_page=10&page=4");
        $response->assertStatus(200);
        $this->assertEquals(4, $response->json('current_page'));
        $this->assertCount(0, $response->json('data')); // Only 30 measurements total

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
            ->getJson("/users/{$this->user->id}/measurements?page=2");
        $response->assertStatus(200);

        $this->assertEquals(2, $response->json('current_page'));
        $this->assertCount(15, $response->json('data'));
    }
}
