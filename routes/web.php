<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MyOrdersController;

Route::get('/', function () {
    return view('welcome');
});

// --- Faza 1: klasični Laravel CRUD (controller + Blade) ---
Route::resource('customers', CustomerController::class);
Route::resource('products', ProductController::class);
Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');

// --- Faza 2: Livewire ekrani (interaktivni, isti domen i baza) ---
Route::view('customers-search', 'customers.search')->name('customers.search');
Route::view('products-filters', 'products.filters')->name('products.filters');
Route::view('orders-create', 'orders.create-livewire')->name('orders.create-livewire');
Route::view('orders-board', 'orders.board')->name('orders.board');
Route::view('products-inline', 'products.inline')->name('products.inline');
Route::view('customers-import', 'customers.import')->name('customers.import');
Route::view('dashboard', 'dashboard')->name('dashboard');
Route::view('conversations', 'conversations')->name('conversations');

// --- Faza 6: Inertia + React (korisnička strana) ---
Route::get('/account', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('account');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Breeze auth rute (login, register, reset lozinke)
require __DIR__ . '/auth.php';

// Napomena: Filament admin panel je na /admin (AdminPanelProvider), ne ovde.
// --- Faza 6: prodavnica (Inertia + React, korisnička strana) ---
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{product}', [ShopController::class, 'show'])->name('shop.show');

// Korpa (sesija)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

// Checkout i porudžbine kupca — samo za ulogovane
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/my-orders', [MyOrdersController::class, 'index'])->name('my-orders.index');
    Route::get('/my-orders/{order}', [MyOrdersController::class, 'show'])->name('my-orders.show');
});