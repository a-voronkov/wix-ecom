<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBySlugResource extends JsonResource
{
    public function toArray($request)
    {
        // Цены
        $price_original = 0.0;
        if (isset($this->priceData['price'])) {
            $price_original = (float) $this->priceData['price'];
        } elseif (isset($this->price) && is_numeric($this->price)) {
            $price_original = (float) $this->price;
        }
        $price_discounted = isset($this->priceData['discountedPrice'])
            ? (float) $this->priceData['discountedPrice']
            : $price_original;

        // Изображения продукта
        $images = [];
        if (!empty($this->media['items'])) {
            foreach ($this->media['items'] as $item) {
                if (!empty($item['image']['url'])) {
                    $images[] = [
                        'full' => $item['image']['url'],
                        'thumbnail' => $item['thumbnail']['url'] ?? $item['image']['url'],
                    ];
                }
            }
        }
        if (empty($images) && !empty($this->media['mainMedia']['image']['url'])) {
            $images[] = [
                'full' => $this->media['mainMedia']['image']['url'],
                'thumbnail' => $this->media['mainMedia']['thumbnail']['url'] ?? $this->media['mainMedia']['image']['url'],
            ];
        }
        if (empty($images)) {
            $images[] = [
                'full' => 'https://via.placeholder.com/480x480?text=No+Image',
                'thumbnail' => 'https://via.placeholder.com/150x150?text=No+Image',
            ];
        }

        // Цвета с привязкой всех изображений
        $colors = [];
        if (!empty($this->productOptions)) {
            foreach ($this->productOptions as $option) {
                if ($option['optionType'] === 'color') {
                    $colors = array_map(function ($choice) {
                        $media = [];
                        // Обрабатываем все изображения из items
                        if (!empty($choice['media']['items'])) {
                            foreach ($choice['media']['items'] as $item) {
                                if (!empty($item['image']['url'])) {
                                    $media[] = [
                                        'full' => $item['image']['url'],
                                        'thumbnail' => $item['thumbnail']['url'] ?? $item['image']['url'],
                                    ];
                                }
                            }
                        }
                        // Если items пуст, проверяем mainMedia
                        if (empty($media) && !empty($choice['media']['mainMedia']['image']['url'])) {
                            $media[] = [
                                'full' => $choice['media']['mainMedia']['image']['url'],
                                'thumbnail' => $choice['media']['mainMedia']['thumbnail']['url'] ?? $choice['media']['mainMedia']['image']['url'],
                            ];
                        }
                        return [
                            'value' => $choice['value'],
                            'description' => $choice['description'],
                            'inStock' => $choice['inStock'],
                            'media' => $media, // Массив всех изображений
                        ];
                    }, $option['choices']);
                }
            }
        }

        // Размер
        $size = null;
        if (!empty($this->productOptions)) {
            foreach ($this->productOptions as $option) {
                if (strtolower($option['name']) === 'size') {
                    $size = array_map(function ($choice) {
                        return [
                            'value' => $choice['description'],
                            'inStock' => $choice['inStock'],
                        ];
                    }, $option['choices']);
                }
            }
        }
        if (!$size && !empty($this->customTextFields)) {
            foreach ($this->customTextFields as $field) {
                if (strtolower($field['title']) === 'size') {
                    $size = [$field['value']];
                }
            }
        }

        // Дополнительные секции
        $additional_info = [];
        if (!empty($this->additionalInfoSections)) {
            $additional_info = array_map(function ($section) {
                return [
                    'title' => $section['title'],
                    'description' => $section['description'],
                ];
            }, $this->additionalInfoSections);
        }

        return [
            'id' => $this->id ?? null,
            'slug' => $this->resource->slug ?? null,
            'name' => $this->name ?? '',
            'description' => $this->description ?? '',
            'sku' => $this->sku ?? '',
            'price_original' => $price_original,
            'price_discounted' => $price_discounted,
            'images' => $images,
            'colors' => $colors,
            'size' => $size,
            'additional_info' => $additional_info,
        ];
    }
}
