<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\EthereumAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = UserRole::cases();

        return view('users.create', compact('roles'));
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
            'role' => ['required', 'in:super_admin,fleet_manager,driver,mechanic,auditor'],
            'wallet_address' => ['nullable', 'string', 'max:42', new EthereumAddress],
        ]);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'wallet_address' => EthereumAddress::normalize($data['wallet_address'] ?? null),
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Statut utilisateur mis à jour.');
    }
}
