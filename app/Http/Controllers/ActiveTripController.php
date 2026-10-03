<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ActiveTrip;
use App\Models\Tenant\AdminAndDriver;
use App\Models\Tenant\Vehicle;
use App\Models\Tenant\VehicleRoute;
use App\Services\ActiveTripService;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ActiveTripController extends Controller
{
    public function __construct(
        private ActiveTripService $activeTripService,
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

        return view('active_trips.index', [
            'activeTrips' => $this->activeTripService->getPaginatedActiveTrips(
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
        return view('active_trips.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['created_by_id'] = $this->currentAdminAndDriverId($request);

        $this->activeTripService->createActiveTrip($validated);

        return redirect()
            ->route('active-trips.index')
            ->with('success', 'Active trip created successfully.');
    }

    public function show(ActiveTrip $activeTrip): View
    {
        $activeTrip->load(['route', 'vehicle', 'driver', 'createdBy', 'updatedBy']);

        return view('active_trips.show', [
            'activeTrip' => $activeTrip,
        ]);
    }

    public function edit(ActiveTrip $activeTrip): View
    {
        return view('active_trips.edit', array_merge(
            ['activeTrip' => $activeTrip],
            $this->formData($activeTrip)
        ));
    }

    public function update(Request $request, ActiveTrip $activeTrip): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['updated_by_id'] = $this->currentAdminAndDriverId($request);

        $this->activeTripService->updateActiveTrip($activeTrip, $validated);

        return redirect()
            ->route('active-trips.index')
            ->with('success', 'Active trip updated successfully.');
    }

    public function destroy(ActiveTrip $activeTrip): RedirectResponse
    {
        $this->activeTripService->deleteActiveTrip($activeTrip);

        return redirect()
            ->route('active-trips.index')
            ->with('success', 'Active trip deleted successfully.');
    }

    private function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:tenant.vehicle_routes,id'],
            'vehicle_id' => ['required', 'integer', 'exists:tenant.vehicles,id'],
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('tenant.admin_and_drivers', 'id')
                    ->where('user_role', 'cab drivers')
                    ->where('is_active', true),
            ],
            'trip_date' => ['required', 'date'],
            'trip_status' => ['required', Rule::in(['started', 'finished', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function formData(?ActiveTrip $activeTrip = null): array
    {
        return [
            'routes' => VehicleRoute::query()
                ->where(function ($query) use ($activeTrip) {
                    $query->where('is_active', true);

                    if ($activeTrip && $activeTrip->route_id) {
                        $query->orWhere('id', $activeTrip->route_id);
                    }
                })
                ->orderBy('route_name')
                ->get(),
            'vehicles' => Vehicle::query()
                ->where(function ($query) use ($activeTrip) {
                    $query->where('is_active', true);

                    if ($activeTrip && $activeTrip->vehicle_id) {
                        $query->orWhere('id', $activeTrip->vehicle_id);
                    }
                })
                ->orderBy('vehicle_number')
                ->get(),
            'drivers' => AdminAndDriver::query()
                ->where(function ($query) use ($activeTrip) {
                    $query->where('is_active', true)
                        ->where('user_role', 'cab drivers');

                    if ($activeTrip && $activeTrip->driver_id) {
                        $query->orWhere('id', $activeTrip->driver_id);
                    }
                })
                ->orderBy('full_name')
                ->get(),
            'tripStatuses' => [
                'started' => 'Started',
                'finished' => 'Finished',
                'cancelled' => 'Cancelled',
            ],
        ];
    }
}
