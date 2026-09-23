<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
       
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

      
        $account = Account::where('username', $request->username)->first();

    
        if ($account && Hash::check($request->password, $account->password)) {
            

            $request->session()->put('accountID', $account->accountID);
            $request->session()->put('role', $account->role);

            return redirect('/home');
        }

        
        return back()->with('error', 'Invalid username or password.');
    }

    public function logout(Request $request)
    {
        // Clear the specific session data
        $request->session()->flush();
        
    
        
        // Redirect back to the login page
        return redirect('/login');
    }
}