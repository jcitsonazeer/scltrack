<?php

namespace App\Services;

use App\Models\Tenant\ActiveTrip;
use App\Models\Tenant\DriverRouteAssignment;
use App\Models\Tenant\LiveVehicleLocation;
use App\Models\Tenant\Stop;
use App\Models\Tenant\VehicleLocationHistory;
use Illuminate\Database\Eloquent\Builder;

class DriverScopeService
{
    /**
     * Cached per driver id so a single request never repeats the queries.
     *
     * @var array<int, array{vehicle_ids: array<int, int>, route_ids: array<int, int>}>
     */
    private array $assignmentCache = [];

    /**
     * Vehicle ids of every active assignment of the given driver.
     *
     * @return array<int, int>
     */
    public function vehicleIds(int $driverId): array
    {
        return $this->assignments($driverId)['vehicle_ids'];
    }

    /**
     * Route ids of every active assignment of the given driver.
     *
     * @return array<int, int>
     */
    public function routeIds(int $driverId): array
    {
        return $this->assignments($driverId)['route_ids'];
    }

    public function hasAssignment(int $driverId): bool
    {
        $assignments = $this->assignments($driverId);

        return $assignments['vehicle_ids'] !== [] || $assignments['route_ids'] !== [];
    }

    /**
     * Trips the driver may see: their own trips, plus trips running on a
     * vehicle or route that is assigned to them.
     */
    public function scopeTrips(Builder $query, int $driverId): Builder
    {
        $vehicleIds = $this->vehicleIds($driverId);
        $routeIds = $this->routeIds($driverId);

        return $query->where(function (Builder $query) use ($driverId, $vehicleIds, $routeIds) {
            $query->where('driver_id', $driverId);

            if ($vehicleIds !== []) {
                $query->orWhereIn('vehicle_id', $vehicleIds);
            }

            if ($routeIds !== []) {
                $query->orWhereIn('route_id', $routeIds);
            }
        });
    }

    /**
     * Live locations the driver may see.
     */
    public function scopeLiveLocations(Builder $query, int $driverId): Builder
    {
        $vehicleIds = $this->vehicleIds($driverId);

        if ($vehicleIds === []) {
            return $query->whereIn('active_trip_id', $this->ownTripIds($driverId));
        }

        return $query->where(function (Builder $query) use ($driverId, $vehicleIds) {
            $query->whereIn('vehicle_id', $vehicleIds)
                ->orWhereIn('active_trip_id', $this->ownTripIds($driverId));
        });
    }

    /**
     * Location history the driver may see.
     */
    public function scopeLocationHistory(Builder $query, int $driverId): Builder
    {
        $vehicleIds = $this->vehicleIds($driverId);

        if ($vehicleIds === []) {
            return $query->whereIn('active_trip_id', $this->ownTripIds($driverId));
        }

        return $query->where(function (Builder $query) use ($driverId, $vehicleIds) {
            $query->whereIn('vehicle_id', $vehicleIds)
                ->orWhereIn('active_trip_id', $this->ownTripIds($driverId));
        });
    }

    /**
     * Stops the driver may see: the stops on their assigned routes only.
     */
    public function scopeStops(Builder $query, int $driverId): Builder
    {
        $routeIds = $this->routeIds($driverId);

        if ($routeIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('route_id', $routeIds);
    }

    /**
     * A trip the driver owns and is allowed to open.
     */
    public function canAccessTrip(ActiveTrip $activeTrip, int $driverId): bool
    {
        return $this->scopeTrips(ActiveTrip::query()->whereKey($activeTrip->id), $driverId)->exists();
    }

    public function canAccessLiveLocation(LiveVehicleLocation $liveVehicleLocation, int $driverId): bool
    {
        return $this->scopeLiveLocations(
            LiveVehicleLocation::query()->whereKey($liveVehicleLocation->id),
            $driverId
        )->exists();
    }

    public function canAccessLocationHistory(VehicleLocationHistory $history, int $driverId): bool
    {
        return $this->scopeLocationHistory(
            VehicleLocationHistory::query()->whereKey($history->id),
            $driverId
        )->exists();
    }

    public function canAccessStop(Stop $stop, int $driverId): bool
    {
        return $this->scopeStops(Stop::query()->whereKey($stop->id), $driverId)->exists();
    }

    /**
     * @return array{vehicle_ids: array<int, int>, route_ids: array<int, int>}
     */
    private function assignments(int $driverId): array
    {
        if (isset($this->assignmentCache[$driverId])) {
            return $this->assignmentCache[$driverId];
        }

        $assignments = DriverRouteAssignment::query()
            ->where('driver_id', $driverId)
            ->where('is_active', true)
            ->get(['vehicle_id', 'route_id']);

        return $this->assignmentCache[$driverId] = [
            'vehicle_ids' => $assignments->pluck('vehicle_id')->filter()->unique()->values()->all(),
            'route_ids' => $assignments->pluck('route_id')->filter()->unique()->values()->all(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function ownTripIds(int $driverId): array
    {
        return ActiveTrip::query()
            ->where('driver_id', $driverId)
            ->pluck('id')
            ->all();
    }
}