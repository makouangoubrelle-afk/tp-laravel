@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-0"><i class="bi bi-people me-2"></i>Gestion des utilisateurs</h1>
    </div>
    <a href="{{ route('users.create') }}" class="btn glass-btn"><i class="bi bi-person-plus me-1"></i>Nouvel utilisateur</a>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="table glass-table mb-0">
            <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Wallet</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge badge-glass-info">{{ $user->role->label() }}</span></td>
                    <td><code class="small text-white-50">{{ $user->wallet_address ? substr($user->wallet_address, 0, 12).'...' : '—' }}</code></td>
                    <td><span class="badge badge-glass-{{ $user->is_active ? 'success' : 'danger' }}">{{ $user->is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('users.toggle', $user) }}">@csrf @method('PATCH')
                            <button class="btn btn-sm glass-btn-outline">{{ $user->is_active ? 'Désactiver' : 'Activer' }}</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
