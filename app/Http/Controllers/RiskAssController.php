<?php

namespace App\Http\Controllers;

use App\Models\ComplianceRiskAssessment;
use Illuminate\Http\Request;

class RiskAssController extends Controller
{
    /**
     * Display the Risk Assessment console dashboard workspace.
     */
    public function index(Request $request)
    {
        $risks = ComplianceRiskAssessment::query()
            ->where('company_id', $this->clientId($request))
            ->latest()
            ->get()
            ->map(fn (ComplianceRiskAssessment $risk): array => [
                'id' => $risk->id,
                'risk_id' => $risk->risk_id,
                'title' => $risk->title,
                'inherent_score' => (float) $risk->inherent_score,
                'inherent_text' => $risk->inherent_text,
                'likelihood' => $risk->likelihood,
                'impact' => $risk->impact,
                'residual_score' => (float) $risk->residual_score,
                'residual_text' => $risk->residual_text,
                'status' => $risk->status,
            ]);

        return view('RiskAss', compact('risks'));
    }

    /**
     * Persist a newly evaluated threat matrix entity within the active configuration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'risk_id' => 'required|string',
            'title' => 'required|string',
            'inherent_score' => 'required|numeric',
            'inherent_text' => 'required|string',
            'likelihood' => 'required|integer|between:1,5',
            'impact' => 'required|integer|between:1,5',
            'residual_score' => 'required|numeric',
            'residual_text' => 'required|string',
            'status' => 'required|string|in:Active,Mitigated,Pending Review'
        ]);

        ComplianceRiskAssessment::create($validated + [
            'company_id' => $this->clientId($request),
        ]);

        return redirect()->route('client.itsm.risk.assessment')->with('success', 'Risk Assessment metric committed successfully.');
    }

    private function clientId(Request $request): int
    {
        $clientId = (int) $request->user()?->company_id;
        abort_unless($clientId > 0, 403);

        return $clientId;
    }
}
