@extends('layout')
@section('title', 'Product - Wix eCommerce API Demo')
@section('content')
@php
    $xData = [
        'gallery' => $product['gallery'] ?? [],
        'colorChoices' => $colorOption['choices'] ?? [],
        'selectedImage' => 0,
        'selectedColor' => 0,
    ];
@endphp

<div 
    x-data='{!! json_encode($xData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}'
    class="w-full max-w-3xl mx-auto p-8 bg-white rounded-2xl shadow-xl mt-12 flex flex-col md:flex-row gap-8"
>
    <!-- Галерея -->
    <div class="md:w-1/2 w-full">
        <img :src="gallery[selectedImage]" alt="Product Image" class="rounded-lg mb-4 w-full h-80 object-contain border">
        <div class="flex gap-2">
            <template x-for="(img, idx) in gallery" :key="idx">
                <img :src="img" class="w-16 h-16 rounded border cursor-pointer" :class="selectedImage === idx ? 'ring-2 ring-indigo-500' : ''" @click="selectedImage = idx">
            </template>
        </div>
    </div>
    <!-- Описание и опции -->
    <div class="md:w-1/2 w-full flex flex-col gap-4">
        <h1 class="text-3xl font-bold">{{ $product['name'] }}</h1>
        @if(!empty($product['sku']))
            <div class="text-gray-500 text-sm">SKU: {{ $product['sku'] }}</div>
        @endif
        <div class="text-2xl font-bold text-indigo-700 mb-2">
            @if(isset($product['priceData']['discountedPrice']) && $product['priceData']['discountedPrice'] < $product['price'])
                <span class="line-through text-gray-400 mr-2">฿{{ number_format($product['price'], 0) }}</span>
                <span class="text-red-600 font-bold">฿{{ number_format($product['priceData']['discountedPrice'], 0) }}</span>
            @else
                THB {{ number_format($product['price'], 0) }}
            @endif
        </div>
        <div class="mb-2 text-gray-700">{!! $product['description'] ?? '' !!}</div>
        <!-- Вариации (цвета) -->
        @php
            $colorOption = collect($product['productOptions'] ?? [])->firstWhere('optionType', 'color');
        @endphp
        @if($colorOption && count($colorOption['choices']) > 1)
            <div class="flex items-center gap-2 mt-2">
                <span class="font-semibold">Color:</span>
                <form id="colorForm" class="flex gap-2" onsubmit="return false;">
                    @foreach($colorOption['choices'] as $i => $choice)
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="color" value="{{ $choice['value'] }}" class="sr-only"
                                @checked($i === 0)>
                            <span class="inline-block w-7 h-7 rounded-full border-2 mx-1 {{ $i === 0 ? 'border-indigo-600 ring-2 ring-indigo-600' : 'border-gray-300' }} color-radio"
                                style="background: {{ $choice['value'] ?: '#ccc' }}"
                                data-color-value="{{ $choice['value'] }}"
                                title="{{ $choice['description'] }}"></span>
                        </label>
                    @endforeach
                </form>
            </div>
            <script>
                // JS для выделения выбранного цвета
                document.addEventListener('DOMContentLoaded', function() {
                    const radios = document.querySelectorAll('input[name=\"color\"]');
                    radios.forEach(radio => {
                        radio.addEventListener('change', function() {
                            document.querySelectorAll('.color-radio').forEach(span => {
                                span.classList.remove('border-indigo-600', 'ring-2', 'ring-indigo-600');
                                span.classList.add('border-gray-300');
                            });
                            const checked = document.querySelector('input[name=\"color\"]:checked');
                            if (checked) {
                                const span = checked.parentElement.querySelector('.color-radio');
                                span.classList.remove('border-gray-300');
                                span.classList.add('border-indigo-600', 'ring-2', 'ring-indigo-600');
                            }
                        });
                    });
                });
            </script>
        @endif
        <!-- Категории -->
        @if(!empty($product['collectionIds']) && !empty($categories))
            <div class="flex flex-wrap gap-2 text-sm mt-2">
                <span class="font-semibold">Categories:</span>
                @foreach($categories as $cat)
                    @if(in_array($cat['id'], $product['collectionIds'] ?? []))
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded">{{ $cat['name'] }}</span>
                    @endif
                @endforeach
            </div>
        @endif
        <!-- Добавить в корзину -->
        <form method="POST" action="{{ route('cart.add') }}" class="mt-6 flex gap-2 items-center">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product['id'] }}">
            @if($colorOption)
                <input type="hidden" name="color" id="selectedColorInput" value="{{ $colorOption['choices'][0]['value'] ?? '' }}">
            @endif
            <input type="number" name="qty" value="1" min="1" class="w-16 border rounded px-2 py-1 text-center">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">Add to cart</button>
        </form>
        <script>
            // При отправке формы подставлять выбранный цвет
            document.addEventListener('DOMContentLoaded', function() {
                const radios = document.querySelectorAll('input[name="color"]');
                const colorInput = document.getElementById('selectedColorInput');
                if (radios.length && colorInput) {
                    radios.forEach(radio => {
                        radio.addEventListener('change', function() {
                            colorInput.value = this.value;
                        });
                    });
                }
            });
        </script>
        <a href="/" class="text-indigo-600 hover:underline mt-4">&larr; Back to catalog</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</div>
@endsection 