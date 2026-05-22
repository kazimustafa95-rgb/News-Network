<?php

use App\Http\Controllers\Web\Auth\PasswordResetPageController;
use App\Http\Controllers\Web\LegalDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/reset-password/{token}', [PasswordResetPageController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetPageController::class, 'update'])->name('password.update');

Route::get('/terms-and-conditions', [LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/privacy-policy', [LegalDocumentController::class, 'privacy'])->name('legal.privacy');
