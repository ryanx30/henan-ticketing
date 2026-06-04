<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Renders the case analytics page shell for API-driven analytics data.
 */
class CaseAnalyticsPageController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('case-analytics.index');
    }
}