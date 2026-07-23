<?php

namespace App\Services\MitraPos;

use App\Models\MitraMaterial;
use App\Models\MitraPosSetting;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregation queries for the mitra dashboard. Deliberately a
 * plain class (not extending BaseService) since it has no single natural
 * "primary" repository — every method spans multiple models/aggregates,
 * so a throwaway repository binding would add indirection without benefit.
 */
class MitraDashboardService
{
    public function stats(int $mitraId): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todayQuery = PosTransaction::forMitra($mitraId)->where('status', 'completed')->whereDate('transacted_at', $today);
        $monthQuery = PosTransaction::forMitra($mitraId)->where('status', 'completed')->whereDate('transacted_at', '>=', $monthStart);

        $revenueToday = (float) $todayQuery->sum('grand_total');
        $revenueMonth = (float) $monthQuery->sum('grand_total');
        $txCountToday = (int) PosTransaction::forMitra($mitraId)->where('status', 'completed')->whereDate('transacted_at', $today)->count();
        $txCountMonth = (int) PosTransaction::forMitra($mitraId)->where('status', 'completed')->whereDate('transacted_at', '>=', $monthStart)->count();

        $monthTotals = PosTransaction::forMitra($mitraId)
            ->where('status', 'completed')
            ->whereDate('transacted_at', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(grand_total), 0) as grand_total, COALESCE(SUM(total_cogs), 0) as total_cogs')
            ->first();

        $grossProfitMonth = (float) ($monthTotals->grand_total ?? 0) - (float) ($monthTotals->total_cogs ?? 0);

        return [
            'revenue_today' => $revenueToday,
            'revenue_month' => $revenueMonth,
            'tx_count_today' => $txCountToday,
            'tx_count_month' => $txCountMonth,
            'gross_profit_month' => $grossProfitMonth,
        ];
    }

    public function paymentMix(int $mitraId, string $range = 'month'): array
    {
        $query = PosTransaction::forMitra($mitraId)->where('status', 'completed');

        if ($range === 'month') {
            $query->whereDate('transacted_at', '>=', now()->startOfMonth()->toDateString());
        } elseif ($range === 'today') {
            $query->whereDate('transacted_at', now()->toDateString());
        }

        return $query->select('payment_method', DB::raw('SUM(grand_total) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }

    public function topProducts(int $mitraId, int $limit = 5): array
    {
        return PosTransactionItem::query()
            ->join('pos_transactions', 'pos_transactions.id', '=', 'pos_transaction_items.pos_transaction_id')
            ->where('pos_transactions.mitra_id', $mitraId)
            ->where('pos_transactions.status', 'completed')
            ->select(
                'pos_transaction_items.mitra_product_id',
                'pos_transaction_items.product_name',
                DB::raw('SUM(pos_transaction_items.qty) as total_qty'),
                DB::raw('SUM(pos_transaction_items.line_total) as total_revenue')
            )
            ->groupBy('pos_transaction_items.mitra_product_id', 'pos_transaction_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'mitra_product_id' => $row->mitra_product_id,
                'product_name' => $row->product_name,
                'total_qty' => (float) $row->total_qty,
                'total_revenue' => (float) $row->total_revenue,
            ])
            ->toArray();
    }

    public function stockAlerts(int $mitraId): Collection
    {
        return MitraMaterial::forMitra($mitraId)
            ->where(function ($query) {
                $query->whereColumn('current_stock', '<', 'min_stock')
                    ->orWhere('current_stock', '<=', 0);
            })
            ->orderBy('current_stock')
            ->get()
            ->map(function (MitraMaterial $material) {
                $material->alert_level = $material->current_stock <= 0 ? 'red' : 'yellow';

                return $material;
            });
    }

    public function target(int $mitraId): ?MitraPosSetting
    {
        return MitraPosSetting::forMitra($mitraId)->first();
    }
}
