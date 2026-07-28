<?php

namespace App\Http\Controllers;

use App\Models\RiskAssessment;
use Illuminate\Http\Request;

class RiskController extends Controller
{
    public function index(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $search = trim((string) $request->input('search'));
        $statusFilter = trim((string) $request->input('status_filter'));
        $risks = RiskAssessment::query()->where('company_id', $clientId)
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('title', 'ilike', "%{$search}%")->orWhere('category', 'ilike', "%{$search}%")))
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest('updated_at')->get()
            ->each(function (RiskAssessment $risk): void {
                $risk->progress = match ($risk->status) { 'Mitigated' => 100, 'In Progress' => 50, default => 0 };
                $risk->last_reviewed = ($risk->review_date ?? $risk->updated_at)?->format('Y-m-d');
            });

        return view('risk', compact('risks', 'search', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:Unmitigated,In Progress,Mitigated'],
        ]);

        RiskAssessment::create($validated + [
            'company_id' => (int) $request->user()->company_id,
            'level' => 'Medium',
            'review_date' => now()->toDateString(),
        ]);

        return redirect()->route('client.itsm.risk')->with('success', 'Risk successfully logged.');
    }

    public function manage(Request $request, RiskAssessment $risk)
    {
        abort_unless((int) $risk->company_id === (int) $request->user()->company_id, 404);

        return view('risk-manage', compact('risk'));
    }

    public function update(Request $request, RiskAssessment $risk)
    {
        abort_unless((int) $risk->company_id === (int) $request->user()->company_id, 404);
        $validated = $request->validate([
            'status' => ['required', 'in:Unmitigated,In Progress,Mitigated'],
            'owner' => ['nullable', 'string', 'max:255'],
            'mitigation_plan' => ['nullable', 'string', 'max:2000'],
            'review_date' => ['nullable', 'date'],
        ]);
        $risk->update($validated);

        return redirect()->route('client.itsm.risk')->with('success', 'Risk management details updated.');
    }
}
