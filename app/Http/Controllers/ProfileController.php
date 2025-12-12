<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get the user's profile
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user()->load('assets');
        
        if (request()->wantsJson()) {
            return response()->json([
                'user' => $user,
            ]);
        }
        
        return view('profile.index', compact('user'));
    }

    /**
     * API endpoint for profile with wallet balances
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiProfile()
    {
        $user = auth()->user();
        $assets = $user->assets;
        
        // Format assets for easy frontend consumption with rounding to 3 decimal places
        $balances = [];
        $balances['USD'] = [
            'symbol' => 'USD',
            'amount' => round($user->balance, 3),
            'locked_amount' => 0,
            'available' => round($user->balance, 3)
        ];
        
        foreach ($assets as $asset) {
            $balances[$asset->symbol] = [
                'symbol' => $asset->symbol,
                'amount' => round($asset->amount, 3),
                'locked_amount' => round($asset->locked_amount ?? 0, 3),
                'available' => round($asset->amount - ($asset->locked_amount ?? 0), 3)
            ];
        }
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'balances' => $balances
        ]);
    }
}
