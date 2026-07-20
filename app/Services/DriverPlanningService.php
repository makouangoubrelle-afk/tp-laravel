<?php

namespace App\Services;

use App\Models\DriverSchedule;
use App\Models\User;
use App\Models\VehicleAssignment;
use Carbon\Carbon;

class DriverPlanningService
{
    public function weekStart(?string $weekParam = null): Carbon
    {
        if ($weekParam) {
            return Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY);
        }

        return now()->startOfWeek(Carbon::MONDAY);
    }

    public function contractualHours(User $driver): float
    {
        return round(
            $driver->schedules()
                ->where('is_active', true)
                ->get()
                ->sum(fn (DriverSchedule $s) => $s->durationHours()),
            1
        );
    }

    public function weeklyContractualHours(User $driver): float
    {
        return $this->contractualHours($driver);
    }

    public function missionHoursForWeek(User $driver, Carbon $weekStart): float
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $assignments = VehicleAssignment::where('driver_id', $driver->id)
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('assigned_at', [$weekStart, $weekEnd])
                    ->orWhereBetween('returned_at', [$weekStart, $weekEnd])
                    ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                        $q2->where('assigned_at', '<=', $weekEnd)
                            ->where(function ($q3) use ($weekStart) {
                                $q3->whereNull('returned_at')
                                    ->orWhere('returned_at', '>=', $weekStart);
                            });
                    });
            })
            ->get();

        $totalMinutes = 0;

        foreach ($assignments as $assignment) {
            $start = $assignment->pickup_confirmed_at ?? $assignment->assigned_at;
            $end = $assignment->returned_at ?? now();

            $clipStart = $start->lt($weekStart) ? $weekStart : $start;
            $clipEnd = $end->gt($weekEnd) ? $weekEnd : $end;

            if ($clipEnd->gt($clipStart)) {
                $totalMinutes += $clipStart->diffInMinutes($clipEnd);
            }
        }

        return round($totalMinutes / 60, 1);
    }

    public function schedulesByDay(User $driver): array
    {
        $grid = array_fill(1, 7, []);

        $driver->schedules()
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->each(function (DriverSchedule $schedule) use (&$grid) {
                $grid[$schedule->day_of_week][] = $schedule;
            });

        return $grid;
    }

    public function isWithinSchedule(User $driver, Carbon $moment): bool
    {
        $day = $moment->dayOfWeekIso;
        $time = $moment->format('H:i:s');

        return $driver->schedules()
            ->where('is_active', true)
            ->where('day_of_week', $day)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->exists();
    }

    public function assignmentScheduleWarning(User $driver, Carbon $assignedAt): ?string
    {
        if ($driver->schedules()->where('is_active', true)->doesntExist()) {
            return 'Aucun horaire défini pour ce chauffeur — pensez à compléter son emploi du temps.';
        }

        if (! $this->isWithinSchedule($driver, $assignedAt)) {
            return 'Attention : cette attribution est en dehors des horaires prévus du chauffeur ('.$assignedAt->format('d/m/Y H:i').').';
        }

        return null;
    }

    public function missionsForWeek(User $driver, Carbon $weekStart): \Illuminate\Support\Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return VehicleAssignment::with('vehicle')
            ->where('driver_id', $driver->id)
            ->where('assigned_at', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->where('returned_at', '>=', $weekStart)
                    ->orWhereNull('returned_at');
            })
            ->orderBy('assigned_at')
            ->get();
    }

    public function tripsForWeek(User $driver, Carbon $weekStart): \Illuminate\Support\Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return $driver->trips()
            ->with('vehicle')
            ->whereBetween('trip_at', [$weekStart, $weekEnd])
            ->orderBy('trip_at')
            ->get();
    }
}
