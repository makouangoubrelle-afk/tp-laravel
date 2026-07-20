<?php

namespace App\Console\Commands;

use App\Services\BlockchainService;
use Illuminate\Console\Command;
use Throwable;

class BlockchainStatus extends Command
{
    protected $signature = 'blockchain:status';

    protected $description = 'Vérifie la connexion RPC et le contrat AutoChain';

    public function handle(BlockchainService $blockchain): int
    {
        if (! $blockchain->isEnabled()) {
            $this->warn('Blockchain en mode simulation. Configurez BLOCKCHAIN_MODE=polygon.');

            return self::FAILURE;
        }

        try {
            $status = $blockchain->status();
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Réseau', 'Chain ID', 'Bloc', 'Contrat déployé'],
            [[
                config('blockchain.network'),
                $status['chain_id'] ?? '?',
                $status['block_number'] ?? '?',
                ($status['contract_deployed'] ?? false) ? 'Oui' : 'Non',
            ]],
        );

        return ($status['contract_deployed'] ?? false)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
