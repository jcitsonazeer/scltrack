<?php

namespace App\Services;

use App\Models\Tenant\Stop;
use App\Models\Tenant\VehicleRoute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StopService
{
    /**
     * When $driverId is given the list is limited to the stops on that driver's routes.
     */
    public function getPaginatedStops(?string $search = null, int $perPage = 10, ?int $driverId = null): LengthAwarePaginator
    {
        $query = Stop::query()->with('route');

        if ($driverId) {
            $query = app(DriverScopeService::class)->scopeStops($query, $driverId);
        }

        return $query
            ->when($search, function ($query, $searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('stop_name', 'like', '%' . $searchText . '%')
                        ->orWhere('stop_code', 'like', '%' . $searchText . '%')
                        ->orWhereHas('route', function ($routeQuery) use ($searchText) {
                            $routeQuery->where('route_name', 'like', '%' . $searchText . '%')
                                ->orWhere('route_code', 'like', '%' . $searchText . '%')
                                ->orWhere('trip_type', 'like', '%' . $searchText . '%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createStop(array $data): Stop
    {
        return Stop::create($this->prepareData($data, true));
    }

    public function updateStop(Stop $stop, array $data): Stop
    {
        $data['stop_id'] = $stop->id;

        $stop->update($this->prepareData($data, false));

        return $stop->fresh();
    }

    public function deleteStop(Stop $stop): void
    {
        if ($stop->studentRouteAssignments()->exists()) {
            throw ValidationException::withMessages([
                'stop' => 'This stop is already used in student route assignments..first remove those assignments and then delete the stop',
            ]);
        }

        $stop->delete();
    }

    private function prepareData(array $data, bool $isCreate): array
    {
        $prepared = [
            'route_id' => $data['route_id'],
            'stop_name' => trim($data['stop_name']),
            'stop_code' => $this->generateStopCode($data, $isCreate),
            'latitude' => $data['latitude'] !== null && $data['latitude'] !== '' ? $data['latitude'] : null,
            'longitude' => $data['longitude'] !== null && $data['longitude'] !== '' ? $data['longitude'] : null,
            'stop_order' => (int) ($data['stop_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'updated_by_id' => $data['updated_by_id'] ?? null,
        ];

        if ($isCreate) {
            $prepared['created_by_id'] = $data['created_by_id'] ?? null;
        }

        return $prepared;
    }

    private function generateStopCode(array $data, bool $isCreate): string
    {
        $route = VehicleRoute::find($data['route_id']);
        $routeName = $route?->route_name ?: 'route';
        $stopName = trim($data['stop_name']);
        $currentStopId = $data['stop_id'] ?? null;

        if (! $isCreate && $currentStopId) {
            $currentStop = Stop::find($currentStopId);

            if ($currentStop
                && $currentStop->route_id == $data['route_id']
                && strcasecmp($currentStop->stop_name, $stopName) === 0
                && $currentStop->stop_code) {
                return $currentStop->stop_code;
            }
        }

        $baseCode = $this->makeCodeBase($routeName, $stopName);
        $code = $baseCode;
        $number = 1;

        while ($this->stopCodeExists($code, $currentStopId)) {
            $code = $baseCode . $number;
            $number++;
        }

        return $code;
    }

    private function makeCodeBase(string $routeName, string $stopName): string
    {
        $routePart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($routeName)), 0, 3));
        $routePart = str_pad($routePart ?: 'RTE', 3, 'X');

        $stopWords = preg_split('/\s+/', trim(Str::ascii($stopName)));
        $stopPart = '';

        if (count($stopWords) === 1) {
            $stopPart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $stopWords[0]), 0, 3));
        } else {
            foreach ($stopWords as $word) {
                $cleanWord = preg_replace('/[^A-Za-z0-9]/', '', $word);

                if ($cleanWord !== '') {
                    $stopPart .= strtoupper(substr($cleanWord, 0, 1));
                }
            }
        }

        if ($stopPart === '') {
            $stopPart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($stopName)), 0, 3));
        }

        return $routePart . ($stopPart ?: 'STP');
    }

    private function stopCodeExists(string $code, ?int $ignoreStopId = null): bool
    {
        return Stop::query()
            ->where('stop_code', $code)
            ->when($ignoreStopId, fn ($query) => $query->whereKeyNot($ignoreStopId))
            ->exists();
    }
}
