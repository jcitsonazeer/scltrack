<?php

namespace App\Services;

use App\Models\Tenant\ActiveTrip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ActiveTripService
{
    /**
     * When $driverId is given the list is limited to the trips that driver may see.
     */
    public function getPaginatedActiveTrips(?string $search = null, int $perPage = 10, ?int $driverId = null): LengthAwarePaginator
    {
        $query = ActiveTrip::query()->with(['route', 'vehicle', 'driver']);

        if ($driverId) {
            $query = app(DriverScopeService::class)->scopeTrips($query, $driverId);
        }

        return $query
            ->when($search, function ($query, $searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('trip_status', 'like', '%' . $searchText . '%')
                        ->orWhereHas('route', function ($routeQuery) use ($searchText) {
                            $routeQuery->where('route_name', 'like', '%' . $searchText . '%')
                                ->orWhere('route_code', 'like', '%' . $searchText . '%')
                                ->orWhere('trip_type', 'like', '%' . $searchText . '%');
                        })
                        ->orWhereHas('vehicle', function ($vehicleQuery) use ($searchText) {
                            $vehicleQuery->where('vehicle_number', 'like', '%' . $searchText . '%')
                                ->orWhere('registration_number', 'like', '%' . $searchText . '%');
                        })
                        ->orWhereHas('driver', function ($driverQuery) use ($searchText) {
                            $driverQuery->where('full_name', 'like', '%' . $searchText . '%')
                                ->orWhere('phone', 'like', '%' . $searchText . '%');
                        });
                });
            })
            ->orderByDesc('trip_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createActiveTrip(array $data): ActiveTrip
    {
        return ActiveTrip::create($this->prepareData($data));
    }

    public function updateActiveTrip(ActiveTrip $activeTrip, array $data): ActiveTrip
    {
        $activeTrip->update($this->prepareData($data, $activeTrip));

        return $activeTrip->fresh(['route', 'vehicle', 'driver']);
    }

    public function deleteActiveTrip(ActiveTrip $activeTrip): void
    {
        if ($activeTrip->liveVehicleLocation()->exists()
            || $activeTrip->vehicleLocationHistory()->exists()) {
            throw ValidationException::withMessages([
                'active_trip' => 'This trip already has vehicle location records..first remove those records and then delete the trip',
            ]);
        }

        $activeTrip->delete();
    }

    private function prepareData(array $data, ?ActiveTrip $activeTrip = null): array
    {
        $startedAt = $activeTrip?->started_at;
        $endedAt = $activeTrip?->ended_at;

        if ($data['trip_status'] === 'started' && ! $startedAt) {
            $startedAt = now();
        }

        if ($data['trip_status'] === 'started') {
            $endedAt = null;
        }

        if ($data['trip_status'] === 'finished' && ! $endedAt) {
            $endedAt = now();
        }

        $prepared = [
            'route_id' => $data['route_id'] ?: null,
            'vehicle_id' => $data['vehicle_id'] ?: null,
            'driver_id' => $data['driver_id'] ?: null,
            'trip_date' => $data['trip_date'] ?: null,
            'trip_status' => $data['trip_status'],
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => $data['notes'] ?: null,
            'updated_by_id' => $data['updated_by_id'] ?? null,
        ];

        if (array_key_exists('created_by_id', $data)) {
            $prepared['created_by_id'] = $data['created_by_id'];
        }

        return $prepared;
    }
}
