<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Show list of orders
    public function index(Request $request)
    {
        $orders = [];
        $user = auth()->user();
        if ($user) {
            $wixCustomer = $user->getWixCustomer();
            $contactId = $wixCustomer['contact']['id'] ?? null;
            if ($contactId) {
                $result = \App\Http\Controllers\WixApiController::searchOrdersByContactId($contactId);
                $orders = $result['orders'] ?? [];
            }
        }
        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    // Show details for a single order
    public function show($id)
    {
        // TODO: Fetch order details from Wix API
        $order = null;
        return view('orders.show', [
            'order' => $order,
        ]);
    }

    // Create a new order (step-by-step via Wix API)
    public function createOrder(Request $request)
    {
        $user = auth()->user();
        $cart = session('cart', []);
        if (!$user || empty($cart)) {
            return back()->with('error', 'Cart is empty or user not authenticated');
        }

        // Validate recipient data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'address_select' => 'required|string',
            'new_label' => 'nullable|string|max:255',
            'new_address' => 'nullable|string|max:1024',
        ]);

        // Determine address
        $addressId = null;
        $addressObj = null;
        if ($validated['address_select'] === 'add_new') {
            if (empty($validated['new_label']) || empty($validated['new_address'])) {
                return back()->with('error', 'Please fill in both label and address for new address.');
            }
            $addressId = DB::table('addresses')->insertGetId([
                'user_id' => $user->id,
                'label' => $validated['new_label'],
                'address' => $validated['new_address'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $addressObj = [
                'label' => $validated['new_label'],
                'address' => $validated['new_address'],
            ];
        } else {
            $addressId = $validated['address_select'];
            $addressObj = DB::table('addresses')->where('id', $addressId)->where('user_id', $user->id)->first();
        }

        // Step 1: Create Wix cart
        $wixCart = \App\Http\Controllers\WixApiController::createCart($user, $cart);
        if (!$wixCart || empty($wixCart['cart']['id'])) {
            return back()->with('error', 'Failed to create Wix cart');
        }
        $cartId = $wixCart['cart']['id'];
dd($cartId);
        // Step 2: Create Wix checkout
        // Prepare shipping/billing address for Wix (stub for now)
        $addressStub = [
            'country' => 'TH',
            'subdivision' => 'TH-10',
            'city' => 'Bangkok',
            'postalCode' => '10110',
            'streetAddress' => [
                'number' => '1',
                'name' => 'Sukhumvit Rd'
            ],
            'addressLine' => '1 Sukhumvit Rd',
            'addressLine2' => 'Floor 1'
        ];
        $shippingAddress = $addressStub;
        $billingAddress = $addressStub;
        // TODO: parse $addressObj->address into these fields
        $wixCheckout = \App\Http\Controllers\WixApiController::createCheckout($cartId, $shippingAddress, $billingAddress, $user->email);
        if (!$wixCheckout || empty($wixCheckout['checkout']['id'])) {
            return back()->with('error', 'Failed to create Wix checkout');
        }
        $checkoutId = $wixCheckout['checkout']['id'];

        // Step 3: Create Wix order from checkout
        $wixOrder = \App\Http\Controllers\WixApiController::createOrderFromCheckout($checkoutId);
        if (!$wixOrder || empty($wixOrder['order']['id'])) {
            return back()->with('error', 'Failed to create Wix order');
        }
        $orderId = $wixOrder['order']['id'];

        // Clear the cart after successful order creation
        session(['cart' => []]);
        return redirect()->route('orders.show', $orderId)->with('success', 'Order created successfully!');
    }
} 