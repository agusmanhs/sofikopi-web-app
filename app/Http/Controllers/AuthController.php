<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function showLogin()
    {
        return view('pages.authentications.auth-login-basic');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            
            // Debugging: Log status login (Bisa dihapus nanti jika sudah oke)
            \Illuminate\Support\Facades\Log::info("Login Attempt: {$user->email} | Has Pegawai: " . ($user->pegawai ? 'Yes' : 'No') . " | Status Opt: " . ($user->pegawai?->status_aktif === false ? 'Non-Aktif' : 'Aktif'));

            // Check if user is an employee and is active
            if ($user->pegawai && $user->pegawai->status_aktif == false) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator untuk informasi lebih lanjut.')->onlyInput('email');
            }

            $request->session()->regenerate();
            
            // Log aktivitas login
            $this->activityLogService->logLogin();
            
            // Redirect ke dashboard
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        // Log aktivitas logout sebelum logout
        $this->activityLogService->logLogout();
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}

