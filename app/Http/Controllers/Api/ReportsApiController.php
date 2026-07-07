<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ExportDataJob;
use App\Models\Ticket;
use App\Models\User;
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

        $user = $request->user();
        $scope = $this->sanitizeReportType((string) $request->query('scope', $this->defaultReportType($request->user())));
        $perPage = $this->sanitizePerPage((int) $request->query('per_page', 10));
        $range = (string) $request->query('range', '1w');
        $selectedUserId = $this->selectedUserIdFromRequest($request);

        if (!$this->reportsPayloadService->canUseUserFilter($user, $selectedUserId)) {
            return $this->validationError([
                'user_id' => ['The selected report user is not available for your role.'],
            ], 'Invalid report user filter.');
        }

        $payload = $this->reportsPayloadService->build(
            $user,
            $start,
            $end,
            $scope,
            $range,
            $perPage,
            $selectedUserId,
        );

        return $this->successResponse($payload, 'Reports loaded');
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        [$start, $end] = $this->rangeResolver->fromRequest($request);

        $user = $request->user();
        $scope = $this->reportsPayloadService->normalizeScopeForUser(
            $this->sanitizeReportType((string) $request->query('scope', $this->defaultReportType($request->user()))),
            $user,
        );
        $selectedUserId = $this->selectedUserIdFromRequest($request);

        if (!$this->reportsPayloadService->canUseUserFilter($user, $selectedUserId)) {
            return $this->validationError([
                'user_id' => ['The selected report user is not available for your role.'],
            ], 'Invalid report user filter.');
        }

        $filename = 'reports-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.csv';
        $storagePath = 'exports/reports/' . $filename;

        $batch = Bus::batch([
            new ExportDataJob('reports_csv', $user->id, [
                'scope' => $scope,
                'selected_user_id' => $selectedUserId,
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

    private function sanitizeReportType(string $scope): string
    {
        return in_array($scope, ['my', 'team', 'it_performance', 'cs_performance', 'all'], true) ? $scope : 'my';
    }

    private function defaultReportType(User $user): string
    {
        if ($user->isAdmin()) {
            return 'it_performance';
        }

        if ($user->isHeadCS()) {
            return 'cs_performance';
        }

        if ($user->isSupervisor()) {
            return 'all';
        }

        return 'my';
    }

    private function sanitizePerPage(int $perPage): int
    {
        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }

    private function selectedUserIdFromRequest(Request $request): ?int
    {
        $value = $request->query('user_id');

        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return ctype_digit((string) $value) ? (int) $value : -1;
    }
}
