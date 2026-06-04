<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Renders the audit log page shell for API-driven monitoring data.
 */
class AuditLogPageController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.audit-logs.index');
    }
}