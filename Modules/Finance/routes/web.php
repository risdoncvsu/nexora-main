<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\InvoiceController;
use Modules\Finance\Http\Controllers\ExpensesController;
use Modules\Finance\Http\Controllers\AccountsController;
use Modules\Finance\Http\Controllers\CashFlowController;
use Modules\Finance\Http\Controllers\DashboardController;
use Modules\Finance\Http\Controllers\OrderController;
use Modules\Finance\Http\Controllers\SalesController;

Route::get('maindash', [DashboardController::class, 'shell'])->name('finance.maindash');
Route::get('dashboard', [DashboardController::class, 'shell'])->name('finance.dashboard');
Route::get('overview', [DashboardController::class, 'overview'])->name('finance.overview');
Route::get('/test-order', function () {return view('finance::test-order');});

Route::get('invoicedash', [InvoiceController::class, 'index'])->name('finance.invoicedash');
Route::put('/invoice/{invoice}', [InvoiceController::class, 'update'])->name('invoice.update');
Route::put('/invoice/{invoice}/reject',[InvoiceController::class, 'reject'])->name('invoice.reject');
Route::post('/orders', [OrderController::class, 'store'])->name('finance.orders.store');

Route::get('expensesdash', [ExpensesController::class, 'index'])->name('finance.expensesdash');
Route::get('salesdash',[SalesController::class, 'index'])->name('finance.salesdash');
Route::get('cashflowdash', [CashFlowController::class, 'index'])->name('finance.cashflowdash');
Route::post('expenses/request/{id}/status', [ExpensesController::class, 'updateStatus'])->name('finance.expenses.status');
Route::get('/accountsdash', [AccountsController::class, 'index'])->name('finance.accountsdash');
Route::post('/accounts', [AccountsController::class, 'store'])->name('finance.accounts.store');
Route::put('/accounts/{account}', [AccountsController::class, 'update'])->name('finance.accounts.update');
Route::delete('/accounts/{account}', [AccountsController::class, 'destroy'])->name('finance.accounts.destroy');
