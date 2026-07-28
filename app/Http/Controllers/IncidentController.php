<?php

namespace App\Http\Controllers;

use App\Models\RiskIncident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $search = trim((string) $request->input('search'));
        $statusFilter = trim((string) $request->input('status'));
        $all = RiskIncident::query()->where('company_id', $clientId)->latest()->get();
        $incidents = $all->when($search, fn ($items) => $items->filter(fn (RiskIncident $item) => str_contains(strtolower($item->incident_no.' '.$item->title.' '.$item->reporter), strtolower($search))))
            ->when($statusFilter, fn ($items) => $items->where('status', $statusFilter))
            ->map(fn (RiskIncident $incident) => [
                'id' => $incident->incident_no,
                'title' => $incident->title,
                'severity' => $incident->severity,
                'datetime' => $incident->created_at?->format('Y-m-d H:i'),
                'reporter' => $incident->reporter ?: 'System',
                'status' => $incident->status,
            ])->values();
        $resolved = $all->where('status', 'Resolved')->whereNotNull('resolved_at');
        $averageMinutes = $resolved->isEmpty() ? null : (int) round($resolved->avg(fn (RiskIncident $incident) => $incident->created_at->diffInMinutes($incident->resolved_at)));

        return view('incident', [
            'incidents' => $incidents,
            'criticalCount' => $all->where('severity', 'Critical')->where('status', '!=', 'Resolved')->count(),
            'totalThisMonth' => $all->count(),
            'avgResolutionTime' => $averageMinutes === null ? 'N/A' : $averageMinutes.' min',
            'currentSearch' => $search,
            'currentStatus' => $statusFilter,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:Low,Medium,High,Critical'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $clientId = (int) $request->user()->company_id;
        $next = (int) RiskIncident::query()->where('company_id', $clientId)->count() + 1;

        RiskIncident::create($validated + [
            'company_id' => $clientId,
            'incident_no' => 'INC-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
            'reporter' => (string) ($request->user()->name ?? 'System'),
            'status' => 'Open',
        ]);

        return redirect()->route('client.itsm.risk.incident')->with('success', 'Incident logged.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate(['status' => ['required', 'in:Open,Investigating,Resolved']]);
        $incident = RiskIncident::query()->where('incident_no', $id)->where('company_id', (int) $request->user()->company_id)->firstOrFail();
        $incident->update(['status' => $validated['status'], 'resolved_at' => $validated['status'] === 'Resolved' ? now() : null]);

        return redirect()->route('client.itsm.risk.incident', array_filter(['status' => $request->input('current_status_context'), 'search' => $request->input('current_search_context')]));
    }
}
