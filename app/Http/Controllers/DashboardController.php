<?php

namespace App\Http\Controllers;

use App\Services\AbsensiService;
use App\Services\PegawaiService;
use App\Services\InformasiService;

class DashboardController extends Controller
{
    public function __construct(
        protected AbsensiService $absensiService,
        protected PegawaiService $pegawaiService,
        protected InformasiService $informasiService
    ) {}

    /**
     * Display the dashboard - different view based on role
     */
    public function index()
    {
        $user = auth()->user();
        $roleSlug = $user->role?->slug;

        // Admin dan Superadmin melihat dashboard admin
        if (in_array($roleSlug, ['super-admin', 'admin'])) {
            $tanggal = today()->toDateString();
            $filterRekap = request('filter_rekap', 'today');
            
            // Determine date range for rekap
            $startDate = $tanggal;
            $endDate = $tanggal;
            
            if ($filterRekap === 'yesterday') {
                $startDate = today()->subDay()->toDateString();
                $endDate = $startDate;
            } elseif ($filterRekap === 'week') {
                $startDate = today()->startOfWeek()->toDateString();
                $endDate = today()->endOfWeek()->toDateString();
            }

            $statistikAbsensi = $this->absensiService->getStatistik($tanggal);
            $rekapDivisi = $this->absensiService->getRekapPerDivisi($startDate, $endDate);
            
            $izinService = app(\App\Services\IzinService::class);
            $statistikIzin = $izinService->getStatistik();
            
            $recentAbsensi = \App\Models\Absensi::with(['pegawai', 'shift'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
                
            $recentIzin = \App\Models\Izin::with(['pegawai', 'jenisIzin'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            return view('pages.dashboard.dashboard', compact(
                'statistikAbsensi',
                'rekapDivisi',
                'statistikIzin',
                'recentAbsensi',
                'recentIzin',
                'tanggal',
                'filterRekap'
            ));
        }

        // User biasa (pegawai) melihat dashboard khusus user
        $pegawai = $this->pegawaiService->getByUserId($user->id);
        
        if (!$pegawai) {
            return view('pages.dashboard.unregistered');
        }

        $absensiHariIni = $pegawai->absensiHariIni();
        $bulan = now()->month;
        $tahun = now()->year;

        $historyAbsensi = $this->absensiService->getByPegawaiBulan($pegawai->id, $bulan, $tahun);
        $statistik = $this->absensiService->getStatistikPegawai($pegawai->id, $bulan, $tahun);
        $informasis = $this->informasiService->getLatest(5);

        return view('pages.dashboard.user', compact(
            'pegawai', 
            'absensiHariIni', 
            'historyAbsensi', 
            'statistik',
            'informasis'
        ));
    }
}
