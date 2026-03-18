<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleItem;
use App\Models\SalesTransaction;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardRepository
{
    public function summary()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $yearStart = Carbon::now()->startOfYear();

        $user = Auth::user();
        $isKasir = $user->hasRole('kasir');
        $userId = $isKasir ? $user->id : null;

        $salesQuery = fn() => $isKasir
            ? SalesTransaction::where('user_id', $userId)
            : SalesTransaction::query();

        $purchaseQuery = fn() => $isKasir
            ? Purchase::where('user_id', $userId)
            : Purchase::query();

        $salesToday = (float) $salesQuery()->whereDate('tanggal', $today)->sum('grand_total');
        $salesMonth = (float) $salesQuery()->whereDate('tanggal', '>=', $monthStart)->sum('grand_total');
        $salesYear  = (float) $salesQuery()->whereDate('tanggal', '>=', $yearStart)->sum('grand_total');
        $salesTotal = (float) $salesQuery()->sum('grand_total');

        $hppToday = (float) SaleItem::whereHas('salesTransaction', fn($q) => $q->whereDate('tanggal', $today)->when($isKasir, fn($q) => $q->where('user_id', $userId)))->sum('hpp_total');
        $hppMonth = (float) SaleItem::whereHas('salesTransaction', fn($q) => $q->whereDate('tanggal', '>=', $monthStart)->when($isKasir, fn($q) => $q->where('user_id', $userId)))->sum('hpp_total');
        $hppYear  = (float) SaleItem::whereHas('salesTransaction', fn($q) => $q->whereDate('tanggal', '>=', $yearStart)->when($isKasir, fn($q) => $q->where('user_id', $userId)))->sum('hpp_total');
        $hppTotal = (float) SaleItem::whereHas('salesTransaction', fn($q) => $q->when($isKasir, fn($q) => $q->where('user_id', $userId)))->sum('hpp_total');

        $purchaseToday = (float) $purchaseQuery()->whereDate('tanggal', $today)->sum('total');
        $purchaseMonth = (float) $purchaseQuery()->whereDate('tanggal', '>=', $monthStart)->sum('total');
        $purchaseYear  = (float) $purchaseQuery()->whereDate('tanggal', '>=', $yearStart)->sum('total');
        $purchaseTotal = (float) $purchaseQuery()->sum('total');

        $dailyDays = collect(range(13, 0))->map(fn($i) => Carbon::today()->subDays($i))->values();
        $dailyLabels = $dailyDays->map(fn($d) => $d->format('d M'))->values();

        $salesDailyMap = $salesQuery()
            ->selectRaw('DATE(tanggal) as tgl, SUM(grand_total) as total')
            ->whereDate('tanggal', '>=', Carbon::today()->subDays(13))
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        $purchaseDailyMap = $purchaseQuery()
            ->selectRaw('DATE(tanggal) as tgl, SUM(total) as total')
            ->whereDate('tanggal', '>=', Carbon::today()->subDays(13))
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        $hppDailyMap = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->selectRaw('DATE(sales_transactions.tanggal) as tgl, SUM(sale_items.hpp_total) as total')
            ->whereDate('sales_transactions.tanggal', '>=', Carbon::today()->subDays(13))
            ->when($isKasir, fn($q) => $q->where('sales_transactions.user_id', $userId))
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        $dailySales = $dailyDays->map(fn($d) => (float) ($salesDailyMap[$d->toDateString()] ?? 0))->values();
        $dailyPurchases = $dailyDays->map(fn($d) => (float) ($purchaseDailyMap[$d->toDateString()] ?? 0))->values();
        $dailyProfit = $dailyDays->map(function ($d) use ($salesDailyMap, $hppDailyMap) {
            $date = $d->toDateString();
            return (float) (($salesDailyMap[$date] ?? 0) - ($hppDailyMap[$date] ?? 0));
        })->values();

        $monthNumbers = collect(range(1, 12));
        $monthLabels = collect(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']);

        $salesMonthlyMap = $salesQuery()
            ->selectRaw('MONTH(tanggal) as bulan, SUM(grand_total) as total')
            ->whereYear('tanggal', now()->year)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $purchaseMonthlyMap = $purchaseQuery()
            ->selectRaw('MONTH(tanggal) as bulan, SUM(total) as total')
            ->whereYear('tanggal', now()->year)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $hppMonthlyMap = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->selectRaw('MONTH(sales_transactions.tanggal) as bulan, SUM(sale_items.hpp_total) as total')
            ->whereYear('sales_transactions.tanggal', now()->year)
            ->when($isKasir, fn($q) => $q->where('sales_transactions.user_id', $userId))
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $monthlySales = $monthNumbers->map(fn($m) => (float) ($salesMonthlyMap[$m] ?? 0))->values();
        $monthlyPurchases = $monthNumbers->map(fn($m) => (float) ($purchaseMonthlyMap[$m] ?? 0))->values();
        $monthlyProfit = $monthNumbers->map(fn($m) => (float) (($salesMonthlyMap[$m] ?? 0) - ($hppMonthlyMap[$m] ?? 0)))->values();

        $data = [
            'is_kasir' => $isKasir,
            'current_user_name' => $user?->name,
            'cards' => [
                'sales' => [
                    'today' => $salesToday,
                    'month' => $salesMonth,
                    'year' => $salesYear,
                    'total' => $salesTotal,
                ],
                'profit' => [
                    'today' => $salesToday - $hppToday,
                    'month' => $salesMonth - $hppMonth,
                    'year' => $salesYear - $hppYear,
                    'total' => $salesTotal - $hppTotal,
                ],
                'purchases' => [
                    'today' => $purchaseToday,
                    'month' => $purchaseMonth,
                    'year' => $purchaseYear,
                    'total' => $purchaseTotal,
                ],
                'summary' => [
                    'purchase_invoices' => Purchase::count(),
                    'available_products' => Product::where('stok', '>', 0)->count(),
                    'sales_invoices' => SalesTransaction::count(),
                    'kasir_users' => User::role('kasir')->count(),
                    'suppliers' => Supplier::count(),
                ],
            ],
            'charts' => [
                'daily' => [
                    'labels' => $dailyLabels,
                    'sales' => $dailySales,
                    'profit' => $dailyProfit,
                    'purchases' => $dailyPurchases,
                ],
                'monthly' => [
                    'labels' => $monthLabels,
                    'sales' => $monthlySales,
                    'profit' => $monthlyProfit,
                    'purchases' => $monthlyPurchases,
                ],
            ],
        ];

        return ResponseHelper::success($data, 'Dashboard summary retrieved successfully');
    }
}
