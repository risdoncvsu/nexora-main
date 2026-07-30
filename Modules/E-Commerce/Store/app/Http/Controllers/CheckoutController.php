<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CustomerNotification;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\CRM\Models\Customer as CrmCustomer;
use App\Services\ErpIntegrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Support\EcommerceClientContext;

class CheckoutController extends Controller
{
    public function index()
    {
        if (!Auth::guard('ecommerce')->check()) {
            session()->put('redirect_after_auth', route('ecommerce.checkout.index'));
            return redirect()->route('ecommerce.login');
        }

        $user = Auth::guard('ecommerce')->user();

        // Fetch saved addresses and payment methods
        $addresses = $user->addresses()->orderBy('is_default', 'desc')->get();
        $paymentMethods = $user->paymentMethods()->orderBy('is_default', 'desc')->get();

        // Require at least one saved address to proceed
        if ($addresses->isEmpty()) {
            return redirect()->route('ecommerce.account.profile')
                ->with('error', 'Please add a delivery address in your account before checking out.');
        }

        $cart = Cart::with('items')->where('user_id', Auth::guard('ecommerce')->id())->first();
        
        $cartItems = [];
        if ($cart) {
            foreach ($cart->items as $item) {
                $cartItems[] = [
                    'id' => $item->product_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'image_url' => $item->image_url,
                    'product_type' => $item->product_type,
                    'configuration' => $item->configuration,
                ];
            }
        }

        if (count($cartItems) === 0) {
            return redirect()->route('ecommerce.cart')->with('error', 'Your cart is empty.');
        }

        $subtotal = collect($cartItems)->sum(function($item) {
            return $item['price'] * $item['quantity'];
        });

        // Tier-based item discount
        $tierBenefits = CrmCustomer::benefitsForUser($user->id);
        $tierDiscountPct = $tierBenefits['item_discount_pct'];
        $discountedSubtotal = collect($cartItems)->sum(function (array $item) use ($tierDiscountPct): float {
            $discountedUnitPrice = round((float) $item['price'] * (1 - ((float) $tierDiscountPct / 100)), 2);

            return $discountedUnitPrice * (int) $item['quantity'];
        });
        $tierDiscountAmount = round($subtotal - $discountedSubtotal, 2);

        // Simple default shipping fee
        $shipping = 150; 
        $discount = $tierDiscountAmount;
        $total = $discountedSubtotal + $shipping;

        return view('ecommerce::checkout', compact('cartItems', 'subtotal', 'shipping', 'discount', 'total', 'addresses', 'paymentMethods', 'tierBenefits', 'tierDiscountPct', 'tierDiscountAmount'));
    }

    public function process(Request $request)
    {
        if (!Auth::guard('ecommerce')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('ecommerce')->user();

        $request->validate([
            'addressId' => 'required|integer',
            'shippingMethod' => 'required|string',
            'paymentMethod' => 'required|string',
        ]);

        // Resolve the saved address
        $address = $user->addresses()->where('id', $request->addressId)->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Selected address not found.'], 422);
        }

        // Resolve payment method (skip for COD)
        $paymentLabel = $request->paymentMethod;
        $paymentMethodRecord = null;
        if ($request->paymentMethod !== 'cod') {
            $paymentMethodRecord = $user->paymentMethods()->where('id', $request->paymentMethod)->first();
            if (!$paymentMethodRecord) {
                return response()->json(['success' => false, 'message' => 'Selected payment method not found.'], 422);
            }
            $paymentLabel = $paymentMethodRecord->type . ' ending in ' . substr($paymentMethodRecord->account_number_mask ?? '', -4);
        }

        $cart = Cart::with('items')->where('user_id', $user->id)->first();
        if (!$cart || $cart->items->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $subtotal = $cart->items->sum(function($item) {
            return $item->price * $item->quantity;
        });

        // Determine base shipping fee
        $shippingFee = $request->shippingMethod === 'express' ? 300 : ($request->shippingMethod === 'pickup' ? 0 : 150);

        // Apply tier shipping benefit
        $tierBenefits = CrmCustomer::benefitsForUser($user->id);
        $shippingBenefit = $tierBenefits['benefits']['shipping_benefit'] ?? null;

        if ($shippingBenefit === 'free_general') {
            // Platinum: free any shipping method
            $shippingFee = 0;
        } elseif ($shippingBenefit === 'free_standard') {
            // Gold: free standard shipping only
            if ($request->shippingMethod === 'standard' || $request->shippingMethod === 'pickup') {
                $shippingFee = 0;
            }
        } elseif ($shippingBenefit === '50%_off') {
            // Silver: 50% off any shipping
            $shippingFee = round($shippingFee * 0.5, 2);
        }

        // Apply tier item discount
        $tierDiscountPct = $tierBenefits['item_discount_pct'] ?? 0;
        // Lock the applied tier price into every order line. Downstream
        // modules must receive the customer-paid price, not the catalog price.
        $discountedSubtotal = $cart->items->sum(function ($item) use ($tierDiscountPct): float {
            return round((float) $item->price * (1 - ((float) $tierDiscountPct / 100)), 2)
                * (int) $item->quantity;
        });
        $tierDiscountAmount = round($subtotal - $discountedSubtotal, 2);
        $total = $discountedSubtotal + $shippingFee;

        $clientId = app(EcommerceClientContext::class)->clientId();
        if (! $clientId) {
            return response()->json(['success' => false, 'message' => 'Storefront client could not be resolved.'], 422);
        }

        // Build shipping address from saved address record
        $fullName = $address->full_name ?? $user->name;
        $nameParts = explode(' ', $fullName, 2);
        $shippingAddress = [
            'first_name' => $nameParts[0] ?? $fullName,
            'last_name' => $nameParts[1] ?? '',
            'phone' => $address->phone_number ?? '',
            'address' => trim(($address->detailed_address ?? '') . ', ' . ($address->barangay ?? '')),
            'city' => $address->city ?? '',
            'province' => $address->province ?? '',
            'zip' => $address->postal_code ?? '',
            'country' => $address->region ?? 'Philippines',
        ];

        try {
            // 1. Create the order within its own ecommerce transaction
            $order = DB::connection('ecommerce')->transaction(function () use ($cart, $request, $shippingFee, $total, $paymentLabel, $shippingAddress, $tierDiscountPct) {
                $order = Order::create([
                    'user_id' => Auth::guard('ecommerce')->id(),
                    'status' => 'processing',
                    'total' => $total,
                    'shipping_fee' => $shippingFee,
                    'payment_method' => $paymentLabel,
                    'payment_status' => $request->paymentMethod === 'cod' ? 'unpaid' : 'paid',
                    'shipping_address' => $shippingAddress,
                    'tracking_number' => 'TF-' . strtoupper(\Illuminate\Support\Str::random(8)),
                ]);

                foreach ($cart->items as $item) {
                    $discountedUnitPrice = round((float) $item->price * (1 - ((float) $tierDiscountPct / 100)), 2);

                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_type' => $item->product_type,
                        'name' => $item->name,
                        'price' => $discountedUnitPrice,
                        'quantity' => $item->quantity,
                        'configuration' => $item->configuration,
                    ]);
                }

                return $order;
            });

            // 2. Propagate to ERP outside the ecommerce transaction.
            //    Each module handles its own database transaction independently,
            //    avoiding cross-connection transaction aborts like PostgreSQL 25P02.
            app(ErpIntegrationService::class)->propagateEcommerceOrder($clientId, $order, $order->items);

            // 3. Create a notification for the customer
            try {
                CustomerNotification::create([
                    'client_id' => $clientId,
                    'user_id' => $user->id,
                    'type' => 'order_status',
                    'title' => 'Order Received',
                    'body' => 'Your order ' . $order->tracking_number . ' has been received and is being processed.',
                    'link' => route('ecommerce.account.orders.show', ['store' => $request->route('store') ?? 'store', 'id' => $order->id]),
                    'icon' => 'ph-package',
                    'icon_color' => 'green',
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Failed to create order notification: ' . $e->getMessage());
            }

            // 4. Clear Cart only after every required ERP record has been created.
            $cart->items()->delete();
        } catch (\RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'redirect_url' => route('ecommerce.checkout.success', $order->id)
        ]);
    }

    public function success($first, $id = null)
    {
        // $id is the order ID when called from subdomain ({store}.domain.com/checkout/success/{id})
        // $first is the order ID when called from localhost fallback (no subdomain)
        $orderId = $id ?? $first;
        $order = Order::with('items')->where('user_id', Auth::guard('ecommerce')->id())->findOrFail($orderId);

        // Load tier benefits for discount breakdown display
        $user = Auth::guard('ecommerce')->user();
        $tierBenefits = $user ? \Modules\Ecommerce\CRM\Models\Customer::benefitsForUser($user->id) : null;
        $tierDiscountPct = $tierBenefits['item_discount_pct'] ?? 0;

        // Order line prices are the locked customer-paid tier prices. Recover
        // the catalog subtotal only for this receipt's discount display.
        $discountedSubtotal = (float) $order->items->sum(fn($i) => $i->price * $i->quantity);
        $discountFactor = 1 - ((float) $tierDiscountPct / 100);
        $originalSubtotal = $tierDiscountPct > 0 && $discountFactor > 0
            ? round($discountedSubtotal / $discountFactor, 2)
            : $discountedSubtotal;
        $tierDiscountAmount = round($originalSubtotal - $discountedSubtotal, 2);

        return view('ecommerce::checkout-success', compact(
            'order', 'tierBenefits', 'tierDiscountPct', 'tierDiscountAmount', 'originalSubtotal'
        ));
    }
}
