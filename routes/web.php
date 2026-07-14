<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

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

// Napomena: Filament admin panel je na /admin (definisan u AdminPanelProvider), ne ovde.