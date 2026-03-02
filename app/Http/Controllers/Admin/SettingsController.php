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
        $this->middleware('can:admin.settings');
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
        ]);
        $this->settings->update($validated);
        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
}
