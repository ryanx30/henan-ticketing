<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ITQueuePageController extends Controller
{
    // Render my queue page
    public function myQueue(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin'], true)) {
            abort(403);
        }

        return view('it.my-queue');
    }

    // Render team queue page
    public function teamQueue(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin'], true)) {
            abort(403);
        }

        return view('it.team-queue');
    }

    // Render history page
    public function history(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin'], true)) {
            abort(403);
        }

        return view('it.history');
    }

    // Export can stay here for now if it still returns file download
    public function export(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin'], true)) {
            abort(403);
        }

        abort(501, 'Export handler is not migrated yet.');
    }
}