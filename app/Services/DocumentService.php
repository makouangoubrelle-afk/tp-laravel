<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function store(UploadedFile $file, int $vehicleId): array
    {
        $path = $file->store("vehicles/{$vehicleId}/documents", 'local');
        $checksum = hash_file('sha256', Storage::disk('local')->path($path));

        return [
            'file_path' => $path,
            'checksum' => $checksum,
            // Aucun faux CID : ce champ reste vide tant qu'un nœud IPFS réel
            // n'est pas configuré.
            'ipfs_hash' => null,
        ];
    }

    public function verifyIntegrity(string $filePath, string $expectedChecksum): bool
    {
        if (! Storage::disk('local')->exists($filePath)) {
            return false;
        }

        $actual = hash_file('sha256', Storage::disk('local')->path($filePath));

        return hash_equals($expectedChecksum, $actual);
    }
}
