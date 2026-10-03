<?php

namespace App\Http\Middleware;

use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    public function __construct(private RolePermissionService $rolePermissionService)
    {
    }

    public function handle(Request $request, Closure $next, string $module = '', string $action = 'view'): Response
    {
        $route = $request->route();

        // When no module is given as a middleware argument, derive the module
        // and the action from the route name, e.g. "parents.destroy".
        if (! $module) {
            $resolved = $this->rolePermissionService->resolveRoute((string) $route?->getName());

            if (! $resolved) {
                return $next($request);
            }

            $action = $resolved['action'];

            // A helper endpoint may serve several modules, so any of them is
            // enough to open it.
            if (isset($resolved['modules'])) {
                if ($this->rolePermissionService->allowsAny($resolved['modules'], $action)) {
                    return $next($request);
                }

                abort(403, $this->deniedMessage(reset($resolved['modules']), $action));
            }

            $module = $resolved['module'];
        }

        if ($this->rolePermissionService->allows($module, $action)) {
            return $next($request);
        }

        abort(403, $this->deniedMessage($module, $action));
    }

    private function deniedMessage(string $module, string $action): string
    {
        return 'You do not have permission to ' . $action . ' ' . str_replace('-', ' ', $module) . '.';
    }
}