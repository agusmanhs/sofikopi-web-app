<?php

namespace App\Services\MitraPos;

use App\Models\AkuntansiAccount;
use App\Repositories\MitraPos\AkuntansiAccountRepository;
use App\Services\BaseService;
use Database\Seeders\AkuntansiCoaTemplateSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AkuntansiCoaService extends BaseService
{
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
     * Only postable (leaf) accounts can be toggled — header/group rows are
     * structural display rows, never posted to, so "active" has no meaning
     * for them.
     */
    public function toggleActive(int $mitraId, int $accountId, bool $isActive): AkuntansiAccount
    {
        $account = $this->findPostableForMitra($mitraId, $accountId);
        $account->update(['is_active' => $isActive]);

        return $account;
    }

    public function updateOpeningBalance(int $mitraId, int $accountId, float $amount): AkuntansiAccount
    {
        $account = $this->findPostableForMitra($mitraId, $accountId);
        $account->update(['opening_balance' => $amount]);

        return $account;
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
