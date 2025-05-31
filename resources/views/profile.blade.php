@extends('layout')
@section('title', 'Profile - Wix eCommerce API Demo')
@section('content')
<div class="w-full max-w-md mx-auto p-8 bg-white rounded-2xl shadow-xl mt-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Profile</h1>
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" value="{{ old('name', $user->name) }}">
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input id="phone" name="phone" type="tel" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" value="{{ old('phone', $user->phone) }}">
        </div>
        <div class="flex justify-between items-center gap-2 pt-2">
            <button type="submit" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow">Save</button>
            <a href="/" class="py-2 px-4 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 font-semibold rounded-md shadow text-center">Back to main</a>
        </div>
    </form>
</div>
@endsection