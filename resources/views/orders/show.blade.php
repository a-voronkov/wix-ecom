@extends('layout')
@section('title', 'Order Details - Wix eCommerce API Demo')
@section('content')
<div class="w-full max-w-2xl mx-auto bg-white rounded-2xl shadow-xl mt-12 p-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Order #{{ $order['id'] ?? '-' }}</h1>
    @if($order)
        <div class="mb-6">
            <div class="mb-2"><span class="font-semibold">Date:</span> {{ $order['date'] ?? '-' }}</div>
            <div class="mb-2"><span class="font-semibold">Status:</span> {{ $order['status'] ?? '-' }}</div>
            <div class="mb-2"><span class="font-semibold">Total:</span> ฿{{ number_format($order['total'] ?? 0, 0) }}</div>
        </div>
        <h2 class="text-lg font-semibold mb-2">Items</h2>
        <ul class="mb-6 divide-y divide-gray-200">
            @foreach($order['items'] ?? [] as $item)
                <li class="py-2 flex items-center gap-4">
                    <img src="{{ $item['image'] ?? 'https://via.placeholder.com/48' }}" alt="{{ $item['name'] ?? '' }}" class="w-12 h-12 rounded object-cover">
                    <div class="flex-1">
                        <div class="font-medium">{{ $item['name'] ?? '-' }}</div>
                        <div class="text-gray-500 text-sm">Qty: {{ $item['qty'] ?? 1 }}</div>
                    </div>
                    <div class="font-bold text-indigo-700">฿{{ number_format($item['subtotal'] ?? 0, 0) }}</div>
                </li>
            @endforeach
        </ul>
        <div class="flex gap-4 mt-6">
            <button class="py-2 px-4 bg-red-500 text-white rounded hover:bg-red-600 transition">Cancel Order</button>
            <button class="py-2 px-4 bg-green-600 text-white rounded hover:bg-green-700 transition">Pay Now</button>
        </div>
    @else
        <div class="text-gray-400 text-center py-12">Order not found.</div>
    @endif
    <div class="mt-8 text-center">
        <a href="{{ route('orders.index') }}" class="text-indigo-600 hover:underline">&larr; Back to orders</a>
    </div>
</div>
@endsection 