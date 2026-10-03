<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\RolePermissionService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private RolePermissionService $rolePermissionService
    ) {
    }

    public function index(): View
    {
        $driverId = $this->rolePermissionService->driverScopeId();

        $runningTrips = $this->dashboardService->getRunningTrips($driverId);
        $alerts = $this->dashboardService->getAlerts($driverId);

        return view('dashboard.index', [
            'stats' => $this->dashboardService->getStats($driverId),
            'runningTrips' => $runningTrips,
            'liveLocations' => $this->dashboardService->getLiveLocations($driverId),
            'recentNotifications' => $this->dashboardService->getRecentNotifications(),
            'alerts' => $alerts,
            'criticalAlertCount' => $alerts->where('severity', 'danger')->count(),
            'liveCutoff' => $this->dashboardService->staleCutoff(),
        ]);
    }
}