<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Support\EmployeePermissionGate;
use Modules\Manufacturing\Models\WorkOrder;
use Modules\Manufacturing\Models\Worker;
use Modules\Manufacturing\Models\QcSession;
use Modules\Manufacturing\Models\ReworkOrder;
use Modules\Manufacturing\Models\Requisition;
use Modules\Manufacturing\Services\ManufacturingDataService;
use Modules\Manufacturing\Services\BenchmarkTargetService;
use Modules\Manufacturing\Services\DueDateService;
use Modules\Manufacturing\Services\InventoryBridgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ManufacturingController extends Controller
{
    // ── Page load ────────────────────────────────────────────────────────────
    public function index()
    {
        $data = (new ManufacturingDataService())->loadAll();

        return view('manufacturing::Manufacturing', [
            'workOrders'       => $data['workOrders'],
            'workers'          => $data['workers'],
            'benchmarkTargets' => $data['benchmarkTargets'],
            'qcSessions'       => $data['qcSessions'],
            'reworkOrders'     => $data['reworkOrders'],
            'requisitions'     => $data['requisitions'],
            'statusStyles'     => config('manufacturing.statusStyles'),
            'partStyles'       => config('manufacturing.partStyles'),
            'rangeStyles'      => config('manufacturing.rangeStyles'),
            'tempData'         => array_merge($data, [
                'statusStyles' => config('manufacturing.statusStyles'),
                'partStyles'   => config('manufacturing.partStyles'),
                'rangeStyles'  => config('manufacturing.rangeStyles'),
            ]),
        ]);
    }

    // ── Work orders ──────────────────────────────────────────────────────────
    public function updateOrder(Request $request): JsonResponse
    {
        $partChanges = (array) $request->input('partChanges', []);
        $sendToQC    = (bool)  $request->input('sendToQC', false);
        $cancelOrder = (bool)  $request->input('cancelOrder', false);

        $order = $request->filled('workOrderId')
            ? WorkOrder::with('parts')->find($request->input('workOrderId'))
            : WorkOrder::with('parts')->orderBy('due_date', 'asc')->get()->values()->get((int) $request->input('orderIndex'));
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $this->assertCanOperateWorkOrder($order);

        if ($sendToQC && $this->isPackingWorkOrder($order)) {
            return response()->json([
                'success' => false,
                'message' => 'Packing-material work orders are not computer builds and cannot be sent to QC benchmarking.',
            ], 422);
        }

        DB::connection('manufacturing')->transaction(function () use ($order, $partChanges, $sendToQC, $cancelOrder) {
            $partsByPosition = $order->parts->values();
            $bridge = new InventoryBridgeService();

            foreach ($partChanges as $position => $newStatus) {
                $part = $partsByPosition->get((int) $position);
                if (!$part) continue;
                if (in_array($part->status, ['Sourcing', 'Missing'], true) && $newStatus === 'Ready') {
                    $part->update(['status' => 'Ready']);
                    $bridge->consumeReservationForPart($order->id, $part->toArray(), $order->client_id);
                }
            }

            $order->refresh()->load('parts');

            if ($order->status === 'Pending'
                && (! empty($order->assigned) || $order->parts->contains(fn ($part) => $part->status === 'Ready'))) {
                $order->status = 'Building';
            }

            $allReady = $order->parts->isNotEmpty() && $order->parts->every(fn ($part) => $part->status === 'Ready');
            if ($allReady && $order->status === 'Building') {
                $order->status = 'Finished';
            }

            if ($sendToQC && in_array($order->status, ['Finished', 'Building'])) {
                $order->status = 'QC Check';
            }

            if  ($cancelOrder) {
                $order->status = 'Cancelled';
            }

            $order->save();
        });

        return response()->json(['success' => true]);
    }

    // ── QC benchmark ─────────────────────────────────────────────────────────
    public function updateQC(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $results = $request->input('results', []);

        $order = WorkOrder::find($woId);
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Work order not found.'], 404);
        }

        $this->assertCanOperateWorkOrder($order);

        if ($this->isPackingWorkOrder($order)) {
            return response()->json([
                'success' => false,
                'message' => 'Packing-material work orders are excluded from computer QC benchmarking.',
            ], 422);
        }

        if ($order->status !== 'QC Check') {
            return response()->json(['success' => false, 'message' => 'Only work orders in QC Check can be released from quality control.'], 422);
        }

        $range = $order->range ?? null;

        $targetService   = new BenchmarkTargetService();
        $allowedVerdicts = ['Pass', 'Warn', 'Fail', ''];

        $cleanResults = array_map(function ($r) use ($targetService, $range, $allowedVerdicts) {
            $checkId = (string) ($r['checkId'] ?? '');
            $value   = isset($r['value']) && $r['value'] !== null ? (float) $r['value'] : null;
            $verdict = $r['verdict'] ?? '';

            if ($verdict === '' && $value !== null) {
                $verdict = $targetService->verdictFor($checkId, $range, $value);
            }

            return [
                'checkId' => $checkId,
                'value'   => $value,
                'verdict' => in_array($verdict, $allowedVerdicts) ? $verdict : '',
                'note'    => (string) ($r['note'] ?? ''),
            ];
        }, $results);

        $flagged = array_values(array_filter($cleanResults, fn ($r) => in_array($r['verdict'], ['Warn', 'Fail'], true)));

        DB::connection('manufacturing')->transaction(function () use ($woId, $cleanResults, $range, $targetService, $order, $flagged) {
            $session = QcSession::where('wo_id', $woId)->first();
            if (!$session) {
                $session = QcSession::create(['wo_id' => $woId, 'build_type' => $range ?? 'mid-range', 'tech' => '']);
            }

            $session->results()->delete();
            foreach ($cleanResults as $r) {
                $session->results()->create([
                    'check_id' => $r['checkId'],
                    'value'    => $r['value'],
                    'verdict'  => $r['verdict'],
                    'note'     => $r['note'],
                ]);
            }

            if (count($flagged) > 0 && !ReworkOrder::where('wo_id', $woId)->exists()) {
                $targets = $targetService->targetsFor($range);

                $rwCount  = ReworkOrder::count() + 1;
                $reworkId = 'RW-' . session('employee_client_id') . '-' . str_pad((string) $rwCount, 4, '0', STR_PAD_LEFT);

                $rework = ReworkOrder::create([
                    'id'                       => $reworkId,
                    'wo_id'                    => $woId,
                    'build_name'               => $order->name ?? $woId,
                    'assigned_tech'            => $order->assigned ?? '',
                    'raised_by'                => $order->assigned ?? '',
                    'raised_date'              => now()->format('M d, Y'),
                    'status'                   => 'In Rework',
                    'priority'                 => 'Medium',
                    'notes'                    => 'Auto-created from QC benchmark flags.',
                    'escalated_to_inventory' => false,
                ]);

                foreach ($flagged as $r) {
                    $def = $targets[$r['checkId']] ?? null;
                    $rework->failedChecks()->create([
                        'check_id'   => $r['checkId'],
                        'check_name' => $def['name'] ?? $r['checkId'],
                        'verdict'    => $r['verdict'],
                        'result'     => $r['value'] !== null
                            ? number_format($r['value']) . ' ' . ($def['unit'] ?? '')
                            : '—',
                        'target'     => ($def['operator'] ?? '') . ' ' . number_format($def['target'] ?? 0) . ' ' . ($def['unit'] ?? ''),
                        'reason'     => $r['note'] ?: 'Flagged during QC benchmark',
                    ]);
                }

                // Build replacement requirements from the actual components
                // behind failed QC checks. A check uses codes such as CPU_* or
                // Storage_*, while BOM categories may say "Processor" or
                // "NVMe SSD"; matching is therefore normalised below rather
                // than relying on an exact label match.
                $this->ensureReplacementParts($rework, $order, $flagged);
            }

            if ($flagged) {
                $order->update(['status' => 'Rework']);
            }
        });

        if ($flagged) {
            return response()->json([
                'success' => true,
                'status' => 'Rework',
                'message' => 'QC flagged issues. A rework order has been created.',
            ]);
        }

        if (! $cleanResults || collect($cleanResults)->contains(fn (array $result) => $result['verdict'] !== 'Pass')) {
            return response()->json([
                'success' => true,
                'status' => 'QC Check',
                'message' => 'QC results were saved. Every check must pass before this order can be released.',
            ]);
        }

        try {
            $fulfillmentOrderId = $this->releaseToFulfillment($order);
            $order->update(['status' => 'Completed']);

            return response()->json([
                'success' => true,
                'status' => 'Completed',
                'fulfillmentOrderId' => $fulfillmentOrderId,
                'message' => $fulfillmentOrderId
                    ? 'QC passed. The order is now ready for packing in Order Fulfillment.'
                    : 'QC passed. The manufacturing work order is complete.',
            ]);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    // ── Rework ───────────────────────────────────────────────────────────────
    public function updateRework(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $rw = ReworkOrder::byPriority()->get()->values()->get($reworkIndex);

        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $workOrder = WorkOrder::find($rw->wo_id);
        if ($workOrder) {
            $this->assertCanOperateWorkOrder($workOrder);
        }

        if ($request->input('status') === 'Ready for QC') {
            $rw->loadMissing('requiredParts');
            $allReplaced = $rw->requiredParts->isEmpty()
                || $rw->requiredParts->every(fn ($part) => $part->status === 'Ready');

            if (! $allReplaced) {
                return response()->json([
                    'success' => false,
                    'message' => 'All replacement parts must be grabbed from stock before retesting.',
                ], 422);
            }
        }

        if ($request->has('status'))     $rw->status   = $request->input('status');
        if ($request->has('priority'))   $rw->priority = $request->input('priority');
        if ($request->has('notes'))      $rw->notes    = $request->input('notes');
        if ($request->boolean('escalate')) {
            $rw->loadMissing('requiredParts');

            // Older rework rows may have been created before replacement
            // requirements were persisted. Recover them from their failed QC
            // checks so they can still be sent to Inventory.
            if ($rw->requiredParts->isEmpty() && $workOrder) {
                $rw->loadMissing('failedChecks');
                $this->ensureReplacementParts($rw, $workOrder, $rw->failedChecks);
                $rw->load('requiredParts');
            }

            if ($rw->requiredParts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This rework order has no failed physical components to send to Inventory.',
                ], 422);
            }

            if (! $this->sendReworkDefectsToInventory($rw, $workOrder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory could not receive the defect report. Please verify the Inventory database connection and try again.',
                ], 503);
            }

            $rw->escalated_to_inventory = true;
            if ($rw->status === 'In Rework') {
                $rw->status = 'Waiting for Part';
            }
        }
        $rw->save();

        if ($rw->status === 'Ready for QC') {
            WorkOrder::where('id', $rw->wo_id)->update(['status' => 'QC Check']);
        }

        return response()->json(['success' => true]);
    }

    public function grabReplacementPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $partIndex = (int) $request->input('partIndex');
        $rework = ReworkOrder::with('requiredParts')->byPriority()->get()->values()->get($reworkIndex);

        if (! $rework) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $workOrder = WorkOrder::find($rework->wo_id);
        if ($workOrder) {
            $this->assertCanOperateWorkOrder($workOrder);
        }

        $part = $rework->requiredParts->values()->get($partIndex);
        if (! $part) {
            return response()->json(['success' => false, 'message' => 'Replacement part not found.'], 404);
        }

        if ($part->status === 'Ready') {
            return response()->json(['success' => true, 'message' => 'This replacement part has already been grabbed.']);
        }

        $clientId = (int) session('employee_client_id') ?: null;
        $grabbed = (new InventoryBridgeService())->grabReplacementFromStock($part->name, 1, $clientId);

        if (! $grabbed) {
            return response()->json(['success' => false, 'message' => 'No unreserved inventory is available for this replacement part.'], 422);
        }

        $part->update(['status' => 'Ready']);

        return response()->json(['success' => true]);
    }

    public function addReworkPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $part        = $request->input('part', []);

        $rw = ReworkOrder::byPriority()->get()->values()->get($reworkIndex);
        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $order = WorkOrder::with('parts')->find($rw->wo_id);
        if ($order) {
            $this->assertCanOperateWorkOrder($order);
        }

        // A manually-added replacement must correspond to a QC issue. This
        // preserves the updated module's workflow while preventing arbitrary
        // inventory items from being pulled into a rework order.
        $partName = trim((string) ($part['name'] ?? ''));
        $session = $order ? QcSession::where('wo_id', $order->id)->first() : null;
        $flaggedCategories = $session
            ? $session->results()->whereIn('verdict', ['Warn', 'Fail'])->get()
                ->map(fn ($result) => explode('_', (string) $result->check_id, 2)[0])
                ->filter()
                ->unique()
            : collect();

        $allowedNames = $flaggedCategories
            ->map(fn (string $category) => $order ? $this->workOrderPartForBenchmarkCategory($order, $category)?->name : null)
            ->filter()
            ->unique();

        if ($partName === '' || ! $allowedNames->contains($partName)) {
            return response()->json([
                'success' => false,
                'message' => 'Only components flagged by the QC benchmark can be added as replacement parts.',
            ], 422);
        }

        $rw->requiredParts()->create([
            'name'   => $partName,
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateReworkPart(Request $request): JsonResponse
    {
        $reworkIndex = (int) $request->input('reworkIndex');
        $partIndex   = (int) $request->input('partIndex');
        $part        = $request->input('part', []);

        $rw = ReworkOrder::with('requiredParts')->byPriority()->get()->values()->get($reworkIndex);
        if (!$rw) {
            return response()->json(['success' => false, 'message' => 'Rework order not found.'], 404);
        }

        $rp = $rw->requiredParts->values()->get($partIndex);
        if (!$rp) {
            return response()->json(['success' => false, 'message' => 'Part not found.'], 404);
        }

        $rp->update([
            'name'   => (string) ($part['name']   ?? ''),
            'status' => (string) ($part['status'] ?? 'Sourcing'),
            'eta'    => $part['eta'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Analytics ────────────────────────────────────────────────────────────
    public function addQcNote(Request $request): JsonResponse
    {
        $woId = $request->input('woId');
        $note = $request->input('note', '');

        $session = QcSession::where('wo_id', $woId)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $session->results()->create([
            'check_id' => null,
            'value'    => null,
            'verdict'  => '',
            'note'     => $note,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Workers ──────────────────────────────────────────────────────────────
    public function addWorker(Request $request): JsonResponse
    {
        $this->assertCanManageManufacturing();

        Worker::create([
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ]);
        return response()->json(['success' => true]);
    }

    public function updateWorker(Request $request): JsonResponse
    {
        $this->assertCanManageManufacturing();

        $worker = Worker::find($request->input('id'));
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        $worker->update([
            'name'  => $request->input('name'),
            'role'  => $request->input('role'),
            'notes' => $request->input('notes', ''),
        ]);
        return response()->json(['success' => true]);
    }

    public function deleteWorker(Request $request): JsonResponse
    {
        $this->assertCanManageManufacturing();

        $worker = Worker::find($request->input('id'));
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Worker not found.'], 404);
        }
        $worker->delete();
        return response()->json(['success' => true]);
    }

    public function assignWorker(Request $request): JsonResponse
    {
        $this->assertCanManageManufacturing();

        $validated = $request->validate([
            'orderId' => ['required', 'string'],
            'workerId' => ['required', 'integer'],
        ]);

        $order = WorkOrder::find($validated['orderId']);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Work order not found.'], 404);
        }

        $worker = DB::connection('hr')->table('employees')
            ->where('id', $validated['workerId'])
            ->where('client_id', $order->client_id)
            ->where('approval_status', 'Active')
            ->where(function ($query): void {
                $query->whereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%production%'])
                    ->orWhereRaw("LOWER(COALESCE(position, '')) LIKE ?", ['%production%'])
                    ->orWhereRaw("LOWER(COALESCE(position, '')) LIKE ?", ['%manufacturing%']);
            })
            ->where(function ($query): void {
                $query->whereRaw("LOWER(COALESCE(position, '')) NOT LIKE ?", ['%manager%'])
                    ->whereRaw("LOWER(COALESCE(position, '')) NOT LIKE ?", ['%supervisor%'])
                    ->whereRaw("LOWER(COALESCE(position, '')) NOT LIKE ?", ['%quality%'])
                    ->whereRaw("LOWER(COALESCE(department, '')) NOT LIKE ?", ['%quality%']);
            })
            ->first();

        if (! $worker) {
            return response()->json(['success' => false, 'message' => 'Select an active production staff member, not a manager, supervisor, or QC role.'], 422);
        }

        $name = trim(implode(' ', array_filter([$worker->first_name, $worker->middle_name, $worker->last_name, $worker->suffix])));
        $changes = [
            'assigned_employee_id' => $worker->id,
            'assigned' => $name,
        ];
        if ($order->status === 'Pending') {
            $changes['status'] = 'Building';
        }
        $order->update($changes);

        return response()->json(['success' => true, 'status' => $changes['status'] ?? $order->status]);
    }

    // ── Requisitions / inventory ─────────────────────────────────────────────
    public function sendToInventory(Request $request): JsonResponse
    {
        $woId    = $request->input('woId');
        $order   = WorkOrder::find($woId);

        $priority = 'Low';
        if ($order && $order->due_date) {
            $daysLeft = now()->startOfDay()->diffInDays($order->due_date->copy()->startOfDay(), false);
            if ($daysLeft <= 0)      $priority = 'Critical';
            elseif ($daysLeft <= 3)  $priority = 'High';
            elseif ($daysLeft <= 7)  $priority = 'Medium';
            else                     $priority = 'Low';
        }

        $reqCount = Requisition::count() + 1;
        $reqId    = 'REQ-' . session('employee_client_id') . '-' . str_pad((string) $reqCount, 4, '0', STR_PAD_LEFT);

        Requisition::create([
            'req_id'         => $reqId,
            'part_name'      => $request->input('partName'),
            'quantity'       => (int) $request->input('quantity', 1),
            'department'     => 'Manufacturing',
            'destination'    => 'Inventory',
            'requested_by'   => $request->input('requestedBy'),
            'priority'       => $priority,
            'wo_id'          => $woId,
            'notes'          => $request->input('notes'),
            'date_requested' => now()->toDateString(),
            'status'         => 'Pending',
        ]);

        return response()->json(['success' => true, 'reqId' => $reqId, 'priority' => $priority]);
    }

    // ── Work orders (cont.) ──────────────────────────────────────────────────
    public function cancelOrder(Request $request): JsonResponse
    {
        $order = $request->filled('workOrderId')
            ? WorkOrder::find($request->input('workOrderId'))
            : WorkOrder::orderBy('due_date', 'asc')->get()->values()->get((int) $request->input('orderIndex'));

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $this->assertCanOperateWorkOrder($order);

        if ($order->status === 'Cancelled') {
            return response()->json(['success' => false, 'message' => 'Order is already cancelled.'], 422);
        }

        $order->update(['status' => 'Cancelled']);

        return response()->json(['success' => true]);
    }

    // ── E-commerce intake ────────────────────────────────────────────────────
    public function receiveOrderFromEcommerce(Request $request): JsonResponse
    {
        if (strtolower((string) $request->input('workOrderType')) === 'packing') {
            return response()->json([
                'success' => false,
                'message' => 'Packing lists must not be created as benchmarkable manufacturing work orders.',
            ], 422);
        }

        $orderDate = $request->has('orderDate')
            ? \Carbon\Carbon::parse($request->input('orderDate'))
            : now();

        $dueDate = (new DueDateService())->calculate($orderDate);

        $attributes = [
            'id'       => $request->input('id'),
            'name'     => $request->input('name'),
            'specs'    => $request->input('specs'),
            'status'   => $request->input('status', 'Pending'),
            'due_date' => $dueDate->toDateString(),
            'source'   => $request->input('source'),
            'fulfillment_order_id' => $request->input('fulfillmentOrderId'),
            'assigned' => $request->input('assigned'),
            'range'    => $request->input('range'),
        ];
        if (Schema::connection('manufacturing')->hasColumn('work_orders', 'work_order_type')) {
            $attributes['work_order_type'] = 'production';
        }
        $order = WorkOrder::create($attributes);

        foreach ($request->input('parts', []) as $part) {
            $order->parts()->create([
                'product_id' => $part['productId'] ?? null,
                'name'       => $part['name'] ?? '',
                'category'   => $part['category'] ?? '',
                'status'     => $part['status'] ?? 'Sourcing',
            ]);
        }

        return response()->json([
            'success' => true,
            'id'      => $order->id,
            'dueDate' => $dueDate->toDateString(),
        ]);
    }

    /**
     * Match a failed benchmark category to a physical work-order component.
     * BOMs use human-readable category names, whereas QC uses short codes.
     */
    private function workOrderPartForBenchmarkCategory(WorkOrder $order, string $benchmarkCategory): mixed
    {
        $aliases = [
            'cpu' => ['cpu', 'processor'],
            'gpu' => ['gpu', 'graphics', 'video'],
            'ram' => ['ram', 'memory'],
            'storage' => ['storage', 'ssd', 'hdd', 'nvme', 'drive', 'disk'],
            'system' => ['system', 'case', 'cable', 'power', 'psu', 'motherboard'],
        ];

        $terms = $aliases[strtolower($benchmarkCategory)] ?? [strtolower($benchmarkCategory)];

        return $order->parts->first(function ($part) use ($terms): bool {
            $haystack = strtolower(trim(($part->category ?? '') . ' ' . ($part->name ?? '')));

            return collect($terms)->contains(fn (string $term) => str_contains($haystack, $term));
        });
    }

    /** Persist replacement requirements for failed physical QC checks. */
    private function ensureReplacementParts(ReworkOrder $rework, WorkOrder $order, iterable $failedChecks): void
    {
        $order->loadMissing('parts');

        collect($failedChecks)
            ->filter(function ($result): bool {
                $verdict = is_array($result) ? ($result['verdict'] ?? '') : ($result->verdict ?? '');

                return $verdict === 'Fail';
            })
            ->map(function ($result): string {
                $checkId = is_array($result) ? ($result['checkId'] ?? '') : ($result->check_id ?? '');

                return explode('_', (string) $checkId)[0];
            })
            ->filter()
            ->unique()
            ->each(function (string $category) use ($rework, $order): void {
                $buildPart = $this->workOrderPartForBenchmarkCategory($order, $category);
                if (! $buildPart) {
                    return;
                }

                $buildPart->update(['status' => 'Missing']);
                $rework->requiredParts()->firstOrCreate(
                    ['name' => $buildPart->name],
                    ['status' => 'Sourcing']
                );
            });
    }

    /**
     * Publish every physical replacement requirement to Inventory as an open
     * defect. Inventory's replacement request picker reads these records.
     */
    private function sendReworkDefectsToInventory(ReworkOrder $rework, ?WorkOrder $workOrder): bool
    {
        $clientId = (int) ($workOrder?->client_id ?: session('employee_client_id')) ?: null;
        $createdBy = (string) session('employee_name', 'Manufacturing');
        $bridge = new InventoryBridgeService();

        foreach ($rework->requiredParts as $part) {
            if (! $bridge->logDefect($rework->wo_id, $part->name, 1, $clientId, $createdBy)) {
                return false;
            }
        }

        return true;
    }

    /**
     * An assigned production worker can only progress their own work order.
     * Production managers, supervisors, and quality staff retain the ability
     * to coordinate the client-wide production queue.
     */
    private function assertCanOperateWorkOrder(WorkOrder $order): void
    {
        if (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin') {
            return;
        }

        if ($this->canManageManufacturing() || $this->isQualityEmployee()) {
            return;
        }

        abort_unless(
            $order->assigned_employee_id && (int) $order->assigned_employee_id === (int) session('employee_id'),
            403,
            'You can only progress work orders assigned to you.'
        );
    }

    private function assertCanManageManufacturing(): void
    {
        if (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin') {
            return;
        }

        abort_unless($this->canManageManufacturing(), 403, 'Only a production manager or supervisor can assign staff.');
    }

    private function canManageManufacturing(): bool
    {
        $position = strtolower((string) session('employee_position'));

        return session('employee_role') === 'admin'
            || EmployeePermissionGate::allows('manufacturing.manage_work_orders')
            || str_contains($position, 'manager')
            || str_contains($position, 'supervisor');
    }

    private function isQualityEmployee(): bool
    {
        return EmployeePermissionGate::allows('manufacturing.record_quality_checks')
            || str_contains(strtolower((string) session('employee_position')), 'quality')
            || str_contains(strtolower((string) session('employee_department')), 'quality');
    }

    /**
     * Packing lists describe operational materials, not assembled computers.
     * Keep the check at both the status transition and benchmark submission so
     * stale browser data cannot accidentally create a QC/rework record.
     */
    private function isPackingWorkOrder(WorkOrder $order): bool
    {
        if (strtolower((string) ($order->work_order_type ?? '')) === 'packing') {
            return true;
        }

        $label = implode(' ', [
            (string) $order->name,
            (string) $order->specs,
            (string) $order->source,
        ]);

        return (bool) preg_match('/\\b(packaging|packing\\s+(?:list|bom|materials?))\\b/i', $label);
    }

    /**
     * Release a passed manufacturing build into the dedicated fulfillment
     * database. Non-ecommerce/internal work orders have no linked order and
     * simply complete in Manufacturing.
     */
    private function releaseToFulfillment(WorkOrder $workOrder): ?string
    {
        $fulfillmentOrderId = $workOrder->fulfillment_order_id;

        if (! $fulfillmentOrderId && preg_match('/^Ecommerce\s+(.+)$/i', (string) $workOrder->source, $matches)) {
            $fulfillmentOrderId = trim($matches[1]);
            $workOrder->update(['fulfillment_order_id' => $fulfillmentOrderId]);
        }

        if (! $fulfillmentOrderId) {
            return null;
        }

        $fulfillment = DB::connection('order_fulfillment')->table('orders')
            ->where('id', $fulfillmentOrderId)
            ->where('client_id', $workOrder->client_id)
            ->first();

        if (! $fulfillment) {
            throw new RuntimeException('The linked Order Fulfillment order could not be found for this work order.');
        }

        if (in_array(strtoupper((string) $fulfillment->status), ['CANCELLED', 'DELIVERED', 'RETURNED'], true)) {
            throw new RuntimeException('The linked Order Fulfillment order can no longer be released for packing.');
        }

        if (strtoupper((string) $fulfillment->status) === 'NEW') {
            DB::connection('order_fulfillment')->table('orders')
                ->where('id', $fulfillmentOrderId)
                ->where('client_id', $workOrder->client_id)
                ->update(['status' => 'PACKING', 'updated_at' => now()]);
        }

        return $fulfillmentOrderId;
    }
}
