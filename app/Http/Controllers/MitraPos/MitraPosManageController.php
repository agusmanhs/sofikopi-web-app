<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Services\MitraPos\MitraPosResetService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for which mitras are enrolled in the Mitra POS system. The `mitras`
 * master table is shared with sales orders, kunjungan, etc. and is already
 * populated in production — a mitra existing there does NOT mean it should
 * appear here. Enrollment is tracked by the (pre-existing, previously
 * unused) mitra_pos_settings row: its existence for a mitra IS the "this
 * mitra is in our POS system" flag. Nothing else in the app depends on it,
 * so this stays a pure admin-UX bookkeeping concern, not an access-control
 * boundary — a mitra user's actual portal access is still governed purely
 * by mitra_id + role, unaffected by enrollment state.
 */
class MitraPosManageController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraPosResetService $resetService
    ) {}

    /**
     * Only mitras enrolled in POS — not every active mitra in the master
     * table.
     */
    public function index()
    {
        $mitras = Mitra::whereHas('posSetting')->orderBy('name')->get();
        $availableMitras = Mitra::aktif()->whereDoesntHave('posSetting')->orderBy('name')->get();

        return view('pages.mitra-pos.manage-picker', compact('mitras', 'availableMitras'));
    }

    /**
     * Enroll an existing mitra into the POS system (create its
     * mitra_pos_settings row). Does not create any material/product data —
     * that's set up afterwards via the Material/Produk screens.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mitra_id' => 'required|integer|exists:mitras,id',
        ]);

        $mitra = Mitra::findOrFail($data['mitra_id']);

        if ($mitra->posSetting()->exists()) {
            throw ValidationException::withMessages([
                'mitra_id' => 'Mitra ini sudah terdaftar di sistem POS.',
            ]);
        }

        MitraPosSetting::create(['mitra_id' => $mitra->id]);

        $this->logActivity('created', 'mitra-pos', "Menambahkan mitra ke sistem POS: {$mitra->name}", $mitra);

        return redirect()->route('mitra-pos-manage.index')
            ->with('success', "[{$mitra->code}] {$mitra->name} berhasil ditambahkan ke sistem POS. Silakan lanjut isi material dan produk.");
    }

    /**
     * Remove a mitra from the POS system entirely: wipes ALL its POS data
     * (transactions, stock ledger, products+BOM, materials — see
     * MitraPosResetService) including the enrollment row itself, so it no
     * longer appears in index(). The mitras master row, its users, kunjungan
     * history, and sales orders are never touched.
     */
    public function destroy(Mitra $mitra)
    {
        $counts = $this->resetService->wipe($mitra->id);
        $total = array_sum($counts);

        $this->logActivity(
            'deleted',
            'mitra-pos',
            "Menghapus mitra dari sistem POS: {$mitra->name} ({$total} baris: "
                .collect($counts)->map(fn ($n, $label) => "{$label} {$n}")->implode(', ').')',
            $mitra
        );

        return redirect()->route('mitra-pos-manage.index')
            ->with('success', "[{$mitra->code}] {$mitra->name} dihapus dari sistem POS ({$total} baris data terhapus). Master mitra, user, kunjungan, dan sales order tidak tersentuh.");
    }

    /**
     * Remove MULTIPLE mitras from the POS system in one request — same
     * guarantees as destroy() (wipes all POS data + de-enrolls), just looped.
     * Each MitraPosResetService::wipe() call is already its own DB::transaction,
     * so this is N independent atomic operations, not one all-or-nothing batch
     * — a rare admin-only decommissioning action, chosen over one giant
     * transaction to avoid holding locks across multiple mitras' full data
     * simultaneously (which could contend with live POS checkout traffic on
     * other tenants).
     */
    public function destroyBulk(Request $request)
    {
        $data = $request->validate([
            'mitra_ids' => 'required|array|min:1',
            'mitra_ids.*' => 'integer|exists:mitras,id',
        ]);

        $mitras = Mitra::whereIn('id', $data['mitra_ids'])->get();

        $totalRows = 0;
        $names = [];
        foreach ($mitras as $mitra) {
            $counts = $this->resetService->wipe($mitra->id);
            $totalRows += array_sum($counts);
            $names[] = $mitra->name;
        }

        $this->logActivity(
            'deleted',
            'mitra-pos',
            'Menghapus '.count($mitras)." mitra dari sistem POS sekaligus ({$totalRows} baris data total): ".implode(', ', $names),
            null
        );

        return redirect()->route('mitra-pos-manage.index')
            ->with('success', count($mitras)." mitra berhasil dihapus dari sistem POS ({$totalRows} baris data terhapus): ".implode(', ', $names));
    }
}
