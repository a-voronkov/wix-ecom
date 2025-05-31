<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    private array $colors = [
        '#000000', '#0033ff', '#888888', '#008000', '#7eeaff', '#ff6a00', '#fbbecb',
        '#7d9aff', '#ff0000', '#ffffff', '#fff933'
    ];
    private array $diagnoses = [
        'ADHD', 'Autism', 'Sensory Processing Disorder', 'Developmental Delays',
        'Anxiety Disorder', 'Learning Disorder', 'Fine Motor Delays',
        'Oral Motor Weakness', 'Dyslexia', 'Gross Motor Delay',
        'Apraxia of Speech',
        'Articulation Disorders',
        'Cerebral Palsy',
        'Depressive Disorders',
        'Down Syndrome',
        'Fine Motor & Handwriting',
        'Gross Motor Skills',
        'Language Disorders',
        'Learning Disorder',
        'Mats & Accessories',
        'OT',
        'PsyD',
        'PT',
        'Sensory Processing',
        'SLP',
        'Speech Delay',
        'Swings & Vestibular',
        'Visual Motor Skills',
        'Learning Disorder',
    ];
    private array $ages = [
        'Birth to 24 months', '2-4 years', '5-7 years', '8-13 years', '14 years and up',
    ];
    private array $clients = [ 'Individual', 'School' ];

    public function index(Request $request)
    {
        // Get filters from GET
        $category = (array) $request->query('category', []);
        $color = $request->query('color');
        $q = $request->query('q');
        // Convert category names to collection ids
        $categoriesData = \App\Http\Controllers\WixApiController::getCategories();
        if (empty($categoriesData['collections'])) {
            Log::error('Wix API: Failed to fetch categories', ['response' => $categoriesData]);
        }
        $categoriesList = $categoriesData['collections'] ?? [];
        $categoryIds = [];
        if (!empty($category)) {
            foreach ($category as $catName) {
                foreach ($categoriesList as $cat) {
                    if ($cat['name'] === $catName) {
                        $categoryIds[] = $cat['id'];
                        break;
                    }
                }
            }
        }
        // Build filter for Wix API
        $filter = [];
        if ($categoryIds) {
            $filter['collectionId'] = $categoryIds;
        }
        if ($color) {
            $filter['color'] = $color;
        }
        if ($q) {
            $filter['name'] = $q;
        }
        $data = \App\Http\Controllers\WixApiController::getProductsFiltered($filter);
        if (empty($data['products'])) {
            Log::error('Wix API: Failed to fetch products', ['filter' => $filter, 'response' => $data]);
        }
        $products = $data['products'] ?? [];
        // Cast price to float for number_format compatibility
        foreach ($products as &$product) {
            if (isset($product['priceData']['price'])) {
                $product['price'] = (float)$product['priceData']['price'];
            } elseif (isset($product['price']) && is_numeric($product['price'])) {
                $product['price'] = (float)$product['price'];
            } else {
                $product['price'] = 0.0;
            }
            // Ensure image exists
            $img = $product['media']['mainMedia']['url'] ?? null;
            if (!$img && isset($product['media']['mainMedia']['image']['url'])) {
                $img = $product['media']['mainMedia']['image']['url'];
            }
            if (!$img && isset($product['media']['items'][0]['image']['url'])) {
                $img = $product['media']['items'][0]['image']['url'];
            }
            if (!$img) {
                $img = 'https://via.placeholder.com/480x480?text=No+Image';
            }
            $product['media']['mainMedia']['url'] = $img;
        }
        unset($product);
        $categories = $categoriesList;
        return view('welcome', [
            'products' => $products,
            'categories' => $categories,
            'colors' => $this->colors,
            'diagnoses' => $this->diagnoses,
            'ages' => $this->ages,
            'clients' => $this->clients,
            'activeCategory' => $category,
            'activeColor' => $color,
            'activeQuery' => $q,
        ]);
    }

    public function show($slug)
    {
        $data = \App\Http\Controllers\WixApiController::getProductBySlug($slug);
        if (empty($data['products'])) {
            Log::error('Wix API: Failed to fetch product by slug', ['slug' => $slug, 'response' => $data]);
        }
        $product = null;
        $categoriesData = \App\Http\Controllers\WixApiController::getCategories();
        if (empty($categoriesData['collections'])) {
            Log::error('Wix API: Failed to fetch categories (show)', ['response' => $categoriesData]);
        }
        $categories = $categoriesData['collections'] ?? [];
        if (!empty($data['products'][0])) {
            $product = $data['products'][0];
            // Цена
            if (isset($product['priceData']['price'])) {
                $product['price'] = (float)$product['priceData']['price'];
            } elseif (isset($product['price']) && is_numeric($product['price'])) {
                $product['price'] = (float)$product['price'];
            } else {
                $product['price'] = 0.0;
            }
            // Картинки (галерея)
            $images = [];
            if (!empty($product['media']['items'])) {
                foreach ($product['media']['items'] as $item) {
                    if (!empty($item['image']['url'])) {
                        $images[] = $item['image']['url'];
                    }
                }
            }
            if (empty($images) && !empty($product['media']['mainMedia']['image']['url'])) {
                $images[] = $product['media']['mainMedia']['image']['url'];
            }
            if (empty($images)) {
                $images[] = 'https://via.placeholder.com/480x480?text=No+Image';
            }
            $product['gallery'] = $images;
            // Гарантируем наличие id
            $product['id'] = $product['id'] ?? null;
        }
        if (!$product) {
            // fallback demo
            $product = [
                'id' => 1,
                'name' => 'Demo Product One',
                'description' => 'A stylish and modern product for your demo store. Perfect for testing eCommerce flows.',
                'price' => 49.99,
                'media' => [ 'mainMedia' => [ 'url' => 'https://static.wixstatic.com/media/9dde33_dcdfc37b4dcd4b72826df9f7c81625de~mv2.png' ] ],
                'gallery' => ['https://static.wixstatic.com/media/9dde33_dcdfc37b4dcd4b72826df9f7c81625de~mv2.png'],
            ];
        }
        return view('product', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }
}
