<?php

namespace App\Http\Controllers\Api;

use App\Services\Dashboard\DashboardPayloadService;
use App\Services\DashboardCacheService;
use Illuminate\Http\Request;

class DashboardApiController extends BaseApiController
{
    public function __construct(
        private DashboardCacheService $dashboardCache,
        private DashboardPayloadService $dashboardPayload
    ) {
    }

    public function index(Request $request)
    {
        return $this->dashboardCache->rememberSummaryForRequest($request, function () use ($request) {
            return $this->success(
                $this->dashboardPayload->make($request),
                'Dashboard data loaded'
            );
        });
    }
}
