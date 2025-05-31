<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Wix eCommerce API Demo')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="w-full flex justify-between items-center p-4 bg-white shadow-sm">
        <!-- Left: Home link -->
        <div>
            <a href="{{ route('welcome') }}" class="text-lg font-bold text-indigo-700 hover:text-indigo-900 transition">Home</a>
        </div>
        <!-- Right: Icons -->
        <div class="flex items-center gap-4">
            <!-- Cart Icon -->
            @php 
                $cart = session('cart', []);
                $cartQty = 0;
                foreach ($cart as $item) {
                    $cartQty += is_array($item) ? ($item['qty'] ?? 1) : (int)$item;
                }
            @endphp
            <a href="{{ route('cart') }}" class="relative group">
                <svg class="w-7 h-7 text-gray-600 hover:text-indigo-600 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m13-9l2 9m-5-9V6a2 2 0 10-4 0v3"/></svg>
                @if($cartQty > 0)
                    <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full px-2 py-0.5 font-bold">{{ $cartQty }}</span>
                @endif
            </a>
            <!-- User Icon / Name -->
            @if(auth()->check())
                <div class="relative group flex items-center gap-2 cursor-pointer">
                    👤<span class="text-gray-700 font-medium">{{ auth()->user()->name }}</span>
                    <!-- Dropdown menu -->
                    <div class="absolute right-0 top-0 mt-2 w-44 bg-white border border-gray-200 rounded-lg shadow-lg transform -translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 group-hover:pointer-events-auto pointer-events-none transition-all duration-200 z-50">
                        <a href="{{ route('profile') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                        <a href="{{ route('addresses') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Addresses</a>
                        <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Orders</a>
                        <div class="border-t my-1"></div>
                        <a href="{{ route('logout') }}" class="block px-4 py-2 text-red-600 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="flex items-center gap-1 hover:text-indigo-600 transition">
                    👤<span class="text-gray-700 font-medium text-sm">Sign in</span>
                </a>
            @endif
        </div>
    </header>
    <main class="flex-1 w-full mx-auto p-6">
        @yield('content')
    </main>
    <footer class="mt-12 text-gray-400 text-xs text-center mb-4">
        &copy; {{ date('Y') }} Alexander Voronkov
    </footer>
    <!-- Toast notifications -->
    <div id="toast-container" class="fixed top-6 right-6 z-50 flex flex-col gap-2"></div>
    <script>
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `px-4 py-2 rounded shadow text-white text-sm font-semibold mb-2 animate-fade-in ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.innerText = message;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => {
                toast.classList.add('animate-fade-out');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }
        // Laravel flash messages
        @if(session('success'))
            showToast(@json(session('success')), 'success');
        @endif
        @if($errors->any())
            showToast(@json($errors->first()), 'error');
        @endif
    </script>
    <style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(-10px);} to { opacity: 1; transform: none; } }
    @keyframes fade-out { from { opacity: 1; } to { opacity: 0; transform: translateY(-10px);} }
    .animate-fade-in { animation: fade-in 0.3s; }
    .animate-fade-out { animation: fade-out 0.4s forwards; }
    </style>
</body>
</html> 