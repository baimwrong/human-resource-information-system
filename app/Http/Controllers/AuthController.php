<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated. Please contact HR.']);
            }

            $user->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            AuditLogger::log(
                action: 'user_login',
                module: 'auth',
                modelType: User::class,
                modelId: $user->id
            );

            return redirect()->intended(route('dashboard'))->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if ($user = Auth::user()) {
            AuditLogger::log(
                action: 'user_logout',
                module: 'auth',
                modelType: User::class,
                modelId: $user->id
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out.');
    }

    public function profile()
    {
        $user = Auth::user()->load('employee.department', 'employee.designation');
        return view('auth.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        if ($user->employee) {
            $names = explode(' ', $validated['name'], 2);
            $user->employee->update([
                'first_name' => $names[0] ?? '',
                'last_name' => $names[1] ?? '',
            ]);
        }

        AuditLogger::log(
            action: 'updated_profile',
            module: 'auth',
            modelType: User::class,
            modelId: $user->id,
            changes: $validated
        );

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::log(
            action: 'updated_password',
            module: 'auth',
            modelType: User::class,
            modelId: $user->id
        );

        return back()->with('success', 'Password updated successfully.');
    }
}
