@extends('layouts.app')



@section('title', 'Chauffeurs')



@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

    <div>

        <h1 class="page-title mb-0"><i class="bi bi-person-badge me-2"></i>Gestion des chauffeurs</h1>

        <p class="page-subtitle mb-0">Ajouter, modifier, supprimer — attribution du wallet Web3 pour les missions signées</p>

    </div>

    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))

        <a href="{{ route('drivers.create') }}" class="btn glass-btn"><i class="bi bi-person-plus me-1"></i>Ajouter un chauffeur</a>

    @endif

</div>



@if($drivers->isEmpty())

<div class="glass-card no-hover-lift">

    <div class="glass-body text-center text-muted-glass py-5">

        <i class="bi bi-person-x fs-1 d-block mb-2"></i>

        Aucun chauffeur enregistré.

    </div>

</div>

@else

<div class="row g-3">

    @foreach($drivers as $driver)

    <div class="col-sm-6 col-xl-4">

        <div class="glass-card driver-card no-hover-lift h-100">

            <div class="glass-body">

                <div class="d-flex justify-content-between align-items-start mb-2">

                    <div>

                        <div class="planning-driver-name fw-bold">{{ $driver->name }}</div>

                        <div class="planning-driver-email">{{ $driver->email }}</div>

                    </div>

                    <span class="badge badge-glass-{{ $driver->is_active ? 'success' : 'danger' }}">{{ $driver->is_active ? 'Actif' : 'Inactif' }}</span>

                </div>

                <div class="mb-3">
                    <span class="badge badge-glass-{{ $driver->availabilityColor() }}">
                        <i class="bi bi-circle-fill me-1"></i>{{ $driver->availabilityLabel() }}
                    </span>
                    @if($driver->availability_note)
                        <div class="small text-muted-glass mt-1">{{ $driver->availability_note }}</div>
                    @endif
                </div>



                <div class="mb-3">

                    @if($driver->wallet_address)

                        <span class="badge badge-glass-success mb-1"><i class="bi bi-wallet2 me-1"></i>Wallet attribué</span>

                        <code class="small driver-wallet-code d-block">{{ substr($driver->wallet_address, 0, 14) }}…</code>

                    @else

                        <span class="badge badge-glass-warning"><i class="bi bi-exclamation-triangle me-1"></i>Wallet non attribué</span>

                    @endif

                </div>



                <div class="small text-muted-glass mb-3">

                    <i class="bi bi-truck me-1"></i>Véhicule :

                    @if($driver->assignedVehicles->first())

                        <a href="{{ route('vehicles.show', $driver->assignedVehicles->first()) }}" class="driver-vehicle-link">{{ $driver->assignedVehicles->first()->plate_number }}</a>

                    @else

                        <span>—</span>

                    @endif

                </div>



                <div class="d-flex gap-2 flex-wrap">

                    <a href="{{ route('planning.show', $driver) }}" class="btn btn-sm glass-btn-outline flex-grow-1">

                        <i class="bi bi-calendar3 me-1"></i>Emploi du temps

                    </a>

                    @if(auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FleetManager))

                    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm glass-btn-outline" title="Modifier"><i class="bi bi-pencil"></i></a>

                    <form method="POST" action="{{ route('drivers.destroy', $driver) }}" onsubmit="return confirm('Supprimer ce chauffeur ?')">

                        @csrf @method('DELETE')

                        <button type="submit" class="btn btn-sm glass-btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>

                    </form>

                    @endif

                </div>

            </div>

        </div>

    </div>

    @endforeach

</div>

<div class="mt-3">{{ $drivers->links() }}</div>

@endif

@endsection

