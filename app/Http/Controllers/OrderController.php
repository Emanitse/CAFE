<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderDetail;
use Carbon\Carbon;

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
        // Ensure an account is logged in
        $accountID = $request->session()->get('accountID');
        
        if (!$accountID) {
            return response()->json(['success' => false, 'message' => 'User not logged in.']);
        }

        // Create the main Order record
        $order = Order::create([
            'accountID' => $accountID,
            'orderdate' => Carbon::now(),
            'totalamount' => $request->totalAmount
        ]);

        // Loop through the jQuery cart array and create Order Details
        foreach ($request->cart as $item) {
            OrderDetail::create([
                'orderID' => $order->orderID,
                'menuID' => $item['menuID'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal']
            ]);

            // Optional: Deduct from stock
            MenuItem::where('menuID', $item['menuID'])->decrement('stock', $item['quantity']);
        }

        // Return a success response with the new orderID so JS can redirect to the Payment screen
        return response()->json(['success' => true, 'orderID' => $order->orderID]);
    }
}