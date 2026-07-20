<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case OnMission = 'on_mission';
    case InRepair = 'in_repair';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::OnMission => 'En mission',
            self::InRepair => 'En réparation',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::OnMission => 'warning',
            self::InRepair => 'danger',
        };
    }
}
