<?php

namespace App\Http\Controllers\Admin;

use App\Core\Settings\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settings
    ) {
        $this->middleware('can:admin.settings.view')->only(['index']);
        $this->middleware('can:admin.settings.edit')->only(['update']);
    }

    public function index(): View
    {
        $settings = $this->settings->all();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'date_format' => ['required', 'string', 'max:32'],
            'time_format' => ['required', 'string', 'max:32'],
            'items_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'mail_mailer' => ['required', 'string', 'max:32'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'max:32'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            'upcoming_notifications_enabled' => ['nullable', 'boolean'],
            'upcoming_notifications_days' => ['required', 'integer', 'min:1', 'max:60'],
            'upcoming_notifications_time' => ['required', 'date_format:H:i'],
            'dormant_years' => ['required', 'integer', 'min:1', 'max:50'],
            'document_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'document_reminder_days' => ['required', 'integer', 'min:1', 'max:365'],
            'q1_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'q2_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'q3_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'q4_start_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);
        $validated['upcoming_notifications_enabled'] = $request->boolean('upcoming_notifications_enabled') ? 1 : 0;
        $this->settings->update($validated);
        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
}
