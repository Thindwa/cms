<?php

namespace App\Core\Support;

use App\Core\Contracts\ModuleInterface;
use Illuminate\Routing\Router;

/**
 * Placeholder module for future features. Shows a "Coming soon" page and adds a sidebar entry.
 */
class PlaceholderModule implements ModuleInterface
{
    public function __construct(
        protected string $moduleName,
        protected string $moduleLabel,
        protected string $routePrefix,
        protected array $menuItems = [],
    ) {
        if (empty($this->menuItems)) {
            $this->menuItems = [
                ['label' => 'Coming soon', 'route' => 'modules.' . $this->moduleName . '.placeholder', 'permission' => null],
            ];
        }
    }

    public function name(): string
    {
        return $this->moduleName;
    }

    public function label(): string
    {
        return $this->moduleLabel;
    }

    public function registerRoutes(Router $router): void
    {
        $router->get($this->routePrefix, function () {
            return view('placeholders.coming-soon', [
                'moduleLabel' => $this->moduleLabel,
                'moduleName' => $this->moduleName,
            ]);
        })
            ->name('modules.' . $this->moduleName . '.placeholder')
            ->middleware('auth');
    }

    public function permissions(): array
    {
        return [];
    }

    public function menuItems(): array
    {
        return $this->menuItems;
    }
}
