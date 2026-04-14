<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\NavigationApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\ITQueueApiController;
use App\Http\Controllers\Api\ResolverInboxApiController;

/*
|--------------------------------------------------------------------------
| Internal API Routes
|--------------------------------------------------------------------------
| Dipakai oleh frontend Blade via fetch()/axios.
| Tetap pakai session auth yang sama, jadi aman untuk project sekarang.
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

    //Sidebar stats
    Route::get('/sidebar', [DashboardApiController::class, 'index']);

    // Tickets
    Route::middleware('role:cs,admin')->group(function () {
        Route::get('/tickets', [TicketApiController::class, 'index']);
        Route::post('/tickets', [TicketApiController::class, 'store']);
        Route::get('/tickets/{ticket}', [TicketApiController::class, 'show']);
        Route::patch('/tickets/{ticket}', [TicketApiController::class, 'update']);
        Route::delete('/tickets/{ticket}', [TicketApiController::class, 'destroy']);

        Route::get('/tickets-similar', [TicketApiController::class, 'similar']);
        Route::get('/tickets-client-history', [TicketApiController::class, 'clientHistory']);
    });

    // IT Queue
    Route::middleware('role:it,admin')->group(function () {
        Route::get('/it/my-queue', [ITQueueApiController::class, 'myQueue']);
        Route::get('/it/team-queue', [ITQueueApiController::class, 'teamQueue']);
        Route::get('/it/history', [ITQueueApiController::class, 'history']);

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
});