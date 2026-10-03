<?php

namespace App\Http\Controllers;

use App\Models\Tenant\ClassSection;
use App\Models\Tenant\Stop;
use App\Models\Tenant\Student;
use App\Models\Tenant\VehicleRoute;
use App\Services\DriverScopeService;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService,
        private DriverScopeService $driverScopeService
    ) {
    }
    public function classSections(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        return response()->json(
            ClassSection::query()
                ->where('is_active', true)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('class_name', 'like', "%{$search}%")
                            ->orWhere('section', 'like', "%{$search}%");
                    });
                })
                ->orderBy('class_name')
                ->orderBy('section')
                ->limit(10)
                ->get()
                ->map(fn (ClassSection $classSection) => [
                    'id' => $classSection->id,
                    'text' => $classSection->display_name,
                ])
                ->values()
        );
    }

    public function students(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();
        $classSectionId = $request->integer('class_section_id');

        return response()->json(
            Student::query()
                ->where('is_active', true)
                ->when($classSectionId, fn ($query) => $query->where('class_section_id', $classSectionId))
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('full_name', 'like', "%{$search}%")
                            ->orWhere('admission_no', 'like', "%{$search}%");
                    });
                })
                ->orderBy('full_name')
                ->limit(10)
                ->get()
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'text' => $student->full_name . ($student->admission_no ? ' - ' . $student->admission_no : ''),
                ])
                ->values()
        );
    }

    public function routes(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $query = VehicleRoute::query()
            ->withMax('stops', 'stop_order')
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('route_name', 'like', "%{$search}%")
                        ->orWhere('route_code', 'like', "%{$search}%");
                });
            });

        // A driver may only pick a route that is assigned to them.
        $driverRouteIds = $this->driverRouteIds();

        if ($driverRouteIds !== null) {
            $query->whereIn('id', $driverRouteIds);
        }

        return response()->json(
            $query
                ->orderBy('route_name')
                ->limit(10)
                ->get()
                ->map(fn (VehicleRoute $route) => [
                    'id' => $route->id,
                    'text' => $route->display_name,
                    'next_order' => ((int) $route->stops_max_stop_order) + 1,
                ])
                ->values()
        );
    }

    public function stops(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();
        $routeId = $request->integer('route_id');
        $selectedId = $request->integer('selected_id');

        $query = Stop::query()
            ->with('route')
            ->where(function ($query) use ($selectedId) {
                $query->where('is_active', true);

                if ($selectedId) {
                    $query->orWhere('id', $selectedId);
                }
            })
            ->when($routeId, fn ($query) => $query->where('route_id', $routeId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('stop_name', 'like', "%{$search}%")
                        ->orWhere('stop_code', 'like', "%{$search}%");
                });
            });

        // A driver may only pick a stop that sits on one of their own routes.
        $driverRouteIds = $this->driverRouteIds();

        if ($driverRouteIds !== null) {
            $query->whereIn('route_id', $driverRouteIds);
        }

        return response()->json(
            $query
                ->orderBy('stop_order')
                ->orderBy('stop_name')
                ->limit(10)
                ->get()
                ->map(fn (Stop $stop) => [
                    'id' => $stop->id,
                    'text' => $stop->stop_name . ($stop->route ? ' - ' . $stop->route->route_name : ''),
                ])
                ->values()
        );
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
