<?php

namespace App\Http\Controllers;

use App\Services\BackupService;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Display the export database page.
     */
    public function index()
    {
        return view('pages.backup.index');
    }

    /**
     * Export database as SQL file and download directly.
     */
    public function export()
    {
        $result = $this->backupService->exportDatabase();

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return response()->download(
            $result['file_path'],
            $result['file_name']
        )->deleteFileAfterSend(true);
    }
}
