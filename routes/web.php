<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountGroupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'workspace', 'workspace.lock'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Accounts
    Route::get('accounts', [AccountGroupController::class, 'index'])->name('accounts.index');
    Route::post('account-groups', [AccountGroupController::class, 'store'])->name('account-groups.store');
    Route::patch('account-groups/{account_group}', [AccountGroupController::class, 'update'])->name('account-groups.update');
    Route::patch('account-groups/{account_group}/move', [AccountGroupController::class, 'move'])->name('account-groups.move');
    Route::delete('account-groups/{account_group}', [AccountGroupController::class, 'destroy'])->name('account-groups.destroy');

    Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
    Route::patch('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::patch('accounts/{account}/move', [AccountController::class, 'move'])->name('accounts.move');
    Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    // Transactions
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('transaction-drafts', [TransactionController::class, 'storeDraft'])->name('transactions.drafts.store');
    Route::get('transaction-drafts/{transaction}/edit', [TransactionController::class, 'editDraft'])->name('transactions.drafts.edit');
    Route::patch('transaction-drafts/{transaction}', [TransactionController::class, 'updateDraft'])->name('transactions.drafts.update');
    Route::post('transactions/{transaction}/duplicate', [TransactionController::class, 'duplicate'])->name('transactions.duplicate');

    // Categories
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::patch('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Merchants
    Route::get('merchants', [MerchantController::class, 'index'])->name('merchants.index');
    Route::post('merchants', [MerchantController::class, 'store'])->name('merchants.store');
    Route::patch('merchants/{merchant}', [MerchantController::class, 'update'])->name('merchants.update');
    Route::delete('merchants/{merchant}', [MerchantController::class, 'destroy'])->name('merchants.destroy');

    // Tags
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
    Route::patch('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});

require __DIR__.'/settings.php';
