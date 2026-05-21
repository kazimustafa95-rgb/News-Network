<?php

use App\Http\Controllers\Web\LegalDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/terms-and-conditions', [LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/privacy-policy', [LegalDocumentController::class, 'privacy'])->name('legal.privacy');
