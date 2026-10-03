<?php

namespace App\Services;

use App\Models\Tenant\Stop;
use App\Models\Tenant\VehicleRoute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class DriverStopService
{
    public function __construct(
        private DriverScopeService $driverScopeService,
        private StopService $stopService
    ) {
    }

    /**
     * Routes the driver is allowed to add or move stops on.
     *
     * @return Collection<int, VehicleRoute>
     */
    public function assignedRoutes(int $driverId): Collection
    {
        return VehicleRoute::query()
            ->whereIn('id', $this->driverScopeService->routeIds($driverId))
            ->orderBy('route_name')
            ->get();
    }

    /**
     * Stops that belong to the driver's assigned routes.
     *
     * @return Collection<int, Stop>
     */
    public function stopsForDriver(int $driverId): Collection
    {
        return $this->driverScopeService
            ->scopeStops(Stop::query()->with('route'), $driverId)
            ->orderBy('route_id')
            ->orderBy('stop_order')
            ->get();
    }

    /**
     * Create a stop at the coordinates the driver submitted.
     */
    public function createStop(array $data, int $driverId): Stop
    {
        $route = $this->assignedRoute((int) $data['route_id'], $driverId);
        $stopOrder = ! empty($data['stop_order'])
            ? (int) $data['stop_order']
            : $this->nextStopOrder($route);

        return $this->stopService->createStop([
            'route_id' => $route->id,
            'stop_name' => $data['stop_name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'stop_order' => $stopOrder,
            'is_active' => true,
            'created_by_id' => $driverId,
            'updated_by_id' => $driverId,
        ]);
    }

    /**
     * Move an existing stop to the new coordinates.
     */
    public function updateStopLocation(Stop $stop, array $data, int $driverId): Stop
    {
        $this->assignedRoute((int) $stop->route_id, $driverId);

        $stop->update([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'updated_by_id' => $driverId,
        ]);

        return $stop->fresh(['route']);
    }

    public function findStop(int $stopId): Stop
    {
        $stop = Stop::query()->find($stopId);

        if (! $stop) {
            throw ValidationException::withMessages([
                'stop' => 'Stop was not found.',
            ]);
        }

        return $stop;
    }

    private function assignedRoute(int $routeId, int $driverId): VehicleRoute
    {
        if (! in_array($routeId, $this->driverScopeService->routeIds($driverId), true)) {
            throw ValidationException::withMessages([
                'route_id' => 'You can only work with stops on a route assigned to you.',
            ]);
        }

        $route = VehicleRoute::query()->find($routeId);

        if (! $route) {
            throw ValidationException::withMessages([
                'route_id' => 'Selected route was not found.',
            ]);
        }

        return $route;
    }

    private function nextStopOrder(VehicleRoute $route): int
    {
        return ((int) $route->stops()->max('stop_order')) + 1;
    }
}