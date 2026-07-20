<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Api\VehicleApiController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\WalletAuthController;
use App\Http\Controllers\BlockchainHistoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverPlanningController;
use App\Http\Controllers\FuelController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OdometerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response('ok', 200));

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/wallet', [WalletAuthController::class, 'show'])->name('wallet.connect');
    Route::post('/wallet', [WalletAuthController::class, 'connectManual'])->name('wallet.legacy');
    Route::post('/wallet/activate', [WalletAuthController::class, 'activateSession'])->name('wallet.activate');
    Route::get('/wallet/nonce', [WalletAuthController::class, 'nonce'])->name('wallet.nonce');
    Route::post('/wallet/verify', [WalletAuthController::class, 'verify'])->name('wallet.verify');
    Route::post('/wallet/manual', [WalletAuthController::class, 'connectManual'])->name('wallet.manual');
    Route::post('/wallet/disconnect', [WalletAuthController::class, 'disconnect'])->name('wallet.disconnect');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    Route::get('/planning', [DriverPlanningController::class, 'index'])->name('planning.index');
    Route::get('/planning/{driver}', [DriverPlanningController::class, 'show'])->name('planning.show');
    Route::get('/maintenances', [MaintenanceController::class, 'index'])->name('maintenances.index');
    Route::get('/fuel', [FuelController::class, 'index'])->name('fuel.index');
    Route::get('/odometer', [OdometerController::class, 'index'])->name('odometer.index');

    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments/{assignment}/pickup', [AssignmentController::class, 'confirmPickup'])->middleware('wallet.verified')->name('assignments.pickup');
    Route::post('/assignments/{assignment}/return', [AssignmentController::class, 'returnVehicle'])->middleware('wallet.verified')->name('assignments.return');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/sync', [AlertController::class, 'sync'])->name('alerts.sync');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    Route::get('/history', [BlockchainHistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{vehicle}', [BlockchainHistoryController::class, 'show'])->name('history.show');
    Route::redirect('/integrity', '/history')->name('integrity.index');
    Route::get('/integrity/{vehicle}', fn ($vehicle) => redirect()->route('history.show', $vehicle))->name('integrity.show');

    Route::middleware('role:super_admin,fleet_manager')->group(function () {
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        Route::get('/drivers/create', [DriverController::class, 'create'])->name('drivers.create');
        Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
        Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        Route::post('/vehicles/{vehicle}/assign', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::post('/documents', [DocumentController::class, 'storeCentral'])->name('documents.store.central');
        Route::post('/documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');
        Route::post('/vehicles/{vehicle}/documents', [DocumentController::class, 'store'])->name('documents.store');
    });

    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

    Route::middleware('role:driver,fleet_manager,super_admin')->group(function () {
        Route::post('/planning/{driver}/schedules', [DriverPlanningController::class, 'storeSchedule'])->name('planning.schedules.store');
        Route::delete('/planning/schedules/{schedule}', [DriverPlanningController::class, 'destroySchedule'])->name('planning.schedules.destroy');
        Route::post('/planning/{driver}/trips', [DriverPlanningController::class, 'storeTrip'])->name('planning.trips.store');
        Route::delete('/planning/trips/{trip}', [DriverPlanningController::class, 'destroyTrip'])->name('planning.trips.destroy');
        Route::patch('/planning/{driver}/availability', [DriverPlanningController::class, 'updateAvailability'])->name('planning.availability.update');

        Route::post('/vehicles/{vehicle}/odometer', [OdometerController::class, 'store'])->name('odometer.store');
        Route::post('/vehicles/{vehicle}/fuel', [FuelController::class, 'store'])->name('fuel.store');
    });

    Route::middleware('role:mechanic,super_admin,fleet_manager')->group(function () {
        Route::post('/vehicles/{vehicle}/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
    });
});

Route::prefix('api')->group(function () {
    Route::get('/vehicles/public/{vehicle}/history', [VehicleApiController::class, 'publicHistory']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/vehicles', [VehicleApiController::class, 'index']);
        Route::get('/vehicles/{vehicle}', [VehicleApiController::class, 'show']);
    });
});
