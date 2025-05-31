<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WixAuthController;
use App\Http\Controllers\OrderController;

Route::get('/', [ProductController::class, 'index'])->name('welcome');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update-qty', [CartController::class, 'updateQty'])->name('cart.updateQty');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product');

Route::get('/addresses', [AddressController::class, 'index'])->name('addresses');
Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.add');
Route::post('/addresses/{id}/delete', [AddressController::class, 'destroy'])->name('addresses.delete');

Route::get('/wix', [WixAuthController::class, 'redirectToWix'])->name('wix.auth');
Route::get('/wix/callback', [WixAuthController::class, 'handleCallback'])->name('wix.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/cart/checkout', [OrderController::class, 'createOrder'])->name('cart.checkout');
});
