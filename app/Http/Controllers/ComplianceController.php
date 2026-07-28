<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComplianceController extends Controller
{
    /**
     * Ipakita ang Compliance tracking requirements kasama ang search at filter features.
     */
    public function index(Request $request)
    {

        // Kung walang laman ang session, i-initialize ito bilang isang empty array placeholder
        if (!session()->has('compliance_items')) {
            session(['compliance_items' => []]);
        }

        $items = collect(session('compliance_items'))->filter(fn ($item) => is_array($item));

        // 1. Search Query execution engine matching (titling strings case-insensitive check)
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['title']), $search) || 
                       str_contains(strtolower($item['audience']), $search);
            });
        }

        // 2. Status selection drop filter execution matching
        if ($request->filled('status')) {
            $items = $items->where('status', $request->status);
        }

        $items = $items->map(function (array $item) {
            if (!empty($item['file_path'])) {
                $item['file_url'] = route('client.itsm.compliance.file', ['path' => $item['file_path']]);
            }

            return $item;
        });

        return view('compliance', ['requirements' => $items]);
    }

    /**
     * I-store ang bagong gawang raw requirement sa session array container storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'audience' => 'required|string|max:255',
            'status' => 'required|string',
            'course_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
        ]);

        // I-set ang tamang tailwind color scheme configuration mapping base sa orihinal na structure
        $colorMap = [
            'Active' => 'bg-[#16A34A]',
            'Urgent' => 'bg-[#DC2626]',
            'Completed' => 'bg-[#16A34A]',
            'Pending Review' => 'bg-[#D97706]'
        ];

        $currentItems = session('compliance_items', []);

        $filePath = $request->hasFile('course_file')
            ? $request->file('course_file')->store('compliance/courses', 'public')
            : null;

        // Gumawa ng panibagong element data framework node array block 
        $newItem = [
            'title' => $validated['title'],
            'audience' => $validated['audience'],
            'status' => $validated['status'],
            // Progress is system-derived from the lifecycle state.  It is not
            // a value the user should have to calculate or type manually.
            'progress' => match ($validated['status']) {
                'Completed' => '100%',
                'Active' => '50%',
                default => '0%',
            },
            'color' => $colorMap[$validated['status']] ?? 'bg-slate-600'
        ];

        if ($filePath !== null) {
            $newItem['file_path'] = $filePath;
        }

        // Isalang sa unahan ng card grid list arrays flow stack gamit ang unshift sequence pipeline
        array_unshift($currentItems, $newItem);
        session(['compliance_items' => $currentItems]);

        return redirect()->route('client.itsm.compliance')
                         ->with('success', 'Compliance requirement added successfully!');
    }

    /** Serve an attachment only when it belongs to the active user's session data. */
    public function file(Request $request, string $path)
    {
        $allowed = collect(session('compliance_items', []))->contains(
            fn ($item) => is_array($item) && ($item['file_path'] ?? null) === $path
        );

        abort_unless($allowed && Storage::disk('public')->exists($path), 404);

        return $request->boolean('download')
            ? Storage::disk('public')->download($path)
            : Storage::disk('public')->response($path);
    }
}
