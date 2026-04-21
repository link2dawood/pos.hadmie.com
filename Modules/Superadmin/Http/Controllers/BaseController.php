<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use Illuminate\Routing\Controller;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Notifications\NewSubscriptionNotification;
use Notification;

class BaseController extends Controller
{
    /**
     * Returns the list of all configured payment gateway
     *
     * @return Response
     */
    public function _payment_gateways()
    {
        $gateways = [];

        //Check if stripe is configured or not
        if (env('STRIPE_PUB_KEY') && env('STRIPE_SECRET_KEY')) {
            $gateways['stripe'] = 'Stripe';
        }

        //Check if paypal is configured or not
        if ((env('PAYPAL_SANDBOX_API_USERNAME') && env('PAYPAL_SANDBOX_API_PASSWORD') && env('PAYPAL_SANDBOX_API_SECRET')) || (env('PAYPAL_LIVE_API_USERNAME') && env('PAYPAL_LIVE_API_PASSWORD') && env('PAYPAL_LIVE_API_SECRET'))) {
            $gateways['paypal'] = 'PayPal';
        }

        //Check if Razorpay is configured or not
        if ((env('RAZORPAY_KEY_ID') && env('RAZORPAY_KEY_SECRET'))) {
            $gateways['razorpay'] = 'Razor Pay';
        }

        //Check if Pesapal is configured or not
        if ((config('pesapal.consumer_key') && config('pesapal.consumer_secret'))) {
            $gateways['pesapal'] = 'PesaPal';
        }

        //check if Paystack is configured or not
        $system = System::getCurrency();
        if (in_array($system->country, ['Nigeria', 'Ghana']) && (config('paystack.publicKey') && config('paystack.secretKey'))) {
            $gateways['paystack'] = 'Paystack';
        }

        //check if Flutterwave is configured or not
        if (env('FLUTTERWAVE_PUBLIC_KEY') && env('FLUTTERWAVE_SECRET_KEY') && env('FLUTTERWAVE_ENCRYPTION_KEY')) {
            $gateways['flutterwave'] = 'VISA or Mobile Money';
        }

        // check if offline payment is enabled or not
        $is_offline_payment_enabled = System::getProperty('enable_offline_payment');

        if ($is_offline_payment_enabled) {
            $gateways['offline'] = 'CASH';
        }

        return $gateways;
    }

    /**
     * Enter details for subscriptions
     *
     * @return object
     */
    public function _add_subscription(
        $business_id, 
        $package, 
        $gateway, 
        $payment_transaction_id, 
        $user_id, 
        $is_superadmin = false)
    {
        \Log::info('=== _add_subscription METHOD CALLED ===');
        \Log::info('Business ID: ' . $business_id);
        \Log::info('Package: ' . (is_object($package) ? 'Object (ID: ' . $package->id . ')' : $package));
        \Log::info('Gateway: ' . ($gateway ?? 'NULL'));
        \Log::info('Payment Transaction ID: ' . ($payment_transaction_id ?? 'NULL'));
        \Log::info('User ID: ' . $user_id);
        \Log::info('Is Superadmin: ' . ($is_superadmin ? 'Yes' : 'No'));
        
        // CRITICAL: Verify business exists before creating subscription to prevent foreign key constraint violation
        \Log::info('Verifying business exists in database...');
        
        // Use raw SQL query for more reliable check
        $business_check = \DB::selectOne("SELECT id FROM business WHERE id = ? LIMIT 1", [$business_id]);
        if (!$business_check || empty($business_check->id)) {
            \Log::warning('Business does not exist for business_id: ' . $business_id);
            \Log::info('Attempting to create missing business for user_id: ' . $user_id);
            
            // Try to create the business if it doesn't exist
            try {
                $user = \App\User::find($user_id);
                if (!$user) {
                    \Log::error('User not found. User ID: ' . $user_id);
                    throw new \Exception('Cannot create subscription: Business ID ' . $business_id . ' does not exist and user ID ' . $user_id . ' not found.');
                }
                
                \Log::info('User found. User ID: ' . $user_id . ', Email: ' . $user->email . ', Business ID in user record: ' . $user->business_id);
                
                // Create minimal business record
                \DB::beginTransaction();
                try {
                    $business_util = app(\App\Utils\BusinessUtil::class);
                    
                    // Get default currency (USD or first available)
                    $default_currency = \DB::table('currencies')->where('code', 'USD')->first();
                    if (!$default_currency) {
                        $default_currency = \DB::table('currencies')->first();
                    }
                    $currency_id = $default_currency ? $default_currency->id : 2; // Default to USD (ID 2) if not found
                    
                    $business_details = [
                        'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '') . ' Business') ?: 'New Business',
                        'owner_id' => $user_id,
                        'currency_id' => $currency_id,
                        'fy_start_month' => 1, // January
                        'accounting_method' => 'fifo',
                        'time_zone' => 'UTC',
                        'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses'],
                    ];
                    
                    \Log::info('Creating business with details: ' . json_encode($business_details));
                    $business = $business_util->createNewBusiness($business_details);
                    
                    if (empty($business->id)) {
                        \Log::error('Business creation failed - no ID returned');
                        throw new \Exception('Failed to create business for user ID ' . $user_id);
                    }
                    
                    \Log::info('Business created successfully. New Business ID: ' . $business->id);
                    
                    // Create default business resources
                    \Log::info('Creating default business resources for business ID: ' . $business->id);
                    $business_util->newBusinessDefaultResources($business->id, $user_id);
                    \Log::info('Default business resources created');
                    
                    // Create a default location
                    \Log::info('Creating default location for business ID: ' . $business->id);
                    $default_location = $business_util->addLocation($business->id, [
                        'name' => 'Main Location',
                        'country' => 'US',
                        'state' => '',
                        'city' => '',
                        'zip_code' => '',
                        'landmark' => '',
                    ]);
                    \Log::info('Default location created. Location ID: ' . $default_location->id);
                    
                    // Create location permission
                    \Spatie\Permission\Models\Permission::create(['name' => 'location.' . $default_location->id]);
                    \Log::info('Location permission created');
                    
                    // Update user's business_id
                    if ($user->business_id != $business->id) {
                        \Log::info('Updating user business_id from ' . $user->business_id . ' to ' . $business->id);
                        $user->business_id = $business->id;
                        $user->save();
                    }
                    
                    \DB::commit();
                    \Log::info('Business creation transaction committed successfully');
                    
                    // Update business_id for subscription creation
                    $business_id = $business->id;
                    \Log::info('Using newly created business ID: ' . $business_id);
                    
                } catch (\Exception $e) {
                    \DB::rollBack();
                    \Log::error('Failed to create business. Rolling back transaction.');
                    throw $e;
                }
                
            } catch (\Exception $e) {
                \Log::emergency('CRITICAL: Failed to create missing business!');
                \Log::emergency('Exception: ' . $e->getMessage());
                \Log::emergency('Stack trace: ' . $e->getTraceAsString());
                throw new \Exception('Cannot create subscription: Business ID ' . $business_id . ' does not exist and failed to create it. Error: ' . $e->getMessage());
            }
        } else {
            \Log::info('Business verified. Business ID: ' . $business_id . ' exists in database (verified via raw SQL)');
        }
        
        if (! is_object($package)) {
            \Log::info('Package is not an object, fetching from database. Package ID: ' . $package);
            $package = Package::active()->find($package);
            if (!$package) {
                \Log::error('Package not found. Package ID: ' . $package);
                throw new \Exception('Package not found: ' . $package);
            }
            \Log::info('Package found. Package ID: ' . $package->id . ', Name: ' . $package->name);
        } else {
            \Log::info('Package is already an object. Package ID: ' . $package->id . ', Name: ' . $package->name);
        }

        $subscription = ['business_id' => $business_id,
            'package_id' => $package->id,
            'paid_via' => $gateway,
            'payment_transaction_id' => $payment_transaction_id,
        ];

        if ($package->price != 0 && (in_array($gateway, ['offline', 'pesapal']) && ! $is_superadmin)) {
            //If offline then dates will be decided when approved by superadmin
            $subscription['start_date'] = null;
            $subscription['end_date'] = null;
            $subscription['trial_end_date'] = null;
            $subscription['status'] = 'waiting';
        } else {
            $dates = $this->_get_package_dates($business_id, $package);

            $subscription['start_date'] = $dates['start'];
            $subscription['end_date'] = $dates['end'];
            $subscription['trial_end_date'] = $dates['trial'];
            $subscription['status'] = 'approved';
        }

        $subscription['package_price'] = $package->price;
        $subscription['package_details'] = [
            'location_count' => $package->location_count,
            'user_count' => $package->user_count,
            'product_count' => $package->product_count,
            'invoice_count' => $package->invoice_count,
            'name' => $package->name,
            'modules' => $package->modules,
        ];
        //Custom permissions.
        if (! empty($package->custom_permissions)) {
            foreach ($package->custom_permissions as $name => $value) {
                $subscription['package_details'][$name] = $value;
            }
        }

        $subscription['created_id'] = $user_id;
        
        // FINAL CHECK: Verify business exists one more time right before creating subscription
        \Log::info('Performing final business verification before subscription creation...');
        $final_business_check = \DB::selectOne("SELECT id FROM business WHERE id = ? FOR UPDATE", [$business_id]);
        if (!$final_business_check || empty($final_business_check->id)) {
            \Log::emergency('CRITICAL: Business does not exist in final check! Business ID: ' . $business_id);
            \Log::emergency('Final check result: ' . json_encode($final_business_check));
            throw new \Exception('Cannot create subscription: Business ID ' . $business_id . ' does not exist in database. Final verification failed.');
        }
        \Log::info('Final business verification passed. Business ID: ' . $business_id . ' confirmed to exist');
        
        \Log::info('Creating subscription with data: ' . json_encode($subscription));
        \Log::info('About to insert subscription into database. Business ID: ' . $business_id);
        
        try {
            $subscription = Subscription::create($subscription);
            \Log::info('Subscription created successfully. Subscription ID: ' . $subscription->id);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('=== QUERY EXCEPTION DURING SUBSCRIPTION CREATION ===');
            \Log::error('QueryException: ' . $e->getMessage());
            \Log::error('SQL State: ' . $e->getCode());
            \Log::error('File: ' . $e->getFile() . ', Line: ' . $e->getLine());
            \Log::error('Business ID: ' . $business_id);
            \Log::error('Subscription data: ' . json_encode($subscription));
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('=== END QUERY EXCEPTION ===');
            throw $e;
        } catch (\Exception $e) {
            \Log::error('=== FAILED TO CREATE SUBSCRIPTION ===');
            \Log::error('Exception: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile() . ', Line: ' . $e->getLine());
            \Log::error('Business ID: ' . $business_id);
            \Log::error('Subscription data: ' . json_encode($subscription));
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('=== END SUBSCRIPTION CREATION FAILURE ===');
            throw $e;
        }

        if (! $is_superadmin) {
            $email = System::getProperty('email');
            $is_notif_enabled = System::getProperty('enable_new_subscription_notification');

            if (! empty($email) && $is_notif_enabled == 1) {
                Notification::route('mail', $email)
                ->notify(new NewSubscriptionNotification($subscription));
            }
        }

        return $subscription;
    }

    /**
     * The function returns the start/end/trial end date for a package.
     *
     * @param  int  $business_id
     * @param  object  $package
     * @return array
     */
    protected function _get_package_dates($business_id, $package)
    {
        $output = ['start' => '', 'end' => '', 'trial' => ''];

        //calculate start date
        $start_date = Subscription::end_date($business_id);
        $output['start'] = $start_date->toDateString();

        //Calculate end date
        if ($package->interval == 'days') {
            $output['end'] = $start_date->addDays($package->interval_count)->toDateString();
        } elseif ($package->interval == 'months') {
            $output['end'] = $start_date->addMonths($package->interval_count)->toDateString();
        } elseif ($package->interval == 'years') {
            $output['end'] = $start_date->addYears($package->interval_count)->toDateString();
        }

        $output['trial'] = $start_date->addDays($package->trial_days);

        return $output;
    }
}
