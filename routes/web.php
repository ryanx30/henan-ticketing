<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResolverInboxPageController;
use App\Http\Controllers\ITQueuePageController;
use App\Http\Controllers\TicketPageController;
use App\Http\Controllers\InsightsPageController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard page
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User profile pages/actions
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Ticket pages only
    Route::middleware('role:cs,admin')->group(function () {
        Route::get('/tickets', [TicketPageController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [TicketPageController::class, 'create'])->name('tickets.create');
        Route::get('/tickets/{ticket}/edit', [TicketPageController::class, 'edit'])->name('tickets.edit');
    });

    // Ticket detail page for CS / IT / Admin
    Route::middleware('role:cs,it,admin')->group(function () {
        Route::get('/tickets/{ticket}', [TicketPageController::class, 'show'])->name('tickets.show');
    });

    // Reports: cs, it, admin
    Route::middleware('role:cs,it,admin')->group(function () {
        Route::get('/reports', [InsightsPageController::class, 'reports'])->name('reports.index');
    });

    // Case Analytics: it, admin
    Route::middleware('role:it,admin')->group(function () {
        Route::get('/case-analytics', [InsightsPageController::class, 'caseAnalytics'])->name('case-analytics.index');
    });

    // Resolver Inbox pages only
    Route::middleware('role:cs,it,admin')->group(function () {
        Route::get('/resolver-inbox', [ResolverInboxPageController::class, 'index'])->name('resolver-inbox.index');
        Route::get('/resolver-inbox/{resolverMessage}', [ResolverInboxPageController::class, 'show'])->name('resolver-inbox.show');
    });

    // IT Queue pages only
    Route::middleware('role:it,admin')->group(function () {
        Route::get('/it/my-queue', [ITQueuePageController::class, 'myQueue'])->name('it.my-queue');
        Route::get('/it/team-queue', [ITQueuePageController::class, 'teamQueue'])->name('it.team-queue');
        Route::get('/it/history', [ITQueuePageController::class, 'history'])->name('it.history');
        Route::get('/it/history/export', [ITQueuePageController::class, 'export'])->name('it.history.export');
    });
});

require __DIR__ . '/auth.php';
