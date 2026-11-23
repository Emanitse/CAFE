<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\MenuItem;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
 
    public function index()
    {
        return view('components.reports');
    }


    public function getCategories()
    {
       
        $categories = Category::orderBy('categoryname', 'asc')
            ->get(['categoryID', 'categoryname']);

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }


    public function getItems(Request $request)
    {
        $query = MenuItem::where('is_active', true)
            ->whereHas('category') 
            ->orderBy('itemname', 'asc');

        if ($request->filled('categoryID') && $request->categoryID !== 'all') {
            $query->where('categoryID', $request->categoryID);
        }

        $items = $query->get(['menuID', 'categoryID', 'itemname']);

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $rawIds = $request->input('selected_ids', '[]');
        $selectedIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true);

        if (empty($selectedIds) || !is_array($selectedIds)) {
            return back()->with('error', 'Please select at least one record to export.');
        }

        $logs = DB::table('orderdetails')
            ->join('orders', 'orderdetails.orderID', '=', 'orders.orderID')
            ->leftJoin('menuitems', 'orderdetails.menuID', '=', 'menuitems.menuID')
            ->leftJoin('categories', 'menuitems.categoryID', '=', 'categories.categoryID')
            ->leftJoin('accounts', 'orders.accountID', '=', 'accounts.accountID')
            ->leftJoin('payments', 'orders.orderID', '=', 'payments.orderID')
            ->whereIn('orderdetails.orderdetailID', $selectedIds)
            ->select(
                'orderdetails.orderdetailID',
                'orders.orderID',
                'orders.orderdate',
                'menuitems.itemname',
                'categories.categoryname',
                'orderdetails.quantity',
                'orderdetails.subtotal',
                'payments.paymentmethod',
                'accounts.username as cashier'
            )
            ->orderBy('orders.orderdate', 'desc')
            ->orderBy('orderdetails.orderdetailID', 'desc')
            ->get()
            ->map(function ($row) {
                $row->formatted_date = Carbon::parse($row->orderdate)->format('M d, Y h:i A');
                return $row;
            });

        $summary = [
            'count' => $logs->count(),
            'totalQuantity' => (int) $logs->sum('quantity'),
            'totalSales' => number_format((float) $logs->sum('subtotal'), 2, '.', ','),
        ];

        $meta = [
            'generatedAt' => Carbon::now()->format('M d, Y h:i A'),
            'generatedBy' => session('username') ?? 'Administrator',
            'dateRange' => $request->input('date_range_label', 'All Dates'),
            'filterLabel' => $request->input('filter_label', 'All Categories / All Items'),
        ];

        $pdf = Pdf::loadView('exports.sales_report_pdf', compact('logs', 'summary', 'meta'))
            ->setPaper('a4', 'landscape');

        $filename = 'Cafe-Ordering-System-Sales-Report-' . Carbon::now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }


    public function getOrderHistory(Request $request)
    {
        $query = DB::table('orderdetails')
            ->join('orders', 'orderdetails.orderID', '=', 'orders.orderID')
            ->leftJoin('menuitems', 'orderdetails.menuID', '=', 'menuitems.menuID')
            ->leftJoin('categories', 'menuitems.categoryID', '=', 'categories.categoryID')
            ->leftJoin('accounts', 'orders.accountID', '=', 'accounts.accountID')
            ->leftJoin('payments', 'orders.orderID', '=', 'payments.orderID')
            ->select(
                'orderdetails.orderdetailID',
                'orders.orderID',
                'orders.orderdate',
                'menuitems.itemname',
                'menuitems.is_active as item_active',
                'categories.categoryname',
                'orderdetails.quantity',
                'orderdetails.subtotal',
                'payments.paymentmethod',
                'accounts.username as cashier'
            );


        if ($request->filled('categoryID') && $request->categoryID !== 'all') {
            $query->where('menuitems.categoryID', $request->categoryID);
        }


        if ($request->filled('menuID') && $request->menuID !== 'all') {
            $query->where('orderdetails.menuID', $request->menuID);
        }

    
        if ($request->filled('fromDate')) {
            $query->whereDate('orders.orderdate', '>=', $request->fromDate);
        }

        if ($request->filled('toDate')) {
            $query->whereDate('orders.orderdate', '<=', $request->toDate);
        }

        
        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanOrderId = ltrim($search, '#');

            $query->where(function ($q) use ($search, $cleanOrderId) {
                $q->where('orders.orderID', 'like', "%{$cleanOrderId}%")
                  ->orWhere('menuitems.itemname', 'like', "%{$search}%")
                  ->orWhere('categories.categoryname', 'like', "%{$search}%")
                  ->orWhere('accounts.username', 'like', "%{$search}%")
                  ->orWhere('payments.paymentmethod', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('orders.orderdate', 'desc')
            ->orderBy('orderdetails.orderdetailID', 'desc')
            ->get()
            ->map(function ($row) {
                $row->formatted_date = Carbon::parse($row->orderdate)->format('M d, Y h:i A');
                return $row;
            });

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'summary' => [
                'count' => $logs->count(),
                'totalQuantity' => (int) $logs->sum('quantity'),
                'totalSales' => number_format((float) $logs->sum('subtotal'), 2, '.', ','),
            ],
        ]);
    }
}