<?php

namespace App\Support;

use Illuminate\Bus\Batch;
use Illuminate\Http\Request;

/**
 * Builds and validates queue batch names so queued export downloads stay bound
 * to the authenticated user and the exact generated storage path.
 */
class ExportBatchAccess
{
    public static function batchName(string $type, int $userId, string $storagePath, string $filename): string
    {
        return implode('|', [
            'export',
            'type:' . $type,
            'user:' . $userId,
            'path:' . sha1(self::normalizePath($storagePath)),
            'file:' . basename($filename),
        ]);
    }

    public static function assertAuthorized(Request $request, Batch $batch, string $storagePath, string $filename): void
    {
        $name = (string) $batch->name;
        $userId = (int) $request->user()->id;
        $pathHash = sha1(self::normalizePath($storagePath));
        $basename = basename($filename);

        abort_if(
            ! str_contains($name, 'user:' . $userId),
            403,
            'You are not allowed to access this export batch.'
        );

        abort_if(
            ! str_contains($name, 'path:' . $pathHash) || ! str_contains($name, 'file:' . $basename),
            403,
            'Export batch metadata does not match the requested file.'
        );
    }

    private static function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
