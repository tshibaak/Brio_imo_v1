<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractDocumentController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        abort_unless($contract->landlord_id === $request->user()->id, 404);
        $data = $request->validate([
            'category' => ['required', 'in:lease,deposit_receipt,inspection,other'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $file = $request->file('document');

        $contract->documents()->create([
            'uploaded_by' => $request->user()->id,
            'category' => $data['category'],
            'path' => $file->store('contract-documents', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Le document a été ajouté.');
    }

    public function download(Request $request, ContractDocument $document): StreamedResponse
    {
        $document->loadMissing('contract');
        $contract = $document->contract;
        abort_unless(in_array($request->user()->id, [$contract->landlord_id, $contract->tenant_id], true), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
