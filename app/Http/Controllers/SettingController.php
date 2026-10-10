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
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLogger::log(
            action: 'updated_system_settings',
            module: 'settings',
            changes: $data
        );

        return back()->with('success', 'System settings updated successfully.');
    }
}
