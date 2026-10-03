<?php

namespace App\Services;

use App\Models\Tenant\AdminAndDriver;
use Illuminate\Support\Facades\Session;

class RolePermissionService
{
    /**
     * The role stored on the logged in admin / driver record.
     */
    public function currentRole(): ?string
    {
        return $this->currentUser()?->user_role;
    }

    public function currentUser(): ?AdminAndDriver
    {
        $userId = Session::get('admin_and_driver_id');

        return $userId ? AdminAndDriver::find($userId) : null;
    }

    /**
     * The logged in user is treated as a driver when their role is "cab drivers".
     */
    public function isDriver(?AdminAndDriver $user = null): bool
    {
        $user = $user ?: $this->currentUser();

        return $user?->user_role === 'cab drivers';
    }

    /**
     * The id to scope list queries by, or null when the user is not a driver.
     */
    public function driverScopeId(): ?int
    {
        $user = $this->currentUser();

        return $this->isDriver($user) ? (int) $user->id : null;
    }

    /**
     * May the logged in user perform $action on $module?
     */
    public function allows(?string $module, ?string $action = 'view', ?AdminAndDriver $user = null): bool
    {
        if (! $module) {
            return false;
        }

        $user = $user ?: $this->currentUser();

        if (! $user) {
            return false;
        }

        return in_array($action, $this->grantedActions($user->user_role, $module), true);
    }

    /**
     * Convenience wrapper used by the views and the middleware.
     */
    public function can(string $module, string $action = 'view'): bool
    {
        return $this->allows($module, $action);
    }

    /**
     * Every module the given role may open, in matrix order.
     *
     * @return array<int, string>
     */
    public function allowedModules(?string $role = null): array
    {
        $role = $role ?: $this->currentRole();

        if (! $role) {
            return [];
        }

        $roleModules = $this->roleMatrix($role);
        $modules = (array) config('permissions.modules', []);

        return array_values(array_filter(
            $modules,
            fn (string $module) => in_array('view', $roleModules[$module] ?? [], true)
        ));
    }

    public function isAllowedModule(string $module, ?string $role = null): bool
    {
        return in_array($module, $this->allowedModules($role), true);
    }

    /**
     * Is this module restricted to the logged in driver's own data?
     */
    public function isDriverScoped(string $module): bool
    {
        return in_array($module, (array) config('permissions.driver_scoped_modules', []), true);
    }

    /**
     * True when the role holds the action on at least one of the modules.
     * Used by helper endpoints that serve more than one module.
     *
     * @param  array<int, string>  $modules
     */
    public function allowsAny(array $modules, string $action = 'view'): bool
    {
        foreach ($modules as $module) {
            if ($this->allows($module, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split a route name such as "parents.destroy" into module and action.
     * Returns null when the route is not part of the permission matrix.
     *
     * @return array{module: string, action: string}|array{modules: array<int, string>, action: string}|null
     */
    public function resolveRoute(string $routeName): ?array
    {
        if (! $routeName) {
            return null;
        }

        // Lookup helper endpoints belong to the module they feed. A few of them
        // feed more than one module, for example the route dropdown is used by
        // the stop form of a driver and by the student route assignment form,
        // so they are allowed when the role can view any of those modules.
        if (str_starts_with($routeName, 'lookups.')) {
            $lookupModules = match (true) {
                str_contains($routeName, 'class-sections') => ['class-sections'],
                str_contains($routeName, 'students') => ['students'],
                str_contains($routeName, 'routes') => ['vehicle-routes', 'stops'],
                str_contains($routeName, 'stops') => ['stops'],
                default => [],
            };

            return $lookupModules ? ['modules' => $lookupModules, 'action' => 'view'] : null;
        }

        $parts = explode('.', $routeName);

        if (count($parts) !== 2) {
            return null;
        }

        [$module, $routeAction] = $parts;

        if (! in_array($module, (array) config('permissions.modules', []), true)) {
            return null;
        }

        $action = config('permissions.route_actions', [])[$routeAction] ?? null;

        return $action ? ['module' => $module, 'action' => $action] : null;
    }

    /**
     * Actions granted to a role on a module.
     *
     * @return array<int, string>
     */
    private function grantedActions(?string $role, string $module): array
    {
        if (! $role) {
            return [];
        }

        return (array) ($this->roleMatrix($role)[$module] ?? []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function roleMatrix(string $role): array
    {
        return (array) ((array) config('permissions.roles', []))[$role] ?? [];
    }
}