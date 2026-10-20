<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the internal Sofikopi area (data master, absensi, penjualan, API).
 *
 * Pengguna mitra (mitra_id terisi) hanya boleh memakai portal `mitra-pos/*`.
 * Tanpa gerbang ini mereka lolos ke rute internal yang tidak punya
 * check.permission (mis. /master/mitra memuat data SEMUA mitra, /api/users
 * memuat email semua pengguna) karena CheckPegawaiStatus meloloskan akun
 * tanpa data pegawai.
 *
 * Pengecualian: beranda (`/`, yang mengalihkan pengguna mitra ke dashboard
 * mitranya) dan halaman profil/ganti password milik sendiri.
 */
class EnsureInternalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mitra_id !== null && ! $request->routeIs('dashboard', 'profile.*')) {
            abort(403, 'Halaman ini hanya untuk pengguna internal Sofikopi.');
        }

        return $next($request);
    }
}
