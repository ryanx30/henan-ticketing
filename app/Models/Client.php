<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact',
        'email',
        'normalized_name',
        'normalized_email',
        'normalized_contact',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public static function normalizeName(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->squish()
            ->toString();
    }

    public static function normalizeEmail(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::of($value)
            ->lower()
            ->squish()
            ->toString();
    }

    public static function normalizeContact(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized !== '' ? $normalized : null;
    }

    public static function resolveForTicket(array $payload): ?self
    {
        $name = trim((string) ($payload['client_name'] ?? ''));
        $contact = trim((string) ($payload['client_contact'] ?? ''));
        $email = trim((string) ($payload['client_email'] ?? ''));

        if ($name === '' && $contact === '' && $email === '') {
            return null;
        }

        $normalizedName = self::normalizeName($name);
        $normalizedEmail = self::normalizeEmail($email);
        $normalizedContact = self::normalizeContact($contact);

        $client = null;

        if (!empty($payload['client_id'])) {
            $client = self::query()->whereKey($payload['client_id'])->first();
        }

        if (!$client && $normalizedEmail) {
            $client = self::query()
                ->where('normalized_email', $normalizedEmail)
                ->first();
        }

        if (!$client && $normalizedContact) {
            $client = self::query()
                ->where('normalized_contact', $normalizedContact)
                ->first();
        }

        if (!$client && $normalizedName !== '') {
            $client = self::query()
                ->where('normalized_name', $normalizedName)
                ->first();
        }

        $finalName = $name !== '' ? $name : ($client?->name ?? 'Unknown Client');
        $finalContact = $contact !== '' ? $contact : $client?->contact;
        $finalEmail = $email !== '' ? $email : $client?->email;

        $attributes = [
            'name' => $finalName,
            'contact' => $finalContact,
            'email' => $finalEmail,
            'normalized_name' => self::normalizeName($finalName),
            'normalized_email' => self::normalizeEmail($finalEmail),
            'normalized_contact' => self::normalizeContact($finalContact),
            'last_used_at' => now(),
        ];

        if ($client) {
            $client->update($attributes);

            return $client->fresh();
        }

        return self::query()->create($attributes);
    }
}
