<?php

/*
|--------------------------------------------------------------------------
| Role Based Access Control Matrix
|--------------------------------------------------------------------------
|
| Each role lists the modules it may reach, together with the actions it is
| allowed to perform on them.
|
| Allowed actions: view, create, update, delete
|
| "view" must be granted whenever any other action is granted, because the
| index / show pages are needed to reach the create, update and delete forms.
|
*/

return [

    'modules' => [
        'dashboard',
        'parents',
        'students',
        'class-sections',
        'vehicles',
        'vehicle-routes',
        'stops',
        'student-route-assignments',
        'active-trips',
        'live-vehicle-locations',
        'vehicle-location-history',
        'admin-and-drivers',
    ],

    'actions' => [
        'view',
        'create',
        'update',
        'delete',
    ],

    'roles' => [

        // Full control over everything.
        'super admin' => [
            'dashboard' => ['view'],
            'parents' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'create', 'update', 'delete'],
            'class-sections' => ['view', 'create', 'update', 'delete'],
            'vehicles' => ['view', 'create', 'update', 'delete'],
            'vehicle-routes' => ['view', 'create', 'update', 'delete'],
            'stops' => ['view', 'create', 'update', 'delete'],
            'student-route-assignments' => ['view', 'create', 'update', 'delete'],
            'active-trips' => ['view', 'create', 'update', 'delete'],
            'live-vehicle-locations' => ['view', 'create', 'update', 'delete'],
            'vehicle-location-history' => ['view', 'create', 'update', 'delete'],
            'admin-and-drivers' => ['view', 'create', 'update', 'delete'],
        ],

        // Full operational control, but cannot delete user accounts.
        'transport admin' => [
            'dashboard' => ['view'],
            'parents' => ['view', 'create', 'update', 'delete'],
            'students' => ['view', 'create', 'update', 'delete'],
            'class-sections' => ['view', 'create', 'update', 'delete'],
            'vehicles' => ['view', 'create', 'update', 'delete'],
            'vehicle-routes' => ['view', 'create', 'update', 'delete'],
            'stops' => ['view', 'create', 'update', 'delete'],
            'student-route-assignments' => ['view', 'create', 'update', 'delete'],
            'active-trips' => ['view', 'create', 'update', 'delete'],
            'live-vehicle-locations' => ['view', 'create', 'update', 'delete'],
            'vehicle-location-history' => ['view', 'create', 'update', 'delete'],
            'admin-and-drivers' => ['view', 'create', 'update'],
        ],

        // Day to day data entry. Can add and correct records, cannot delete.
        // Stop coordinates are not editable here, they come from the driver.
        'data entry staff' => [
            'dashboard' => ['view'],
            'parents' => ['view', 'create', 'update'],
            'students' => ['view', 'create', 'update'],
            'class-sections' => ['view', 'create', 'update'],
            'vehicles' => ['view', 'create', 'update'],
            'vehicle-routes' => ['view', 'create', 'update'],
            'stops' => ['view'],
            'student-route-assignments' => ['view', 'create', 'update'],
            'active-trips' => ['view', 'create', 'update'],
            'live-vehicle-locations' => ['view', 'create', 'update'],
            'vehicle-location-history' => ['view', 'create', 'update'],
        ],

        // Assistant follows trips and locations, read only.
        'cab assistant' => [
            'dashboard' => ['view'],
            'active-trips' => ['view'],
            'live-vehicle-locations' => ['view'],
            'vehicle-location-history' => ['view'],
            'stops' => ['view'],
        ],

        // Driver only sees the trips, locations and stops of their own assignment.
        // Stops are created and their coordinates captured on the assigned
        // routes only, either from the stop form or from the driver portal.
        'cab drivers' => [
            'dashboard' => ['view'],
            'active-trips' => ['view'],
            'live-vehicle-locations' => ['view'],
            'vehicle-location-history' => ['view'],
            'stops' => ['view', 'create', 'update'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Route Name Mapping
    |--------------------------------------------------------------------------
    |
    | Every named route resolves to one module and one action, so the
    | middleware can stay generic instead of repeating itself per route.
    |
    */
    'route_actions' => [
        'index' => 'view',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules That Are Scoped To The Logged In Driver
    |--------------------------------------------------------------------------
    |
    | For these modules a "cab drivers" user only ever sees rows belonging to
    | their own driver route assignment or their own trips.
    |
    */
    'driver_scoped_modules' => [
        'active-trips',
        'live-vehicle-locations',
        'vehicle-location-history',
        'stops',
    ],

];