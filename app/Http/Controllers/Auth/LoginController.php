<?php

namespace App\Http\Controllers\Auth;

use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

        if (! Auth::attempt([
            'username' => $validated['username'],
            'password' => $validated['password'],
        ], (bool) ($validated['remember'] ?? false))) {
            $this->audit->log('auth.login.failed', null, null, null, [
                'username' => $validated['username'],
            ]);

            throw ValidationException::withMessages([
                'username' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();
        $this->audit->log('auth.login.success');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->audit->log('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
