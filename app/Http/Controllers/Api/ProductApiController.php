<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WixApiController;
use App\Http\Resources\Product\ProductBySlugResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductApiController extends Controller
{
    /**
     * @OA\Info(
     *     title="Wix E-commerce API",
     *     version="1.0.0",
     *     description="API for Wix E-commerce platform",
     *     @OA\Contact(
     *         email="support@example.com"
     *     )
     * ),
     * @OA\Get(
     *     path="/api/products/{slug}",
     *     summary="Get product by slug",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="product_123"),
     *             @OA\Property(property="slug", type="string", example="product-slug"),
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA\Property(property="description", type="string", example="Product Description"),
     *             @OA\Property(property="sku", type="string", example="0063"),
     *             @OA\Property(property="price_original", type="number", format="float", example=100.00),
     *             @OA\Property(property="price_discounted", type="number", format="float", example=80.00),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="full", type="string", example="https://example.com/image.jpg"),
     *                     @OA\Property(property="thumbnail", type="string", example="https://example.com/thumbnail.jpg")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="colors",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="value", type="string", example="#ffffff"),
     *                     @OA\Property(property="description", type="string", example="White"),
     *                     @OA\Property(property="inStock", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="media",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="full", type="string", example="https://example.com/color-image.jpg"),
     *                             @OA\Property(property="thumbnail", type="string", example="https://example.com/color-thumbnail.jpg")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="size",
     *                 type="array",
     *                 nullable=true,
     *                 @OA\Items(
     *                     @OA\Property(property="value", type="string", example="Medium"),
     *                     @OA\Property(property="inStock", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="additional_info",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="title", type="string", example="PRODUCT INFO"),
     *                     @OA\Property(property="description", type="string", example="Product details here")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function show($slug)
    {
        $data = WixApiController::getProductBySlug($slug);
        $product = $data['products'][0] ?? null;

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return new ProductBySlugResource((object) array_merge($product, ['slug' => $slug]));
    }
}
