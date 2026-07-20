<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\EthereumAddress;
use App\Support\DemoWallets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(): View
    {
        $drivers = User::with('assignedVehicles')
            ->where('role', UserRole::Driver)
            ->latest()
            ->paginate(12);

        return view('drivers.index', compact('drivers'));
    }

    public function create(): View
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'wallet_address' => $request->filled('wallet_address') ? trim($request->wallet_address) : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'wallet_address' => ['nullable', 'string', 'max:42', new EthereumAddress],
        ], [
            'email.unique' => 'Cet email est déjà utilisé par un autre utilisateur.',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Driver,
            'wallet_address' => EthereumAddress::normalize($data['wallet_address'] ?? null),
            'is_active' => true,
        ]);

        return redirect()->route('drivers.index')->with('success', 'Chauffeur ajouté avec succès.');
    }

    public function edit(User $driver): View
    {
        abort_unless($driver->role === UserRole::Driver, 404);

        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, User $driver)
    {
        abort_unless($driver->role === UserRole::Driver, 404);

        $request->merge([
            'wallet_address' => $request->filled('wallet_address') ? trim($request->wallet_address) : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$driver->id],
            'password' => ['nullable', 'string', 'min:8'],
            'wallet_address' => ['nullable', 'string', 'max:42', new EthereumAddress],
            'is_active' => ['boolean'],
        ]);

        $driver->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'wallet_address' => EthereumAddress::normalize($data['wallet_address'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $driver->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()->route('drivers.index')->with('success', 'Chauffeur modifié.');
    }

    public function destroy(User $driver)
    {
        abort_unless($driver->role === UserRole::Driver, 404);

        if ($driver->assignedVehicles()->exists()) {
            return back()->with('error', 'Impossible de supprimer : ce chauffeur a un véhicule assigné.');
        }

        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Chauffeur supprimé.');
    }
}
