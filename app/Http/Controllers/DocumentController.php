<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\StoreDocumentVersionRequest;
use App\Models\Document;
use App\Models\Vault;
use App\Services\DocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DocumentController extends Controller
{
    public function create(Vault $vault): View
    {
        $this->authorize('createDocument', $vault);

        return view('documents.create', ['vault' => $vault]);
    }

    public function store(StoreDocumentRequest $request, Vault $vault, DocumentService $documents): RedirectResponse
    {
        $version = $documents->uploadPrivateDocument($vault, $request->file('file'), $request->validated());

        return redirect()->route('documents.show', $version->document)->with('status', 'Document uploaded.');
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        return view('documents.show', [
            'document' => $document->load(['ownerVault', 'versions.originalObject']),
        ]);
    }

    public function addVersion(StoreDocumentVersionRequest $request, Document $document, DocumentService $documents): RedirectResponse
    {
        $documents->addPrivateVersion($document, $request->file('file'), $request->validated());

        return redirect()->route('documents.show', $document)->with('status', 'Version added.');
    }
}
