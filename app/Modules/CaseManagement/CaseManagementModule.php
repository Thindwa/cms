<?php

namespace App\Modules\CaseManagement;

use App\Core\Contracts\ModuleInterface;
use Illuminate\Routing\Router;

class CaseManagementModule implements ModuleInterface
{
    public function name(): string
    {
        return 'case_management';
    }

    public function label(): string
    {
        return 'Case Management';
    }

    public function registerRoutes(Router $router): void
    {
        $router->prefix('cases')->name('cases.')->middleware('auth')->group(function () use ($router) {
            $router->get('/', [\App\Modules\CaseManagement\Controllers\CaseController::class, 'index'])->name('index');
            $router->get('create', [\App\Modules\CaseManagement\Controllers\CaseController::class, 'create'])->name('create');
            $router->post('/', [\App\Modules\CaseManagement\Controllers\CaseController::class, 'store'])->name('store');
            $router->get('reports', [\App\Modules\CaseManagement\Controllers\ReportController::class, 'index'])->name('reports');
            $router->post('{case}/documents', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'store'])->name('documents.store');
            $router->get('{case}/documents/{document}/download', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'download'])->name('documents.download');
            $router->post('{case}/notes', [\App\Modules\CaseManagement\Controllers\CaseNoteController::class, 'store'])->name('notes.store');
            $router->get('{case}', [\App\Modules\CaseManagement\Controllers\CaseController::class, 'show'])->name('show');
            $router->get('{case}/edit', [\App\Modules\CaseManagement\Controllers\CaseController::class, 'edit'])->name('edit');
            $router->put('{case}', [\App\Modules\CaseManagement\Controllers\CaseController::class, 'update'])->name('update');
        });
    }

    public function permissions(): array
    {
        return [
            'cases.view' => 'View cases',
            'cases.create' => 'Register new case',
            'cases.edit' => 'Edit case',
            'cases.assign' => 'Assign officer to case',
            'reports.view' => 'View reports',
            'reports.export' => 'Export reports',
        ];
    }

    public function menuItems(): array
    {
        return [
            ['label' => 'Register Case', 'route' => 'cases.create', 'permission' => 'cases.create'],
            ['label' => 'Case List', 'route' => 'cases.index', 'permission' => 'cases.view'],
            ['label' => 'Reports', 'route' => 'cases.reports', 'permission' => 'reports.view'],
        ];
    }
}
