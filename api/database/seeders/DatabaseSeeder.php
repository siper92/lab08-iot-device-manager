<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Device;
use App\Models\UserDevice;
use App\Models\DeviceMeasurement;
use App\Models\SystemAdmin;
use App\Enums\MeasureType;
use App\Services\AlertProcessorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    const MAX_USERS = 3;
    const MAX_DEVICES = 8;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        SystemAdmin::create([
            'email' => 'admin@lab08.test',
            'password' => Hash::make('admin123'),
        ]);
        $this->command->info('System Admin Credentials:');
        $this->command->info('- Email: admin@lab08.test');
        $this->command->info('- Password: admin123');
        $this->command->info('');

        $devices=[];
        $users=[];
        for ($i = 0; $i < self::MAX_DEVICES; $i++) {
            $devices[$i] = Device::factory()->create();
        }

        // Create users
        for ($i = 1; $i <= self::MAX_USERS; $i++) {
            $users[] = User::factory()->create([
                'name' => "User {$i}",
                'email' => "test{$i}@lab08.test",
                'password' => Hash::make('password-'.$i),
            ]);
            $this->command->info("User {$i} Credentials:");
            $this->command->info("- Email: test{$i}@lab08.test");
            $this->command->info("- Password: password-{$i}");
            $this->command->info('');
        }

        // devices per user
        $devicesPerUser = intdiv(self::MAX_DEVICES, self::MAX_USERS);
        for ($i = 0; $i < self::MAX_USERS; $i++) {
            for ($j = 0; $j < $devicesPerUser; $j++) {
                $deviceIndex = $i * $devicesPerUser + $j;
                $userId = $users[$i]->id;
                $deviceId = $devices[$deviceIndex]->id;
                UserDevice::create([
                    'user_id' => $userId,
                    'device_id' => $deviceId,
                    'access_token' => "user_{$userId}_device_{$deviceId}",
                    'attached_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // Create temperature measurements with various scenarios
        $alertProcessor = app(AlertProcessorService::class);

        $measureData = [
            0 => [22.5, 23.1, 21.8, 24.2, 22.9],  // Normal temperature readings
            1 => [28.5, 31.2, 35.8, 33.1, 29.5],  // High temperature readings (alerts)
            2 => [18.3, 15.7, -2.5, -5.1, 12.4],  // Low temperature readings (alerts)
            3 => [20.0, 20.1, 19.9, 20.0, 20.2], // Stable temperature readings
            4 => [-18.5, -20.2, -19.8, -21.1, -19.3], // Very low temperature readings (alerts)
            5 => [23.5, 32.8, 16.2, 20.1, -19.7], // Recent measurements (last 24 hours)
            6 => [24.1, 30.5, -1.2, 19.8, -20.1], // Most recent measurements
            7 => [21.0, 29.9, 0.0, 25.5, -10.0], // Additional varied data
        ];

        for ($i = 0; $i < self::MAX_DEVICES; $i++) {
            $device = $devices[$i];
            foreach ($measureData[$i] as $index => $temp) {
                $this->createMeasurement($device, $temp, now()->subDays(5 - $index), $alertProcessor);
            }
        }

        $this->command->info('Seeding completed successfully!');
        $this->command->info('Created 1 system admin, 3 users, 8 devices, device attachments, and measurements with alerts.');
        $this->command->info('');
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
