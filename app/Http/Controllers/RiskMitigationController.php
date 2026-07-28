<?php

namespace App\Http\Controllers;

use App\Models\RiskAssessment;
use App\Models\RiskMitigationPlan;
use Illuminate\Http\Request;

class RiskMitigationController extends Controller
{
    public function index(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $search = trim((string) $request->input('search'));
        $statusFilter = trim((string) $request->input('status_filter'));
        $mitigations = RiskMitigationPlan::query()->where('company_id', $clientId)
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('title', 'ilike', "%{$search}%")->orWhere('owner', 'ilike', "%{$search}%")))
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest()->get();
        $risks = RiskAssessment::query()->where('company_id', $clientId)->orderBy('title')->get(['id', 'title']);

        return view('mitigation', [
            'mitigations' => $mitigations,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'activeCount' => $mitigations->where('status', 'In Progress')->count(),
            'totalBudget' => (float) $mitigations->sum('budget'),
            'overdueCount' => $mitigations->where('status', 'Draft')->count(),
            'risks' => $risks,
        ]);
    }

    public function store(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'risk_id' => ['required', 'integer'],
            'owner' => ['required', 'string', 'max:255'],
            'budget' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Draft,In Progress,Completed'],
        ]);
        abort_unless(RiskAssessment::query()->whereKey($validated['risk_id'])->where('company_id', $clientId)->exists(), 422);

        RiskMitigationPlan::create([
            'company_id' => $clientId,
            'risk_assessment_id' => $validated['risk_id'],
            'title' => $validated['title'],
            'owner' => $validated['owner'],
            'budget' => $validated['budget'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('client.itsm.risk.mitigation')->with('success', 'Mitigation plan saved.');
    }
}
