<?php

namespace App\Http\Controllers;

use App\Models\ResolverMessage;
use Illuminate\Http\Request;

/**
 * Renders resolver inbox pages; inbox data and message actions are loaded through the internal API.
 */
class ResolverInboxPageController extends Controller
{
    // Render resolver inbox index page
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['cs', 'it', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('resolver-inbox.index');
    }

    // Render resolver inbox detail/open page
    public function show(Request $request, ResolverMessage $resolverMessage)
    {
        if (!in_array($request->user()->role, ['cs', 'it', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('resolver-inbox.show', compact('resolverMessage'));
    }
}