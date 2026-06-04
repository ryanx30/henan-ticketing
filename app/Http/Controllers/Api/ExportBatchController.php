<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use App\Support\ExportBatchAccess;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tracks queued export batches and exposes polling endpoints for frontend export progress.
 */
class ExportBatchController extends BaseApiController
{
    /**
     * Return the current queue batch state for an export request.
     *
     * The frontend uses this endpoint to poll queued exports instead of opening
     * the queued JSON response in a browser tab. The requested storage path is
     * validated before it is reported back to the client.
     */
    public function status(Request $request, string $batchId)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $path = $this->validatedExportPath($validated['path']);
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return $this->notFound('Export batch was not found.');
        }

        ExportBatchAccess::assertAuthorized($request, $batch, $path, $validated['filename']);

        $exists = Storage::disk('local')->exists($path);
        $finished = $batch->finished() || ($batch->pendingJobs === 0 && $exists);

        return $this->success([
            'batch_id' => $batch->id,
            'ready' => $finished && $exists,
            'finished' => $finished,
            'cancelled' => $batch->cancelled(),
            'failed' => $batch->hasFailures(),
            'progress' => $batch->progress(),
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'filename' => basename($validated['filename']),
            'download_url' => $finished && $exists
                ? url('/api/exports/' . $batch->id . '/download?' . http_build_query([
                    'path' => $path,
                    'filename' => basename($validated['filename']),
                ]))
                : null,
        ], 'Export batch status loaded.');
    }

    /**
     * Download a completed queued export.
     */
    public function download(Request $request, string $batchId): StreamedResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $path = $this->validatedExportPath($validated['path']);
        $batch = Bus::findBatch($batchId);

        abort_if(! $batch, 404, 'Export batch was not found.');
        ExportBatchAccess::assertAuthorized($request, $batch, $path, $validated['filename']);
        abort_if($batch->cancelled(), 410, 'Export batch was cancelled.');
        abort_if($batch->hasFailures(), 500, 'Export batch failed.');
        abort_if(! Storage::disk('local')->exists($path), 404, 'Export file was not found.');

        return Storage::disk('local')->download($path, basename($validated['filename']));
    }

    /**
     * Allow downloads only from known export folders and block path traversal.
     */
    private function validatedExportPath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if(str_contains($path, '..'), 422, 'Invalid export path.');

        $allowedPrefixes = [
            'exports/reports/',
            'exports/ticket-history/',
            'exports/case-analytics/',
            'exports/audit-logs/',
        ];

        $isAllowed = collect($allowedPrefixes)->contains(
            fn (string $prefix) => str_starts_with($path, $prefix)
        );

        abort_if(! $isAllowed, 422, 'Invalid export path.');

        return $path;
    }
}
