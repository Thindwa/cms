<?php

namespace App\Core\Support;

use App\Core\Contracts\ModuleInterface;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

class ModuleRegistry
{
    /** @var array<string, ModuleInterface> */
    protected array $modules = [];

    public function register(ModuleInterface $module): void
    {
        $this->modules[$module->name()] = $module;
    }

    /**
     * @return Collection<string, ModuleInterface>
     */
    public function all(): Collection
    {
        return collect($this->modules);
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    public function registerRoutes(Router $router): void
    {
        foreach ($this->modules as $module) {
            $router->middleware('web')->group(function () use ($router, $module) {
                $module->registerRoutes($router);
            });
        }
    }

    /**
     * All permissions from all modules ['name' => 'Description'].
     *
     * @return array<string, string>
     */
    public function allPermissions(): array
    {
        $permissions = [];
        foreach ($this->modules as $module) {
            $permissions = array_merge($permissions, $module->permissions());
        }
        return $permissions;
    }

    /**
     * All menu items from all modules (for sidebar).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allMenuItems(): array
    {
        $items = [];
        foreach ($this->modules as $module) {
            $items[] = [
                'label' => $module->label(),
                'route' => '#',
                'permission' => null,
                'children' => $module->menuItems(),
            ];
        }
        return $items;
    }
}
