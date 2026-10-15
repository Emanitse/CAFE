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
    // 1. Load the POS Screen
    public function index()
    {
        // Fetch categories and items to display on the left side
        $categories = Category::all();
        $menuItems = MenuItem::all();
        
        return view('components.orders', compact('categories', 'menuItems'));
    }

    // 2. Process the Checkout
    public function store(Request $request)
{
    $accountID = $request->session()->get('accountID') ?? 1; // Fallback to 1 for testing if session is null

    try {
        // Use a database transaction to ensure either everything saves, or nothing saves
        DB::beginTransaction();

        // 1. Create the Order
        $order = Order::create([
            'accountID' => $accountID,
            'orderdate' => Carbon::now(),
            'totalamount' => $request->totalAmount
        ]);

        // 2. Insert into orderdetails table batch
        foreach ($request->cart as $item) {
            OrderDetail::create([
                'orderID' => $order->orderID,
                'menuID' => $item['menuID'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal']
            ]);
        }

        // 3. Create the Payment record
        Payment::create([
            'orderID' => $order->orderID,
            'paymentmethod' => $request->paymentMethod,
            'amountpaid' => $request->amountReceived,
            'paymentdate' => Carbon::now()
        ]);

        DB::commit();

        // Return the actual auto-incremented Order ID back to the JS alert
        return response()->json(['success' => true, 'orderID' => $order->orderID]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
}