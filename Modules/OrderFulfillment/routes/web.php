<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\OrderFulfillment\Http\Controllers\DashboardController;
use Modules\OrderFulfillment\Http\Controllers\OrderController;
use Modules\OrderFulfillment\Http\Controllers\PackingController;
use Modules\OrderFulfillment\Http\Controllers\ShippingController;
use Modules\OrderFulfillment\Http\Controllers\MaterialRequestController;
use Modules\OrderFulfillment\Http\Controllers\ReturnController;
use Modules\OrderFulfillment\Http\Controllers\ActivityController;
use Modules\OrderFulfillment\Http\Controllers\TestPanelController;

// Protected order-fulfillment routes
Route::prefix('order-fulfillment')->name('order-fulfillment.')->middleware('order-fulfillment.access')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/activity/recent', [ActivityController::class, 'recent'])->name('activity.recent');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::post('/orders/{id}/prepare', [OrderController::class, 'prepare'])->name('orders.prepare');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/packing', [PackingController::class, 'index'])->name('packing');
    Route::post('/packing/process/{id}', [PackingController::class, 'processOrder'])->name('packing.process');

    Route::get('/shipping', [ShippingController::class, 'index'])->name('shipping');
    Route::post('/shipping/{shipmentId}/dispatch', [ShippingController::class, 'dispatch'])->name('shipping.dispatch');
    Route::post('/shipping/{shipmentId}/cancel', [ShippingController::class, 'cancel'])->name('shipping.cancel');

    Route::post('/material-requests', [MaterialRequestController::class, 'store'])->name('material-requests.store');

Route::get('returns', [ReturnController::class, 'index'])->name('return');
Route::post('returns/{id}/accept', [ReturnController::class, 'accept'])->name('returns.accept');
Route::post('returns/{id}/decline', [ReturnController::class, 'decline'])->name('returns.decline');
Route::post('returns/{id}/status', [ReturnController::class, 'updateStatus'])->name('returns.status');

    // Available only outside production for presentation and QA. It bypasses
    // normal status transitions and must never be exposed to live users.
    if (! app()->environment('production')) {
        Route::get('/test-panel', [TestPanelController::class, 'index'])->name('test-panel');
        Route::post('/test-panel/orders/{id}/status', [TestPanelController::class, 'updateOrder'])->name('test-panel.orders.status');
        Route::post('/test-panel/shipments/{shipmentId}/status', [TestPanelController::class, 'updateShipment'])->name('test-panel.shipments.status');
        Route::post('/test-panel/returns/{id}/status', [TestPanelController::class, 'updateReturn'])->name('test-panel.returns.status');
        Route::delete('/test-panel/orders/{id}', [TestPanelController::class, 'deleteOrder'])->name('test-panel.orders.delete');
        Route::delete('/test-panel/shipments/{shipmentId}', [TestPanelController::class, 'deleteShipment'])->name('test-panel.shipments.delete');
        Route::delete('/test-panel/returns/{id}', [TestPanelController::class, 'deleteReturn'])->name('test-panel.returns.delete');
    }

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');


});
