<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Models\Ticket;
use App\Services\Reports\ReportRangeResolver;
use App\Services\Reports\ReportsPayloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use App\Support\ExportBatchAccess;

/**
 * Builds report payloads and export responses through report services so cards, charts, rows, and exports stay consistent.
 */
class ReportsApiController extends BaseApiController
{
    public function __construct(
        private ReportRangeResolver $rangeResolver,
        private ReportsPayloadService $reportsPayloadService,
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        [$start, $end] = $this->rangeResolver->fromRequest($request);

        $scope = $this->sanitizeScope((string) $request->query('scope', 'my'));
        $perPage = $this->sanitizePerPage((int) $request->query('per_page', 10));
        $range = (string) $request->query('range', 'this_week');

        $payload = $this->reportsPayloadService->build(
            $request->user(),
            $start,
            $end,
            $scope,
            $range,
            $perPage,
        );

        return $this->successResponse($payload, 'Reports loaded');
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        [$start, $end] = $this->rangeResolver->fromRequest($request);

        $user = $request->user();
        $scope = $this->reportsPayloadService->normalizeScopeForUser(
            $this->sanitizeScope((string) $request->query('scope', 'my')),
            $user,
        );

        $filename = 'reports-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.csv';
        $storagePath = 'exports/reports/' . $filename;

        $batch = Bus::batch([
            new ExportDataJob('reports_csv', $user->id, [
                'scope' => $scope,
                'date_from' => $start->toDateTimeString(),
                'date_to' => $end->toDateTimeString(),
            ], $filename),
        ])->name(ExportBatchAccess::batchName('reports', $user->id, $storagePath, $filename))->dispatch();

        return $this->acceptedResponse([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $filename,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
        ], 'Report export has been queued.');
    }

    private function sanitizeScope(string $scope): string
    {
        return in_array($scope, ['my', 'team', 'all'], true) ? $scope : 'my';
    }

    private function sanitizePerPage(int $perPage): int
    {
        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
