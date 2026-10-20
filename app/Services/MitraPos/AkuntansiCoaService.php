<?php

namespace App\Services\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\AkuntansiJournalLine;
use App\Repositories\MitraPos\AkuntansiAccountRepository;
use App\Services\BaseService;
use Database\Seeders\AkuntansiCoaTemplateSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AkuntansiCoaService extends BaseService
{
    public const JENIS_LABELS = [
        'aset' => 'Aset',
        'kewajiban' => 'Kewajiban',
        'modal' => 'Modal',
        'pendapatan' => 'Pendapatan',
        'hpp' => 'Harga Pokok',
        'biaya_adm_umum' => 'Biaya',
    ];

    private const DEBIT_NORMAL = ['aset', 'hpp', 'biaya_adm_umum'];

    public static function jenisLabel(?string $accountType): string
    {
        return self::JENIS_LABELS[$accountType] ?? '-';
    }

    public static function normalSideLabel(?string $accountType): string
    {
        if ($accountType === null) {
            return '';
        }

        return in_array($accountType, self::DEBIT_NORMAL, true) ? 'bertambah di Debit' : 'bertambah di Kredit';
    }

    public function __construct(AkuntansiAccountRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * One-time copy of the 190-row COA template into a mitra's own
     * akuntansi_accounts rows. Idempotent — a no-op if this mitra already
     * has accounts, so it's safe to call unconditionally from the
     * enrollment flow (MitraPosManageController::store()).
     */
    public function seedForMitra(int $mitraId): void
    {
        if (AkuntansiAccount::forMitra($mitraId)->exists()) {
            return;
        }

        $now = Carbon::now();

        $rows = collect(AkuntansiCoaTemplateSeeder::template())->map(fn (array $row) => array_merge($row, [
            'mitra_id' => $mitraId,
            'opening_balance' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]))->all();

        AkuntansiAccount::insert($rows);
    }

    public function listForMitra(int $mitraId): Collection
    {
        return AkuntansiAccount::forMitra($mitraId)
            ->orderBy('code')
            ->get();
    }

    /**
     * Menyimpan seluruh baris COA sekaligus (semua atau tidak sama sekali).
     * Hanya baris yang ikut dikirim form yang diproses. Akun tidak boleh
     * dinonaktifkan bila sudah dipakai jurnal, punya saldo awal, atau dipakai
     * posting otomatis — kalau tidak, Neraca jadi tidak seimbang diam-diam.
     *
     * @param  array<int, array{is_active: bool, opening_balance: float}>  $rows  keyed by account id
     */
    public function updateMany(int $mitraId, array $rows): void
    {
        DB::transaction(function () use ($mitraId, $rows) {
            $accounts = AkuntansiAccount::forMitra($mitraId)
                ->where('is_postable', true)
                ->whereIn('id', array_keys($rows))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $usedIds = AkuntansiJournalLine::whereIn('akuntansi_account_id', $accounts->keys())
                ->distinct()
                ->pluck('akuntansi_account_id')
                ->flip();

            $errors = [];

            foreach ($rows as $accountId => $row) {
                $account = $accounts->get($accountId);
                if (! $account) {
                    throw new NotFoundHttpException('Akun tidak ditemukan.');
                }

                if (! $account->is_active || $row['is_active']) {
                    continue;
                }

                $reason = $this->deactivationBlocker($account, $row['opening_balance'], $usedIds->has($account->id));
                if ($reason) {
                    $errors['is_active.'.$account->id] = "Akun {$account->code} {$account->name} tidak bisa dinonaktifkan: {$reason}";
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            foreach ($rows as $accountId => $row) {
                $accounts->get($accountId)->update([
                    'is_active' => $row['is_active'],
                    'opening_balance' => $row['opening_balance'],
                ]);
            }
        });
    }

    private function deactivationBlocker(AkuntansiAccount $account, float $newOpeningBalance, bool $hasJournalLines): ?string
    {
        if ($account->system_role !== null) {
            return 'akun ini dipakai otomatis oleh kasir/stok, jadi harus tetap aktif.';
        }

        if ($hasJournalLines) {
            return 'akun ini sudah dipakai di jurnal. Menonaktifkannya membuat Neraca tidak seimbang.';
        }

        if (abs($newOpeningBalance) >= 0.005) {
            return 'akun ini masih punya saldo awal. Kosongkan saldo awalnya (isi 0) lebih dulu.';
        }

        return null;
    }

    private function findPostableForMitra(int $mitraId, int $accountId): AkuntansiAccount
    {
        $account = AkuntansiAccount::forMitra($mitraId)
            ->where('id', $accountId)
            ->where('is_postable', true)
            ->first();

        if (! $account) {
            throw new NotFoundHttpException('Akun tidak ditemukan.');
        }

        return $account;
    }
}
