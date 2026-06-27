<?php

namespace App\Providers;

use App\Core\Settings\SettingsService;
use App\Modules\CaseManagement\Models\CaseCategory;
use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseImportBatch;
use App\Modules\CaseManagement\Models\CaseImportBulkBatch;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Policies\CaseCategoryPolicy;
use App\Modules\CaseManagement\Policies\CaseDocumentPolicy;
use App\Modules\CaseManagement\Policies\CaseImportBatchPolicy;
use App\Modules\CaseManagement\Policies\CaseImportBulkBatchPolicy;
use App\Modules\CaseManagement\Policies\CasePolicy;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once __DIR__ . '/../Helpers/audit.php';
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Password::defaults(fn () => Password::min(10)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised()
        );

        $this->applyMailSettings();
        $this->applyAppSettings();

        Carbon::macro('formatDate', fn () => $this->format(config('app.date_format', 'Y-m-d')));
        Carbon::macro('formatDateTime', fn () => $this->format(config('app.date_format', 'Y-m-d') . ' ' . config('app.time_format', 'H:i')));

        Gate::policy(CaseModel::class, CasePolicy::class);
        Gate::policy(CaseCategory::class, CaseCategoryPolicy::class);
        Gate::policy(CaseDocument::class, CaseDocumentPolicy::class);
        Gate::policy(CaseImportBatch::class, CaseImportBatchPolicy::class);
        Gate::policy(CaseImportBulkBatch::class, CaseImportBulkBatchPolicy::class);

        Route::bind('role', fn (string $value) => Role::findOrFail((int) $value));
    }

    protected function applyMailSettings(): void
    {
        try {
            /** @var SettingsService $settings */
            $settings = $this->app->make(SettingsService::class);

            $mailer = (string) $settings->get('mail_mailer', Config::get('mail.default', 'smtp'));
            $host = (string) $settings->get('smtp_host', Config::get('mail.mailers.smtp.host', ''));
            $port = (int) $settings->get('smtp_port', Config::get('mail.mailers.smtp.port', 587));
            $username = (string) $settings->get('smtp_username', Config::get('mail.mailers.smtp.username', ''));
            $password = (string) $settings->get('smtp_password', Config::get('mail.mailers.smtp.password', ''));
            $encryption = (string) $settings->get('smtp_encryption', Config::get('mail.mailers.smtp.encryption', 'tls'));
            $fromAddress = (string) $settings->get('smtp_from_address', Config::get('mail.from.address', ''));
            $fromName = (string) $settings->get('smtp_from_name', Config::get('mail.from.name', config('app.name')));

            Config::set('mail.default', $mailer ?: 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port ?: 587);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption ?: null);

            if ($fromAddress !== '') {
                Config::set('mail.from.address', $fromAddress);
            }
            if ($fromName !== '') {
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Throwable) {
            // Ignore when settings table is unavailable (install/migrations).
        }
    }

    protected function applyAppSettings(): void
    {
        try {
            /** @var SettingsService $settings */
            $settings = $this->app->make(SettingsService::class);

            Config::set('app.name', (string) $settings->get('app_name', Config::get('app.name')));
            Config::set('app.date_format', (string) $settings->get('date_format', 'Y-m-d'));
            Config::set('app.time_format', (string) $settings->get('time_format', 'H:i'));
            Config::set('app.items_per_page', (int) $settings->get('items_per_page', 15));
        } catch (\Throwable) {
            // Ignore when settings table is unavailable (install/migrations).
        }
    }
}
