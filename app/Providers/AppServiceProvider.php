<?php

namespace App\Providers;

use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Policies\CasePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(CaseModel::class, CasePolicy::class);

        Route::bind('role', fn (string $value) => Role::findOrFail((int) $value));
    }
}
