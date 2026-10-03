<?php

namespace App\Services;

use App\Models\Tenant\LiveVehicleLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LiveVehicleLocationService
{
    /**
     * When $driverId is given the list is limited to the locations that driver may see.
     */
    public function getPaginatedLiveLocations(?string $search = null, int $perPage = 10, ?int $driverId = null): LengthAwarePaginator
    {
        $query = LiveVehicleLocation::query()
            ->with(['vehicle', 'activeTrip.route', 'activeTrip.vehicle', 'activeTrip.driver']);

        if ($driverId) {
            $query = app(DriverScopeService::class)->scopeLiveLocations($query, $driverId);
        }

        return $query
            ->when($search, function ($query, $searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('latitude', 'like', '%' . $searchText . '%')
                        ->orWhere('longitude', 'like', '%' . $searchText . '%')
                        ->orWhereHas('vehicle', function ($vehicleQuery) use ($searchText) {
                            $vehicleQuery->where('vehicle_number', 'like', '%' . $searchText . '%')
                                ->orWhere('registration_number', 'like', '%' . $searchText . '%');
                        })
                        ->orWhereHas('activeTrip.route', function ($routeQuery) use ($searchText) {
                            $routeQuery->where('route_name', 'like', '%' . $searchText . '%')
                                ->orWhere('route_code', 'like', '%' . $searchText . '%');
                        });
                });
            })
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createLiveLocation(array $data): LiveVehicleLocation
    {
        return LiveVehicleLocation::create($this->prepareData($data));
    }

    public function updateLiveLocation(LiveVehicleLocation $liveVehicleLocation, array $data): LiveVehicleLocation
    {
        $liveVehicleLocation->update($this->prepareData($data));

        return $liveVehicleLocation->fresh(['vehicle', 'activeTrip']);
    }

    public function deleteLiveLocation(LiveVehicleLocation $liveVehicleLocation): void
    {
        $liveVehicleLocation->delete();
    }

    private function prepareData(array $data): array
    {
        $prepared = [
            'vehicle_id' => $data['vehicle_id'],
            'active_trip_id' => ($data['active_trip_id'] ?? null) ?: null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'speed' => ($data['speed'] ?? null) !== null && $data['speed'] !== '' ? $data['speed'] : null,
            'heading' => ($data['heading'] ?? null) !== null && $data['heading'] !== '' ? $data['heading'] : null,
            'ignition_on' => (bool) ($data['ignition_on'] ?? false),
            'recorded_at' => ($data['recorded_at'] ?? null) ?: now(),
            'updated_by_id' => $data['updated_by_id'] ?? null,
        ];

        if (array_key_exists('created_by_id', $data)) {
            $prepared['created_by_id'] = $data['created_by_id'];
        }

        return $prepared;
    }
}
