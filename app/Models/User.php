<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'wallet_address',
        'is_active',
        'availability_status',
        'availability_note',
        'availability_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'is_active' => 'boolean',
        'availability_updated_at' => 'datetime',
    ];

    public function assignedVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'assigned_driver_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class, 'driver_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'mechanic_id');
    }

    public function odometerReadings(): HasMany
    {
        return $this->hasMany(OdometerReading::class, 'recorded_by');
    }

    public function fuelRecords(): HasMany
    {
        return $this->hasMany(FuelRecord::class, 'driver_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DriverSchedule::class, 'driver_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(DriverTrip::class, 'driver_id');
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function availabilityLabel(): string
    {
        return match ($this->availability_status) {
            'occupied' => 'Occupé',
            'off_duty' => 'Hors service',
            default => 'Disponible',
        };
    }

    public function availabilityColor(): string
    {
        return match ($this->availability_status) {
            'occupied' => 'warning',
            'off_duty' => 'danger',
            default => 'success',
        };
    }
}
