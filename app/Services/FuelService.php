<?php

namespace App\Services;

use App\Models\FuelRecord;
use App\Models\Vehicle;

class FuelService
{
    public function recordConsumption(
        Vehicle $vehicle,
        int $driverId,
        float $liters,
        float $cost,
        int $mileageAtFill,
        ?string $slipReference = null
    ): FuelRecord {
        $previous = FuelRecord::where('vehicle_id', $vehicle->id)
            ->orderByDesc('mileage_at_fill')
            ->first();

        $consumption = null;

        if ($previous && $mileageAtFill > $previous->mileage_at_fill && $liters > 0) {
            $distance = $mileageAtFill - $previous->mileage_at_fill;
            $consumption = round(($liters / $distance) * 100, 2);
        }

        return FuelRecord::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driverId,
            'liters' => $liters,
            'cost' => $cost,
            'mileage_at_fill' => $mileageAtFill,
            'consumption_avg' => $consumption,
            'filled_at' => now(),
            'slip_reference' => $slipReference,
        ]);
    }
}
