<?php

namespace App\Providers;

use App\Core\Support\ModuleRegistry;
use App\Core\Support\PlaceholderModule;
use App\Modules\CaseManagement\CaseManagementModule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, function () {
            $registry = new ModuleRegistry();
            $registry->register(new CaseManagementModule());
            // Base platform: future modules (placeholders until implemented)
            $registry->register(new PlaceholderModule('complaints', 'Complaints', 'complaints'));

            $registry->register(new PlaceholderModule('asset_management', 'Asset Management', 'asset-management'));

            return $registry;
        });
    }

    public function boot(): void
    {
        View::addNamespace('case_management', resource_path('views/modules/case_management'));

        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $registry->registerRoutes($router);
    }
}
