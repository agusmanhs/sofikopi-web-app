<?php

namespace App\Services\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\AkuntansiJournalEntry;
use App\Models\Mitra;
use App\Models\PosTransaction;
use App\Repositories\MitraPos\AkuntansiJournalEntryRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AkuntansiJournalService extends BaseService
{
    public function __construct(AkuntansiJournalEntryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Auto-posts a completed POS sale. Called from INSIDE
     * PosTransactionService::checkout()'s own DB::transaction (mirrors
     * MitraStockService::applyMovement() — never opens its own transaction,
     * a failure here must roll back the whole sale, not just silently skip
     * the accounting side).
     *
     * Lines mirror the sheet JURNAL pattern from the reference workbook,
     * expanded to cover service_charge/tax/admin_fee (not present in the
     * original sample — see the Akuntansi plan doc for the mapping
     * rationale). Zero-amount lines are skipped so entries stay minimal.
     */
    public function postForSale(PosTransaction $transaction): AkuntansiJournalEntry
    {
        $mitraId = $transaction->mitra_id;

        $cashOrReceivableRole = $transaction->payment_method === 'cash' ? 'kas_kasir' : 'piutang_elektronik';
        $cashOrReceivableAccount = $this->resolveAccount($mitraId, $cashOrReceivableRole);

        $base = (float) $transaction->subtotal - (float) $transaction->discount;
        $revenue = $base + (float) $transaction->service_charge;
        $tax = (float) $transaction->tax;
        $totalHpp = (float) $transaction->total_hpp;
        $overhead = (float) $transaction->total_cogs - $totalHpp;
        $adminFee = (float) $transaction->admin_fee;

        $lines = [];

        $lines[] = ['account' => $cashOrReceivableAccount, 'debit' => (float) $transaction->grand_total, 'credit' => 0];
        $lines[] = ['account' => $this->resolveAccount($mitraId, 'penjualan'), 'debit' => 0, 'credit' => $revenue];

        if ($tax > 0) {
            $lines[] = ['account' => $this->resolveAccount($mitraId, 'hutang_lain_lain'), 'debit' => 0, 'credit' => $tax];
        }

        if ($totalHpp > 0) {
            $lines[] = ['account' => $this->resolveAccount($mitraId, 'harga_pokok_penjualan'), 'debit' => $totalHpp, 'credit' => 0];
            $lines[] = ['account' => $this->resolveAccount($mitraId, 'persediaan_bahan_baku'), 'debit' => 0, 'credit' => $totalHpp];
        }

        if ($overhead > 0.0001) {
            $lines[] = ['account' => $this->resolveAccount($mitraId, 'beban_overhead_produksi'), 'debit' => $overhead, 'credit' => 0];
            $lines[] = ['account' => $this->resolveAccount($mitraId, 'hutang_bop'), 'debit' => 0, 'credit' => $overhead];
        }

        if ($adminFee > 0) {
            $lines[] = ['account' => $this->resolveAccount($mitraId, 'beban_admin_bank'), 'debit' => $adminFee, 'credit' => 0];
            $lines[] = ['account' => $cashOrReceivableAccount, 'debit' => 0, 'credit' => $adminFee];
        }

        return $this->createEntry(
            mitraId: $mitraId,
            entryDate: $transaction->transacted_at ? Carbon::parse($transaction->transacted_at) : Carbon::now(),
            description: "Penjualan POS {$transaction->transaction_no}",
            sourceType: 'pos_sale',
            reference: $transaction,
            userId: $transaction->user_id,
            lines: $lines,
        );
    }

    /**
     * Reverses a sale's journal entry when its transaction is voided — a
     * brand-new entry with every line's debit/credit swapped, never
     * edits/deletes the original (same immutable-ledger philosophy as
     * MitraStockService's void-time stock reversal).
     */
    public function reverseForTransaction(PosTransaction $transaction, string $reason): AkuntansiJournalEntry
    {
        $original = AkuntansiJournalEntry::forMitra($transaction->mitra_id)
            ->where('reference_type', $transaction->getMorphClass())
            ->where('reference_id', $transaction->id)
            ->where('source_type', 'pos_sale')
            ->with('lines')
            ->first();

        if (! $original) {
            throw new RuntimeException("Tidak ditemukan jurnal penjualan untuk transaksi {$transaction->transaction_no} — void tidak bisa membalik jurnal yang tidak ada.");
        }

        $lines = $original->lines->map(fn ($line) => [
            'account' => $line->account,
            'debit' => (float) $line->credit,
            'credit' => (float) $line->debit,
        ])->all();

        return $this->createEntry(
            mitraId: $transaction->mitra_id,
            entryDate: Carbon::now(),
            description: "Void POS {$transaction->transaction_no}: {$reason}",
            sourceType: 'pos_void',
            reference: $transaction,
            userId: $transaction->voided_by,
            lines: $lines,
        );
    }

    public function listForMitra(int $mitraId, int $perPage = 20)
    {
        return AkuntansiJournalEntry::forMitra($mitraId)
            ->with('lines.account', 'user')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Account types whose balance increases on the debit side (aset/hpp/
     * biaya) vs the credit side (kewajiban/modal/pendapatan) — drives both
     * neraca() and labaRugi() below. Not present in the reference workbook
     * as an explicit column; derived from standard double-entry convention,
     * matching how the sample AKUNTAN sheet's Saldo Akhir behaves per
     * section (Aset grows with Debit, Kewajiban/Modal grow with Kredit).
     */
    private const DEBIT_NORMAL_TYPES = ['aset', 'hpp', 'biaya_adm_umum'];

    /**
     * Balance sheet as of a given date — mirrors sheet AKUNTAN's layout:
     * Aset / Kewajiban / Modal, with the cumulative (all-time-to-date)
     * Laba/Rugi Berjalan folded into Modal exactly like the reference
     * workbook's "SALDO LABA BERJALAN" row (no period-close mechanism
     * exists yet, so this is always the running, unclosed P&L).
     *
     * @return array{as_of_date: Carbon, aset: array, aset_total: float, kewajiban: array, kewajiban_total: float, modal: array, modal_total: float, laba_berjalan: float, total_pasiva: float}
     */
    public function neraca(int $mitraId, Carbon $asOfDate): array
    {
        $sums = $this->sumsByAccountUpTo($mitraId, $asOfDate);

        $accounts = AkuntansiAccount::forMitra($mitraId)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->where('position', 'neraca')
            ->orderBy('code')
            ->get();

        $rows = ['aset' => [], 'kewajiban' => [], 'modal' => []];
        $totals = ['aset' => 0.0, 'kewajiban' => 0.0, 'modal' => 0.0];

        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            $debit = (float) ($sum->total_debit ?? 0);
            $credit = (float) ($sum->total_credit ?? 0);
            $delta = in_array($account->account_type, self::DEBIT_NORMAL_TYPES, true) ? ($debit - $credit) : ($credit - $debit);
            $saldoAkhir = (float) $account->opening_balance + $delta;

            $group = $rows[$account->account_type] ?? null;
            if ($group === null) {
                continue;
            }

            $rows[$account->account_type][] = ['account' => $account, 'saldo_akhir' => $saldoAkhir];
            $totals[$account->account_type] += $saldoAkhir;
        }

        $labaBerjalan = $this->cumulativeLabaRugi($mitraId, $asOfDate);
        $totals['modal'] += $labaBerjalan;

        return [
            'as_of_date' => $asOfDate,
            'aset' => $rows['aset'],
            'aset_total' => $totals['aset'],
            'kewajiban' => $rows['kewajiban'],
            'kewajiban_total' => $totals['kewajiban'],
            'modal' => $rows['modal'],
            'modal_total' => $totals['modal'],
            'laba_berjalan' => $labaBerjalan,
            'total_pasiva' => $totals['kewajiban'] + $totals['modal'],
        ];
    }

    /**
     * Income statement for a date range — not present as its own sheet in
     * the reference workbook (it folds P&L straight into the Neraca), added
     * here because it's a useful standalone view of the same ledger data.
     *
     * @return array{from: Carbon, to: Carbon, pendapatan: array, pendapatan_total: float, hpp: array, hpp_total: float, biaya_adm_umum: array, biaya_total: float, laba_bersih: float}
     */
    public function labaRugi(int $mitraId, Carbon $from, Carbon $to): array
    {
        $sums = $this->sumsByAccountBetween($mitraId, $from, $to);

        $accounts = AkuntansiAccount::forMitra($mitraId)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->where('position', 'laba_rugi')
            ->orderBy('code')
            ->get();

        $rows = ['pendapatan' => [], 'hpp' => [], 'biaya_adm_umum' => []];
        $totals = ['pendapatan' => 0.0, 'hpp' => 0.0, 'biaya_adm_umum' => 0.0];

        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            $debit = (float) ($sum->total_debit ?? 0);
            $credit = (float) ($sum->total_credit ?? 0);
            $amount = in_array($account->account_type, self::DEBIT_NORMAL_TYPES, true) ? ($debit - $credit) : ($credit - $debit);

            $group = $rows[$account->account_type] ?? null;
            if ($group === null) {
                continue;
            }

            $rows[$account->account_type][] = ['account' => $account, 'amount' => $amount];
            $totals[$account->account_type] += $amount;
        }

        return [
            'from' => $from,
            'to' => $to,
            'pendapatan' => $rows['pendapatan'],
            'pendapatan_total' => $totals['pendapatan'],
            'hpp' => $rows['hpp'],
            'hpp_total' => $totals['hpp'],
            'biaya_adm_umum' => $rows['biaya_adm_umum'],
            'biaya_total' => $totals['biaya_adm_umum'],
            'laba_bersih' => $totals['pendapatan'] - $totals['hpp'] - $totals['biaya_adm_umum'],
        ];
    }

    private function cumulativeLabaRugi(int $mitraId, Carbon $asOfDate): float
    {
        $sums = $this->sumsByAccountUpTo($mitraId, $asOfDate);

        $accounts = AkuntansiAccount::forMitra($mitraId)
            ->where('is_postable', true)
            ->where('position', 'laba_rugi')
            ->get();

        $net = 0.0;
        foreach ($accounts as $account) {
            $sum = $sums->get($account->id);
            $debit = (float) ($sum->total_debit ?? 0);
            $credit = (float) ($sum->total_credit ?? 0);
            $amount = in_array($account->account_type, self::DEBIT_NORMAL_TYPES, true) ? ($debit - $credit) : ($credit - $debit);
            // Pendapatan adds to laba, HPP/Biaya subtract — both already
            // signed correctly by the debit/credit-normal convention above,
            // except pendapatan's $amount is itself the credit-normal
            // positive contribution, while hpp/biaya's debit-normal $amount
            // is a positive cost that must be subtracted.
            $net += $account->account_type === 'pendapatan' ? $amount : -$amount;
        }

        return $net;
    }

    /**
     * @return Collection<int, object{total_debit: float, total_credit: float}>
     */
    private function sumsByAccountUpTo(int $mitraId, Carbon $asOfDate)
    {
        return DB::table('akuntansi_journal_lines as l')
            ->join('akuntansi_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.mitra_id', $mitraId)
            ->where('e.entry_date', '<=', $asOfDate->toDateString())
            ->groupBy('l.akuntansi_account_id')
            ->selectRaw('l.akuntansi_account_id, SUM(l.debit) as total_debit, SUM(l.credit) as total_credit')
            ->get()
            ->keyBy('akuntansi_account_id');
    }

    /**
     * @return Collection<int, object{total_debit: float, total_credit: float}>
     */
    private function sumsByAccountBetween(int $mitraId, Carbon $from, Carbon $to)
    {
        return DB::table('akuntansi_journal_lines as l')
            ->join('akuntansi_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.mitra_id', $mitraId)
            ->whereBetween('e.entry_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('l.akuntansi_account_id')
            ->selectRaw('l.akuntansi_account_id, SUM(l.debit) as total_debit, SUM(l.credit) as total_credit')
            ->get()
            ->keyBy('akuntansi_account_id');
    }

    /**
     * Manual entry — the only path a user directly controls (auto-posted
     * entries never take raw account_id/debit/credit from a request). Opens
     * its OWN transaction since, unlike postForSale/reverseForTransaction,
     * there's no existing checkout()/void() transaction wrapping this call.
     *
     * @param  array<int, array{account_id: int, debit: float|string|null, credit: float|string|null}>  $lines
     */
    public function createManual(int $mitraId, int $userId, Carbon $date, string $description, array $lines): AkuntansiJournalEntry
    {
        return DB::transaction(function () use ($mitraId, $userId, $date, $description, $lines) {
            if (count($lines) < 2) {
                throw new RuntimeException('Jurnal manual minimal harus punya 2 baris.');
            }

            $resolvedLines = collect($lines)->map(function (array $line) use ($mitraId) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    throw new RuntimeException('Satu baris jurnal tidak boleh mengisi debit dan kredit sekaligus.');
                }

                if ($debit <= 0 && $credit <= 0) {
                    throw new RuntimeException('Setiap baris jurnal harus mengisi debit atau kredit.');
                }

                $account = AkuntansiAccount::forMitra($mitraId)
                    ->where('id', $line['account_id'])
                    ->where('is_postable', true)
                    ->first();

                if (! $account) {
                    throw new RuntimeException("Akun tidak ditemukan atau bukan akun postable (id: {$line['account_id']}).");
                }

                return ['account' => $account, 'debit' => $debit, 'credit' => $credit];
            })->all();

            return $this->createEntry(
                mitraId: $mitraId,
                entryDate: $date,
                description: $description,
                sourceType: 'manual',
                reference: null,
                userId: $userId,
                lines: $resolvedLines,
            );
        });
    }

    /**
     * @param  array<int, array{account: AkuntansiAccount, debit: float, credit: float}>  $lines
     */
    private function createEntry(
        int $mitraId,
        Carbon $entryDate,
        string $description,
        string $sourceType,
        ?Model $reference,
        ?int $userId,
        array $lines,
    ): AkuntansiJournalEntry {
        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new RuntimeException("Jurnal tidak balance: debit {$totalDebit} != kredit {$totalCredit}.");
        }

        $entry = AkuntansiJournalEntry::create([
            'mitra_id' => $mitraId,
            'entry_no' => $this->generateEntryNo($mitraId),
            'entry_date' => $entryDate,
            'description' => $description,
            'source_type' => $sourceType,
            'user_id' => $userId,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);

        foreach ($lines as $line) {
            $entry->lines()->create([
                'akuntansi_account_id' => $line['account']->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ]);
        }

        return $entry->fresh('lines.account');
    }

    private function resolveAccount(int $mitraId, string $systemRole): AkuntansiAccount
    {
        $account = AkuntansiAccount::forMitra($mitraId)
            ->where('system_role', $systemRole)
            ->first();

        if (! $account) {
            throw new RuntimeException("Akun akuntansi dengan system_role '{$systemRole}' tidak ditemukan untuk mitra ini — pastikan Chart of Account sudah di-seed (lihat AkuntansiCoaService::seedForMitra).");
        }

        return $account;
    }

    /**
     * JU/{mitra_code}/{Ymd}/{seq4}, same locked-lookup pattern as
     * PosTransactionService::generateTransactionNo() — generated inside the
     * caller's already-open transaction.
     */
    private function generateEntryNo(int $mitraId): string
    {
        $mitra = Mitra::findOrFail($mitraId);
        $ymd = now()->format('Ymd');
        $prefix = "JU/{$mitra->code}/{$ymd}/";

        $latest = AkuntansiJournalEntry::forMitra($mitraId)
            ->where('entry_no', 'like', $prefix.'%')
            ->orderBy('entry_no', 'desc')
            ->lockForUpdate()
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest->entry_no, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
