<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function __construct(
        protected AuditService $audit
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'Username is required.',
            'password.required' => 'Password is required.',
        ]);

        $remember = (bool) ($validated['remember'] ?? false);
        $loginInput = trim($validated['username']);
        $password = $validated['password'];

        $user = null;
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($loginInput)])
                ->first();
        } else {
            $query = User::query();
            if ($query->getConnection()->getDriverName() === 'mysql') {
                $user = $query->whereRaw('BINARY username = ?', [$loginInput])->first();
            } else {
                // PostgreSQL/string compare is case-sensitive by default.
                $user = $query->where('username', $loginInput)->first();
            }
        }

        $authenticated = $user !== null && Hash::check($password, $user->password);
        if ($authenticated) {
            Auth::login($user, $remember);
        }

        if (! $authenticated) {
            $this->safeAudit('auth.login.failed', [
                'username' => $validated['username'],
            ]);

            throw ValidationException::withMessages([
                'username' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();
        $this->safeAudit('auth.login.success');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->safeAudit('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function safeAudit(string $action, ?array $newValues = null): void
    {
        try {
            $this->audit->log($action, null, null, null, $newValues);
        } catch (Throwable $e) {
            Log::warning('Audit logging failed during auth flow', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
