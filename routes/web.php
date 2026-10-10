<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MyOrdersController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Two front doors:
|   /admin  back office for staff (Filament, see AdminPanelProvider)
|   /shop   B2B ordering portal for customers (Inertia + React)
*/

$home = function (Request $request) {
    return $request->user()?->isStaff()
        ? redirect('/admin')
        : redirect()->route($request->user() ? 'my-orders.index' : 'shop.index');
};

Route::get('/', $home)->name('home');
Route::get('/account', $home)->middleware('auth')->name('account');
Route::get('/dashboard', $home)->middleware('auth')->name('dashboard');

// Catalog and cart are open; ordering needs an account linked to a customer.
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{product}', [ShopController::class, 'show'])->name('shop.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/my-orders', [MyOrdersController::class, 'index'])->name('my-orders.index');
    Route::get('/my-orders/{order}', [MyOrdersController::class, 'show'])->name('my-orders.show');
    Route::post('/my-orders/{order}/cancel', [MyOrdersController::class, 'cancel'])->name('my-orders.cancel');

    // Printable invoice: the customer sees their own, staff see all.
    Route::get('/invoices/{order}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
