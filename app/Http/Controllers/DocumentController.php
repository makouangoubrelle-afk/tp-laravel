<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Vehicle;
use App\Services\AlertService;
use App\Services\BlockchainService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Document::with(['vehicle', 'uploader']);

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $documents = $query->latest()->paginate(12);

        $stats = [
            'total' => Document::count(),
            'insurance' => Document::where('type', 'insurance')->count(),
            'registration' => Document::where('type', 'registration')->count(),
            'expiring' => Document::whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->count(),
        ];

        $vehicles = Vehicle::orderBy('plate_number')->get();

        return view('documents.index', compact('documents', 'stats', 'vehicles'));
    }

    public function store(
        Request $request,
        Vehicle $vehicle,
        DocumentService $documentService,
        AlertService $alertService,
        BlockchainService $blockchain
    )
    {
        $data = $request->validate([
            'type' => ['required', 'in:registration,insurance,invoice,inspection,contract,other'],
            'title' => ['required', 'string', 'max:200'],
            'file' => ['required', 'file', 'max:5120'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $stored = $documentService->store($data['file'], $vehicle->id);
        $payload = $this->documentPayload(
            $vehicle->id,
            $request->user()->id,
            $data,
            $stored['checksum'],
        );
        $record = $blockchain->record($payload, 'document', $vehicle->id);

        Document::create([
            'vehicle_id' => $vehicle->id,
            'uploaded_by' => $request->user()->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'file_path' => $stored['file_path'],
            'checksum' => $stored['checksum'],
            'content_hash' => $record['content_hash'],
            'blockchain_tx_hash' => $record['blockchain_tx_hash'],
            'ipfs_hash' => $stored['ipfs_hash'],
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);

        $alertService->syncForVehicle($vehicle);

        return back()->with('success', 'Document archivé de façon centralisée. Checksum : '.substr($stored['checksum'], 0, 16).'...');
    }

    public function storeCentral(
        Request $request,
        DocumentService $documentService,
        AlertService $alertService,
        BlockchainService $blockchain
    )
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'type' => ['required', 'in:registration,insurance,invoice,inspection,contract,other'],
            'title' => ['required', 'string', 'max:200'],
            'file' => ['required', 'file', 'max:5120'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $stored = $documentService->store($data['file'], $vehicle->id);
        $payload = $this->documentPayload(
            $vehicle->id,
            $request->user()->id,
            $data,
            $stored['checksum'],
        );
        $record = $blockchain->record($payload, 'document', $vehicle->id);

        Document::create([
            'vehicle_id' => $vehicle->id,
            'uploaded_by' => $request->user()->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'file_path' => $stored['file_path'],
            'checksum' => $stored['checksum'],
            'content_hash' => $record['content_hash'],
            'blockchain_tx_hash' => $record['blockchain_tx_hash'],
            'ipfs_hash' => $stored['ipfs_hash'],
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);

        $alertService->syncForVehicle($vehicle);

        return redirect()->route('documents.index')
            ->with('success', 'Document « '.$data['title'].' » centralisé pour '.$vehicle->plate_number);
    }

    public function download(Document $document)
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($document->file_path, $document->title);
    }

    public function verify(
        Document $document,
        DocumentService $documentService,
        BlockchainService $blockchain
    )
    {
        $fileValid = $documentService->verifyIntegrity($document->file_path, $document->checksum);
        $payload = $this->documentPayload(
            $document->vehicle_id,
            $document->uploaded_by,
            [
                'type' => $document->type,
                'title' => $document->title,
                'expiry_date' => $document->expiry_date?->toDateString(),
            ],
            $document->checksum,
        );
        $chainValid = $document->content_hash
            && $blockchain->verify($payload, $document->content_hash);
        $valid = $fileValid && $chainValid;

        return back()->with(
            $valid ? 'success' : 'error',
            $valid ? 'Intégrité du document vérifiée — aucune altération détectée.' : 'ALERTE : le document a été modifié !'
        );
    }

    private function documentPayload(
        int $vehicleId,
        int $uploaderId,
        array $data,
        string $checksum
    ): string {
        return json_encode([
            'vehicle_id' => $vehicleId,
            'uploaded_by' => $uploaderId,
            'type' => $data['type'],
            'title' => $data['title'],
            'checksum' => $checksum,
            'expiry_date' => empty($data['expiry_date'])
                ? null
                : \Illuminate\Support\Carbon::parse($data['expiry_date'])->toDateString(),
        ]);
    }
}
