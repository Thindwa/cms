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
            $router->get('imports', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'index'])->name('imports.index');
            $router->get('imports/create', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'create'])->name('imports.create');
            $router->post('imports', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'store'])->name('imports.store');
            $router->post('imports/reset-selected', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'resetSelected'])->name('imports.resetSelected');
            $router->get('imports/bulk/create', [\App\Modules\CaseManagement\Controllers\BulkCaseImportController::class, 'create'])->name('imports.bulk.create');
            $router->post('imports/bulk', [\App\Modules\CaseManagement\Controllers\BulkCaseImportController::class, 'store'])->name('imports.bulk.store');
            $router->get('imports/bulk/{bulk}', [\App\Modules\CaseManagement\Controllers\BulkCaseImportController::class, 'show'])->name('imports.bulk.show');
            $router->put('imports/bulk/{bulk}/reanalyze', [\App\Modules\CaseManagement\Controllers\BulkCaseImportController::class, 'reanalyze'])->name('imports.bulk.reanalyze');
            $router->post('imports/bulk/{bulk}/start', [\App\Modules\CaseManagement\Controllers\BulkCaseImportController::class, 'start'])->name('imports.bulk.start');
            $router->get('imports/bulk/{bulk}/progress', [\App\Modules\CaseManagement\Controllers\BulkCaseImportController::class, 'progress'])->name('imports.bulk.progress');
            $router->get('imports/{import}', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'show'])->name('imports.show');
            $router->put('imports/{import}/reanalyze', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'reanalyze'])->name('imports.reanalyze');
            $router->post('imports/{import}/dry-run', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'dryRun'])->name('imports.dryRun');
            $router->post('imports/{import}/execute', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'execute'])->name('imports.execute');
            $router->post('imports/{import}/rollback', [\App\Modules\CaseManagement\Controllers\CaseImportController::class, 'rollback'])->name('imports.rollback');
            $router->post('{case}/documents', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'store'])->name('documents.store');
            $router->get('{case}/documents/{document}/download', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'download'])->name('documents.download');
            $router->delete('{case}/documents/{document}', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'destroy'])->name('documents.destroy');
            $router->post('{case}/documents/{document}/restore', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'restore'])->name('documents.restore');
            $router->get('documents/recycle-bin', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'recycleBin'])->name('documents.recycle-bin');
            $router->delete('documents/recycle-bin/{document}', [\App\Modules\CaseManagement\Controllers\CaseDocumentController::class, 'purge'])->name('documents.purge');
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
            'cases.import' => 'Import cases from Excel',
            'reports.view' => 'View reports',
            'reports.export' => 'Export reports',
        ];
    }

    public function menuItems(): array
    {
        return [
            ['label' => 'Case List', 'route' => 'cases.index', 'permission' => 'cases.view', 'icon' => 'bi-list-ul'],
            ['label' => 'Reports', 'route' => 'cases.reports', 'permission' => 'reports.view', 'icon' => 'bi-bar-chart-line'],
            ['label' => 'Excel Imports', 'route' => 'cases.imports.index', 'permission' => 'cases.import', 'icon' => 'bi-file-earmark-spreadsheet'],
            ['label' => 'Recycle Bin', 'route' => 'cases.documents.recycle-bin', 'permission' => 'cases.view', 'icon' => 'bi-recycle'],
        ];
    }
}
