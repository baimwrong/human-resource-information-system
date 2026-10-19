<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'timezone'],
            'work_days_per_month' => ['required', 'integer', 'between:1,31'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLogger::log(
            action: 'updated_system_settings',
            module: 'settings',
            changes: $validated
        );

        return back()->with('success', 'System settings updated successfully.');
    }
}
