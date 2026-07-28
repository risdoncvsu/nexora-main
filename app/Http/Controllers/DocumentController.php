<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display the Documents console dashboard workspace.
     */
    public function index(Request $request)
    {
        // Fetch session dataset if modifications were made, otherwise stick to an empty placeholder
        $documents = session('stored_documents', []);
        $documents = is_array($documents) ? array_values(array_filter($documents, 'is_array')) : [];

        // Dynamic Filtering
        $currentFilter = $request->query('filter', 'All');
        if ($currentFilter !== 'All') {
            $documents = array_filter($documents, function ($doc) use ($currentFilter) {
                return isset($doc['status']) && $doc['status'] === $currentFilter;
            });
        }

        // Dynamic Search
        $search = $request->query('search');
        if (!empty($search)) {
            $documents = array_filter($documents, function ($doc) use ($search) {
                return (str_contains(strtolower($doc['details'] ?? ''), strtolower($search)) || 
                        str_contains(strtolower($doc['linked_id'] ?? ''), strtolower($search)) ||
                        str_contains(strtolower($doc['classification'] ?? ''), strtolower($search)));
            });
        }

        $documents = array_map(function (array $document) {
            if (!empty($document['file_path'])) {
                $document['file_url'] = route('client.itsm.document.file', ['path' => $document['file_path']]);
            }

            return $document;
        }, $documents);

        // Compute Metric Strip Totals based on base session data
        $baseDocs = session('stored_documents', []);
        $baseDocs = is_array($baseDocs) ? array_values(array_filter($baseDocs, 'is_array')) : [];
        $totalStored = count($baseDocs);
        $needsSignOff = count(array_filter($baseDocs, fn($d) => ($d['status'] ?? '') === 'Needs Sign-Off'));
        $lapsedCount = count(array_filter($baseDocs, fn($d) => ($d['status'] ?? '') === 'Lapsed'));

        // Deployment runs on Linux, where view filenames are case-sensitive.
        // The actual template is `resources/views/document.blade.php`.
        return view('document', [
            'documents' => $documents,
            'currentFilter' => $currentFilter,
            'totalStored' => $totalStored,
            'needsSignOff' => $needsSignOff,
            'lapsedCount' => $lapsedCount
        ]);
    }

    /**
     * Persist a newly uploaded document metric entity within the active configuration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'details' => 'required|string',
            'linked_id' => 'nullable|string',
            'classification' => 'required|string',
            'status' => 'required|string|in:Active,Needs Sign-Off,Lapsed',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
        ]);

        $currentDocs = session('stored_documents', []);
        $currentDocs = is_array($currentDocs) ? array_values(array_filter($currentDocs, 'is_array')) : [];
        
        $validated['id'] = count($currentDocs) + 1;
        $validated['linked_id'] = $validated['linked_id'] ?: sprintf('DOC-%04d', $validated['id']);
        unset($validated['document_file']);
        if ($request->hasFile('document_file')) {
            $validated['file_path'] = $request->file('document_file')->store('compliance/documents', 'public');
        }
        $currentDocs[] = $validated;
        
        session(['stored_documents' => $currentDocs]);

        return redirect()->route('client.itsm.document')->with('success', 'Document registered successfully.');
    }

    /** Serve an attachment only when it belongs to the active user's session data. */
    public function file(Request $request, string $path)
    {
        $allowed = collect(session('stored_documents', []))->contains(
            fn ($document) => is_array($document) && ($document['file_path'] ?? null) === $path
        );

        abort_unless($allowed && Storage::disk('public')->exists($path), 404);

        return $request->boolean('download')
            ? Storage::disk('public')->download($path)
            : Storage::disk('public')->response($path);
    }
}
