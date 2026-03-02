<?php

namespace App\Core\Contracts;

use Illuminate\Routing\Router;

interface ModuleInterface
{
    /**
     * Module identifier (e.g. 'case_management').
     */
    public function name(): string;

    /**
     * Human-readable label for menus.
     */
    public function label(): string;

    /**
     * Register module web routes.
     */
    public function registerRoutes(Router $router): void;

    /**
     * Permissions defined by this module ['permission.name' => 'Description'].
     *
     * @return array<string, string>
     */
    public function permissions(): array;

    /**
     * Sidebar menu items. Each item: ['label' => string, 'route' => string, 'permission' => string|null, 'children' => array].
     *
     * @return array<int, array<string, mixed>>
     */
    public function menuItems(): array;
}
