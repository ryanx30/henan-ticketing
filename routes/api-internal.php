<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\NavigationApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\ITQueueApiController;
use App\Http\Controllers\Api\ResolverInboxApiController;
use App\Http\Controllers\Api\ReportsApiController;
use App\Http\Controllers\Api\CaseAnalyticsApiController;
use App\Http\Controllers\Api\AdminUserApiController;
use App\Http\Controllers\Api\AdminMasterDataApiController;
use App\Http\Controllers\Api\AdminAuditLogApiController;
use App\Http\Controllers\Api\ExportBatchController;
use App\Support\NavigationMenu;

/*
|--------------------------------------------------------------------------
| Internal API Routes
|--------------------------------------------------------------------------
| Used by Blade frontend via fetch()/axios.
| Still uses the same session auth for this project.
|--------------------------------------------------------------------------
*/

// ========= AUTHENTICATED INTERNAL API ROUTES =========
Route::middleware(['web', 'auth', 'active', 'throttle:120,1'])->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'status' => true,
            'message' => 'Authenticated user loaded.',
            'data' => $request->user(),
        ]);
    });

    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Navigation Data
    Route::get('/navigation', [NavigationApiController::class, 'index']);

    // Notifications
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);
    Route::post('/notifications/read', [NotificationApiController::class, 'markAsRead']);
    Route::post('/notifications/dismiss', [NotificationApiController::class, 'dismiss']);

    // Sidebar stats
    Route::get('/sidebar', [DashboardApiController::class, 'index']);

    // Queued exports
    Route::middleware('role:cs,head_cs,it,admin,supervisor')->group(function () {
        Route::get('/exports/{batchId}/status', [ExportBatchController::class, 'status']);
        Route::get('/exports/{batchId}/download', [ExportBatchController::class, 'download']);
    });

    // Reports
    Route::middleware(NavigationMenu::roleMiddlewareFor('reports'))->group(function () {
        Route::get('/reports', [ReportsApiController::class, 'index']);
        Route::get('/reports/export', [ReportsApiController::class, 'export']);
    });

    // Tickets - list access for CS/Admin/Supervisor, mutations for CS/Admin only
    Route::middleware(NavigationMenu::roleMiddlewareFor('tickets'))->group(function () {
        Route::get('/tickets', [TicketApiController::class, 'index']);
    });

    Route::middleware(NavigationMenu::roleMiddlewareFor('new-ticket'))->group(function () {
        Route::post('/tickets', [TicketApiController::class, 'store']);

        Route::get('/tickets-similar', [TicketApiController::class, 'similar']);
        Route::get('/tickets-client-history', [TicketApiController::class, 'clientHistory']);

        // Ticket form options from master data
        Route::get('/ticket-form/options', [TicketApiController::class, 'formOptions']);
        Route::get('/ticket-form/issue-types', [TicketApiController::class, 'issueTypesByCategory']);

        // Client directory suggestions for Create Ticket autofill
        Route::get('/clients/suggest', [ClientApiController::class, 'suggest']);
        Route::get('/clients/{client}/history', [ClientApiController::class, 'history']);
    });

    Route::middleware('role:cs,head_cs,admin')->group(function () {
        Route::patch('/tickets/{ticket}', [TicketApiController::class, 'update']);
        Route::delete('/tickets/{ticket}', [TicketApiController::class, 'destroy']);
    });

    // Ticket detail endpoints for CS / IT / Admin / Supervisor
    Route::middleware('role:cs,head_cs,it,admin,supervisor')->group(function () {
        Route::get('/tickets/{ticket}', [TicketApiController::class, 'show']);
        Route::get('/tickets/{ticket}/similar', [TicketApiController::class, 'similarByTicket']);
        Route::get('/tickets/{ticket}/attachments/{attachment}/download', [TicketApiController::class, 'downloadAttachment']);
    });

    Route::middleware('role:cs,head_cs,it,admin')->group(function () {
        Route::patch('/tickets/{ticket}/escalate', [TicketApiController::class, 'escalate']);
    });

    // IT Queue monitoring
    Route::middleware(NavigationMenu::roleMiddlewareFor('team-queue'))->group(function () {
        Route::get('/it/team-queue', [ITQueueApiController::class, 'teamQueue']);
    });

    Route::middleware(NavigationMenu::roleMiddlewareFor('history'))->group(function () {
        Route::get('/it/history', [ITQueueApiController::class, 'history']);
        Route::get('/it/history/export', [ITQueueApiController::class, 'exportHistory']);
    });

    // IT Queue operational actions
    Route::middleware(NavigationMenu::roleMiddlewareFor('my-queue'))->group(function () {
        Route::get('/it/my-queue', [ITQueueApiController::class, 'myQueue']);
    });

    Route::middleware('role:it,admin')->group(function () {
        Route::post('/it/tickets/{ticket}/claim', [ITQueueApiController::class, 'claim']);
        Route::patch('/it/tickets/{ticket}/status', [ITQueueApiController::class, 'updateStatus']);
    });

    // Resolver Inbox read access
    Route::middleware(NavigationMenu::roleMiddlewareFor('resolver-inbox'))->group(function () {
        Route::get('/resolver-inbox', [ResolverInboxApiController::class, 'index']);
        Route::get('/resolver-inbox/{resolverMessage}/attachment/download', [ResolverInboxApiController::class, 'downloadAttachment']);
        Route::get('/resolver-inbox/{resolverMessage}', [ResolverInboxApiController::class, 'show']);
    });

    // Resolver Inbox actions
    Route::middleware('role:cs,head_cs,it,admin')->group(function () {
        Route::post('/resolver-inbox', [ResolverInboxApiController::class, 'store']);
        Route::patch('/resolver-inbox/{resolverMessage}/read', [ResolverInboxApiController::class, 'markAsRead']);
        Route::delete('/resolver-inbox/{resolverMessage}', [ResolverInboxApiController::class, 'destroy']);
    });

    // Case Analytics - IT/Admin/Supervisor
    Route::middleware(NavigationMenu::roleMiddlewareFor('case-analytics'))->group(function () {
        Route::get('/case-analytics', [CaseAnalyticsApiController::class, 'index'])
            ->name('api.case_analytics.index');
        Route::get('/case-analytics/export', [CaseAnalyticsApiController::class, 'export'])
            ->name('api.case_analytics.export');
    });

    // Admin - Users read access for Admin and IT. Mutations stay Admin-only.
    Route::middleware(NavigationMenu::roleMiddlewareFor('users'))->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserApiController::class, 'index']);
        Route::get('/users/{user}', [AdminUserApiController::class, 'show']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('/users', [AdminUserApiController::class, 'store']);
        Route::patch('/users/{user}', [AdminUserApiController::class, 'update']);
        Route::patch('/users/{user}/status', [AdminUserApiController::class, 'toggleStatus']);
    });

    // Master Data read access follows navigation roles; mutations are limited to Admin and Head CS.
    Route::middleware(NavigationMenu::roleMiddlewareFor('master-data'))->prefix('admin/master-data')->group(function () {
        Route::get('/', [AdminMasterDataApiController::class, 'index']);
    });

    Route::middleware('role:admin,head_cs')->prefix('admin/master-data')->group(function () {
        Route::post('/{type}', [AdminMasterDataApiController::class, 'store']);
        Route::patch('/{type}/{id}', [AdminMasterDataApiController::class, 'update']);
        Route::patch('/{type}/{id}/status', [AdminMasterDataApiController::class, 'toggleStatus']);
    });

    // Admin - Audit Logs read/export access for Admin and IT
    Route::middleware(NavigationMenu::roleMiddlewareFor('audit-logs'))->prefix('admin')->group(function () {
        Route::get('/audit-logs', [AdminAuditLogApiController::class, 'index']);
        Route::get('/audit-logs/export', [AdminAuditLogApiController::class, 'export']);
    });
});
