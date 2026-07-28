<?php

namespace App\Http\Controllers;

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
        $portal = $request->routeIs('admin.*') ? 'admin' : 'client';
        if (! Schema::hasTable('erp_audit_logs')) {
            $logs = new LengthAwarePaginator([], 0, 20, max(1, (int) $request->input('page', 1)), [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('audittrail.index', compact('portal', 'logs'));
        }

        $logs = $this->logsQuery($request, $portal)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $logs->getCollection()->transform(fn (object $log): object => $this->presentLog($log));

        return view('audittrail.index', compact('portal', 'logs'));
    }

    public function export(Request $request): StreamedResponse
    {
        $portal = $request->routeIs('admin.*') ? 'admin' : 'client';
        $fileName = 'nexora-audit-trail-'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($request, $portal): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Log ID', 'Client ID', 'Actor', 'Department', 'Action', 'Module', 'Date and Time', 'Details']);

            if (! Schema::hasTable('erp_audit_logs')) {
                fclose($handle);

                return;
            }

            $this->logsQuery($request, $portal)
                ->orderBy('id')
                ->chunkById(200, function ($logs) use ($handle): void {
                    foreach ($logs as $log) {
                        $log = $this->presentLog($log);
                        fputcsv($handle, [
                            'LOG-'.str_pad((string) $log->id, 6, '0', STR_PAD_LEFT),
                            $log->client_id,
                            $log->actor,
                            $log->department,
                            $log->event,
                            $log->module,
                            $log->created_at?->format('Y-m-d H:i:s'),
                            json_encode($log->details),
                        ]);
                    }
                }, 'id');

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function logsQuery(Request $request, string $portal)
    {
        $query = DB::table('erp_audit_logs');

        if ($portal === 'client') {
            $query->where('client_id', (int) $request->user()->company_id);
        }

        if ($search = trim((string) $request->input('search'))) {
            $like = '%'.strtolower($search).'%';
            $query->where(function ($query) use ($like): void {
                $query->whereRaw('LOWER(event) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(module) LIKE ?', [$like])
                    ->orWhereRaw('CAST(id AS TEXT) LIKE ?', [$like]);
            });
        }

        return $query;
    }

    private function presentLog(object $log): object
    {
        $details = is_string($log->details ?? null)
            ? json_decode($log->details, true) ?: []
            : (array) ($log->details ?? []);

        $log->details = $details;
        $log->actor = (string) ($details['actor'] ?? 'System');
        $log->department = ucwords(str_replace(['_', '-'], ' ', (string) $log->module));
        $log->created_at = isset($log->created_at) ? Carbon::parse($log->created_at) : null;

        return $log;
    }
}
