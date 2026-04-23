<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\NavigationApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\ITQueueApiController;
use App\Http\Controllers\Api\ResolverInboxApiController;
use App\Http\Controllers\Api\ReportsApiController;
use App\Http\Controllers\Api\CaseAnalyticsApiController;

/*
|--------------------------------------------------------------------------
| Internal API Routes
|--------------------------------------------------------------------------
| Used by Blade frontend via fetch()/axios.
| Still uses the same session auth for this project.
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    });

    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Navigation Data
    Route::get('/navigation', [NavigationApiController::class, 'index']);

    // Sidebar stats
    Route::get('/sidebar', [DashboardApiController::class, 'index']);

    // Reports
    Route::middleware('role:cs,it,admin')->group(function () {
        Route::get('/reports', [ReportsApiController::class, 'index']);
        Route::get('/reports/export', [ReportsApiController::class, 'export']);
    });

    // Tickets - list/create/update/delete for CS/Admin
    Route::middleware('role:cs,admin')->group(function () {
        Route::get('/tickets', [TicketApiController::class, 'index']);
        Route::post('/tickets', [TicketApiController::class, 'store']);
        Route::patch('/tickets/{ticket}', [TicketApiController::class, 'update']);
        Route::delete('/tickets/{ticket}', [TicketApiController::class, 'destroy']);

        Route::get('/tickets-similar', [TicketApiController::class, 'similar']);
        Route::get('/tickets-client-history', [TicketApiController::class, 'clientHistory']);
    });

    // Ticket detail endpoints for CS / IT / Admin
    Route::middleware('role:cs,it,admin')->group(function () {
        Route::get('/tickets/{ticket}', [TicketApiController::class, 'show']);
        Route::get('/tickets/{ticket}/similar', [TicketApiController::class, 'similarByTicket']);
    });

    // IT Queue
    Route::middleware('role:it,admin')->group(function () {
        Route::get('/it/my-queue', [ITQueueApiController::class, 'myQueue']);
        Route::get('/it/team-queue', [ITQueueApiController::class, 'teamQueue']);
        Route::get('/it/history', [ITQueueApiController::class, 'history']);
        Route::get('/it/history/export', [ITQueueApiController::class, 'exportHistory']);

        Route::post('/it/tickets/{ticket}/claim', [ITQueueApiController::class, 'claim']);
        Route::patch('/it/tickets/{ticket}/status', [ITQueueApiController::class, 'updateStatus']);
    });

    // Resolver Inbox
    Route::middleware('role:cs,it,admin')->group(function () {
        Route::get('/resolver-inbox', [ResolverInboxApiController::class, 'index']);
        Route::get('/resolver-inbox/{resolverMessage}', [ResolverInboxApiController::class, 'show']);
        Route::post('/resolver-inbox', [ResolverInboxApiController::class, 'store']);
        Route::patch('/resolver-inbox/{resolverMessage}/read', [ResolverInboxApiController::class, 'markAsRead']);
        Route::delete('/resolver-inbox/{resolverMessage}', [ResolverInboxApiController::class, 'destroy']);
    });

    // Case Analytics - only for IT/Admin
    Route::middleware('role:it,admin')->group(function () {
        Route::get('/case-analytics', [CaseAnalyticsApiController::class, 'index'])
            ->name('api.case_analytics.index');
    });
});
