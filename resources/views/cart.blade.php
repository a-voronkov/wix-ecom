@extends('layout')
@section('title', 'Cart - Wix eCommerce API Demo')
@section('content')
<div class="w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-xl mt-12 p-0 flex flex-col md:flex-row overflow-hidden">
    <!-- Левая колонка: товары -->
    <div class="md:w-2/3 w-full p-8 border-r border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center md:text-left">Shopping Cart</h1>
        @if(count($cartItems))
            <ul class="divide-y divide-gray-200 mb-6">
                @foreach($cartItems as $item)
                    <li class="flex items-center py-4">
                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-16 h-16 rounded object-cover mr-4">
                        <div class="flex-1">
                            <div class="font-semibold">{{ $item['name'] }}</div>
                            @if($item['variation'])
                                <div class="text-xs text-gray-500 mb-1">Color: <span class="inline-block w-4 h-4 rounded-full align-middle border ml-1" style="background: {{ $item['variation'] }};"></span> <span class="ml-1">{{ $item['variation_name'] }}</span></div>
                            @endif
                            <div class="text-gray-500 text-sm">
                                @if($item['discounted_price'])
                                    <span class="line-through text-gray-400 mr-2">฿{{ number_format($item['price'], 0) }}</span>
                                    <span class="text-red-600 font-bold">฿{{ number_format($item['discounted_price'], 0) }}</span>
                                @else
                                    ฿{{ number_format($item['price'], 0) }}
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mr-4">
                            <form method="POST" action="{{ route('cart.updateQty') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['cart_key'] }}">
                                <input type="hidden" name="action" value="dec">
                                <button type="submit" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 text-lg font-bold">-</button>
                            </form>
                            <span class="w-8 text-center">{{ $item['qty'] }}</span>
                            <form method="POST" action="{{ route('cart.updateQty') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['cart_key'] }}">
                                <input type="hidden" name="action" value="inc">
                                <button type="submit" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 text-lg font-bold">+</button>
                            </form>
                        </div>
                        <div class="font-bold text-indigo-700 mr-4">฿{{ number_format($item['subtotal'], 0) }}</div>
                        <button class="text-red-500 hover:text-red-700">Remove</button>
                    </li>
                @endforeach
            </ul>
            <div class="text-right text-lg font-bold text-gray-900 mb-4">Total: ฿{{ number_format($total, 0) }}</div>
        @else
            <div class="text-gray-400 text-center py-12">Your cart is empty.</div>
        @endif
    </div>
    <!-- Правая колонка: контакты, адрес, оформление -->
    <div class="md:w-1/3 w-full p-8 bg-gray-50 flex flex-col justify-between">
        <form method="POST" action="{{ route('cart.checkout') }}" class="space-y-6">
            @csrf
            <div>
                <h2 class="text-lg font-semibold mb-4">Contact info</h2>
                <label class="block text-sm font-medium text-gray-700">Recipient Name</label>
                <input type="text" name="name" value="{{ $user?->name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Enter recipient name" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="tel" name="phone" value="{{ $user?->phone }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="+66 ..." required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
                <select name="address_select" id="address_select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" onchange="document.getElementById('new-address-block').style.display = this.value === 'add_new' ? 'block' : 'none'">
                    <option value="">Select address...</option>
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}">{{ $address->label }} — {{ $address->address }}</option>
                    @endforeach
                    <option value="add_new">Add new address...</option>
                </select>
                <div id="new-address-block" style="display:none;" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Label</label>
                    <input type="text" name="new_label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g. Home, Work">
                    <label class="block text-sm font-medium text-gray-700 mt-3">Address</label>
                    <input type="text" name="new_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Enter address">
                </div>
            </div>
            <div class="pt-4">
                <button type="submit" class="w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-md shadow">Checkout & pay</button>
            </div>
        </form>
    </div>
</div>
@endsection 