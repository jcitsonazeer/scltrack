<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ActiveTrip;
use App\Models\Tenant\Vehicle;
use App\Models\Tenant\VehicleLocationHistory;
use App\Services\RolePermissionService;
use App\Services\VehicleLocationHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleLocationHistoryController extends Controller
{
    public function __construct(
        private VehicleLocationHistoryService $locationHistoryService,
        private RolePermissionService $rolePermissionService
    ) {
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [5, 10, 25, 50], true)) {
            $perPage = 10;
        }

        return view('vehicle_location_history.index', [
            'locationHistory' => $this->locationHistoryService->getPaginatedLocationHistory(
                $search,
                $perPage,
                $this->rolePermissionService->driverScopeId()
            ),
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('vehicle_location_history.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['created_by_id'] = $this->currentAdminAndDriverId($request);

        $this->locationHistoryService->createLocationHistory($validated);

        return redirect()
            ->route('vehicle-location-history.index')
            ->with('success', 'Vehicle location history created successfully.');
    }

    public function show(VehicleLocationHistory $vehicleLocationHistory): View
    {
        $vehicleLocationHistory->load(['vehicle', 'activeTrip.route', 'activeTrip.vehicle', 'activeTrip.driver', 'createdBy', 'updatedBy']);

        return view('vehicle_location_history.show', [
            'locationHistory' => $vehicleLocationHistory,
        ]);
    }

    public function edit(VehicleLocationHistory $vehicleLocationHistory): View
    {
        return view('vehicle_location_history.edit', array_merge(
            ['locationHistory' => $vehicleLocationHistory],
            $this->formData($vehicleLocationHistory)
        ));
    }

    public function update(Request $request, VehicleLocationHistory $vehicleLocationHistory): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['updated_by_id'] = $this->currentAdminAndDriverId($request);

        $this->locationHistoryService->updateLocationHistory($vehicleLocationHistory, $validated);

        return redirect()
            ->route('vehicle-location-history.index')
            ->with('success', 'Vehicle location history updated successfully.');
    }

    public function destroy(VehicleLocationHistory $vehicleLocationHistory): RedirectResponse
    {
        $this->locationHistoryService->deleteLocationHistory($vehicleLocationHistory);

        return redirect()
            ->route('vehicle-location-history.index')
            ->with('success', 'Vehicle location history deleted successfully.');
    }

    private function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:tenant.vehicles,id'],
            'active_trip_id' => ['nullable', 'integer', 'exists:tenant.active_trips,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'ignition_on' => ['required', 'boolean'],
            'recorded_at' => ['nullable', 'date'],
            'raw_payload' => ['nullable', 'json'],
        ];
    }

    private function formData(?VehicleLocationHistory $vehicleLocationHistory = null): array
    {
        return [
            'vehicles' => Vehicle::query()
                ->where(function ($query) use ($vehicleLocationHistory) {
                    $query->where('is_active', true);

                    if ($vehicleLocationHistory && $vehicleLocationHistory->vehicle_id) {
                        $query->orWhere('id', $vehicleLocationHistory->vehicle_id);
                    }
                })
                ->orderBy('vehicle_number')
                ->get(),
            'activeTrips' => ActiveTrip::query()
                ->with(['route', 'vehicle', 'driver'])
                ->orderByDesc('trip_date')
                ->orderByDesc('id')
                ->get(),
        ];
    }
}
