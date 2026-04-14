<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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