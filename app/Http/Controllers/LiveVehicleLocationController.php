<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ActiveTrip;
use App\Models\Tenant\LiveVehicleLocation;
use App\Models\Tenant\Vehicle;
use App\Services\LiveVehicleLocationService;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LiveVehicleLocationController extends Controller
{
    public function __construct(
        private LiveVehicleLocationService $liveLocationService,
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

        return view('live_vehicle_locations.index', [
            'liveLocations' => $this->liveLocationService->getPaginatedLiveLocations(
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
        return view('live_vehicle_locations.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['created_by_id'] = $this->currentAdminAndDriverId($request);

        $this->liveLocationService->createLiveLocation($validated);

        return redirect()
            ->route('live-vehicle-locations.index')
            ->with('success', 'Live vehicle location created successfully.');
    }

    public function show(LiveVehicleLocation $liveVehicleLocation): View
    {
        $liveVehicleLocation->load(['vehicle', 'activeTrip.route', 'activeTrip.vehicle', 'activeTrip.driver', 'createdBy', 'updatedBy']);

        return view('live_vehicle_locations.show', [
            'liveLocation' => $liveVehicleLocation,
        ]);
    }

    public function edit(LiveVehicleLocation $liveVehicleLocation): View
    {
        return view('live_vehicle_locations.edit', array_merge(
            ['liveLocation' => $liveVehicleLocation],
            $this->formData($liveVehicleLocation)
        ));
    }

    public function update(Request $request, LiveVehicleLocation $liveVehicleLocation): RedirectResponse
    {
        $validated = $request->validate($this->rules($liveVehicleLocation->id));
        $validated['updated_by_id'] = $this->currentAdminAndDriverId($request);

        $this->liveLocationService->updateLiveLocation($liveVehicleLocation, $validated);

        return redirect()
            ->route('live-vehicle-locations.index')
            ->with('success', 'Live vehicle location updated successfully.');
    }

    public function destroy(LiveVehicleLocation $liveVehicleLocation): RedirectResponse
    {
        $this->liveLocationService->deleteLiveLocation($liveVehicleLocation);

        return redirect()
            ->route('live-vehicle-locations.index')
            ->with('success', 'Live vehicle location deleted successfully.');
    }

    private function rules(?int $liveLocationId = null): array
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                'exists:tenant.vehicles,id',
                Rule::unique('tenant.live_vehicle_locations', 'vehicle_id')->ignore($liveLocationId),
            ],
            'active_trip_id' => ['nullable', 'integer', 'exists:tenant.active_trips,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'ignition_on' => ['required', 'boolean'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }

    private function formData(?LiveVehicleLocation $liveLocation = null): array
    {
        return [
            'vehicles' => Vehicle::query()
                ->where(function ($query) use ($liveLocation) {
                    $query->where('is_active', true);

                    if ($liveLocation && $liveLocation->vehicle_id) {
                        $query->orWhere('id', $liveLocation->vehicle_id);
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
