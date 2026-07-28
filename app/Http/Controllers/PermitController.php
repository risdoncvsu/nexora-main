<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PermitController extends Controller
{
    public function index(Request $request)
    {
        // Baseline records are now empty by default
        $basePermits = [];

        // Process form submission to session store
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'issuer' => 'required|string|max:255',
                'expiry_date' => 'required|date',
                'status' => 'required|string|in:Active,Expiring Soon,Expired',
                'renew_id' => 'nullable|integer',
                'permit_file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            ]);

            $sessionPermits = session()->get('added_permits', []);
            $sessionPermits = is_array($sessionPermits) ? array_values(array_filter($sessionPermits, 'is_array')) : [];

            $statusColor = 'bg-green-600';
            if ($request->input('status') === 'Expiring Soon') {
                $statusColor = 'bg-amber-500';
            } elseif ($request->input('status') === 'Expired') {
                $statusColor = 'bg-red-600';
            }

            $renewId = $validated['renew_id'] ?? null;
            $existingIndex = $renewId === null ? null : collect($sessionPermits)->search(
                fn (array $permit) => (int) ($permit['id'] ?? 0) === (int) $renewId
            );
            $existingPermit = $existingIndex !== false && $existingIndex !== null ? $sessionPermits[$existingIndex] : [];
            $filePath = $request->hasFile('permit_file')
                ? $request->file('permit_file')->store('compliance/permits', 'public')
                : ($existingPermit['file_path'] ?? null);

            $permit = [
                'id' => $existingPermit['id'] ?? ((collect($sessionPermits)->max('id') ?? 0) + 1),
                'title' => $validated['title'],
                'issuer' => $validated['issuer'],
                'expiry' => 'Expires: ' . $validated['expiry_date'],
                'expiry_date' => $validated['expiry_date'],
                'status' => $validated['status'],
                'status_color' => $statusColor,
            ];

            if ($filePath !== null) {
                $permit['file_path'] = $filePath;
            }

            if ($existingIndex !== false && $existingIndex !== null) {
                $sessionPermits[$existingIndex] = $permit;
            } else {
                $sessionPermits[] = $permit;
            }

            session()->put('added_permits', $sessionPermits);
            return redirect()->route('client.itsm.permit');
        }

        $sessionPermits = session()->get('added_permits', []);
        $sessionPermits = is_array($sessionPermits) ? array_values(array_filter($sessionPermits, 'is_array')) : [];
        $allPermits = collect(array_merge($basePermits, $sessionPermits));

        // Compute live metrics dynamically based on available items
        $activeCount = $allPermits->where('status', 'Active')->count();
        $expiredCount = $allPermits->where('status', 'Expired')->count();
        $expiringSoonCount = $allPermits->where('status', 'Expiring Soon')->count();

        // Handle text search filters
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = strtolower($request->search);
            $allPermits = $allPermits->filter(function ($permit) use ($searchTerm) {
                return str_contains(strtolower($permit['title']), $searchTerm) ||
                       str_contains(strtolower($permit['issuer']), $searchTerm);
            });
        }

        // Handle dropdown filter selection
        $currentStatus = $request->get('status', 'All');
        if ($currentStatus !== 'All') {
            $allPermits = $allPermits->filter(function ($permit) use ($currentStatus) {
                return $permit['status'] === $currentStatus;
            });
        }

        $allPermits = $allPermits->map(function (array $permit) {
            if (!empty($permit['file_path'])) {
                $permit['file_url'] = route('client.itsm.permit.file', ['path' => $permit['file_path']]);
            }

            return $permit;
        });

        return view('permit', [
            'permits' => $allPermits,
            'currentStatus' => $currentStatus,
            'activeCount' => $activeCount,
            'expiredCount' => $expiredCount,
            'expiringSoonCount' => $expiringSoonCount,
            'search' => $request->get('search', '')
        ]);
    }

    /** Serve an attachment only when it belongs to the active user's session data. */
    public function file(Request $request, string $path)
    {
        $allowed = collect(session('added_permits', []))->contains(
            fn ($permit) => is_array($permit) && ($permit['file_path'] ?? null) === $path
        );

        abort_unless($allowed && Storage::disk('public')->exists($path), 404);

        return $request->boolean('download')
            ? Storage::disk('public')->download($path)
            : Storage::disk('public')->response($path);
    }
}
