<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class SyncAlertsCommand extends Command
{
    protected $signature = 'alerts:sync';

    protected $description = 'Synchronise les alertes automatiques (CT, assurance, vidange, documents)';

    public function handle(AlertService $alertService): int
    {
        $count = $alertService->syncAll();
        $stats = $alertService->stats();

        $this->info("Alertes synchronisées pour {$count} véhicule(s).");
        $this->table(
            ['En attente', 'Critiques', 'En retard', 'Résolues'],
            [[$stats['pending'], $stats['critical'], $stats['overdue'], $stats['resolved']]]
        );

        return self::SUCCESS;
    }
}
