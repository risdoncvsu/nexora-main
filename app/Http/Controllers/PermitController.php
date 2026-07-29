<?php

namespace App\Http\Controllers;

use App\Models\CompliancePermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PermitController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $this->clientId($request);

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'issuer' => 'required|string|max:255',
                'expiry_date' => 'required|date',
                'status' => 'required|string|in:Active,Expiring Soon,Expired',
                'renew_id' => 'nullable|integer',
                'permit_file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            ]);

            $permit = isset($validated['renew_id'])
                ? CompliancePermit::query()->where('company_id', $clientId)->findOrFail($validated['renew_id'])
                : new CompliancePermit(['company_id' => $clientId]);

            $filePath = $request->hasFile('permit_file')
                ? $request->file('permit_file')->store('compliance/permits', 'public')
                : $permit->file_path;

            $permit->fill([
                'title' => $validated['title'],
                'issuer' => $validated['issuer'],
                'expiry_date' => $validated['expiry_date'],
                'status' => $validated['status'],
                'file_path' => $filePath,
            ])->save();

            return redirect()->route('client.itsm.permit')->with('success', 'Permit saved successfully.');
        }

        $base = CompliancePermit::query()->where('company_id', $clientId);
        $activeCount = (clone $base)->where('status', 'Active')->count();
        $expiredCount = (clone $base)->where('status', 'Expired')->count();
        $expiringSoonCount = (clone $base)->where('status', 'Expiring Soon')->count();
        $currentStatus = $request->string('status')->toString() ?: 'All';
        $search = trim($request->string('search')->toString());

        $permits = $base
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%'])->orWhereRaw('LOWER(issuer) LIKE ?', ['%'.strtolower($search).'%'])))
            ->when($currentStatus !== 'All', fn ($query) => $query->where('status', $currentStatus))
            ->latest()->get()->map(fn (CompliancePermit $permit): array => [
                'id' => $permit->id,
                'title' => $permit->title,
                'issuer' => $permit->issuer,
                'expiry' => 'Expires: '.$permit->expiry_date?->toDateString(),
                'expiry_date' => $permit->expiry_date?->toDateString(),
                'status' => $permit->status,
                'status_color' => $this->statusColor($permit->status),
                'file_path' => $permit->file_path,
                'file_url' => $permit->file_path ? route('client.itsm.permit.file', ['permit' => $permit->id]) : null,
            ]);

        return view('permit', compact('permits', 'currentStatus', 'activeCount', 'expiredCount', 'expiringSoonCount', 'search'));
    }

    public function file(Request $request, CompliancePermit $permit)
    {
        abort_unless((int) $permit->company_id === $this->clientId($request), 404);
        abort_unless($permit->file_path && Storage::disk('public')->exists($permit->file_path), 404);

        return $request->boolean('download')
            ? Storage::disk('public')->download($permit->file_path)
            : Storage::disk('public')->response($permit->file_path);
    }

    private function clientId(Request $request): int
    {
        $clientId = (int) $request->user()?->company_id;
        abort_unless($clientId > 0, 403);

        return $clientId;
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'Expiring Soon' => 'bg-amber-500',
            'Expired' => 'bg-red-600',
            default => 'bg-green-600',
        };
    }
}
