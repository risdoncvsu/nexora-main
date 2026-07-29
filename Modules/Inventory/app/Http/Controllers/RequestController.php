<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RequestController extends Controller
{
    public function index()
    {
        try {
            // Keep one database row per requested item. Grouping by batch hid
            // individual requisitions and made request numbers appear to skip.
            $requests = DB::connection('inventory')
                ->table('requisitions')
                ->where('client_id', (int) session('employee_client_id'))
                ->where('requested_by', session('employee_name'))
                ->orderByDesc('created_at')
                ->paginate(10);

            $pending = DB::connection('inventory')->table('requisitions')
                ->where('client_id', (int) session('employee_client_id'))
                ->where('requested_by', session('employee_name'))
                ->where('status', 'Pending')
                ->count();
            $total = $requests->total();
        } catch (\Exception $e) {
            $requests = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $total = 0;
            $pending = 0;
        }

        return view('inventory::requests', [
            'requests' => $requests,
            'pendingCount' => $pending,
            'totalCount' => $total,
            'activePage' => 'requests',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:restock,replacement',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:restock,replacement',
            'items.*.part_name' => 'required|string|max:150',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:1000',
            'items.*.item_id' => 'nullable|integer',
            'items.*.defect_id' => 'nullable|integer',
        ]);

        $rows = [];
        $names = [];
        $year = now()->format('Y');
        $clientId = (int) session('employee_client_id');
        $reqSeq = DB::connection('inventory')->table('requisitions')
            ->where('client_id', $clientId)
            ->whereYear('created_at', $year)
            ->count() + 1;
        // req_id is globally unique in the Inventory database. Including the
        // client prevents one company’s first request from colliding with
        // another company’s first request in the same year.
        $batchId = 'BATCH-' . $clientId . '-' . $year . '-' . str_pad((string) $reqSeq, 4, '0', STR_PAD_LEFT);

        foreach ($validated['items'] as $i => $item) {
            $priority = 'Low';

            if ($item['type'] === 'restock' && !empty($item['item_id'])) {
                $invItem = \Modules\Inventory\Models\Item::find($item['item_id']);
                if (!$invItem) {
                    return back()->withInput()->withErrors(['items.' . $i . '.item_id' => 'Item not found.']);
                }
                $item['part_name'] = $invItem->name;
                $priority = $invItem->status === 'Out of Stock' ? 'High' : 'Low';
            }

            if ($item['type'] === 'replacement' && !empty($item['defect_id'])) {
                $defect = \Modules\Inventory\Models\Defect::find($item['defect_id']);
                if (!$defect || $defect->status !== 'Open') {
                    return back()->withInput()->withErrors(['items.' . $i . '.defect_id' => 'Selected defect is no longer open.']);
                }
                $item['part_name'] = $defect->part_name;
            }

            $reqId = 'REQ-' . $clientId . '-' . $year . '-' . str_pad((string) ($reqSeq + $i), 4, '0', STR_PAD_LEFT);

            $structuredNotes = $item['notes'] ?? '';
            $structuredNotes = '[batch:' . $batchId . '] ' . $structuredNotes;
            if ($item['type'] === 'restock' && !empty($item['item_id'])) {
                $structuredNotes = '[item_id:' . $item['item_id'] . '] ' . $structuredNotes;
            } elseif (!empty($item['defect_id'])) {
                $structuredNotes = '[defect_id:' . $item['defect_id'] . '] ' . $structuredNotes;
            }

            $rows[] = [
                'client_id' => $clientId,
                'req_id' => $reqId,
                'part_name' => $item['part_name'],
                'quantity' => $item['quantity'],
                'department' => 'Inventory',
                'requested_by' => session('employee_name', 'Unknown'),
                'type' => $item['type'],
                'notes' => $structuredNotes,
                'priority' => $priority,
                'date_requested' => now()->toDateString(),
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $names[] = $item['part_name'];
        }

        try {
            $connection = DB::connection('inventory');
            $schema = Schema::connection('inventory');
            if (! $schema->hasTable('requisitions')) {
                throw new \RuntimeException('The Inventory requisitions table is not installed.');
            }

            // Older Inventory databases may not yet have optional workflow
            // columns (type/priority). Insert only available fields so a
            // request still reaches Procurement instead of failing silently.
            $availableColumns = array_flip($schema->getColumnListing('requisitions'));
            $connection->table('requisitions')->insert(array_map(
                fn (array $row): array => array_intersect_key($row, $availableColumns),
                $rows
            ));
        } catch (\Exception $e) {
            Log::error('Inventory requisition submission failed.', [
                'client_id' => $clientId,
                'exception' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['submit' => 'Unable to save the Inventory request. Please try again or contact your system administrator.']);
        }

        $count = count($rows);
        return redirect()->route('inventory.requests')
            ->with('success', "{$count} item(s) submitted to Procurement: " . implode(', ', $names));
    }

    public function restockItems()
    {
        $items = \Modules\Inventory\Models\Item::with('stockLevels')
            ->where(function ($q) {
                $q->lowStock()->orWhere(function ($sq) {
                    $sq->whereIn('items.id', function ($sub) {
                        $sub->select('item_id')
                            ->from('stock_levels')
                            ->groupBy('item_id')
                            ->havingRaw('COALESCE(SUM(stock - reserved_quantity), 0) <= 0');
                    });
                });
            })
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'total_available' => $item->total_available,
                'status' => $item->status,
            ])
            ->sortBy('status')
            ->values();

        return response()->json($items);
    }

    public function replacementItems()
    {
        // Do not offer a defect twice while its replacement request is still
        // moving through Procurement. The request rows are raw cross-module
        // data, so scope the lookup explicitly to the signed-in client.
        $activeDefectIds = DB::connection('inventory')
            ->table('requisitions')
            ->where('client_id', (int) session('employee_client_id'))
            ->where('type', 'replacement')
            ->whereIn('status', ['Pending', 'Approved', 'Processing', 'In Transit'])
            ->pluck('notes')
            ->map(function ($notes) {
                return preg_match('/\[defect_id:(\d+)\]/', (string) $notes, $matches)
                    ? (int) $matches[1]
                    : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $items = \Modules\Inventory\Models\Defect::where('status', 'Open')
            ->when($activeDefectIds !== [], fn ($query) => $query->whereNotIn('id', $activeDefectIds))
            ->get()->map(fn ($d) => [
            'id' => $d->id,
            'part_name' => $d->part_name,
            'quantity' => $d->quantity,
            'source' => ucfirst((string) $d->source),
        ]);

        return response()->json($items);
    }
}
