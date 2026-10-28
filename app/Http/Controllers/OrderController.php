<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    
public function index()
    {
       
        $categories = Category::where('is_active', true)->get();
        $menuItems = MenuItem::whereHas('category', fn($q) => $q->where('is_active', true))->get();
        
        return view('components.orders', compact('categories', 'menuItems'));
    }

   
    public function store(Request $request)
{
    $accountID = $request->session()->get('accountID') ?? 1; 

    try {
        
        DB::beginTransaction();

       
        $order = Order::create([
            'accountID' => $accountID,
            'orderdate' => Carbon::now(),
            'totalamount' => $request->totalAmount
        ]);

        
        foreach ($request->cart as $item) {
            OrderDetail::create([
                'orderID' => $order->orderID,
                'menuID' => $item['menuID'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal']
            ]);
        }

   
        Payment::create([
            'orderID' => $order->orderID,
            'paymentmethod' => $request->paymentMethod,
            'amountpaid' => $request->amountReceived,
            'paymentdate' => Carbon::now()
        ]);

        DB::commit();

        
        return response()->json(['success' => true, 'orderID' => $order->orderID]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
}