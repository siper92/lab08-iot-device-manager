<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Device;
use App\Models\UserDevice;
use App\Models\DeviceMeasurement;
use App\Enums\MeasureType;
use App\Services\AlertProcessorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users
        $user1 = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
        ]);

        $user2 = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ]);

        $user3 = User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@example.com',
        ]);

        // Create devices with specific identifiers
        $device1 = Device::create([
            'device_identifier' => 'TEMP-001',
            'name' => 'Office Temperature Sensor',
            'manufacturer' => 'SensorTech Inc',
            'description' => 'High precision temperature sensor for office environments',
        ]);

        $device2 = Device::create([
            'device_identifier' => 'TEMP-002',
            'name' => 'Warehouse Sensor',
            'manufacturer' => 'IoT Solutions Ltd',
            'description' => 'Industrial-grade temperature monitoring device',
        ]);

        $device3 = Device::create([
            'device_identifier' => 'TEMP-003',
            'name' => 'Home Climate Monitor',
            'manufacturer' => 'SmartHome Corp',
            'description' => 'Residential temperature and climate sensor',
        ]);

        $device4 = Device::create([
            'device_identifier' => 'TEMP-004',
            'name' => 'Laboratory Precision Sensor',
            'manufacturer' => 'SensorTech Inc',
            'description' => 'Laboratory-grade precision temperature measurement',
        ]);

        $device5 = Device::create([
            'device_identifier' => 'TEMP-005',
            'name' => 'Cold Storage Monitor',
            'manufacturer' => 'ColdChain Systems',
            'description' => 'Specialized sensor for cold storage facilities',
        ]);

        // Attach devices to users
        $userDevice1 = UserDevice::create([
            'user_id' => $user1->id,
            'device_id' => $device1->id,
            'access_token' => Str::random(32),
            'attached_at' => now()->subDays(30),
        ]);

        $userDevice2 = UserDevice::create([
            'user_id' => $user1->id,
            'device_id' => $device2->id,
            'access_token' => Str::random(32),
            'attached_at' => now()->subDays(25),
        ]);

        $userDevice3 = UserDevice::create([
            'user_id' => $user2->id,
            'device_id' => $device3->id,
            'access_token' => Str::random(32),
            'attached_at' => now()->subDays(20),
        ]);

        $userDevice4 = UserDevice::create([
            'user_id' => $user2->id,
            'device_id' => $device4->id,
            'access_token' => Str::random(32),
            'attached_at' => now()->subDays(15),
        ]);

        $userDevice5 = UserDevice::create([
            'user_id' => $user3->id,
            'device_id' => $device5->id,
            'access_token' => Str::random(32),
            'attached_at' => now()->subDays(10),
        ]);

        // Create temperature measurements with various scenarios
        $alertProcessor = app(AlertProcessorService::class);

        // User 1 - Device 1: Normal temperatures
        $this->createMeasurement($device1, 22.5, now()->subDays(5), $alertProcessor);
        $this->createMeasurement($device1, 23.1, now()->subDays(4), $alertProcessor);
        $this->createMeasurement($device1, 21.8, now()->subDays(3), $alertProcessor);
        $this->createMeasurement($device1, 24.2, now()->subDays(2), $alertProcessor);
        $this->createMeasurement($device1, 22.9, now()->subDays(1), $alertProcessor);

        // User 1 - Device 2: Some high temperature alerts
        $this->createMeasurement($device2, 28.5, now()->subDays(5), $alertProcessor);
        $this->createMeasurement($device2, 31.2, now()->subDays(4), $alertProcessor); // Alert: > 30
        $this->createMeasurement($device2, 35.8, now()->subDays(3), $alertProcessor); // Alert: > 30
        $this->createMeasurement($device2, 33.1, now()->subDays(2), $alertProcessor); // Alert: > 30
        $this->createMeasurement($device2, 29.5, now()->subDays(1), $alertProcessor);

        // User 2 - Device 3: Mix of normal and cold alerts
        $this->createMeasurement($device3, 18.3, now()->subDays(5), $alertProcessor);
        $this->createMeasurement($device3, 15.7, now()->subDays(4), $alertProcessor);
        $this->createMeasurement($device3, -2.5, now()->subDays(3), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device3, -5.1, now()->subDays(2), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device3, 12.4, now()->subDays(1), $alertProcessor);

        // User 2 - Device 4: Precise laboratory conditions
        $this->createMeasurement($device4, 20.0, now()->subDays(5), $alertProcessor);
        $this->createMeasurement($device4, 20.1, now()->subDays(4), $alertProcessor);
        $this->createMeasurement($device4, 19.9, now()->subDays(3), $alertProcessor);
        $this->createMeasurement($device4, 20.0, now()->subDays(2), $alertProcessor);
        $this->createMeasurement($device4, 20.2, now()->subDays(1), $alertProcessor);

        // User 3 - Device 5: Cold storage - all below zero
        $this->createMeasurement($device5, -18.5, now()->subDays(5), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device5, -20.2, now()->subDays(4), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device5, -19.8, now()->subDays(3), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device5, -21.1, now()->subDays(2), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device5, -19.3, now()->subDays(1), $alertProcessor); // Alert: < 0

        // Recent measurements (last 24 hours)
        $this->createMeasurement($device1, 23.5, now()->subHours(12), $alertProcessor);
        $this->createMeasurement($device2, 32.8, now()->subHours(10), $alertProcessor); // Alert: > 30
        $this->createMeasurement($device3, 16.2, now()->subHours(8), $alertProcessor);
        $this->createMeasurement($device4, 20.1, now()->subHours(6), $alertProcessor);
        $this->createMeasurement($device5, -19.7, now()->subHours(4), $alertProcessor); // Alert: < 0

        // Most recent measurements
        $this->createMeasurement($device1, 24.1, now()->subHours(2), $alertProcessor);
        $this->createMeasurement($device2, 30.5, now()->subHours(1), $alertProcessor); // Alert: > 30
        $this->createMeasurement($device3, -1.2, now()->subMinutes(30), $alertProcessor); // Alert: < 0
        $this->createMeasurement($device4, 19.8, now()->subMinutes(15), $alertProcessor);
        $this->createMeasurement($device5, -20.1, now()->subMinutes(5), $alertProcessor); // Alert: < 0

        $this->command->info('Seeding completed successfully!');
        $this->command->info('Created 3 users, 5 devices, 5 device attachments, and 35 measurements with alerts.');
        $this->command->info('');
        $this->command->info('User Credentials (password for all: password):');
        $this->command->info('- john.smith@example.com');
        $this->command->info('- jane.doe@example.com');
        $this->command->info('- bob.johnson@example.com');
    }

    /**
     * Create a device measurement and process alerts
     */
    private function createMeasurement(Device $device, float $temperature, $recordedAt, AlertProcessorService $alertProcessor): void
    {
        $measurement = DeviceMeasurement::create([
            'device_id' => $device->id,
            'measure_type' => MeasureType::TEMPERATURE->value,
            'f_measure' => $temperature,
            'recorded_at' => $recordedAt,
        ]);

        $alertProcessor->processMeasurement($measurement);
    }
}
