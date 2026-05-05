<?php

namespace App\Http\Controllers\Api;

use App\Services\CaseAnalyticsService;
use Illuminate\Http\Request;

class CaseAnalyticsApiController extends BaseApiController
{
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

        return $this->caseAnalyticsService->export($timeRange, $team, $format);
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
