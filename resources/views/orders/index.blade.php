@extends('layout')
@section('title', 'Orders - Wix eCommerce API Demo')
@section('content')
<div class="w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-xl mt-12 p-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">My Orders</h1>
    @if(count($orders))
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b">
                    <th class="py-2">Order #</th>
                    <th class="py-2">Date</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Total</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 font-mono">{{ $order['id'] ?? '-' }}</td>
                        <td class="py-2">{{ isset($order['createdDate']) ? \Carbon\Carbon::parse($order['createdDate'])->format('Y-m-d H:i') : '-' }}</td>
                        <td class="py-2">{{ $order['status'] ?? '-' }}</td>
                        <td class="py-2">฿{{ isset($order['totals']['totalPrice']['amount']) ? number_format($order['totals']['totalPrice']['amount'], 0) : '0' }}</td>
                        <td class="py-2">
                            <a href="{{ route('orders.show', $order['id']) }}" class="text-indigo-600 hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-gray-400 text-center py-12">No orders yet.</div>
    @endif
</div>
@endsection 