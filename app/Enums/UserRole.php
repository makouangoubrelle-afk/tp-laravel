<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case FleetManager = 'fleet_manager';
    case Driver = 'driver';
    case Mechanic = 'mechanic';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::FleetManager => 'Gestionnaire de Parc',
            self::Driver => 'Chauffeur',
            self::Mechanic => 'Garagiste Agréé',
            self::Auditor => 'Auditeur / Acheteur',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManageFleet(): bool
    {
        return in_array($this, [self::SuperAdmin, self::FleetManager], true);
    }

    public function isReadOnly(): bool
    {
        return $this === self::Auditor;
    }
}
