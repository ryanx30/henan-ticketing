<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResolverInboxPageController;
use App\Http\Controllers\ITQueuePageController;
use App\Http\Controllers\TicketPageController;
use App\Http\Controllers\InsightsPageController;
use App\Http\Controllers\Admin\UserManagementPageController;
use App\Http\Controllers\Admin\MasterDataPageController;
use App\Http\Controllers\Admin\AuditLogPageController;
use App\Support\NavigationMenu;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ========= AUTHENTICATED PAGE ROUTES =========
Route::middleware(['auth', 'active'])->group(function () {

    // Dashboard page
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User profile pages/actions
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Ticket pages
    Route::middleware(NavigationMenu::roleMiddlewareFor('tickets'))->group(function () {
        Route::get('/tickets', [TicketPageController::class, 'index'])->name('tickets.index');
    });

    Route::middleware(NavigationMenu::roleMiddlewareFor('new-ticket'))->group(function () {
        Route::get('/tickets/create', [TicketPageController::class, 'create'])->name('tickets.create');
        Route::get('/tickets/{ticket}/edit', [TicketPageController::class, 'edit'])->name('tickets.edit');
    });

    // Ticket detail page for CS / IT / Admin / Supervisor
    Route::middleware('role:cs,head_cs,it,admin,supervisor')->group(function () {
        Route::get('/tickets/{ticket}', [TicketPageController::class, 'show'])->name('tickets.show');
    });

    // Reports: CS / IT / Admin / Supervisor
    Route::middleware(NavigationMenu::roleMiddlewareFor('reports'))->group(function () {
        Route::get('/reports', [InsightsPageController::class, 'reports'])->name('reports.index');
    });

    // Case Analytics: IT / Admin / Supervisor
    Route::middleware(NavigationMenu::roleMiddlewareFor('case-analytics'))->group(function () {
        Route::get('/case-analytics', [InsightsPageController::class, 'caseAnalytics'])->name('case-analytics.index');
    });

    // Resolver Inbox pages
    Route::middleware(NavigationMenu::roleMiddlewareFor('resolver-inbox'))->group(function () {
        Route::get('/resolver-inbox', [ResolverInboxPageController::class, 'index'])->name('resolver-inbox.index');
        Route::get('/resolver-inbox/{resolverMessage}', [ResolverInboxPageController::class, 'show'])->name('resolver-inbox.show');
    });

    // IT Queue monitoring pages
    Route::middleware(NavigationMenu::roleMiddlewareFor('team-queue'))->group(function () {
        Route::get('/it/team-queue', [ITQueuePageController::class, 'teamQueue'])->name('it.team-queue');
    });

    Route::middleware(NavigationMenu::roleMiddlewareFor('history'))->group(function () {
        Route::get('/it/history', [ITQueuePageController::class, 'history'])->name('it.history');
        Route::get('/it/history/export', [ITQueuePageController::class, 'export'])->name('it.history.export');
    });

    Route::middleware(NavigationMenu::roleMiddlewareFor('my-queue'))->group(function () {
        Route::get('/it/my-queue', [ITQueuePageController::class, 'myQueue'])->name('it.my-queue');
    });

    // System Control - Master Data is visible for Admin, Head CS, Supervisor, and IT.
    Route::middleware([NavigationMenu::roleMiddlewareFor('master-data')])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/master-data', [MasterDataPageController::class, 'index'])->name('master-data.index');
    });

    // System Control - Users can be viewed by Admin and IT, but user management forms stay Admin-only.
    Route::middleware([NavigationMenu::roleMiddlewareFor('users')])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementPageController::class, 'index'])->name('users.index');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users/create', [UserManagementPageController::class, 'create'])->name('users.create');
        Route::get('/users/{user}/edit', [UserManagementPageController::class, 'edit'])->name('users.edit');
    });

    // System Control - Audit Logs can be viewed by Admin and IT.
    Route::middleware([NavigationMenu::roleMiddlewareFor('audit-logs')])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/audit-logs', [AuditLogPageController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__ . '/auth.php';
