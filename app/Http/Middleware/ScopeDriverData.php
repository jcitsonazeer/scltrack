<?php

namespace App\Http\Middleware;

use App\Models\Tenant\ActiveTrip;
use App\Models\Tenant\LiveVehicleLocation;
use App\Models\Tenant\Stop;
use App\Models\Tenant\VehicleLocationHistory;
use App\Services\DriverScopeService;
use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeDriverData
{
    public function __construct(
        private RolePermissionService $rolePermissionService,
        private DriverScopeService $driverScopeService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->rolePermissionService->currentUser();

        if (! $user || ! $this->rolePermissionService->isDriver($user)) {
            return $next($request);
        }

        $resolved = $this->rolePermissionService->resolveRoute((string) $request->route()?->getName());

        if (! $resolved || ! $this->rolePermissionService->isDriverScoped($resolved['module'])) {
            return $next($request);
        }

        // Guards the show / edit / update / destroy routes of a single record.
        $record = $this->resolveRecord((string) $request->route()?->getName());

        if (! $record) {
            return $next($request);
        }

        $allowed = match (true) {
            $record instanceof ActiveTrip => $this->driverScopeService->canAccessTrip($record, $user->id),
            $record instanceof LiveVehicleLocation => $this->driverScopeService->canAccessLiveLocation($record, $user->id),
            $record instanceof VehicleLocationHistory => $this->driverScopeService->canAccessLocationHistory($record, $user->id),
            $record instanceof Stop => $this->driverScopeService->canAccessStop($record, $user->id),
            default => true,
        };

        if (! $allowed) {
            abort(403, 'You do not have permission to open this record.');
        }

        return $next($request);
    }

    /**
     * Returns the bound record of a single-record route, or null for index pages.
     */
    private function resolveRecord(string $routeName): ?object
    {
        $parameter = match (true) {
            str_starts_with($routeName, 'active-trips.') => 'activeTrip',
            str_starts_with($routeName, 'live-vehicle-locations.') => 'liveVehicleLocation',
            str_starts_with($routeName, 'vehicle-location-history.') => 'vehicleLocationHistory',
            str_starts_with($routeName, 'stops.') => 'stop',
            default => null,
        };

        if (! $parameter) {
            return null;
        }

        return request()->route($parameter) ?: null;
    }
}