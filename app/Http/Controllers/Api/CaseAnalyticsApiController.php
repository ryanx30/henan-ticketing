<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Services\CaseAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class CaseAnalyticsApiController extends BaseApiController
{
    private const PDF_EXPORT_TICKET_LIMIT = 5000;
    public function __construct(
        private CaseAnalyticsService $caseAnalyticsService
    ) {
    }

    public function index(Request $request)
    {
        [$timeRange, $team] = $this->validatedFilters($request);

        return $this->success(
            $this->caseAnalyticsService->analyticsPayload($timeRange, $team),
            'Case analytics loaded'
        );
    }

    public function export(Request $request)
    {
        [$timeRange, $team] = $this->validatedFilters($request);

        $format = strtolower((string) $request->query('format', 'excel'));

        if (!in_array($format, ['excel', 'xls', 'pdf'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid export format. Use excel or pdf.',
            ], 422);
        }

        if ($format === 'pdf') {
            $ticketCount = $this->caseAnalyticsService->exportTicketCount($timeRange, $team);

            if ($ticketCount > self::PDF_EXPORT_TICKET_LIMIT) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF export is limited to ' . self::PDF_EXPORT_TICKET_LIMIT . ' tickets. Please reduce the time range or team filter, or export as Excel instead.',
                    'data' => [
                        'limit' => self::PDF_EXPORT_TICKET_LIMIT,
                        'estimated_tickets' => $ticketCount,
                    ],
                ], 422);
            }
        }

        $extension = $format === 'pdf' ? 'pdf' : 'xls';
        $filename = 'case-analytics-' . $team . '-' . $timeRange . '-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.' . $extension;

        $batch = Bus::batch([
            new ExportDataJob('case_analytics_' . ($format === 'pdf' ? 'pdf' : 'excel'), $request->user()->id, [
                'time_range' => $timeRange,
                'team' => $team,
            ], $filename),
        ])->name('case-analytics-export-' . $filename)->dispatch();

        return $this->success([
            'queued' => true,
            'batch_id' => $batch->id,
            'filename' => $filename,
            'storage_disk' => 'local',
            'storage_path' => 'exports/case-analytics/' . $filename,
        ], 'Case analytics export has been queued.', 202);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'time_range' => ['nullable', 'in:1m,3m,6m,1y,all_time'],
            'team' => ['nullable', 'in:all,it,finance,compliance'],
        ]);

        return [
            $validated['time_range'] ?? '1y',
            $validated['team'] ?? 'all',
        ];
    }
}
