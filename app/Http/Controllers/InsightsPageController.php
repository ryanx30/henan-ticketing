<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InsightsPageController extends Controller
{
    public function reports(Request $request)
    {
        if (!in_array($request->user()->role, ['cs', 'it', 'admin'], true)) {
            abort(403);
        }

        return view('reports.index');
    }

    public function caseAnalytics(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin'], true)) {
            abort(403);
        }

        return view('case-analytics.index');
    }
}