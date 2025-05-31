@extends('layout')
@section('title', 'Login - Wix eCommerce API Demo')
@section('content')
<div class="w-full max-w-md mx-auto p-8 bg-white rounded-2xl shadow-xl mt-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Sign in to Demo</h1>
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
        <div class="font-semibold mb-2">Demo users:</div>
        <ul class="list-disc pl-5">
            <li><span class="font-mono">demo1@example.com</span> / <span class="font-mono">demo</span> — Alice Demo, +66 1234 5678</li>
            <li><span class="font-mono">demo2@example.com</span> / <span class="font-mono">demo</span> — Bob Demo, +66 2345 6789</li>
            <li><span class="font-mono">demo3@example.com</span> / <span class="font-mono">demo</span> — Charlie Demo, +66 3456 7890</li>
        </ul>
        <div class="mt-2 text-xs text-gray-500">Use any of these accounts to log in for demo purposes.</div>
    </div>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" required autofocus class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" value="{{ old('email') }}">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>
        <div class="flex justify-between items-center gap-2 pt-2">
            <button type="submit" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow">Sign in</button>
            <a href="/" class="py-2 px-4 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 font-semibold rounded-md shadow text-center">Back to main</a>
        </div>
    </form>
</div>
@endsection 