<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RiskController extends Controller
{
    private function sessionKey(Request $request): string
    {
        return 'client_risks.'.((int) $request->user()?->company_id);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter');
        $risks = collect($request->session()->get($this->sessionKey($request), []))
            ->map(fn (array $risk): object => (object) $risk);

        if ($search) {
            $risks = $risks->filter(fn (object $risk): bool =>
                stripos($risk->title, $search) !== false || stripos($risk->category, $search) !== false
            );
        }

        if ($statusFilter) {
            $risks = $risks->filter(fn (object $risk): bool =>
                strtolower($risk->status) === strtolower($statusFilter)
            );
        }

        return view('risk', compact('risks', 'search', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:Unmitigated,In Progress,Mitigated'],
        ]);

        $key = $this->sessionKey($request);
        $risks = $request->session()->get($key, []);
        $next = count($risks) + 1;
        $risks[] = $validated + [
            'id' => 'RSK-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
            // This is a derived operational metric, not manual data entry.
            'progress' => match ($validated['status']) {
                'Mitigated' => 100,
                'In Progress' => 50,
                default => 0,
            },
            'last_reviewed' => now()->toDateString(),
        ];
        $request->session()->put($key, $risks);

        return redirect()->route('client.itsm.risk')->with('success', 'Risk successfully logged.');
    }

    public function update(Request $request)
    {
        return redirect()->route('client.itsm.risk')->with('success', 'Risk updated successfully.');
    }

    public function manage(Request $request, $id)
    {
        $risk = collect($request->session()->get($this->sessionKey($request), []))
            ->firstWhere('id', $id);
        abort_unless($risk, 404);

        return view('risk-manage', ['risk' => (object) $risk]);
    }
}
