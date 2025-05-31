<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class CartController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $user = null;
        $addresses = collect();
        if (auth()->check()) {
            $user = auth()->user();
            $addresses = \Illuminate\Support\Facades\DB::table('addresses')->where('user_id', $user->id)->get();
        }
        $cart = session('cart', []); // ['product_id' => ['qty' => X, 'color' => Y, ...], ...]
        $productIds = [];
        foreach (array_keys($cart) as $key) {
            $id = explode(':', $key)[0];
            $productIds[] = $id;
        }
        $productIds = array_unique($productIds);
        $cartItems = [];
        $total = 0;
        $qty = 0;
        if ($productIds) {
            $data = \App\Http\Controllers\WixApiController::getProductsByIds($productIds);
            $productsById = [];
            foreach (($data['products'] ?? []) as $prod) {
                $productsById[$prod['id']] = $prod;
            }
            foreach ($cart as $key => $cartData) {
                $id = explode(':', $key)[0];
                $product = $productsById[$id] ?? null;
                if ($product) {
                    // Find selected color variation (if any)
                    $variationName = null;
                    if (is_array($cartData) && isset($cartData['color']) && !empty($product['productOptions'])) {
                        foreach ($product['productOptions'] as $option) {
                            if ($option['optionType'] === 'color') {
                                foreach ($option['choices'] as $choice) {
                                    if ($choice['value'] == $cartData['color']) {
                                        $variationName = $choice['description'] ?? $choice['value'];
                                    }
                                }
                            }
                        }
                    }
                    // Use discounted price if available
                    $price = $product['priceData']['price'] ?? 0;
                    if (isset($product['priceData']['discountedPrice']) && $product['priceData']['discountedPrice'] < $price) {
                        $discounted = $product['priceData']['discountedPrice'];
                    } else {
                        $discounted = null;
                    }
                    $item = [
                        'id' => $product['id'],
                        'cart_key' => $key,
                        'name' => $product['name'],
                        'image' => $product['media']['mainMedia']['thumbnail']['url'] ?? ($product['media']['mainMedia']['url'] ?? ''),
                        'price' => $price,
                        'discounted_price' => $discounted,
                        'qty' => is_array($cartData) ? ($cartData['qty'] ?? 1) : $cartData,
                        'subtotal' => 0,
                        'variation' => $cartData['color'] ?? null,
                        'variation_name' => $variationName,
                    ];
                    $item['subtotal'] = $item['qty'] * ($discounted ?? $item['price']);
                    $cartItems[] = $item;
                    $total += $item['subtotal'];
                    $qty += $item['qty'];
                }
            }
        }
        return view('cart', [
            'user' => $user,
            'addresses' => $addresses,
            'cartItems' => $cartItems,
            'total' => $total,
            'cartQty' => $qty,
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string',
        ]);
        $cart = session('cart', []);
        $productId = $request->product_id;
        $color = $request->input('color');
        $qty = (int)($request->input('qty', 1));

        // Ключ для уникальности вариации (например, id + цвет)
        $key = $productId;
        if ($color) {
            $key .= ':' . $color;
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = [
                'qty' => $qty,
            ];
            if ($color) {
                $cart[$key]['color'] = $color;
            }
        }

        session(['cart' => $cart]);
        return back()->with('success', 'Product added to cart!');
    }

    public function updateQty(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string',
            'action' => 'required|in:inc,dec',
        ]);
        $cart = session('cart', []);
        $productId = $request->product_id;
        if (!isset($cart[$productId])) {
            return back();
        }
        if ($request->action === 'inc') {
            if (is_array($cart[$productId])) {
                $cart[$productId]['qty']++;
            } else {
                $cart[$productId]++;
            }
        } elseif ($request->action === 'dec') {
            if (is_array($cart[$productId])) {
                $cart[$productId]['qty']--;
                if ($cart[$productId]['qty'] <= 0) {
                    unset($cart[$productId]);
                }
            } else {
                $cart[$productId]--;
                if ($cart[$productId] <= 0) {
                    unset($cart[$productId]);
                }
            }
        }
        session(['cart' => $cart]);
        return back();
    }
}
