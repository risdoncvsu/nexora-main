<?php

namespace App\Http\Controllers;

use App\Models\ComplianceDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $currentFilter = $request->query('filter', 'All');
        $search = trim((string) $request->query('search'));
        $base = ComplianceDocument::query()->where('company_id', $clientId);
        $documents = (clone $base)->when($currentFilter !== 'All', fn ($query) => $query->where('status', $currentFilter))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('details', 'ilike', "%{$search}%")->orWhere('linked_id', 'ilike', "%{$search}%")->orWhere('classification', 'ilike', "%{$search}%")))
            ->latest()->get()->map(fn (ComplianceDocument $document) => [
                'id' => $document->id,
                'details' => $document->details,
                'linked_id' => $document->linked_id,
                'classification' => $document->classification,
                'status' => $document->status,
                'file_path' => $document->file_path,
                'file_url' => $document->file_path ? route('client.itsm.document.file', ['document' => $document->id]) : null,
            ]);

        return view('document', [
            'documents' => $documents,
            'currentFilter' => $currentFilter,
            'totalStored' => (clone $base)->count(),
            'needsSignOff' => (clone $base)->where('status', 'Needs Sign-Off')->count(),
            'lapsedCount' => (clone $base)->where('status', 'Lapsed')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'details' => ['required', 'string', 'max:255'],
            'classification' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Needs Sign-Off,Lapsed'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
        ]);
        $clientId = (int) $request->user()->company_id;
        $next = (int) ComplianceDocument::query()->where('company_id', $clientId)->max('id') + 1;
        $filePath = $request->hasFile('document_file') ? $request->file('document_file')->store('compliance/documents', 'public') : null;

        ComplianceDocument::create($validated + [
            'company_id' => $clientId,
            'linked_id' => sprintf('DOC-%04d', $next),
            'file_path' => $filePath,
        ]);

        return redirect()->route('client.itsm.document')->with('success', 'Document registered successfully.');
    }

    public function file(Request $request, ComplianceDocument $document)
    {
        abort_unless((int) $document->company_id === (int) $request->user()->company_id, 404);
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return $request->boolean('download') ? Storage::disk('public')->download($document->file_path) : Storage::disk('public')->response($document->file_path);
    }
}
