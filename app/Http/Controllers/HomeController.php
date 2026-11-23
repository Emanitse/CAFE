<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\MenuItem;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $dashboardData = $this->buildDashboardData();
        return view('components.home', compact('dashboardData'));
    }

    public function getData()
    {
        return response()->json([
            'success' => true,
            'data' => $this->buildDashboardData(),
        ]);
    }

    private function buildDashboardData(): array
    {
        $today = Carbon::today();

        // KPI Summary Metrics
        $todaySales = (float) DB::table('orders')
            ->whereDate('orderdate', $today)
            ->sum('totalamount');

        $todayOrders = (int) DB::table('orders')
            ->whereDate('orderdate', $today)
            ->count();

        $totalRevenue = (float) DB::table('orders')
            ->sum('totalamount');

        $activeItemsCount = (int) MenuItem::where('is_active', 1)->count();

        // 1. LINE CHART: Daily Sales Trend (Last 7 Days)
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dailyRows = DB::table('orders')
            ->select(
                DB::raw('DATE(orderdate) as date'),
                DB::raw('SUM(totalamount) as daily_total')
            )
            ->whereBetween('orderdate', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(orderdate)'))
            ->pluck('daily_total', 'date');

        $lineLabels = [];
        $lineValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dateKey = $dateObj->format('Y-m-d');
            $lineLabels[] = $dateObj->format('M d');
            $lineValues[] = round((float) ($dailyRows[$dateKey] ?? 0), 2);
        }

        // 2. BAR CHART: Top 6 Best-Selling Menu Items (by Quantity Sold)
        $topItems = DB::table('orderdetails')
            ->join('menuitems', 'orderdetails.menuID', '=', 'menuitems.menuID')
            ->select(
                'menuitems.itemname',
                DB::raw('SUM(orderdetails.quantity) as total_qty')
            )
            ->groupBy('menuitems.menuID', 'menuitems.itemname')
            ->orderByDesc('total_qty')
            ->limit(6)
            ->get();

        // 3. DOUGHNUT CHART: Sales Revenue by Category
        $categorySales = DB::table('orderdetails')
            ->join('menuitems', 'orderdetails.menuID', '=', 'menuitems.menuID')
            ->join('categories', 'menuitems.categoryID', '=', 'categories.categoryID')
            ->select(
                'categories.categoryname',
                DB::raw('SUM(orderdetails.subtotal) as total_sales')
            )
            ->groupBy('categories.categoryID', 'categories.categoryname')
            ->orderByDesc('total_sales')
            ->get();

        // 4. PIE CHART: Payment Method Distribution
        $paymentMethods = DB::table('payments')
            ->select(
                'paymentmethod',
                DB::raw('COUNT(*) as tx_count'),
                DB::raw('SUM(amountpaid) as total_paid')
            )
            ->groupBy('paymentmethod')
            ->orderByDesc('tx_count')
            ->get();

        return [
            'kpis' => [
                'todaySales' => number_format($todaySales, 2, '.', ','),
                'todayOrders' => $todayOrders,
                'totalRevenue' => number_format($totalRevenue, 2, '.', ','),
                'activeItems' => $activeItemsCount,
            ],
            'line' => [
                'labels' => $lineLabels,
                'values' => $lineValues,
            ],
            'bar' => [
                'labels' => $topItems->pluck('itemname')->toArray(),
                'values' => $topItems->pluck('total_qty')->map(fn($v) => (int) $v)->toArray(),
            ],
            'doughnut' => [
                'labels' => $categorySales->pluck('categoryname')->toArray(),
                'values' => $categorySales->pluck('total_sales')->map(fn($v) => round((float) $v, 2))->toArray(),
            ],
            'pie' => [
                'labels' => $paymentMethods->pluck('paymentmethod')->toArray(),
                'values' => $paymentMethods->pluck('tx_count')->map(fn($v) => (int) $v)->toArray(),
                'amounts' => $paymentMethods->pluck('total_paid')->map(fn($v) => round((float) $v, 2))->toArray(),
            ],
        ];
    }
}