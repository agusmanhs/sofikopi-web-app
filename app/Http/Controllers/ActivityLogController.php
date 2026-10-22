<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Tampilkan halaman activity log
     */
    public function index(Request $request)
    {
        $filters = $request->only(['action', 'module', 'user_id', 'subject_type', 'start_date', 'end_date', 'search']);

        $logs = $this->activityLogService->getPaginated(25, $filters)->withQueryString();
        $actions = $this->activityLogService->getAvailableActions();
        $modules = $this->activityLogService->getAvailableModules();
        $users = \App\Models\User::select('id', 'name')->orderBy('name')->get();

        return view('pages.activity-log.index', compact('logs', 'actions', 'modules', 'users', 'filters'));
    }

    /**
     * API: Get logs untuk DataTable atau API
     */
    public function getData(Request $request)
    {
        $filters = $request->only(['action', 'module', 'user_id', 'subject_type', 'start_date', 'end_date', 'search']);
        $logs = $this->activityLogService->getPaginated($request->get('per_page', 15), $filters);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        $days = $request->get('days', 30);
        $stats = $this->activityLogService->getStatistics($days);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
