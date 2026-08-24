<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->status === UserStatus::PendingVerification) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account is awaiting verification by the Divisional Secretariat.'])->onlyInput('email');
        }

        if ($user->status === UserStatus::Inactive) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been deactivated.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($user->isOfficer() ? route('dashboard') : '/admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
