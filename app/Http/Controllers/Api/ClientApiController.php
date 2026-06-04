<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Provides client lookup and history endpoints used by ticket creation and editing screens.
 */
class ClientApiController extends BaseApiController
{
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->success([], 'No keyword');
        }

        $normalizedName = Client::normalizeName($q);
        $normalizedEmail = Client::normalizeEmail($q);
        $normalizedContact = Client::normalizeContact($q);

        $clients = Client::query()
            ->withCount('tickets')
            ->where(function ($query) use ($q, $normalizedName, $normalizedEmail, $normalizedContact) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('contact', 'like', '%' . $q . '%')
                    ->orWhere('normalized_name', 'like', '%' . $normalizedName . '%');

                if ($normalizedEmail) {
                    $query->orWhere('normalized_email', 'like', '%' . $normalizedEmail . '%');
                }

                if ($normalizedContact) {
                    $query->orWhere('normalized_contact', 'like', '%' . $normalizedContact . '%');
                }
            })
            ->orderByDesc('last_used_at')
            ->latest('id')
            ->take(8)
            ->get([
                'id',
                'name',
                'contact',
                'email',
                'last_used_at',
            ])
            ->map(function (Client $client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'contact' => $client->contact,
                    'email' => $client->email,
                    'ticket_count' => $client->tickets_count ?? 0,
                    'last_used_at' => optional($client->last_used_at)?->toISOString(),
                ];
            })
            ->values();

        return $this->success($clients, 'Client suggestions loaded');
    }

    public function history(Client $client)
    {
        $tickets = Ticket::query()
            ->where(function ($query) use ($client) {
                $query->where('client_id', $client->id)
                    ->orWhere(function ($fallback) use ($client) {
                        $fallback->where('client_name', $client->name);

                        if ($client->email) {
                            $fallback->orWhere('client_email', $client->email);
                        }

                        if ($client->contact) {
                            $fallback->orWhere('client_contact', $client->contact);
                        }
                    });
            })
            ->latest()
            ->take(8)
            ->get([
                'id',
                'ticket_code',
                'title',
                'status',
                'priority',
                'category',
                'issue_type',
                'team',
                'created_at',
            ]);

        return $this->success([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'contact' => $client->contact,
                'email' => $client->email,
                'last_used_at' => optional($client->last_used_at)?->toISOString(),
            ],
            'tickets' => $tickets,
        ], 'Client history loaded');
    }
}
