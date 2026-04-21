<?php

namespace App\Http\Middleware;

use App\Business;
use App\Utils\BusinessUtil;
use Closure;
use Illuminate\Support\Facades\Auth;

class SetSessionData
{
    /**
     * Checks if session data is set or not for a user. If data is not set then set it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->session()->has('user')) {
            $business_util = new BusinessUtil;

            $user = Auth::user();
            $session_data = ['id' => $user->id,
                'surname' => $user->surname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'business_id' => $user->business_id,
                'language' => $user->language,
            ];
            
            // Check if business exists before proceeding
            if (empty($user->business_id)) {
                \Log::error('User has no business_id. User ID: ' . $user->id);
                // Redirect to login or show error
                Auth::logout();
                return redirect()->route('login')->with('status', ['success' => 0, 'msg' => 'Your account is not associated with any business. Please contact support.']);
            }
            
            $business = Business::find($user->business_id);
            
            if (!$business) {
                \Log::error('Business not found for user. User ID: ' . $user->id . ', Business ID: ' . $user->business_id);
                // Redirect to login or show error
                Auth::logout();
                return redirect()->route('login')->with('status', ['success' => 0, 'msg' => 'Your business account was not found. Please contact support.']);
            }

            $currency = $business->currency;
            if (!$currency) {
                \Log::error('Currency not found for business. Business ID: ' . $business->id);
                // Use default currency or handle gracefully
                $currency_data = [
                    'id' => 1,
                    'code' => 'USD',
                    'symbol' => '$',
                    'thousand_separator' => ',',
                    'decimal_separator' => '.',
                ];
            } else {
                $currency_data = ['id' => $currency->id,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'thousand_separator' => $currency->thousand_separator,
                    'decimal_separator' => $currency->decimal_separator,
                ];
            }

            $request->session()->put('user', $session_data);
            $request->session()->put('business', $business);
            $request->session()->put('currency', $currency_data);

            //set current financial year to session
            $financial_year = $business_util->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);
        }

        return $next($request);
    }
}
