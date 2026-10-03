<?php

namespace App\Services;

use App\Models\Tenant\ActiveTrip;
use App\Models\Tenant\AdminAndDriver;
use App\Models\Tenant\DriverRouteAssignment;
use App\Models\Tenant\LiveVehicleLocation;
use App\Models\Tenant\ParentModel;
use App\Models\Tenant\Student;
use App\Models\Tenant\Stop;
use App\Models\Tenant\StudentRouteAssignment;
use App\Models\Tenant\TenantNotification;
use App\Models\Tenant\Vehicle;
use App\Models\Tenant\VehicleRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * A live location older than this many minutes is treated as stale.
     */
    public const LIVE_LOCATION_STALE_MINUTES = 5;

    /**
     * Driver licences expiring within this many days raise an alert.
     */
    public const LICENSE_EXPIRY_ALERT_DAYS = 30;

    public function getStats(?int $driverId = null): array
    {
        $runningTrips = $this->runningTripsQuery();
        $liveLocations = LiveVehicleLocation::query()
            ->where('recorded_at', '>=', $this->staleCutoff());

        if ($driverId) {
            $runningTrips = $this->driverScopeService()->scopeTrips($runningTrips, $driverId);
            $liveLocations = $this->driverScopeService()->scopeLiveLocations($liveLocations, $driverId);
        }

        return [
            'totalStudents' => Student::query()->count(),
            'activeStudents' => Student::query()->where('is_active', true)->count(),
            'totalParents' => ParentModel::query()->where('is_active', true)->count(),
            'totalVehicles' => Vehicle::query()->count(),
            'activeVehicles' => Vehicle::query()->where('is_active', true)->count(),
            'totalRoutes' => VehicleRoute::query()->where('is_active', true)->count(),
            'totalStops' => Stop::query()->where('is_active', true)->count(),
            'activeStudentRouteAssignments' => StudentRouteAssignment::query()->where('is_active', true)->count(),
            'activeDriverRouteAssignments' => DriverRouteAssignment::query()->where('is_active', true)->count(),
            'busesOnTrip' => (clone $runningTrips)->count(),
            'totalDrivers' => $this->driversQuery()->count(),
            'activeDrivers' => $this->driversQuery()->where('is_active', true)->count(),
            'driversOnTrip' => (clone $runningTrips)
                ->whereNotNull('driver_id')
                ->distinct()
                ->count('driver_id'),
            'liveLocations' => $liveLocations->count(),
            'unreadNotifications' => TenantNotification::query()
                ->where('is_read', false)
                ->count(),
        ];
    }

    /**
     * Trips that are currently running, newest started trip first.
     *
     * @return Collection<int, ActiveTrip>
     */
    public function getRunningTrips(?int $driverId = null): Collection
    {
        $query = $this->runningTripsQuery()
            ->with(['route', 'vehicle', 'driver', 'liveVehicleLocation']);

        if ($driverId) {
            $query = $this->driverScopeService()->scopeTrips($query, $driverId);
        }

        return $query
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Latest location row of every vehicle that reported recently.
     *
     * @return Collection<int, LiveVehicleLocation>
     */
    public function getLiveLocations(?int $driverId = null): Collection
    {
        $query = LiveVehicleLocation::query()
            ->with(['vehicle', 'activeTrip.route']);

        if ($driverId) {
            $query = $this->driverScopeService()->scopeLiveLocations($query, $driverId);
        }

        return $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, TenantNotification>
     */
    public function getRecentNotifications(): Collection
    {
        return TenantNotification::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();
    }

    /**
     * Every alert the admin should look at, most severe first.
     *
     * @return Collection<int, array>
     */
    public function getAlerts(?int $driverId = null): Collection
    {
        $alerts = collect();

        foreach ($this->getLocationAlerts($driverId) as $alert) {
            $alerts->push($alert);
        }

        // A driver only needs their own licence alert, not the whole fleet's.
        foreach ($this->getLicenseAlerts($driverId) as $alert) {
            $alerts->push($alert);
        }

        foreach ($this->getNotificationAlerts() as $alert) {
            $alerts->push($alert);
        }

        return $alerts
            ->sortBy('sort_order')
            ->values();
    }

    public function staleCutoff(): \Illuminate\Support\Carbon
    {
        return now()->subMinutes(self::LIVE_LOCATION_STALE_MINUTES);
    }

    private function driverScopeService(): DriverScopeService
    {
        return app(DriverScopeService::class);
    }

    private function runningTripsQuery(): Builder
    {
        return ActiveTrip::query()->where('trip_status', 'started');
    }

    private function driversQuery(): Builder
    {
        return AdminAndDriver::query()->where('user_role', 'cab drivers');
    }

    /**
     * Running trips with a missing or outdated location.
     *
     * @return Collection<int, array>
     */
    private function getLocationAlerts(?int $driverId = null): Collection
    {
        return $this->getRunningTrips($driverId)
            ->map(function (ActiveTrip $trip) {
                $location = $trip->liveVehicleLocation;
                $label = $trip->display_name ?: ('Trip #' . $trip->id);

                if (! $location) {
                    return [
                        'sort_order' => 1,
                        'severity' => 'danger',
                        'severity_label' => 'No Location',
                        'title' => 'Running trip is not reporting location',
                        'detail' => $label,
                        'occurred_at' => $trip->started_at,
                        'action_url' => route('active-trips.show', $trip),
                        'action_label' => 'View Trip',
                        'action_module' => 'active-trips',
                    ];
                }

                if (! $location->recorded_at || $location->recorded_at->lt($this->staleCutoff())) {
                    return [
                        'sort_order' => 1,
                        'severity' => 'danger',
                        'severity_label' => 'Stale Location',
                        'title' => 'Vehicle location is out of date',
                        'detail' => $label . ' - last update '
                            . ($location->recorded_at ? $location->recorded_at->format('d-m-Y H:i') : 'unknown'),
                        'occurred_at' => $location->recorded_at,
                        'action_url' => route('live-vehicle-locations.index'),
                        'action_label' => 'Open Live Map',
                        'action_module' => 'live-vehicle-locations',
                    ];
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * Driver licences that expired or expire soon.
     *
     * @return Collection<int, array>
     */
    private function getLicenseAlerts(?int $driverId = null): Collection
    {
        return $this->driversQuery()
            ->when($driverId, fn ($query) => $query->whereKey($driverId))
            ->where('is_active', true)
            ->whereNotNull('license_expiry_date')
            ->whereDate('license_expiry_date', '<=', now()->addDays(self::LICENSE_EXPIRY_ALERT_DAYS))
            ->orderBy('license_expiry_date')
            ->get()
            ->map(function (AdminAndDriver $driver) {
                $expiryDate = $driver->license_expiry_date;
                $isExpired = $expiryDate->isBefore(today());

                return [
                    'sort_order' => $isExpired ? 1 : 2,
                    'severity' => $isExpired ? 'danger' : 'warning',
                    'severity_label' => $isExpired ? 'Expired Licence' : 'Licence Expiring',
                    'title' => $isExpired
                        ? 'Driver licence has expired'
                        : 'Driver licence expiring in ' . (int) ceil(now()->diffInDays($expiryDate, false)) . ' day(s)',
                    'detail' => $driver->full_name . ($driver->license_number ? ' - ' . $driver->license_number : '')
                        . ' - expires ' . $expiryDate->format('d-m-Y'),
                    'occurred_at' => null,
                    'action_url' => route('admin-and-drivers.show', $driver),
                    'action_label' => 'View Driver',
                    'action_module' => 'admin-and-drivers',
                ];
            });
    }

    /**
     * Unread notifications from the notifications table.
     *
     * @return Collection<int, array>
     */
    private function getNotificationAlerts(): Collection
    {
        return TenantNotification::query()
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (TenantNotification $notification) {
                return [
                    'sort_order' => 3,
                    'severity' => 'warning',
                    'severity_label' => 'Unread Alert',
                    'title' => $notification->title ?: 'Unread notification',
                    'detail' => str($notification->message)->limit(140)->toString(),
                    'occurred_at' => $notification->created_at,
                    'action_url' => route('active-trips.index'),
                    'action_label' => 'Review Trips',
                    'action_module' => 'active-trips',
                ];
            });
    }
}