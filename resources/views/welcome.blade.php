@extends('layout')
@section('title', 'Wix eCommerce API Demo')
@section('content')
<div class="flex flex-col md:flex-row gap-8 w-full px-4 md:px-12">
    <!-- Left column: filters -->
    <aside class="w-full md:w-64 mb-8 md:mb-0">
        <form id="filtersForm" method="get" action="{{ route('welcome') }}" class="space-y-6">
            @php
                $grouped = [
                    'Categories' => [],
                    'Clients' => [],
                    'Ages' => [],
                    'Diagnoses' => [],
                ];
                foreach ($categories as $cat) {
                    if (in_array($cat['name'], $clients)) {
                        $grouped['Clients'][] = $cat;
                    } elseif (in_array($cat['name'], $ages)) {
                        $grouped['Ages'][] = $cat;
                    } elseif (in_array($cat['name'], $diagnoses)) {
                        $grouped['Diagnoses'][] = $cat;
                    } else {
                        $grouped['Categories'][] = $cat;
                    }
                }
                $order = ['Categories', 'Clients', 'Ages', 'Diagnoses'];
                $active = (array) request()->get('category', []);
                $activeColors = (array) request()->get('color', []);
            @endphp
            @foreach($order as $group)
                @if(count($grouped[$group]))
                    <div class="mb-2">
                        <button type="button" class="flex items-center justify-between w-full font-bold text-lg mb-2 focus:outline-none" onclick="toggleFilter('{{ strtolower($group) }}')">
                            {{ $group }} <span id="icon-{{ strtolower($group) }}">+</span>
                        </button>
                        <div id="filter-{{ strtolower($group) }}" class="hidden mb-2">
                            <div class="flex flex-col gap-2">
                                @foreach($grouped[$group] as $cat)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="category[]" value="{{ $cat['name'] }}" @checked(in_array($cat['name'], $active))>
                                        <span>{{ $cat['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
            <div>
                <div class="font-bold mb-2">Colors</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($colors as $color)
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="color[]" value="{{ $color }}" @checked(in_array($color, $activeColors))>
                            <span class="inline-block w-6 h-6 rounded-full border" style="background: {{ $color }}"></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="submit" id="applyFiltersBtn"
                class="w-full py-2 px-4 bg-indigo-600 text-white rounded disabled:bg-gray-300 transition"
                disabled>
                Apply filters
            </button>
        </form>
    </aside>
    <!-- Right column: catalog -->
    <div class="flex-1">
        @if(session('success'))
            {{-- Toast handled in layout --}}
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            @foreach($products as $product)
            <div class="bg-white rounded-xl shadow p-6 flex flex-col">
                <a href="/product/{{ $product['slug'] }}">
                    <img src="{{ $product['media']['mainMedia']['url'] ?? '' }}" alt="{{ $product['name'] }}" class="rounded-lg mb-4 h-48 w-full object-cover">
                </a>
                <h2 class="text-xl font-semibold mb-2">
                    <a href="/product/{{ $product['slug'] }}" class="hover:underline text-indigo-700">{{ $product['name'] }}</a>
                </h2>
                <span class="text-lg font-bold text-indigo-600">
                    @if(isset($product['priceData']['discountedPrice']) && $product['priceData']['discountedPrice'] < $product['price'])
                        <span class="line-through text-gray-400 mr-2">B{{ number_format($product['price'], 0) }}</span>
                        <span class="text-red-600 font-bold">B{{ number_format($product['priceData']['discountedPrice'], 0) }}</span>
                    @else
                        THB {{ number_format($product['price'], 0) }}
                    @endif
                </span>
                @php
                    $colorOption = collect($product['productOptions'] ?? [])->firstWhere('optionType', 'color');
                @endphp
                @if($colorOption && count($colorOption['choices']) > 1)
                    <form class="flex gap-2 mt-2 color-form" onsubmit="return false;">
                        @foreach($colorOption['choices'] as $i => $choice)
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="color_{{ $product['id'] }}" value="{{ $choice['value'] }}" class="sr-only"
                                    @checked($i === 0)>
                                <span class="inline-block w-6 h-6 rounded-full border-2 mx-1 {{ $i === 0 ? 'border-indigo-600 ring-2 ring-indigo-600' : 'border-gray-300' }} color-radio"
                                    style="background: {{ $choice['value'] ?: '#ccc' }}"
                                    data-color-value="{{ $choice['value'] }}"
                                    title="{{ $choice['description'] }}"></span>
                            </label>
                        @endforeach
                    </form>
                @endif
                <div class="flex items-center justify-between mt-2">
                    <div class="flex gap-2">
                        <button type="button" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Details</button>
                        <form method="POST" action="{{ route('cart.add') }}" class="add-to-cart-form">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                            @if($colorOption)
                                <input type="hidden" name="color" class="selectedColorInput" value="{{ $colorOption['choices'][0]['value'] ?? '' }}">
                            @endif
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">Add</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<script>
function toggleFilter(key) {
    const block = document.getElementById('filter-' + key);
    const icon = document.getElementById('icon-' + key);
    if (block.classList.contains('hidden')) {
        block.classList.remove('hidden');
        icon.textContent = '−';
    } else {
        block.classList.add('hidden');
        icon.textContent = '+';
    }
}

// For each product: highlight selected color and set hidden input
// Initialize filters and button

document.addEventListener('DOMContentLoaded', function() {
    // Collapse all filter groups by default, except those with checked items
    ['categories','clients','ages','diagnoses'].forEach(function(key) {
        const group = document.getElementById('filter-' + key);
        const checkboxes = group ? group.querySelectorAll('input[type=checkbox]') : [];
        let hasChecked = false;
        checkboxes.forEach(cb => { if (cb.checked) hasChecked = true; });
        if (!hasChecked) {
            group.classList.add('hidden');
            document.getElementById('icon-' + key).textContent = '+';
        } else {
            group.classList.remove('hidden');
            document.getElementById('icon-' + key).textContent = '−';
        }
    });

    // Colors in product cards
    document.querySelectorAll('.color-form').forEach(function(form) {
        const radios = form.querySelectorAll('input[type=radio]');
        const productId = form.closest('.bg-white').querySelector('input[name=product_id]')?.value;
        const colorInput = form.closest('.bg-white').querySelector('.selectedColorInput');
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                form.querySelectorAll('.color-radio').forEach(span => {
                    span.classList.remove('border-indigo-600', 'ring-2', 'ring-indigo-600');
                    span.classList.add('border-gray-300');
                });
                const checked = form.querySelector('input[type=radio]:checked');
                if (checked) {
                    const span = checked.parentElement.querySelector('.color-radio');
                    span.classList.remove('border-gray-300');
                    span.classList.add('border-indigo-600', 'ring-2', 'ring-indigo-600');
                    if (colorInput) colorInput.value = checked.value;
                }
            });
        });
    });

    // Enable 'Apply filters' button only if filters changed
    const form = document.getElementById('filtersForm');
    const btn = document.getElementById('applyFiltersBtn');
    let initial = new FormData(form);
    form.addEventListener('change', function() {
        let current = new FormData(form);
        let changed = false;
        for (let [key, value] of current.entries()) {
            if (initial.getAll(key).toString() !== current.getAll(key).toString()) {
                changed = true;
                break;
            }
        }
        // Check if all filters are cleared
        if (!changed) {
            // If all filters are cleared, compare the number of selected filters
            let initialCount = Array.from(initial.entries()).length;
            let currentCount = Array.from(current.entries()).length;
            changed = initialCount !== currentCount;
        }
        btn.disabled = !changed;
    });
});
</script>
@endsection
