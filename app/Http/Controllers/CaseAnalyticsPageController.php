<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CaseAnalyticsPageController extends Controller
{
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['it', 'admin'], true)) {
            abort(403);
        }

        return view('case-analytics.index');
    }
}