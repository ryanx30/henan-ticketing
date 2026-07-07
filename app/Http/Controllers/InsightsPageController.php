<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Renders reports and insight pages that consume API-driven payloads.
 */
class InsightsPageController extends Controller
{
    public function reports(Request $request)
    {
        if (!in_array($request->user()->role, ['cs', 'head_cs', 'it', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('reports.index');
    }

    public function caseAnalytics(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('case-analytics.index');
    }
}