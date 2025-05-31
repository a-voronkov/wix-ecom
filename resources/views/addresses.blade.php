@extends('layout')
@section('title', 'Addresses - Wix eCommerce API Demo')
@section('content')
<div class="w-full max-w-lg mx-auto p-8 bg-white rounded-2xl shadow-xl mt-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">My Addresses</h1>
    @if(session('success'))
        {{-- Toast handled in layout --}}
    @endif
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">Saved addresses</h2>
        @if(count($addresses))
            <ul class="divide-y divide-gray-200">
                @foreach($addresses as $address)
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <div class="font-medium">{{ $address->label }}</div>
                            <div class="text-gray-500 text-sm">{{ $address->address }}</div>
                        </div>
                        <form method="POST" action="{{ route('addresses.delete', $address->id) }}" onsubmit="return confirm('Delete this address?')">
                            @csrf
                            <button type="submit" class="text-red-500 hover:text-red-700 px-3 py-1 rounded">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-gray-400 text-sm">No addresses yet.</div>
        @endif
    </div>
    <div class="border-t pt-6 mt-6">
        <h2 class="text-lg font-semibold mb-2">Add new address</h2>
        <form method="POST" action="{{ route('addresses.add') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Label</label>
                <input name="label" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g. Home, Work">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <input name="address" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Enter address">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="py-2 px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow">Add address</button>
            </div>
        </form>
    </div>
</div>
@endsection 