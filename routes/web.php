<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
Route::get('/', function () {
    return view('welcome');
});
Route::resource('customers', CustomerController::class);
Route::resource('products', ProductController::class);
Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
Route::get('customers-search', function () {
    return view('customers.search');
})->name('customers.search');
Route::get('products-filters', fn() => view('products.filters'))->name('products.filters');
Route::get('orders-create', fn() => view('orders.create-livewire'))->name('orders.create-livewire');
Route::get('orders-board', fn() => view('orders.board'))->name('orders.board');
Route::get('products-inline', fn() => view('products.inline'))->name('products.inline');