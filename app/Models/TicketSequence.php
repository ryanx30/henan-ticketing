<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketSequence extends Model
{
    protected $fillable = [
        'prefix',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public static function nextCode(string $prefix, int $sequenceLength = 5): string
    {
        return DB::transaction(function () use ($prefix, $sequenceLength) {
            $lastNumberFromTickets = self::lastNumberFromExistingTickets($prefix, $sequenceLength);

            DB::table('ticket_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'last_number' => $lastNumberFromTickets,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = self::query()
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new RuntimeException('Unable to initialize ticket sequence.');
            }

            if ((int) $sequence->last_number < $lastNumberFromTickets) {
                $sequence->last_number = $lastNumberFromTickets;
            }

            $sequence->last_number = ((int) $sequence->last_number) + 1;
            $sequence->save();

            return $prefix . str_pad((string) $sequence->last_number, $sequenceLength, '0', STR_PAD_LEFT);
        });
    }

    protected static function lastNumberFromExistingTickets(string $prefix, int $sequenceLength): int
    {
        $lastCode = Ticket::query()
            ->where('ticket_code', 'like', $prefix . '%')
            ->orderByDesc('ticket_code')
            ->value('ticket_code');

        if (!$lastCode) {
            return 0;
        }

        $cleanCode = preg_replace('/[\s#]+/', '', (string) $lastCode);
        $cleanCode = preg_replace('/^T-?/i', '', (string) $cleanCode);

        if (!str_starts_with((string) $cleanCode, $prefix)) {
            return 0;
        }

        $sequence = substr((string) $cleanCode, strlen($prefix), $sequenceLength);

        return ctype_digit($sequence) ? (int) $sequence : 0;
    }
}
