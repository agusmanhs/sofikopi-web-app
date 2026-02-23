<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ShiftRequest;
use App\Models\Shift;
use App\Models\Divisi;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['divisi_id']);
        $data = $this->shiftService->getAllShifts($filters);
        $divisis = Divisi::aktif()->get();
        return view('pages.data-master.shift.index', compact('data', 'divisis'));
    }

    public function store(ShiftRequest $request)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);
        $data['ikut_libur'] = $request->boolean('ikut_libur', false);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $result = $this->shiftService->storeShift($data);

            if ($result) {
                return ResponseHelper::success($result, 'Shift berhasil ditambahkan');
            }

            return ResponseHelper::error('Gagal menambahkan shift', 500);
        });
    }

    public function update(ShiftRequest $request, $id)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);
        $data['ikut_libur'] = $request->boolean('ikut_libur', false);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $result = $this->shiftService->updateShift($id, $data);

            if ($result) {
                return ResponseHelper::success($result, 'Shift berhasil diperbarui');
            }

            return ResponseHelper::error('Gagal memperbarui shift', 500);
        });
    }

    public function destroy($id)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $result = $this->shiftService->deleteShift($id);

            if ($result) {
                return ResponseHelper::success(null, 'Shift berhasil dihapus');
            }

            return ResponseHelper::error('Gagal menghapus shift', 500);
        });
    }

    public function getByDivisi($divisiId)
    {
        try {
            $shifts = $this->shiftService->getShiftsByDivisi($divisiId);
            return response()->json($shifts);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }
}
