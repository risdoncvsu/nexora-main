<?php

namespace App\Http\Controllers;

use App\Models\RiskAssessment;
use App\Models\RiskIncident;
use App\Models\RiskMitigationPlan;
use Illuminate\Http\Request;

class RiskAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $timeframe = $request->input('timeframe', '30_days');
        $from = match ($timeframe) { '6_months' => now()->subMonths(6), 'this_year' => now()->startOfYear(), default => now()->subDays(30) };
        $risks = RiskAssessment::query()->where('company_id', $clientId)->where('created_at', '>=', $from)->get();
        $plans = RiskMitigationPlan::query()->where('company_id', $clientId)->where('created_at', '>=', $from)->get();
        $incidents = RiskIncident::query()->where('company_id', $clientId)->where('created_at', '>=', $from)->get();
        $totalRisks = $risks->count();
        $controlledHazards = $risks->where('status', 'Mitigated')->count();
        $resolved = $incidents->where('status', 'Resolved')->whereNotNull('resolved_at');
        $averageMinutes = $resolved->isEmpty() ? null : (int) round($resolved->avg(fn (RiskIncident $incident) => $incident->created_at->diffInMinutes($incident->resolved_at)));
        $vectors = $risks->groupBy(fn (RiskAssessment $risk) => $risk->category ?: 'Uncategorised')->map(fn ($items, $name) => ['name' => $name, 'percentage' => $totalRisks ? (int) round($items->count() / $totalRisks * 100) : 0])->sortByDesc('percentage')->values();

        return view('analytics', [
            'timeframe' => $timeframe,
            'totalRisks' => $totalRisks,
            'mitigationIndex' => $totalRisks ? (int) round($controlledHazards / $totalRisks * 100) : 0,
            'avgResolutionFormatted' => $averageMinutes === null ? 'N/A' : $averageMinutes.' min',
            'controlledHazards' => $controlledHazards,
            'totalHazards' => $totalRisks,
            'unassignedRisks' => $risks->filter(fn (RiskAssessment $risk) => empty($risk->owner))->count(),
            'statusDistribution' => ['unmitigated' => $risks->where('status', 'Unmitigated')->count(), 'in_progress' => $risks->where('status', 'In Progress')->count(), 'secured' => $controlledHazards],
            'vulnerabilityVectors' => $vectors,
            'hasData' => $totalRisks > 0 || $plans->isNotEmpty() || $incidents->isNotEmpty(),
        ]);
    }

    public function export(Request $request)
    {
        $clientId = (int) $request->user()->company_id;
        $risks = RiskAssessment::query()->where('company_id', $clientId)->orderBy('id')->get();

        return response()->streamDownload(function () use ($risks): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Risk ID', 'Title', 'Category', 'Status', 'Owner', 'Review date']);
            foreach ($risks as $risk) fputcsv($file, [$risk->id, $risk->title, $risk->category, $risk->status, $risk->owner, optional($risk->review_date)->format('Y-m-d')]);
            fclose($file);
        }, 'nexora-risk-report.csv', ['Content-Type' => 'text/csv']);
    }
}
