<?php

use App\Http\Controllers\PublicLeadFormController;
use App\Models\Company;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/admin');

Route::get('/form/{hashId}', [PublicLeadFormController::class, 'show'])
    ->name('public.lead-form.show');

Route::post('/form/{hashId}', [PublicLeadFormController::class, 'submit'])
    ->name('public.lead-form.submit');
