<?php

use App\Http\Controllers\LeadContractPdfController;
use App\Http\Controllers\LeadProposalPdfController;
use App\Http\Controllers\ProjectBudgetProposalPdfController;
use App\Http\Controllers\ProjectBudgetComparisonPdfController;
use App\Http\Controllers\ProjectSeatingPlanPdfController;
use App\Http\Controllers\PublicLeadFormController;
use App\Http\Controllers\PublicGuestRsvpController;
use App\Http\Controllers\PublicProjectWebsiteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/form/{hashId}', [PublicLeadFormController::class, 'show'])
    ->name('public.lead-form.show');

Route::post('/form/{hashId}', [PublicLeadFormController::class, 'submit'])
    ->name('public.lead-form.submit');

Route::get('/rsvp/{token}', [PublicGuestRsvpController::class, 'show'])
    ->name('public.rsvp.show');

Route::post('/rsvp/{token}', [PublicGuestRsvpController::class, 'submit'])
    ->name('public.rsvp.submit');

Route::get('/event/{projectAlias}', PublicProjectWebsiteController::class)
    ->name('public.project-website.show');

Route::get('/admin/leads/{lead}/proposal.pdf', LeadProposalPdfController::class)
    ->middleware('auth')
    ->name('admin.leads.proposal.pdf');

Route::get('/admin/leads/{lead}/contract.pdf', LeadContractPdfController::class)
    ->middleware('auth')
    ->name('admin.leads.contract.pdf');

Route::get('/admin/projects/{project}/budget/{categoryBudget}/proposals.pdf', ProjectBudgetProposalPdfController::class)
    ->middleware('auth')
    ->name('admin.projects.budget.proposals.pdf');

Route::get('/admin/projects/{project}/budget/{categoryBudget}/comparison.pdf', ProjectBudgetComparisonPdfController::class)
    ->middleware('auth')
    ->name('admin.projects.budget.comparison.pdf');

Route::get('/admin/projects/{project}/layouts/{seatingPlan}/seating-plan.pdf', ProjectSeatingPlanPdfController::class)
    ->middleware('auth')
    ->name('admin.projects.layouts.seating-plan.pdf');
