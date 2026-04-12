<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Display a listing of the backups.
     */
    public function index()
    {
        $backups = $this->backupService->getBackups();
        return view('pages.backup.index', compact('backups'));
    }

    /**
     * Run a new backup
     */
    public function store(Request $request)
    {
        $option = $request->input('option', ''); // '', '--only-db', '--only-files'
        $result = $this->backupService->runBackup($option);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Download a backup file
     */
    public function download(Request $request)
    {
        $filePath = $request->get('file');
        $download = $this->backupService->downloadBackup($filePath);

        if ($download) {
            return $download;
        }

        return redirect()->back()->with('error', 'File tidak ditemukan.');
    }

    /**
     * Remove a backup file
     */
    public function destroy(Request $request)
    {
        $filePath = $request->get('file');
        $result = $this->backupService->deleteBackup($filePath);

        if ($result) {
            return redirect()->back()->with('success', 'Backup berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Gagal menghapus backup atau file tidak ditemukan.');
    }
}
