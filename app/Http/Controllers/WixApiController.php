<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Setting;
use Illuminate\Support\Facades\Log;

class WixApiController extends Controller
{
    
    /**
     * Get current Wix access token from settings. If expired, return null (or refresh in future).
     *
     * @return string|null
     */
    public static function getAccessToken()
    {
        $token = Setting::get('wix_access_token');
        $expiresAt = Setting::get('wix_access_token_expires_at');
        if ($expiresAt && \Carbon\Carbon::parse($expiresAt)->isPast()) {
            // Try to refresh token
            $refreshToken = Setting::get('wix_refresh_token');
            $clientId = config('services.wix.client_id');
            $clientSecret = config('services.wix.client_secret');
            $url = 'https://www.wix.com/oauth/access';
            $response = \Illuminate\Support\Facades\Http::asForm()->post($url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                Setting::set('wix_access_token', $data['access_token']);
                if (isset($data['refresh_token'])) {
                    Setting::set('wix_refresh_token', $data['refresh_token']);
                }
                if (isset($data['expires_in'])) {
                    $newExpiresAt = now()->addSeconds($data['expires_in']);
                    Setting::set('wix_access_token_expires_at', $newExpiresAt);
                }
                return $data['access_token'];
            } else {
                // Fallback to API key from settings
                $apiKey = Setting::get('wix_api_key');
                return $apiKey;
            }
        }
        // If no token at all, fallback to API key
        if (!$token) {
            $apiKey = Setting::get('wix_api_key');
            return $apiKey;
        }
        return $token;
    }
    
    /**
     * Get products by their IDs from Wix API, with caching.
     *
     * @param array $ids
     * @return array|null
     */
    public static function getProductsByIds(array $ids)
    {
        if (empty($ids)) {
            $cacheKey = 'wix_products_all';
            return Cache::remember($cacheKey, 60, function () {
                $token = self::getAccessToken();
                $siteId = env('WIX_SITE_ID');
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'wix-site-id' => $siteId,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://www.wixapis.com/stores/v1/products/query', [
                    'query' => [
                        'paging' => [
                            'limit' => 100
                        ]
                    ]
                ]);
                if (!$response->successful()) {
                    Log::error('Wix API: getProductsByIds (all) failed', [
                        'endpoint' => 'stores/v1/products/query',
                        'params' => [],
                        'response' => $response->body()
                    ]);
                }
                return $response->json();
            });
        }
        $cacheKey = 'wix_products_' . md5(json_encode($ids));
        return Cache::remember($cacheKey, 60, function () use ($ids) {
            $token = self::getAccessToken();
            $siteId = env('WIX_SITE_ID');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'wix-site-id' => $siteId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.wixapis.com/stores/v1/products/query', [
                'query' => [
                    'filter' => json_encode([
                        'id' => [
                            '$hasSome' => $ids
                        ]
                    ]),
                    'paging' => [
                        'limit' => count($ids)
                    ]
                ]
            ]);
            if (!$response->successful()) {
                Log::error('Wix API: getProductsByIds failed', [
                    'endpoint' => 'stores/v1/products/query',
                    'params' => $ids,
                    'response' => $response->body()
                ]);
            }
            return $response->json();
        });
    }

    /**
     * Get all product categories (collections) from Wix API, with caching.
     * Only visible and non-empty collections are returned.
     *
     * @return array|null
     */
    public static function getCategories()
    {
        $cacheKey = 'wix_categories_all';
        return Cache::remember($cacheKey, 60, function () {
            $token = self::getAccessToken();
            $siteId = env('WIX_SITE_ID');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'wix-site-id' => $siteId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.wixapis.com/stores-reader/v1/collections/query', [
                'query' => [
                    'paging' => [
                        'limit' => 50
                    ],
                ]
            ]);
            $data = $response->json();
            // Filter out collections that are not visible or are named 'All Products'
            if (!empty($data['collections'])) {
                $data['collections'] = array_values(array_filter($data['collections'], function($col) {
                    return $col['visible'] && $col['name'] !== 'All Products';
                }));
            }
            return $data;
        });
    }

    /**
     * Get a product by its slug from Wix API, with caching.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getProductBySlug($slug)
    {
        $cacheKey = 'wix_product_slug_' . md5($slug);
        return Cache::remember($cacheKey, 60, function () use ($slug) {
            $token = self::getAccessToken();
            $siteId = env('WIX_SITE_ID');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'wix-site-id' => $siteId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.wixapis.com/stores/v1/products/query', [
                'query' => [
                    'filter' => json_encode([
                        'slug' => $slug
                    ]),
                    'paging' => [
                        'limit' => 1
                    ]
                ]
            ]);
            return $response->json();
        });
    }

    /**
     * Get products from Wix API with filters (category, color, name, etc.), with caching.
     *
     * @param array $filter
     * @return array|null
     */
    public static function getProductsFiltered(array $filter = [])
    {
        $cacheKey = 'wix_products_filtered-' . md5(json_encode($filter));
        return Cache::remember($cacheKey, 60, function () use ($filter) {
            $token = self::getAccessToken();
            $siteId = env('WIX_SITE_ID');
            $query = [
                'paging' => [
                    'limit' => 100
                ]
            ];
            $apiFilter = [];
            if (!empty($filter['collectionId'])) {
                if (is_array($filter['collectionId'])) {
                    $apiFilter['collections.id'] = ['$hasSome' => $filter['collectionId']];
                } else {
                    $apiFilter['collections.id'] = $filter['collectionId'];
                }
            }
            if (!empty($filter['color'])) {
                if (is_array($filter['color'])) {
                    $apiFilter['productOptions.color'] = ['$hasSome' => $filter['color']];
                } else {
                    $apiFilter['productOptions.color'] = $filter['color'];
                }
            }
            if (!empty($filter['name'])) {
                $apiFilter['name'] = ['$contains' => $filter['name']];
            }
            if (!empty($apiFilter)) {
                $query['filter'] = json_encode($apiFilter);
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'wix-site-id' => $siteId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.wixapis.com/stores/v1/products/query', [
                'query' => $query
            ]);
            return $response->json();
        });
    }

    /**
     * Find a Wix customer by email using Wix Contacts API.
     *
     * @param string $email
     * @return array|null
     */
    public static function findCustomerByEmail($email)
    {
        $token = self::getAccessToken();
        $siteId = env('WIX_SITE_ID');
        $url = 'https://www.wixapis.com/contacts/v4/contacts/query';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'wix-site-id' => $siteId,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'query' => [
                'filter' => [
                    'info.emails.email' => [ '$eq' => $email ]
                ]
            ]
        ]);
        if ($response->successful()) {
            $data = $response->json();
            return $data['contacts'][0] ?? null;
        }
        return null;
    }

    /**
     * Create a new Wix customer using Wix Contacts API.
     *
     * @param array $data
     * @return array|null
     */
    public static function createCustomer($data)
    {
        $payload = [
            'info' => [
                'name' => [
                    'first' => $data['first_name'] ?? '',
                    'last' => $data['last_name'] ?? '',
                ],
                'phones' => [
                    'items' => [[
                        'tag' => 'MAIN',
                        'countryCode' => 'TH',
                        'phone' => preg_replace('/(?!^\+)\D/', '', $data['phone'] ?? ''),
                        'primary' => true,
                    ]],
                ],
                'emails' => [
                    'items' => [[
                        'email' => $data['email'] ?? '',
                        'primary' => true,
                    ]],
                ],
            ],
            'allowDuplicates' => false,
        ];
        $token = self::getAccessToken();
        $siteId = env('WIX_SITE_ID');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'wix-site-id' => $siteId,
            'Content-Type' => 'application/json',
        ])->post('https://www.wixapis.com/contacts/v4/contacts', $payload);
        if ($response->successful()) {
            $data = $response->json();
            return $data['contacts'][0] ?? null;
        }
        return null;
    }

    /**
     * Create a cart in Wix Stores using the current user's data and session cart.
     *
     * @param \App\Models\User $user
     * @param array $cart
     * @return array|null
     */
    public static function createCart($user, $cart)
    {
        $wixCustomer = $user->getWixCustomer();
        if (!$wixCustomer || empty($wixCustomer['id'])) {
            return null;
        }

        $contactId = $wixCustomer['id'];
        $appId = '215238eb-22a5-4c36-9e7b-e7c08025e04e'; // Wix Stores App ID
        $lineItems = [];
        foreach ($cart as $key => $item) {
            $parts = explode(':', $key);
            $productId = $parts[0];
            $color = $item['color'] ?? null;
            $lineItem = [
                'catalogReference' => [
                    'appId' => $appId,
                    'catalogItemId' => $productId,
                ],
                'quantity' => $item['qty'] ?? 1,
            ];
            if ($color) {
                $lineItem['catalogReference']['options'] = [
                    'options' => [
                        'color' => $color
                    ]
                ];
            }
            $lineItems[] = $lineItem;
        }
        $payload = [
            'lineItems' => $lineItems,
            'cartInfo' => [
                'buyerInfo' => [
                    'contactId' => $contactId
                ]
            ]
        ];
        
        $token = self::getAccessToken();
        $siteId = env('WIX_SITE_ID');
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => $token,
            'wix-site-id' => $siteId,
            'Content-Type' => 'application/json',
        ])->post('https://www.wixapis.com/ecom/v1/carts', $payload);
dd($response->json());
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

    /**
     * Create a checkout in Wix Stores for the given cart, with address and email.
     *
     * @param string $cartId
     * @param array|null $shippingAddress
     * @param array|null $billingAddress
     * @param string|null $email
     * @return array|null
     */
    public static function createCheckout($cartId, $shippingAddress = null, $billingAddress = null, $email = null)
    {
        $url = "https://www.wixapis.com/ecom/v1/carts/{$cartId}/create-checkout";
        // Temporary address stubs if not provided
        $addressStub = [
            'country' => 'TH',
            'subdivision' => 'TH-10',
            'city' => 'Bangkok',
            'postalCode' => '10110',
            'streetAddress' => [
                'number' => '1',
                'name' => 'Sukhumvit Rd'
            ],
            'addressLine' => '1 Sukhumvit Rd',
            'addressLine2' => 'Floor 1'
        ];
        $userEmail = $email;
        if (!$userEmail) {
            $user = auth()->user();
            $userEmail = $user && $user->email ? $user->email : 'demo@example.com';
        }
        $payload = [
            'channelType' => 'OTHER_PLATFORM',
            'shippingAddress' => $shippingAddress ?? $addressStub,
            'billingAddress' => $billingAddress ?? $addressStub,
            'email' => $userEmail,
        ];
        $token = self::getAccessToken();
        $siteId = env('WIX_SITE_ID');
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'wix-site-id' => $siteId,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

    /**
     * Create an order in Wix Stores from a checkout.
     *
     * @param string $checkoutId
     * @return array|null
     */
    public static function createOrderFromCheckout($checkoutId)
    {
        $url = "https://www.wixapis.com/ecom/v1/checkouts/{$checkoutId}/create-order";
        $payload = [
            'savePaymentMethod' => false,
            'delayCapture' => true,
        ];
        $token = self::getAccessToken();
        $siteId = env('WIX_SITE_ID');
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'wix-site-id' => $siteId,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

    /**
     * Search orders in Wix Stores by customer contactId.
     *
     * @param string $contactId
     * @param int $limit
     * @return array|null
     */
    public static function searchOrdersByContactId($contactId, $limit = 100)
    {
        $url = 'https://www.wixapis.com/ecom/v1/orders/search';
        $payload = [
            'search' => [
                'filter' => [
                    'buyerInfo.contactId' => $contactId
                ],
                'cursorPaging' => [
                    'limit' => $limit
                ],
                'sort' => [
                    [
                        'fieldName' => 'createdDate',
                        'order' => 'DESC'
                    ]
                ]
            ]
        ];
        $token = self::getAccessToken();
        $siteId = env('WIX_SITE_ID');
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'wix-site-id' => $siteId,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

} 