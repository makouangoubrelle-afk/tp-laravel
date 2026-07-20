<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\DriverSchedule;
use App\Support\DemoWallets;
use App\Services\AlertService;
use App\Services\BlockchainService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $blockchain = app(BlockchainService::class);

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@emmaus.fr',
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin,
            'wallet_address' => DemoWallets::ADMIN,
        ]);

        $manager = User::create([
            'name' => 'Jean Gestionnaire',
            'email' => 'gestionnaire@emmaus.fr',
            'password' => Hash::make('password'),
            'role' => UserRole::FleetManager,
            'wallet_address' => DemoWallets::MANAGER,
        ]);

        $driver = User::create([
            'name' => 'Pierre Chauffeur',
            'email' => 'chauffeur@emmaus.fr',
            'password' => Hash::make('password'),
            'role' => UserRole::Driver,
            'wallet_address' => DemoWallets::DRIVER,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            DriverSchedule::create([
                'driver_id' => $driver->id,
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'label' => 'Matin',
            ]);
            DriverSchedule::create([
                'driver_id' => $driver->id,
                'day_of_week' => $day,
                'start_time' => '14:00:00',
                'end_time' => '18:00:00',
                'label' => 'Après-midi',
            ]);
        }

        User::create([
            'name' => 'Sophie Conductrice',
            'email' => 'sophie@emmaus.fr',
            'password' => Hash::make('password'),
            'role' => UserRole::Driver,
        ]);

        User::create([
            'name' => 'Marc Garagiste',
            'email' => 'garagiste@emmaus.fr',
            'password' => Hash::make('password'),
            'role' => UserRole::Mechanic,
        ]);

        User::create([
            'name' => 'Auditeur Public',
            'email' => 'auditeur@emmaus.fr',
            'password' => Hash::make('password'),
            'role' => UserRole::Auditor,
        ]);

        $vehicles = [
            ['brand' => 'Renault', 'model' => 'Master', 'plate_number' => 'AB-123-CD', 'current_mileage' => 45000, 'status' => VehicleStatus::Available],
            ['brand' => 'Peugeot', 'model' => 'Boxer', 'plate_number' => 'EF-456-GH', 'current_mileage' => 78000, 'status' => VehicleStatus::OnMission, 'assigned_driver_id' => $driver->id],
            ['brand' => 'Citroën', 'model' => 'Jumper', 'plate_number' => 'IJ-789-KL', 'current_mileage' => 32000, 'status' => VehicleStatus::InRepair],
        ];

        foreach ($vehicles as $data) {
            $record = $blockchain->record(json_encode($data), 'vehicle_seed');
            Vehicle::create([
                ...$data,
                'technical_inspection_due' => now()->addDays(15),
                'insurance_expiry' => now()->addMonths(2),
                'next_oil_change' => now()->addDays(25),
                'blockchain_hash' => $record['content_hash'],
                'blockchain_tx_hash' => $record['blockchain_tx_hash'],
            ]);
        }

        app(AlertService::class)->syncAll();
    }
}
