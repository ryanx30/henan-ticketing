<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Renders the role-specific dashboard shell; dashboard data is loaded from the internal API.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->role;

        if ($role === 'it') {
            return view('dashboard-it');
        }

        return view('dashboard-cs');
    }
}