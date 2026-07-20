<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class BlockchainService
{
    public function record(string $payload, string $recordType = 'generic', int $entityId = 0): array
    {
        $contentHash = hash('sha256', $this->canonicalize($payload));

        if (! $this->isEnabled()) {
            return $this->simulate($contentHash);
        }

        $result = $this->callBridge([
            'action' => 'record',
            'record_id' => $contentHash,
            'content_hash' => $contentHash,
            'record_type' => $recordType,
            'entity_id' => $entityId,
        ]);

        if (($result['already_recorded'] ?? false) && ! ($result['valid'] ?? false)) {
            throw new RuntimeException('Le registre contient déjà un enregistrement différent pour cet identifiant.');
        }

        return [
            'content_hash' => $contentHash,
            'blockchain_tx_hash' => $result['transaction_hash'] ?? '0x'.$contentHash,
            'recorded_at' => now()->toIso8601String(),
            'block_number' => $result['block_number'] ?? null,
            'chain_id' => $result['chain_id'] ?? config('blockchain.chain_id'),
            'simulated' => false,
        ];
    }

    public function verify(string $payload, string $expectedHash): bool
    {
        $legacyHash = hash('sha256', $payload);
        $canonicalHash = hash('sha256', $this->canonicalize($payload));
        if (! hash_equals($expectedHash, $legacyHash)
            && ! hash_equals($expectedHash, $canonicalHash)) {
            return false;
        }

        if (! $this->isEnabled()) {
            return true;
        }

        try {
            $result = $this->callBridge([
                'action' => 'verify',
                'record_id' => $expectedHash,
                'content_hash' => $expectedHash,
            ]);

            return (bool) ($result['valid'] ?? false);
        } catch (RuntimeException) {
            return false;
        }
    }

    public function status(): array
    {
        if (! $this->isEnabled()) {
            return ['connected' => false, 'mode' => 'simulation'];
        }

        return $this->callBridge(['action' => 'status']);
    }

    public function isEnabled(): bool
    {
        return config('blockchain.mode') === 'polygon';
    }

    public function explorerTransactionUrl(string $transactionHash): ?string
    {
        $explorer = rtrim((string) config('blockchain.explorer_url'), '/');

        return $explorer && preg_match('/^0x[a-fA-F0-9]{64}$/', $transactionHash)
            ? $explorer.'/tx/'.$transactionHash
            : null;
    }

    public function recoverSigner(string $message, string $signature): string
    {
        $result = $this->callBridge([
            'action' => 'recover',
            'message' => $message,
            'signature' => $signature,
        ]);

        if (empty($result['address'])) {
            throw new RuntimeException('Impossible de récupérer le signataire.');
        }

        return strtolower($result['address']);
    }

    private function callBridge(array $payload): array
    {
        $process = new Process(
            [(string) config('blockchain.node_binary', 'node'), base_path('blockchain/bridge.mjs')],
            base_path(),
            [
                'POLYGON_RPC_URL' => (string) config('blockchain.rpc_url'),
                'BLOCKCHAIN_CONTRACT_ADDRESS' => (string) config('blockchain.contract_address'),
                'BLOCKCHAIN_PRIVATE_KEY' => (string) config('blockchain.private_key'),
                'BLOCKCHAIN_CHAIN_ID' => (string) config('blockchain.chain_id'),
                'BLOCKCHAIN_CONFIRMATIONS' => (string) config('blockchain.confirmations'),
                // Le serveur PHP Windows expose un environnement minimal.
                // Node 22+ a besoin de ces variables pour initialiser son CSPRNG.
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
                'COMSPEC' => getenv('COMSPEC') ?: 'C:\\Windows\\System32\\cmd.exe',
                'PATHEXT' => getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD',
                'PATH' => getenv('PATH') ?: '',
                'APPDATA' => getenv('APPDATA') ?: '',
                'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: '',
                'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
                'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
            ],
        );
        $process->setInput(json_encode($payload, JSON_THROW_ON_ERROR));
        $process->setTimeout((float) config('blockchain.timeout', 120));
        $process->run();

        $result = json_decode($process->getOutput(), true);
        if (! $process->isSuccessful() || ! is_array($result) || isset($result['error'])) {
            $message = $result['error']
                ?? (trim($process->getErrorOutput()) ?: 'Erreur inconnue du pont Polygon.');
            throw new RuntimeException('Transaction blockchain impossible : '.$message);
        }

        return $result;
    }

    private function simulate(string $contentHash): array
    {
        return [
            'content_hash' => $contentHash,
            'blockchain_tx_hash' => '0x'.hash('sha256', $contentHash.microtime(true).random_int(1000, 9999)),
            'recorded_at' => now()->toIso8601String(),
            'simulated' => true,
        ];
    }

    private function canonicalize(string $payload): string
    {
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return $payload;
        }

        $sort = function (array $value) use (&$sort): array {
            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $value[$key] = $sort($item);
                }
            }

            if (! array_is_list($value)) {
                ksort($value, SORT_STRING);
            }

            return $value;
        };

        return json_encode(
            $sort($decoded),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );
    }
}
