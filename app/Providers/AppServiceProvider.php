<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('permission', function (string $expression) {
            return "<?php if (app(\\App\\Services\\RolePermissionService::class)->allows({$expression})): ?>";
        });

        Blade::directive('unlesspermission', function (string $expression) {
            return "<?php if (! app(\\App\\Services\\RolePermissionService::class)->allows({$expression})): ?>";
        });

        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });
    }
}
