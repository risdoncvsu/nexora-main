<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\ReturnRequest;
use Modules\Ecommerce\CRM\Models\Customer as CrmCustomer;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    private function customerVisibleStatus(?string $fulfillmentStatus, ?string $manufacturingStatus, ?string $ecommerceStatus): string
    {
        $fulfillmentStatus = strtoupper((string) $fulfillmentStatus);

        // Fulfillment deliberately waits while Manufacturing builds and runs
        // QC. During that wait, show the live production milestone instead.
        if ($fulfillmentStatus === 'AWAITING_MANUFACTURING' && $manufacturingStatus) {
            return strtoupper((string) $manufacturingStatus);
        }

        return $fulfillmentStatus ?: strtoupper((string) ($ecommerceStatus ?: 'NEW'));
    }

    protected function redirectToPasswordPane(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to(route('ecommerce.account.profile') . '#password');
    }
    public function index(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();

        $paymentMethods = $user->paymentMethods()->orderBy('is_default', 'desc')->get();
        $addresses = $user->addresses()->orderBy('is_default', 'desc')->get();

        // Load CRM customer profile for loyalty tier display
        $crmCustomer = null;
        if ($user) {
            $crmCustomer = CrmCustomer::withoutGlobalScope('ecommerce-client')
                ->where('user_id', $user->id)
                ->first();
        }

        $orders = collect();

        if ($user) {
            // Fetch ecommerce orders for this user
            $ecomOrders = Order::with('items')
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $ecomOrderIds = $ecomOrders->pluck('id')->filter()->all();

            $fulfillmentOrders = collect();
            $shipments = collect();
            $manufacturingOrders = collect();

            // Always fetch fulfillment orders that match by ID
            if (!empty($ecomOrderIds)) {
                $fulfillmentOrders = DB::connection('order_fulfillment')
                    ->table('orders')
                    ->whereIn('id', $ecomOrderIds)
                    ->get()
                    ->keyBy('id');

                $shipments = DB::connection('order_fulfillment')
                    ->table('shipments')
                    ->whereIn('order_id', $ecomOrderIds)
                    ->get()
                    ->keyBy('order_id');

                if (\Illuminate\Support\Facades\Schema::connection('manufacturing')->hasTable('work_orders')) {
                    $manufacturingOrders = DB::connection('manufacturing')
                        ->table('work_orders')
                        ->whereIn('fulfillment_order_id', $ecomOrderIds)
                        ->get()
                        ->keyBy('fulfillment_order_id');
                }
            }

            // Also fetch fulfillment orders that match by customer_name but DON'T have a matching ecom order ID
            $linkedFulfillmentIds = $fulfillmentOrders->pluck('id')->all();
            $unmatchedFulfillmentOrders = DB::connection('order_fulfillment')
                ->table('orders')
                ->where('customer_name', 'LIKE', '%' . $user->name . '%')
                ->whereNotIn('id', array_merge($ecomOrderIds, $linkedFulfillmentIds))
                ->latest()
                ->get();

            $unmatchedShipmentIds = $unmatchedFulfillmentOrders->pluck('id')->all();
            $unmatchedShipments = collect();
            if (!empty($unmatchedShipmentIds)) {
                $unmatchedShipments = DB::connection('order_fulfillment')
                    ->table('shipments')
                    ->whereIn('order_id', $unmatchedShipmentIds)
                    ->get()
                    ->keyBy('order_id');
            }

            // Build the merged orders collection
            if ($ecomOrders->isNotEmpty()) {
                foreach ($ecomOrders as $order) {
                    $fo = $fulfillmentOrders->get($order->id);
                    $shipment = $shipments->get($order->id);
                    $manufacturing = $manufacturingOrders->get($order->id);

                    $order->fulfillment_status = $this->customerVisibleStatus($fo->status ?? null, $manufacturing->status ?? null, $order->status);
                    $order->manufacturing_status = $manufacturing?->status;
                    $order->fulfillment_details = $fo;
                    $order->shipment_details = $shipment;

                    // Parse shipping_address JSON for use in the view/modal
                    $addr = $order->shipping_address;
                    if (is_string($addr)) {
                        $addr = json_decode($addr, true);
                    }
                    $order->shipping_address_parsed = $addr;

                    // If fulfillment DB has address but ecom doesn't, use fulfillment address
                    if (empty($addr) && $fo && isset($fo->address)) {
                        $order->shipping_address_parsed = ['raw' => $fo->address];
                    }

                    $orders->push($order);
                }
            }

            // Append unmatched fulfillment-only orders (created directly in fulfillment DB)
            foreach ($unmatchedFulfillmentOrders as $fo) {
                $fakeOrder = new Order();
                $fakeOrder->id = $fo->id;
                $fakeOrder->user_id = $user->id;
                $fakeOrder->total = $fo->product_amount ?? 0;
                $fakeOrder->status = strtolower($fo->status ?? 'NEW');
                $fakeOrder->created_at = Carbon::parse($fo->created_at);
                $fakeOrder->tracking_number = 'TF-' . strtoupper(substr(md5($fo->id), 0, 8));

                $fakeItem = (object)[
                    'name' => $fo->product_name ?? 'Storefront order',
                    'price' => $fo->product_amount ?? 0,
                    'quantity' => $fo->qty ?? 1
                ];
                $fakeOrder->setRelation('items', collect([$fakeItem]));

                $fakeOrder->fulfillment_status = strtoupper($fo->status);
                $fakeOrder->fulfillment_details = $fo;
                $fakeOrder->shipment_details = $unmatchedShipments->get($fo->id);
                $fakeOrder->shipping_address_parsed = ['raw' => $fo->address ?? ''];

                $orders->push($fakeOrder);
            }
        }

        return view('ecommerce::account.index', compact('paymentMethods', 'addresses', 'orders', 'crmCustomer'));
    }

    public function orderHistory(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();
        $orders = collect();

        if ($user) {
            $ecomOrders = Order::with('items')
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            $ecomOrderIds = $ecomOrders->pluck('id')->filter()->all();

            $fulfillmentOrders = collect();
            $shipments = collect();
            $manufacturingOrders = collect();

            if (!empty($ecomOrderIds)) {
                $fulfillmentOrders = DB::connection('order_fulfillment')
                    ->table('orders')
                    ->whereIn('id', $ecomOrderIds)
                    ->get()
                    ->keyBy('id');

                $shipments = DB::connection('order_fulfillment')
                    ->table('shipments')
                    ->whereIn('order_id', $ecomOrderIds)
                    ->get()
                    ->keyBy('order_id');

                if (\Illuminate\Support\Facades\Schema::connection('manufacturing')->hasTable('work_orders')) {
                    $manufacturingOrders = DB::connection('manufacturing')
                        ->table('work_orders')
                        ->whereIn('fulfillment_order_id', $ecomOrderIds)
                        ->get()
                        ->keyBy('fulfillment_order_id');
                }
            }

            if ($ecomOrders->isEmpty()) {
                $legacyFulfillmentOrders = DB::connection('order_fulfillment')
                    ->table('orders')
                    ->where('customer_name', 'LIKE', '%' . $user->name . '%')
                    ->latest()
                    ->get();

                if ($legacyFulfillmentOrders->isNotEmpty()) {
                    $legacyShipments = DB::connection('order_fulfillment')
                        ->table('shipments')
                        ->whereIn('order_id', $legacyFulfillmentOrders->pluck('id'))
                        ->get()
                        ->keyBy('order_id');

                    foreach ($legacyFulfillmentOrders as $fo) {
                        $fakeOrder = new Order();
                        $fakeOrder->id = $fo->id;
                        $fakeOrder->user_id = $user->id;
                        $fakeOrder->total = $fo->product_amount;
                        $fakeOrder->status = strtolower($fo->status);
                        $fakeOrder->created_at = Carbon::parse($fo->created_at);
                        $fakeOrder->tracking_number = 'TF-' . strtoupper(substr(md5($fo->id), 0, 8));
                        
                        $fakeItem = (object)[
                            'name' => $fo->product_name,
                            'price' => $fo->product_amount,
                            'quantity' => $fo->qty
                        ];
                        $fakeOrder->setRelation('items', collect([$fakeItem]));

                        $fakeOrder->fulfillment_status = strtoupper($fo->status);
                        $fakeOrder->fulfillment_details = $fo;
                        $fakeOrder->shipment_details = $legacyShipments->get($fo->id);

                        $orders->push($fakeOrder);
                    }
                }
            } else {
                foreach ($ecomOrders as $order) {
                    $fo = $fulfillmentOrders->get($order->id);
                    $shipment = $shipments->get($order->id);
                    $manufacturing = $manufacturingOrders->get($order->id);

                    $order->fulfillment_status = $this->customerVisibleStatus($fo->status ?? null, $manufacturing->status ?? null, $order->status);
                    $order->manufacturing_status = $manufacturing?->status;
                    $order->fulfillment_details = $fo;
                    $order->shipment_details = $shipment;

                    $orders->push($order);
                }
            }
        }

        return redirect()->to(route('ecommerce.account.profile') . '#order-history');
    }

    public function showOrder(Request $request, $store, $id)
    {
        $user = Auth::guard('ecommerce')->user();

        $order = Order::with('items')->where('user_id', $user->id)->where('id', $id)->first();

        $fo = DB::connection('order_fulfillment')->table('orders')->where('id', $id)->first();
        $shipment = DB::connection('order_fulfillment')->table('shipments')->where('order_id', $id)->first();

        if (!$order && $fo) {
            // Legacy / demo order fallback
            $order = new Order();
            $order->id = $fo->id;
            $order->user_id = $user->id;
        $order->total = $fo->product_amount ?? 0;
        $order->status = strtolower($fo->status ?? 'NEW');
        $order->created_at = Carbon::parse($fo->created_at);
        $order->tracking_number = 'TF-' . strtoupper(substr(md5($fo->id), 0, 8));
        $order->shipping_address = ['address' => $fo->address ?? '', 'name' => $fo->customer_name ?? ''];
        
        $fakeItem = (object)[
            'name' => $fo->product_name ?? 'Storefront order',
            'price' => $fo->product_amount ?? 0,
            'quantity' => $fo->qty ?? 1,
                'configuration' => null,
            ];
            $order->setRelation('items', collect([$fakeItem]));
        }

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->fulfillment_status = strtoupper($fo->status ?? $order->status ?? 'NEW');
        $order->fulfillment_details = $fo;
        $order->shipment_details = $shipment;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        }

        return view('ecommerce::account.order-detail', compact('order'));
    }

    public function requestReturn(Request $request, $store, $id)
    {
        $user = Auth::guard('ecommerce')->user();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'condition' => 'nullable|string|in:like_new,good,fair,poor',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'string',
        ]);

        $order = Order::with('items')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found.'], 404);
        }

        // Only delivered/completed orders can be returned
        $status = strtoupper($order->status ?? 'NEW');
        $returnableStates = ['DELIVERED', 'COMPLETED'];
        if (!in_array($status, $returnableStates)) {
            return response()->json([
                'success' => false,
                'error' => 'This order cannot be returned as it has not been delivered yet.',
            ], 422);
        }

        // Check return window — only allow returns within the company's configured window
        $windowDays = $order->client_id
            ? (\App\Models\Company::find($order->client_id)?->return_window_days ?? 30)
            : 30;
        $deliveredAt = $order->delivered_at;
        if ($deliveredAt) {
            $daysSinceDelivery = now()->diffInDays($deliveredAt, false);
            if ($daysSinceDelivery > $windowDays) {
                return response()->json([
                    'success' => false,
                    'error' => 'The return window of ' . $windowDays . ' days has passed. Your order was delivered ' . now()->diffInDays($deliveredAt) . ' days ago.',
                ], 422);
            }
        }

        // Check for existing pending return request
        $existingReturn = ReturnRequest::where('order_id', $order->id)
            ->where('type', 'return')
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingReturn) {
            return response()->json([
                'success' => false,
                'error' => 'A return request has already been submitted for this order.',
            ], 409);
        }

        try {
            DB::connection('ecommerce')->beginTransaction();

            // Snapshot the selected items (or all items if none specified)
            $selectedIds = $validated['item_ids'] ?? [];
            $itemsData = $order->items
                ->when(!empty($selectedIds), fn($q) => $q->whereIn('id', $selectedIds))
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ])->values()->toArray();

            if (empty($itemsData)) {
                DB::connection('ecommerce')->rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'Please select at least one item to return.',
                ], 422);
            }

            // Create the return request
            ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'type' => 'return',
                'reason' => $validated['reason'] ?? null,
                'condition' => $validated['condition'] ?? 'good',
                'status' => 'pending',
                'items_data' => $itemsData,
            ]);

            // Update the order status
            $order->status = 'return_requested';
            $order->save();

            DB::connection('ecommerce')->commit();

            // Best-effort update to fulfillment DB
            try {
                $fulfillment = DB::connection('order_fulfillment');
                $fulfillment->table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'status' => 'RETURN_REQUESTED',
                        'return_reason' => $validated['reason'] ?? null,
                        'updated_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                report($e);
            }

            // Trigger CRM recalculation
            try {
                \Modules\Ecommerce\CRM\Models\Customer::recalculateForUser($user->id);
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'success' => true,
                'message' => 'Return request submitted successfully. We will review and process your return shortly.',
            ]);
        } catch (\Throwable $e) {
            DB::connection('ecommerce')->rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    public function requestCancel(Request $request, $store, $id)
    {
        $user = Auth::guard('ecommerce')->user();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $order = Order::with('items')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found.'], 404);
        }

        // Check if order is in a cancellable state. The ecommerce status is
        // mirrored from Manufacturing/fulfillment progress (processing,
        // manufacturing, qc_check, packing, ...), so instead of an allowlist
        // that keeps drifting out of sync, block only the genuinely
        // non-cancellable terminal states.
        $status = strtoupper($order->status ?? 'NEW');
        $nonCancellableStates = [
            'SHIPPED', 'OUT_FOR_DELIVERY', 'DELIVERED', 'COMPLETED',
            'CANCELLED', 'CANCEL_REQUESTED', 'RETURN_REQUESTED', 'RETURN_APPROVED', 'REFUNDED',
        ];
        if (in_array($status, $nonCancellableStates)) {
            return response()->json([
                'success' => false,
                'error' => 'This order cannot be cancelled as it has already been shipped or delivered.',
            ], 422);
        }

        // Check for existing pending cancel request
        $existingCancel = ReturnRequest::where('order_id', $order->id)
            ->where('type', 'cancel')
            ->whereIn('status', ['pending'])
            ->first();

        if ($existingCancel) {
            return response()->json([
                'success' => false,
                'error' => 'A cancel request has already been submitted for this order.',
            ], 409);
        }

        try {
            DB::connection('ecommerce')->beginTransaction();

            // Snapshot the order items for the return request
            $itemsData = $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ])->toArray();

            // Create the return request
            ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'type' => 'cancel',
                'reason' => $validated['reason'] ?? null,
                'status' => 'pending',
                'items_data' => $itemsData,
            ]);

            // Update the order status
            $order->status = 'cancel_requested';
            $order->save();

            DB::connection('ecommerce')->commit();

            // Try to notify the admin via the fulfillment DB
            try {
                $fulfillment = DB::connection('order_fulfillment');
                $fulfillment->table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'status' => 'CANCEL_REQUESTED',
                        'cancel_reason' => $validated['reason'] ?? null,
                        'updated_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                // Fulfillment DB update is best-effort
                report($e);
            }

            // Trigger CRM recalculation
            try {
                \Modules\Ecommerce\CRM\Models\Customer::recalculateForUser($user->id);
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cancel request submitted successfully. We will review and update your order shortly.',
            ]);
        } catch (\Throwable $e) {
            DB::connection('ecommerce')->rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    public function confirmReceived(Request $request, $store, $id)
    {
        $user = Auth::guard('ecommerce')->user();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        $order = Order::with('items')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found.'], 404);
        }

        // Prevent double-confirmation: if already marked delivered/completed
        $status = strtoupper($order->status ?? 'NEW');
        if (in_array($status, ['DELIVERED', 'COMPLETED'])) {
            return response()->json([
                'success' => false,
                'error' => 'This order has already been confirmed as received.',
            ], 409);
        }

        // Safety net: verify the fulfillment status is actually deliverable
        try {
            $fo = DB::connection('order_fulfillment')
                ->table('orders')
                ->where('id', $id)
                ->first();

            if ($fo) {
                $fulfillmentStatus = strtoupper($fo->status ?? '');
                $confirmable = ['SHIPPED', 'OUT_FOR_DELIVERY', 'DELIVERED', 'COMPLETED'];
                if (!in_array($fulfillmentStatus, $confirmable)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This order is not yet in a deliverable state. Please wait until it has been shipped.',
                    ], 422);
                }
            }
        } catch (\Throwable $e) {
            // Fulfillment DB check is best-effort; proceed if the DB is unreachable
            report($e);
        }

        // Update the ecommerce order status and record delivery timestamp
        $order->status = 'delivered';
        $order->delivered_at = now();
        $order->save();

        // Mark both Fulfillment records delivered. The Orders dashboard reads
        // orders while the Shipping dashboard reads shipments, so updating
        // only one leaves operations with contradictory delivery states.
        try {
            $fulfillment = DB::connection('order_fulfillment');
            $schema = \Illuminate\Support\Facades\Schema::connection('order_fulfillment');

            $orderUpdate = ['status' => 'DELIVERED', 'updated_at' => now()];
            if ($schema->hasColumn('orders', 'delivered_at')) {
                $orderUpdate['delivered_at'] = now();
            }

            $fulfillmentOrder = $fulfillment->table('orders')->where('id', $order->id);
            if ($schema->hasColumn('orders', 'client_id')) {
                $fulfillmentOrder->where('client_id', $order->client_id);
            }
            $fulfillmentOrder->update($orderUpdate);

            if ($schema->hasTable('shipments')) {
                $shipmentUpdate = ['status' => 'DELIVERED', 'updated_at' => now()];
                if ($schema->hasColumn('shipments', 'delivered_at')) {
                    $shipmentUpdate['delivered_at'] = now();
                }

                $shipment = $fulfillment->table('shipments')->where('order_id', $order->id);
                if ($schema->hasColumn('shipments', 'client_id')) {
                    $shipment->where('client_id', $order->client_id);
                }
                $shipment->update($shipmentUpdate);
            }
        } catch (\Throwable $e) {
            // Fulfillment DB update is best-effort — don't fail the request
            report($e);
        }

        // Trigger CRM tier recalculation
        try {
            \Modules\Ecommerce\CRM\Models\Customer::recalculateForUser($user->id);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order confirmed as received! Thank you.',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('ecommerce')->user();

        // ========== PROFILE FIELDS ==========
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob_year' => 'nullable|integer',
            'dob_month' => 'nullable|integer',
            'dob_day' => 'nullable|integer',
        ]);

        $user->name = $validated['name'] ?? $user->name;
        $user->email = $validated['email'] ?? $user->email;
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->gender = $validated['gender'] ?? $user->gender;

        if (!empty($validated['dob_year']) && !empty($validated['dob_month']) && !empty($validated['dob_day'])) {
            try {
                $user->dob = Carbon::createFromDate($validated['dob_year'], $validated['dob_month'], $validated['dob_day'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Invalid date
            }
        }

        $user->save();

        return redirect()->route('ecommerce.account.profile')->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => ['required', 'current_password:ecommerce'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->redirectToPasswordPane()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $user = Auth::guard('ecommerce')->user();
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return $this->redirectToPasswordPane()->with('success', 'Password updated successfully!');
    }
}
