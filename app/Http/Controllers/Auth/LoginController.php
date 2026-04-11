<?php

namespace App\Http\Controllers\Auth;

use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $attempts = [
            ['username' => $loginInput, 'password' => $password],
        ];

        // Support email-based login as well to reduce login friction.
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $attempts[] = ['email' => $loginInput, 'password' => $password];
        }

        $authenticated = false;
        foreach ($attempts as $credentials) {
            if (Auth::attempt($credentials, $remember)) {
                $authenticated = true;
                break;
            }
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
