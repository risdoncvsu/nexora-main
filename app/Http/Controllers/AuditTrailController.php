<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $this->validateFilters($request);
        $portal = $request->routeIs('admin.*') ? 'admin' : 'client';
        if (! Schema::hasTable('erp_audit_logs')) {
            if ($portal === 'admin') {
                return $this->rootOverview();
            }

            $modules = collect();
            $logs = new LengthAwarePaginator([], 0, 20, max(1, (int) $request->input('page', 1)), [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('audittrail.index', compact('portal', 'logs', 'modules'));
        }

        // Root administrators start with a compact troubleshooting overview,
        // not a combined stream of every client's routine actions. Selecting a
        // client tile deliberately opens that client's full audit history.
        if ($portal === 'admin' && ! $request->filled('client_id')) {
            return $this->rootOverview();
        }

        $modules = $this->scopedLogsQuery($request, $portal)
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');
        $logs = $this->logsQuery($request, $portal)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $timezones = $this->clientTimezones($logs->getCollection());
        $logs->getCollection()->transform(
            fn (object $log): object => $this->presentLog($log, $timezones[(int) $log->client_id] ?? config('app.timezone', 'UTC'))
        );

        $selectedClient = $portal === 'admin'
            ? Company::query()->find((int) $request->input('client_id'))
            : null;

        return view('audittrail.index', compact('portal', 'logs', 'modules', 'selectedClient'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);
        $portal = $request->routeIs('admin.*') ? 'admin' : 'client';
        $fileName = 'nexora-audit-trail-'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($request, $portal): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Log ID', 'Client ID', 'Actor', 'Department', 'Category', 'Action', 'Module', 'HTTP Status', 'Date and Time', 'Details']);

            if (! Schema::hasTable('erp_audit_logs')) {
                fclose($handle);

                return;
            }

            $this->logsQuery($request, $portal)
                ->orderBy('id')
                ->chunkById(200, function ($logs) use ($handle): void {
                    $timezones = $this->clientTimezones($logs);
                    foreach ($logs as $log) {
                        $log = $this->presentLog($log, $timezones[(int) $log->client_id] ?? config('app.timezone', 'UTC'));
                        fputcsv($handle, [
                            'LOG-'.str_pad((string) $log->id, 6, '0', STR_PAD_LEFT),
                            $log->client_id,
                            $log->actor,
                            $log->department,
                            $log->category,
                            $log->event,
                            $log->module,
                            $log->http_status,
                            $log->created_at?->format('Y-m-d H:i:s T'),
                            json_encode($log->details),
                        ]);
                    }
                }, 'id');

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function scopedLogsQuery(Request $request, string $portal)
    {
        $query = DB::table('erp_audit_logs');

        if ($portal === 'client') {
            $query->where('client_id', (int) $request->user()->company_id);
        } elseif ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->input('client_id'));
        }

        return $query;
    }

    private function logsQuery(Request $request, string $portal)
    {
        $query = $this->scopedLogsQuery($request, $portal);

        if ($search = trim((string) $request->input('search'))) {
            $like = '%'.strtolower($search).'%';
            $query->where(function ($query) use ($like): void {
                $query->whereRaw('LOWER(event) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(module) LIKE ?', [$like])
                    ->orWhereRaw('CAST(id AS TEXT) LIKE ?', [$like]);
            });
        }

        if ($module = trim((string) $request->input('module'))) {
            $query->where('module', $module);
        }

        match ($request->input('category')) {
            'errors' => $query->where(function ($query): void {
                $query->where('event', 'request.error')
                    ->orWhere('event', 'like', '%failed%');
            }),
            'user_actions' => $query->where('event', 'like', 'action.%'),
            'erp_events' => $query->where(function ($query): void {
                $query->where('event', 'not like', 'action.%')
                    ->where('event', '!=', 'request.error')
                    ->where('event', 'not like', '%failed%');
            }),
            default => null,
        };

        if ($portal === 'client' && ($request->filled('from') || $request->filled('to'))) {
            $timezone = $this->clientTimezone((int) $request->user()->company_id);
            if ($from = $request->input('from')) {
                $query->where('created_at', '>=', Carbon::parse($from, $timezone)->startOfDay()->utc());
            }
            if ($to = $request->input('to')) {
                $query->where('created_at', '<=', Carbon::parse($to, $timezone)->endOfDay()->utc());
            }
        } else {
            if ($from = $request->input('from')) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to = $request->input('to')) {
                $query->whereDate('created_at', '<=', $to);
            }
        }

        return $query;
    }

    private function presentLog(object $log, string $timezone): object
    {
        $details = is_string($log->details ?? null)
            ? json_decode($log->details, true) ?: []
            : (array) ($log->details ?? []);

        $log->details = $details;
        $log->actor = (string) ($details['actor'] ?? 'System');
        $log->department = ucwords(str_replace(['_', '-'], ' ', (string) $log->module));
        $log->http_status = data_get($details, 'response.status') ?? data_get($details, 'error.status');
        $log->category = $log->event === 'request.error' || str_contains($log->event, 'failed')
            ? 'Error'
            : (str_starts_with($log->event, 'action.') ? 'User action' : 'ERP event');
        $log->timezone = in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
        $log->created_at = isset($log->created_at) ? Carbon::parse($log->created_at, 'UTC')->setTimezone($log->timezone) : null;

        return $log;
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:user_actions,erp_events,errors'],
            'module' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'client_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);
    }

    private function rootOverview()
    {
        $summaries = collect();
        $errors = collect();

        if (Schema::hasTable('erp_audit_logs')) {
            $summaries = DB::table('erp_audit_logs')
                ->selectRaw("client_id, COUNT(*) as activity_count, MAX(created_at) as last_activity, SUM(CASE WHEN event = 'request.error' OR event LIKE '%failed%' THEN 1 ELSE 0 END) as error_count")
                ->where('client_id', '>', 0)
                ->groupBy('client_id')
                ->get()
                ->keyBy('client_id');

            $errors = DB::table('erp_audit_logs')
                ->where('client_id', '>', 0)
                ->where(function ($query): void {
                    $query->where('event', 'request.error')->orWhere('event', 'like', '%failed%');
                })
                ->latest('created_at')
                ->limit(25)
                ->get();
        }

        $companies = Company::query()->orderBy('company_name')->get();
        $companyNames = $companies->pluck('company_name', 'id');
        $timezones = $this->clientTimezones($errors);
        $errors->transform(function (object $log) use ($companyNames, $timezones): object {
            $log = $this->presentLog($log, $timezones[(int) $log->client_id] ?? config('app.timezone', 'UTC'));
            $log->company_name = $companyNames[(int) $log->client_id] ?? 'Unknown client';

            return $log;
        });

        $companyCards = $companies->map(function (Company $company) use ($summaries): object {
            $summary = $summaries->get($company->id);

            return (object) [
                'id' => $company->id,
                'name' => $company->company_name,
                'status' => $company->status,
                'activity_count' => (int) ($summary->activity_count ?? 0),
                'error_count' => (int) ($summary->error_count ?? 0),
                'last_activity' => isset($summary->last_activity)
                    ? Carbon::parse($summary->last_activity, 'UTC')->setTimezone($this->clientTimezone((int) $company->id))
                    : null,
            ];
        });

        return view('audittrail.admin-overview', compact('companyCards', 'errors'));
    }

    /** @return array<int, string> */
    private function clientTimezones(iterable $logs): array
    {
        $clientIds = collect($logs)->pluck('client_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($clientIds->isEmpty()) {
            return [];
        }

        return Company::query()
            ->whereIn('id', $clientIds)
            ->pluck('timezone', 'id')
            ->map(fn ($timezone) => is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC'))
            ->all();
    }

    private function clientTimezone(int $clientId): string
    {
        $timezone = Company::query()->whereKey($clientId)->value('timezone');

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone', 'UTC');
    }
}
