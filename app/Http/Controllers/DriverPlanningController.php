<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\DriverSchedule;
use App\Models\DriverTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DriverPlanningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverPlanningController extends Controller
{
    public function index(Request $request, DriverPlanningService $planning): View
    {
        $weekStart = $planning->weekStart($request->query('week'));

        if ($request->user()->hasRole(UserRole::Driver)) {
            return redirect()->route('planning.show', [
                'driver' => $request->user(),
                'week' => $weekStart->toDateString(),
            ]);
        }

        $drivers = User::with('schedules')
            ->where('role', UserRole::Driver)
            ->orderBy('name')
            ->get()
            ->map(function (User $driver) use ($planning, $weekStart) {
                return [
                    'driver' => $driver,
                    'contract_hours' => $planning->weeklyContractualHours($driver),
                    'mission_hours' => $planning->missionHoursForWeek($driver, $weekStart),
                    'trips_count' => $planning->tripsForWeek($driver, $weekStart)->count(),
                    'slots_count' => $driver->schedules->where('is_active', true)->count(),
                ];
            });

        return view('planning.index', compact('drivers', 'weekStart'));
    }

    public function show(Request $request, User $driver, DriverPlanningService $planning): View
    {
        abort_unless($driver->role === UserRole::Driver, 404);
        abort_unless($this->canManage($request, $driver), 403);

        $weekStart = $planning->weekStart($request->query('week'));
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $schedulesByDay = $planning->schedulesByDay($driver);
        $missions = $planning->missionsForWeek($driver, $weekStart);
        $trips = $planning->tripsForWeek($driver, $weekStart);
        $canEdit = $this->canManage($request, $driver);
        $vehicles = Vehicle::orderBy('plate_number')->get();

        $stats = [
            'contract_hours' => $planning->weeklyContractualHours($driver),
            'mission_hours' => $planning->missionHoursForWeek($driver, $weekStart),
            'missions_count' => $missions->count(),
            'trips_count' => $trips->count(),
        ];

        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();

        return view('planning.show', compact(
            'driver', 'weekStart', 'weekEnd', 'schedulesByDay', 'missions', 'trips',
            'stats', 'prevWeek', 'nextWeek', 'canEdit', 'vehicles'
        ));
    }

    public function storeSchedule(Request $request, User $driver)
    {
        abort_unless($driver->role === UserRole::Driver, 404);
        abort_unless($this->canManage($request, $driver), 403);

        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:50'],
        ], [
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ]);

        DriverSchedule::create([
            'driver_id' => $driver->id,
            ...$data,
            'start_time' => $data['start_time'].':00',
            'end_time' => $data['end_time'].':00',
        ]);

        return redirect()
            ->route('planning.show', ['driver' => $driver, 'week' => $request->input('week')])
            ->with('success', 'Créneau horaire enregistré.');
    }

    public function destroySchedule(Request $request, DriverSchedule $schedule)
    {
        abort_unless($this->canManage($request, $schedule->driver), 403);

        $driver = $schedule->driver;
        $schedule->delete();

        return redirect()
            ->route('planning.show', ['driver' => $driver, 'week' => $request->input('week')])
            ->with('success', 'Créneau supprimé.');
    }

    public function storeTrip(Request $request, User $driver)
    {
        abort_unless($driver->role === UserRole::Driver, 404);
        abort_unless($this->canManage($request, $driver), 403);

        $data = $request->validate([
            'trip_at' => ['required', 'date'],
            'origin' => ['required', 'string', 'max:150'],
            'destination' => ['required', 'string', 'max:150'],
            'distance_km' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DriverTrip::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'trip_at' => Carbon::parse($data['trip_at']),
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'distance_km' => $data['distance_km'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('planning.show', ['driver' => $driver, 'week' => $request->input('week')])
            ->with('success', 'Course enregistrée.');
    }

    public function destroyTrip(Request $request, DriverTrip $trip)
    {
        abort_unless($this->canManage($request, $trip->driver), 403);

        $driver = $trip->driver;
        $trip->delete();

        return redirect()
            ->route('planning.show', ['driver' => $driver, 'week' => $request->input('week')])
            ->with('success', 'Course supprimée.');
    }

    public function updateAvailability(Request $request, User $driver)
    {
        abort_unless($driver->role === UserRole::Driver, 404);
        abort_unless($this->canManage($request, $driver), 403);

        $data = $request->validate([
            'availability_status' => ['required', 'in:available,occupied,off_duty'],
            'availability_note' => ['nullable', 'string', 'max:255'],
        ]);

        $driver->update([
            'availability_status' => $data['availability_status'],
            'availability_note' => $data['availability_note'] ?? null,
            'availability_updated_at' => now(),
        ]);

        return redirect()
            ->route('planning.show', ['driver' => $driver, 'week' => $request->input('week')])
            ->with('success', 'Votre disponibilité a été mise à jour.');
    }

    private function canManage(Request $request, User $driver): bool
    {
        if ($request->user()->hasRole(UserRole::SuperAdmin, UserRole::FleetManager)) {
            return true;
        }

        return $request->user()->hasRole(UserRole::Driver)
            && $request->user()->id === $driver->id;
    }
}
