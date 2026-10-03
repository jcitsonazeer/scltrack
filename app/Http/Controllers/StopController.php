<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Stop;
use App\Models\Tenant\VehicleRoute;
use App\Services\DriverScopeService;
use App\Services\RolePermissionService;
use App\Services\StopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StopController extends Controller
{
    public function __construct(
        private StopService $stopService,
        private RolePermissionService $rolePermissionService,
        private DriverScopeService $driverScopeService
    ) {
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [5, 10, 25, 50], true)) {
            $perPage = 10;
        }

        return view('stops.index', [
            'stops' => $this->stopService->getPaginatedStops($search, $perPage, $this->rolePermissionService->driverScopeId()),
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('stops.create', [
            'routes' => $this->getRoutesForForm(),
            'hasAssignedRoutes' => $this->driverHasAssignedRoutes(),
            'requireCoordinates' => $this->rolePermissionService->isDriver(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $this->guardDriverRoute((int) $validated['route_id']);
        $validated['created_by_id'] = $this->currentAdminAndDriverId($request);

        $this->stopService->createStop($validated);

        return redirect()
            ->route('stops.index')
            ->with('success', 'Stop created successfully.');
    }

    public function show(Stop $stop): View
    {
        $stop->load(['route', 'createdBy', 'updatedBy']);

        return view('stops.show', [
            'stop' => $stop,
        ]);
    }

    public function edit(Stop $stop): View
    {
        return view('stops.edit', [
            'stop' => $stop,
            'routes' => $this->getRoutesForForm($stop),
            'requireCoordinates' => $this->rolePermissionService->isDriver(),
        ]);
    }

    public function update(Request $request, Stop $stop): RedirectResponse
    {
        $validated = $request->validate($this->rules($stop->id));
        $this->guardDriverRoute((int) $validated['route_id']);
        $validated['updated_by_id'] = $this->currentAdminAndDriverId($request);

        $this->stopService->updateStop($stop, $validated);

        return redirect()
            ->route('stops.index')
            ->with('success', 'Stop updated successfully.');
    }

    public function destroy(Stop $stop): RedirectResponse
    {
        $this->stopService->deleteStop($stop);

        return redirect()
            ->route('stops.index')
            ->with('success', 'Stop deleted successfully.');
    }

    private function rules(?int $stopId = null): array
    {
        // A driver captures the coordinates on the device, so they are required
        // for a driver and stay optional for the office roles.
        $latitudeRules = ['nullable', 'numeric', 'between:-90,90'];
        $longitudeRules = ['nullable', 'numeric', 'between:-180,180'];

        if ($this->rolePermissionService->isDriver()) {
            $latitudeRules = ['required', 'numeric', 'between:-90,90'];
            $longitudeRules = ['required', 'numeric', 'between:-180,180'];
        }

        return [
            'route_id' => ['required', 'integer', 'exists:tenant.vehicle_routes,id'],
            'stop_name' => ['required', 'string', 'max:255'],
            'latitude' => $latitudeRules,
            'longitude' => $longitudeRules,
            'stop_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('tenant.stops', 'stop_order')
                    ->where(fn ($query) => $query->where('route_id', request('route_id')))
                    ->ignore($stopId),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function getRoutesForForm(?Stop $stop = null)
    {
        $query = VehicleRoute::query()
            ->withMax('stops', 'stop_order')
            ->where(function ($query) use ($stop) {
                $query->where('is_active', true);

                if ($stop && $stop->route_id) {
                    $query->orWhere('id', $stop->route_id);
                }
            });

        // A driver only sees the routes assigned to them.
        $driverRouteIds = $this->driverRouteIds();

        if ($driverRouteIds !== null) {
            $query->whereIn('id', $driverRouteIds);
        }

        return $query->orderBy('route_name')->get();
    }

    /**
     * A driver may only add or move a stop on a route assigned to them, so the
     * form is limited to those routes and the posted route id is rechecked.
     */
    private function guardDriverRoute(int $routeId): void
    {
        $driverRouteIds = $this->driverRouteIds();

        if ($driverRouteIds === null) {
            return;
        }

        if (! in_array($routeId, $driverRouteIds, true)) {
            throw ValidationException::withMessages([
                'route_id' => 'You can only work with stops on a route assigned to you.',
            ]);
        }
    }

    private function driverHasAssignedRoutes(): bool
    {
        $driverRouteIds = $this->driverRouteIds();

        return $driverRouteIds === null || $driverRouteIds !== [];
    }

    /**
     * Assigned route ids of the logged in driver, or null when the user is not
     * a driver. An empty array means "no route assigned", which must return no
     * routes at all rather than every route.
     *
     * @return array<int, int>|null
     */
    private function driverRouteIds(): ?array
    {
        $user = $this->rolePermissionService->currentUser();

        if (! $this->rolePermissionService->isDriver($user)) {
            return null;
        }

        return $this->driverScopeService->routeIds((int) $user->id);
    }
}
