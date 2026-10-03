<?php

namespace App\Services;

use App\Models\Tenant\VehicleLocationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleLocationHistoryService
{
    /**
     * When $driverId is given the list is limited to the history that driver may see.
     */
    public function getPaginatedLocationHistory(?string $search = null, int $perPage = 10, ?int $driverId = null): LengthAwarePaginator
    {
        $query = VehicleLocationHistory::query()
            ->with(['vehicle', 'activeTrip.route', 'activeTrip.vehicle', 'activeTrip.driver']);

        if ($driverId) {
            $query = app(DriverScopeService::class)->scopeLocationHistory($query, $driverId);
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

    public function createLocationHistory(array $data): VehicleLocationHistory
    {
        return VehicleLocationHistory::create($this->prepareData($data));
    }

    public function updateLocationHistory(VehicleLocationHistory $vehicleLocationHistory, array $data): VehicleLocationHistory
    {
        $vehicleLocationHistory->update($this->prepareData($data));

        return $vehicleLocationHistory->fresh(['vehicle', 'activeTrip']);
    }

    public function deleteLocationHistory(VehicleLocationHistory $vehicleLocationHistory): void
    {
        $vehicleLocationHistory->delete();
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
            'raw_payload' => ! empty($data['raw_payload']) ? json_decode($data['raw_payload'], true) : null,
            'updated_by_id' => $data['updated_by_id'] ?? null,
        ];

        if (array_key_exists('created_by_id', $data)) {
            $prepared['created_by_id'] = $data['created_by_id'];
        }

        return $prepared;
    }
}
