# Adding a New Module

The CMS is built as a **base platform** that can easily accommodate future modules (e.g. Complaints, HR, Asset Management, Licensing, Finance, Workflow Automation) **without architectural changes**. Each new module is self-contained under `app/Modules/<ModuleName>/` and integrates by implementing the module contract and registering once—no refactoring of Core, ModuleRegistry, or layout is needed.

## 1. Create the module class

Create a class that implements `App\Core\Contracts\ModuleInterface`:

```php
// app/Modules/YourModule/YourModule.php
namespace App\Modules\YourModule;

use App\Core\Contracts\ModuleInterface;
use Illuminate\Routing\Router;

class YourModule implements ModuleInterface
{
    public function name(): string
    {
        return 'your_module';  // unique slug
    }

    public function label(): string
    {
        return 'Your Module';  // sidebar heading
    }

    public function registerRoutes(Router $router): void
    {
        $router->prefix('your-module')->name('your_module.')->middleware('auth')->group(function () use ($router) {
            $router->get('/', [YourController::class, 'index'])->name('index');
            // ... more routes
        });
    }

    public function permissions(): array
    {
        return [
            'your_module.view' => 'View your module',
            'your_module.manage' => 'Manage your module',
        ];
    }

    public function menuItems(): array
    {
        return [
            ['label' => 'List', 'route' => 'your_module.index', 'permission' => 'your_module.view'],
        ];
    }
}
```

## 2. Register the module

In `app/Providers/ModulesServiceProvider.php`:

**In `register()`:** add the module to the registry:

```php
$registry->register(new CaseManagementModule());
$registry->register(new \App\Modules\YourModule\YourModule());  // add this
```

**In `boot()`:** add the view namespace so you can use `view('your_module::path.to.view')`:

```php
View::addNamespace('your_module', resource_path('views/modules/your_module'));
```

## 3. Add permissions to roles

Run your role seeder (or create a seeder) so the new permissions exist and are assigned to roles. The `RolesAndPermissionsSeeder` uses `ModuleRegistry::allPermissions()`, so once your module is registered, its permissions are created when you run:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

(You may need to add the new permission names to the seeder’s role assignments if you use explicit lists.)

## 4. Module structure (optional but recommended)

Keep each module self-contained:

```
app/Modules/YourModule/
  YourModule.php           # implements ModuleInterface
  Controllers/
  Models/
  Services/
  Requests/
  Policies/
  Migrations/              # or use database/migrations with a prefix
  Routes/                  # optional: load from a file instead of registerRoutes()
```

Views go in `resources/views/modules/your_module/`.

## 5. Removing a module (or replacing a placeholder)

1. Remove the `$registry->register(new YourModule());` line from `ModulesServiceProvider`.
2. Remove the `View::addNamespace('your_module', ...)` line.
3. Optionally remove or disable the module’s migrations, or leave tables in place if you might re-enable the module.

**Replacing a placeholder:** When you implement a full module (e.g. Complaints, HR) that currently has a `PlaceholderModule` entry, remove the placeholder registration and add your real module class instead. The sidebar label and URL path can match or differ as needed.

The core (Dashboard, Auth, Audit, Settings, Admin) does not depend on any specific module by name; it only uses the registry. Case Management is Module 1; Complaints, HR, Asset Management, Licensing, Finance, Workflow Automation, and others can be added the same way. Placeholder entries for planned modules are registered in `ModulesServiceProvider` until replaced by full implementations.
