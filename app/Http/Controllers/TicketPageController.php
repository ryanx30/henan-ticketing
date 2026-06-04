<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Renders ticket pages only; ticket data and mutations are handled by internal API controllers.
 */
class TicketPageController extends Controller
{
    // Render tickets index page
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['cs', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('tickets.index');
    }

    // Render create ticket page
    public function create(Request $request)
    {
        if (!in_array($request->user()->role, ['cs', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('tickets.create');
    }

    // Render detail ticket page
    public function show(Request $request, Ticket $ticket)
    {
        if (!in_array($request->user()->role, ['cs', 'it', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('tickets.detail', [
            'ticketId' => $ticket->id,
        ]);
    }

    // Render edit/open ticket page
    public function edit(Request $request, Ticket $ticket)
    {
        if (!in_array($request->user()->role, ['cs', 'admin', 'supervisor'], true)) {
            abort(403);
        }

        return view('tickets.edit', compact('ticket'));
    }
}