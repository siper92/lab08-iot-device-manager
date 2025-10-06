<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceMeasurementTest extends TestCase
{
    use RefreshDatabase;

    protected string $deviceToken;
    protected Device $device;
    protected User $user;


    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();
        $this->disableKafkaProcess();

        // Get user token for authenticated requests
        $response = $this->postJson('/login', [
            'email' => self::DEFAULT_USER_EMAIL,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $userToken = $response->json('token');
        $this->user = User::where('email', self::DEFAULT_USER_EMAIL)->first();

        // Create and attach a device to get device token
        $this->device = Device::factory()->create([
            'device_identifier' => 'TEST-MEASUREMENT-DEVICE',
        ]);

        $attachResponse = $this->withHeader('Authorization', 'Bearer ' . $userToken)
            ->postJson("/users/{$this->user->id}/devices/{$this->device->id}/attach", [
                'device_identifier' => 'TEST-MEASUREMENT-DEVICE',
            ]);

        $this->deviceToken = $attachResponse->json('access_token');
    }

    public function test_device_can_submit_measurement_with_valid_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'temperature',
                'f_measure' => 25.5,
                's_measure' => 'normal',
                'i_measure' => 100,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'timestamp',
                'message'
            ]);

        $this->assertDatabaseHas('device_measurements', [
            'device_id' => $this->device->id,
            'measure_type' => 'temperature',
            'f_measure' => 25.5,
            's_measure' => 'normal',
            'i_measure' => 100,
        ]);
    }

    public function test_device_can_submit_measurement_with_only_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'humidity',
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'timestamp',
                'message'
            ]);

        $this->assertDatabaseHas('device_measurements', [
            'device_id' => $this->device->id,
            'measure_type' => 'humidity',
            'f_measure' => null,
            's_measure' => null,
            'i_measure' => null,
        ]);
    }

    public function test_device_can_submit_measurement_with_custom_recorded_at(): void
    {
        $recordedAt = '2024-10-01 12:00:00';

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'pressure',
                'f_measure' => 1013.25,
                'recorded_at' => $recordedAt,
            ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('device_measurements', [
            'device_id' => $this->device->id,
            'measure_type' => 'pressure',
        ]);
    }

    public function test_submit_measurement_requires_measure_type(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'f_measure' => 25.5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['measure_type']);
    }

    public function test_submit_measurement_validates_f_measure_is_numeric(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'temperature',
                'f_measure' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['f_measure']);
    }

    public function test_submit_measurement_validates_i_measure_is_integer(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'temperature',
                'i_measure' => 'not-an-integer',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['i_measure']);
    }

    public function test_submit_measurement_validates_recorded_at_is_date(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'temperature',
                'recorded_at' => 'not-a-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recorded_at']);
    }

    public function test_submit_measurement_fails_with_invalid_device_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/99999/measurements", [
                'measure_type' => 'temperature',
                'f_measure' => 25.5,
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'forbidden device...']);
    }

    public function test_submit_measurement_requires_authentication(): void
    {
        $response = $this->postJson("/devices/{$this->device->id}/measurements", [
            'measure_type' => 'temperature',
            'f_measure' => 25.5,
        ]);

        $response->assertStatus(401);
    }

    public function test_multiple_measurements_can_be_submitted_for_same_device(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'temperature',
                'f_measure' => 25.5,
            ])
            ->assertStatus(202);

        $this->withHeader('Authorization', 'Bearer ' . $this->deviceToken)
            ->postJson("/devices/{$this->device->id}/measurements", [
                'measure_type' => 'humidity',
                'f_measure' => 60.0,
            ])
            ->assertStatus(202);

        $this->assertEquals(2, DeviceMeasurement::where('device_id', $this->device->id)->count());
    }
}
