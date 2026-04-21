<?php

namespace App\Http\Controllers;

use App\Business;
use App\Currency;
use App\Notifications\TestEmailNotification;
use App\System;
use App\TaxRate;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Spatie\Permission\Models\Permission;

class BusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BusinessController
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new business/business as well as their
    | validation and creation.
    |
    */

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $restaurantUtil;

    protected $moduleUtil;

    protected $mailDrivers;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, RestaurantUtil $restaurantUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->theme_colors = [
            'primary' => 'Blue',
            // 'black' => 'Black',
            'purple' => 'Purple',
            'green' => 'Green',
            'red' => 'Red',
            'yellow' => 'Yellow',
            'orange' => 'Orange',
            'sky' => 'Sky',
            // 'blue-light' => 'Blue Light',
            // 'black-light' => 'Black Light',
            // 'purple-light' => 'Purple Light',
            // 'green-light' => 'Green Light',
            // 'red-light' => 'Red Light',
        ];

        $this->mailDrivers = [
            'smtp' => 'SMTP',
            // 'sendmail' => 'Sendmail',
            // 'mailgun' => 'Mailgun',
            // 'mandrill' => 'Mandrill',
            // 'ses' => 'SES',
            // 'sparkpost' => 'Sparkpost'
        ];
    }

    /**
     * Shows registration form
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        if (! config('constants.allow_registration')) {
            return redirect('/');
        }

        $currencies = $this->businessUtil->allCurrencies();

        $timezone_list = $this->businessUtil->allTimeZones();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.'.$i);
        }

        $accounting_methods = $this->businessUtil->allAccountingMethods();
        $package_id = request()->package;

        $system_settings = System::getProperties(['superadmin_enable_register_tc', 'superadmin_register_tc'], true);
        
        // Get business types for selection
        $business_types = \App\Utils\BusinessTypeUtil::getBusinessTypes();
        $business_type_options = [];
        foreach ($business_types as $key => $type) {
            $business_type_options[$key] = $type['label'];
        }

        return view('business.register', compact(
            'currencies',
            'timezone_list',
            'months',
            'accounting_methods',
            'package_id',
            'system_settings',
            'business_type_options'
        ));
    }

    /**
     * Handles the registration of a new business and it's owner
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        if (! config('constants.allow_registration')) {
            return redirect('/');
        }

        // Clear session and cookies if user exists
        if (Auth::check()) {
            $userId = Auth::id();
            Auth::logout();
            Session::flush();
            
            Log::info('User logged out during business registration');
          
        }

          // Clear all cookies
          $cookies = $request->cookies->all();
          foreach ($cookies as $name => $value) {
              Cookie::queue(Cookie::forget($name));
          }
          
          Log::info('Session and cookies cleared during business registration');

        try {
            $validator = $request->validate(
                [
                    'name' => 'required|max:255',
                    'currency_id' => 'required|numeric',
                    'country' => 'required|max:255',
                    'state' => 'required|max:255',
                    'city' => 'required|max:255',
                    'zip_code' => 'required|max:255',
                    'landmark' => 'required|max:255',
                    'time_zone' => 'required|max:255',
                    'surname' => 'max:10',
                    'email' => 'sometimes|nullable|email|unique:users|max:255',
                    'first_name' => 'required|max:255',
                    'username' => 'required|min:4|max:255|unique:users',
                    'password' => 'required|min:4|max:255',
                    'fy_start_month' => 'required',
                    'accounting_method' => 'required',
                    'business_type' => 'required|in:restaurant_bar,repair_shop,manufacturing,essential_business,hotel_management,hospital_management,school_management,gym_management',
                ],
                [
                    'name.required' => __('validation.required', ['attribute' => __('business.business_name')]),
                    'name.currency_id' => __('validation.required', ['attribute' => __('business.currency')]),
                    'country.required' => __('validation.required', ['attribute' => __('business.country')]),
                    'state.required' => __('validation.required', ['attribute' => __('business.state')]),
                    'city.required' => __('validation.required', ['attribute' => __('business.city')]),
                    'zip_code.required' => __('validation.required', ['attribute' => __('business.zip_code')]),
                    'landmark.required' => __('validation.required', ['attribute' => __('business.landmark')]),
                    'time_zone.required' => __('validation.required', ['attribute' => __('business.time_zone')]),
                    'email.email' => __('validation.email', ['attribute' => __('business.email')]),
                    'email.email' => __('validation.unique', ['attribute' => __('business.email')]),
                    'first_name.required' => __('validation.required', ['attribute' => __('business.first_name')]),
                    'username.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'username.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'password.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'password.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'fy_start_month.required' => __('validation.required', ['attribute' => __('business.fy_start_month')]),
                    'accounting_method.required' => __('validation.required', ['attribute' => __('business.accounting_method')]),
                ]
            );

            \Log::info('=== BUSINESS REGISTRATION STARTED ===');
            \Log::info('Registration request received. Business name: ' . ($request->input('name') ?? 'N/A'));
            
            DB::beginTransaction();
            \Log::info('Database transaction started for business registration');

            //Create owner.
            $owner_details = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password', 'language']);
            \Log::info('Creating user/owner. Username: ' . ($owner_details['username'] ?? 'N/A') . ', Email: ' . ($owner_details['email'] ?? 'N/A'));

            $owner_details['language'] = empty($owner_details['language']) ? config('app.locale') : $owner_details['language'];

            $user = User::create_user($owner_details);
            \Log::info('User created successfully. User ID: ' . $user->id);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'time_zone',
                'fy_start_month', 'accounting_method', 'tax_label_1', 'tax_number_1',
                'tax_label_2', 'tax_number_2', 'business_type']);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark',
                'website', 'mobile', 'alternate_number', ]);

            //Create the business
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = Carbon::createFromFormat(config('constants.default_date_format'), $business_details['start_date'])->toDateString();
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            //default enabled modules
            $business_details['enabled_modules'] = ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses'];
            \Log::info('Creating business. Business name: ' . ($business_details['name'] ?? 'N/A') . ', Business type: ' . ($business_details['business_type'] ?? 'N/A'));

            $business = $this->businessUtil->createNewBusiness($business_details);
            \Log::info('Business model created. Business ID: ' . ($business->id ?? 'NULL'));
            
            // Ensure business is saved and has a valid ID
            if (empty($business->id)) {
                \Log::error('Business creation failed - no ID returned from createNewBusiness');
                throw new \Exception('Business creation failed - no ID returned');
            }
            
            // Verify business was actually saved to database (before commit)
            $business_saved = DB::table('business')->where('id', $business->id)->exists();
            if (!$business_saved) {
                \Log::emergency('CRITICAL: Business was not saved to database before commit! Business ID: ' . $business->id);
                \Log::emergency('Business name: ' . ($business->name ?? 'N/A'));
                throw new \Exception('Business creation failed - business not found in database before commit. Business ID: ' . $business->id);
            }
            \Log::info('Business verified in database before commit. Business ID: ' . $business->id);

            //Update user with business id
            \Log::info('Updating user with business ID. User ID: ' . $user->id . ', Business ID: ' . $business->id);
            $user->business_id = $business->id;
            $user->save();
            \Log::info('User updated successfully');

            \Log::info('Creating default business resources. Business ID: ' . $business->id);
            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            \Log::info('Default business resources created');
            
            \Log::info('Adding business location. Business ID: ' . $business->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);
            \Log::info('Business location created. Location ID: ' . $new_location->id);

            //create new permission with the new location
            \Log::info('Creating location permission. Location ID: ' . $new_location->id);
            Permission::create(['name' => 'location.'.$new_location->id]);
            \Log::info('Location permission created');

            // Note: business_type is stored for future module segregation based on business type
            // It is not used for automatic package/subscription assignment

            // Commit business creation transaction first - business data must exist before subscription
            \Log::info('Committing business creation transaction. Business ID: ' . $business->id);
            DB::commit();
            \Log::info('Business creation transaction committed successfully');
            
            // Verify business was actually saved to database using raw query
            $business_exists = DB::table('business')->where('id', $business->id)->exists();
            if (!$business_exists) {
                \Log::emergency('CRITICAL: Business was not saved to database after commit! Business ID: ' . $business->id);
                \Log::emergency('Business name: ' . ($business->name ?? 'N/A'));
                // Business creation failed - throw exception to prevent subscription creation
                throw new \Exception('Business creation failed - business not found in database after commit. Business ID: ' . $business->id);
            }
            \Log::info('Business verified in database after commit. Business ID: ' . $business->id);
            
            // Refresh business model to ensure it's synced with database after commit
            $business->refresh();
            \Log::info('Business model refreshed after commit');

            // Start a new transaction for subscription creation (business already exists in DB)
            $is_installed_superadmin = $this->moduleUtil->isSuperadminInstalled();
            \Log::info('Checking if Superadmin module is installed: ' . ($is_installed_superadmin ? 'Yes' : 'No'));
            \Log::info('Environment: ' . config('app.env'));
            
            if ($is_installed_superadmin && (config('app.env') != 'demo')) {
                \Log::info('=== SUBSCRIPTION CREATION PROCESS STARTED ===');
                // CRITICAL: Final verification using raw SQL query to ensure business exists
                // $business_id = $business->id;
                // \Log::info('Starting subscription creation for business ID: ' . $business_id);
                
                // $business_exists_raw = DB::selectOne("SELECT COUNT(*) as count FROM business WHERE id = ?", [$business_id]);
                // \Log::info('Raw SQL check result: ' . json_encode($business_exists_raw));
                
                // if (empty($business_exists_raw) || $business_exists_raw->count == 0) {
                //     \Log::emergency('CRITICAL: Business does not exist in database before subscription creation! Business ID: ' . $business_id);
                //     \Log::emergency('Skipping subscription creation to prevent foreign key constraint violation.');
                //     // Skip subscription creation - business doesn't exist
                // } else {
                //     \Log::info('Business exists in database (raw SQL check passed). Proceeding with subscription creation.');
                //     try {
                //         \Log::info('Starting subscription transaction');
                //         DB::beginTransaction();

                        

                //         // ADD SUBSCRIPTION FOR  THE USER

                //         $free_trial_package = \Modules\Superadmin\Entities\Package::active()
                //         ->where('is_private', 0)
                //         ->where('description', 'not like', 'Auto-generated package for%')
                //         ->where(function($query) {
                //             $query->where('price', 0)
                //                   ->orWhere('trial_days', '>', 0);
                //         })
                //         ->orderBy('sort_order')
                //         ->first();
                    
                //         \Log::info('Free trial package query executed. Found: ' . ($free_trial_package ? 'Yes (ID: ' . $free_trial_package->id . ')' : 'No'));
                        
                //         if ($free_trial_package) {
                //             \Log::info('Free trial package found. Package ID: ' . $free_trial_package->id . ', Name: ' . $free_trial_package->name);
                            
                //             // Assign the free trial package (only existing packages, never auto-generate)
                //             // Business already exists in DB, so foreign key constraint will be satisfied
                //             \Log::info('Calling _add_subscription method. Business ID: ' . $business_id . ', Package ID: ' . $free_trial_package->id . ', User ID: ' . $user->id);
                //             $baseController = new \Modules\Superadmin\Http\Controllers\BaseController(
                //                 $this->businessUtil,
                //                 $this->moduleUtil
                //             );
                //             $baseController->_add_subscription(
                //                 $business_id,
                //                 $free_trial_package,
                //                 null,
                //                 'FREE_TRIAL',
                //                 $user->id
                //             );
                //             \Log::info('_add_subscription method completed successfully');
                //         } else {
                //             \Log::warning('No free trial package found. Subscription will not be created.');
                //         }
                            
                            
                        
                //         DB::commit();
                //         \Log::info('Subscription transaction committed successfully');
                //         \Log::info('Successfully created subscription for business ID: ' . $business_id);
                //         \Log::error('SQL State: ' . $e->getCode());
                //         \Log::error('Business ID: ' . $business_id . ', Package ID: ' . ($free_trial_package->id ?? 'N/A'));
                //         \Log::error('Stack trace: ' . $e->getTraceAsString());
                //         \Log::error('=== END QUERY EXCEPTION ===');
                //     } catch (\Exception $e) {
                //         DB::rollBack();
                //         // Log error but don't fail registration - subscription can be added later
                //         \Log::error('=== EXCEPTION DURING SUBSCRIPTION CREATION ===');
                //         \Log::error('Exception: ' . $e->getMessage());
                //         \Log::error('File: ' . $e->getFile() . ', Line: ' . $e->getLine());
                //         \Log::error('Business ID: ' . $business_id . ', Package ID: ' . ($free_trial_package->id ?? 'N/A'));
                //         \Log::error('Stack trace: ' . $e->getTraceAsString());
                //         \Log::error('=== END EXCEPTION ===');
                //     }
                // }
                \Log::info('=== SUBSCRIPTION CREATION PROCESS ENDED ===');
            } else {
                \Log::info('Skipping subscription creation. Superadmin installed: ' . ($is_installed_superadmin ? 'Yes' : 'No') . ', Environment: ' . config('app.env'));
            }

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                \Log::info('Calling module data hook: after_business_created');
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
                \Log::info('Module data hook completed');
            }

            //Process payment information if superadmin is installed & package information is present
            $package_id = $request->get('package_id', null);
            if ($is_installed_superadmin && ! empty($package_id) && (config('app.env') != 'demo')) {
                \Log::info('Package ID provided in request: ' . $package_id);
                $package = \Modules\Superadmin\Entities\Package::find($package_id);
                if (! empty($package)) {
                    \Log::info('Package found. Redirecting to payment page. Package ID: ' . $package_id);
                    Auth::login($user);
                    return redirect()->route('register-pay', ['package_id' => $package_id]);
                } else {
                    \Log::warning('Package not found for ID: ' . $package_id);
                }
            }

            \Log::info('=== BUSINESS REGISTRATION COMPLETED SUCCESSFULLY ===');
            \Log::info('Business ID: ' . $business->id . ', User ID: ' . $user->id);
            
            $output = ['success' => 1,
                'msg' => __('business.business_created_succesfully'),
            ];

            return redirect('login')->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('=== BUSINESS REGISTRATION FAILED ===');
            \Log::emergency('Exception caught in postRegister method');
            \Log::emergency('File: ' . $e->getFile());
            \Log::emergency('Line: ' . $e->getLine());
            \Log::emergency('Message: ' . $e->getMessage());
            \Log::emergency('Stack trace: ' . $e->getTraceAsString());
            \Log::emergency('=== END REGISTRATION FAILURE ===');
            
            DB::rollBack();
            \Log::warning('Database transaction rolled back due to exception');

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];

            return back()->with('status', $output)->withInput();
        }
    }

    /**
     * Assign modules to business based on business type
     * 
     * DISABLED: This method is not used. We do not auto-generate packages based on business type.
     * New signups get the free trial package only (existing packages, never auto-generated).
     *
     * @param int $business_id
     * @param string $business_type
     * @param int $user_id
     * @return void
     */
    private function assignModulesForBusinessType($business_id, $business_type, $user_id)
    {
        if (!$this->moduleUtil->isSuperadminInstalled()) {
            return;
        }

        $modules = \App\Utils\BusinessTypeUtil::getModulesForBusinessType($business_type);
        
        if (empty($modules)) {
            // No specific modules - create free subscription with all modules
            return;
        }

        // Find or create a package for this business type
        $package_name = ucfirst(str_replace('_', ' ', $business_type)) . ' Package';
        $package = \Modules\Superadmin\Entities\Package::where('name', $package_name)
            ->where('is_active', 1)
            ->first();

        if (!$package) {
            // Create a free package for this business type
            $package = \Modules\Superadmin\Entities\Package::create([
                'name' => $package_name,
                'description' => 'Auto-generated package for ' . \App\Utils\BusinessTypeUtil::getBusinessTypeLabel($business_type),
                'location_count' => 0,
                'user_count' => 0,
                'product_count' => 0,
                'invoice_count' => 0,
                'interval' => 'years',
                'interval_count' => 1,
                'trial_days' => 0,
                'price' => 0,
                'sort_order' => 0,
                'is_active' => 1,
                'is_private' => 0,
                'is_one_time' => 0,
                'created_by' => $user_id,
                'modules' => $modules,
            ]);
        } else {
            // Update existing package with modules if not set
            if (empty($package->modules)) {
                $package->modules = $modules;
                $package->save();
            }
        }

        // Create free subscription for this business
        $baseController = new \Modules\Superadmin\Http\Controllers\BaseController(
            $this->businessUtil,
            $this->moduleUtil
        );
        $baseController->_add_subscription($business_id, $package->id, 'offline', null, $user_id, true);
    }

    /**
     * Handles the validation username
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckUsername(Request $request)
    {
        $username = $request->input('username');

        if (! empty($request->input('username_ext'))) {
            $username .= $request->input('username_ext');
        }

        $count = User::where('username', $username)->count();

        if ($count == 0) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * Shows business settings form
     *
     * @return \Illuminate\Http\Response
     */
    public function getBusinessSettings()
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $timezone_list = [];
        foreach ($timezones as $timezone) {
            $timezone_list[$timezone] = $timezone;
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $currencies = $this->businessUtil->allCurrencies();
        $tax_details = TaxRate::forBusinessDropdown($business_id);
        $tax_rates = $tax_details['tax_rates'];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.'.$i);
        }

        $accounting_methods = [
            'fifo' => __('business.fifo'),
            'lifo' => __('business.lifo'),
        ];
        $commission_agent_dropdown = [
            '' => __('lang_v1.disable'),
            'logged_in_user' => __('lang_v1.logged_in_user'),
            'user' => __('lang_v1.select_from_users_list'),
            'cmsn_agnt' => __('lang_v1.select_from_commisssion_agents_list'),
        ];

        $units_dropdown = Unit::forDropdown($business_id, true);

        $date_formats = Business::date_formats();

        $shortcuts = json_decode($business->keyboard_shortcuts, true);

        $pos_settings = empty($business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business->pos_settings, true);

        $email_settings = empty($business->email_settings) ? $this->businessUtil->defaultEmailSettings() : $business->email_settings;

        $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

        $modules = $this->moduleUtil->availableModules();

        $theme_colors = $this->theme_colors;

        $mail_drivers = $this->mailDrivers;

        $allow_superadmin_email_settings = System::getProperty('allow_email_settings_to_businesses');

        $custom_labels = ! empty($business->custom_labels) ? json_decode($business->custom_labels, true) : [];

        $common_settings = ! empty($business->common_settings) ? $business->common_settings : [];

        $weighing_scale_setting = ! empty($business->weighing_scale_setting) ? $business->weighing_scale_setting : [];

        $payment_types = $this->moduleUtil->payment_types(null, false, $business_id);

        return view('business.settings', compact('business', 'currencies', 'tax_rates', 'timezone_list', 'months', 'accounting_methods', 'commission_agent_dropdown', 'units_dropdown', 'date_formats', 'shortcuts', 'pos_settings', 'modules', 'theme_colors', 'email_settings', 'sms_settings', 'mail_drivers', 'allow_superadmin_email_settings', 'custom_labels', 'common_settings', 'weighing_scale_setting', 'payment_types'));
    }

    /**
     * Updates business settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postBusinessSettings(Request $request)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->businessUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'default_profit_percent', 'default_sales_tax', 'default_sales_discount', 'sell_price_tax', 'sku_prefix', 'time_zone', 'fy_start_month', 'accounting_method', 'transaction_edit_days', 'sales_cmsn_agnt', 'item_addition_method', 'currency_symbol_placement', 'on_product_expiry',
                'stop_selling_before', 'default_unit', 'expiry_type', 'date_format',
                'time_format', 'ref_no_prefixes', 'theme_color', 'email_settings',
                'sms_settings', 'rp_name', 'amount_for_unit_rp',
                'min_order_total_for_rp', 'max_rp_per_order',
                'redeem_amount_per_unit_rp', 'min_order_total_for_redeem',
                'min_redeem_point', 'max_redeem_point', 'rp_expiry_period',
                'rp_expiry_type', 'custom_labels', 'weighing_scale_setting',
                'code_label_1', 'code_1', 'code_label_2', 'code_2', 'currency_precision', 'quantity_precision', ]);

            if (! empty($request->input('enable_rp')) && $request->input('enable_rp') == 1) {
                $business_details['enable_rp'] = 1;
            } else {
                $business_details['enable_rp'] = 0;
            }

            $business_details['amount_for_unit_rp'] = ! empty($business_details['amount_for_unit_rp']) ? $this->businessUtil->num_uf($business_details['amount_for_unit_rp']) : 1;
            $business_details['min_order_total_for_rp'] = ! empty($business_details['min_order_total_for_rp']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_rp']) : 1;
            $business_details['redeem_amount_per_unit_rp'] = ! empty($business_details['redeem_amount_per_unit_rp']) ? $this->businessUtil->num_uf($business_details['redeem_amount_per_unit_rp']) : 1;
            $business_details['min_order_total_for_redeem'] = ! empty($business_details['min_order_total_for_redeem']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_redeem']) : 1;

            $business_details['default_profit_percent'] = ! empty($business_details['default_profit_percent']) ? $this->businessUtil->num_uf($business_details['default_profit_percent']) : 0;

            $business_details['default_sales_discount'] = ! empty($business_details['default_sales_discount']) ? $this->businessUtil->num_uf($business_details['default_sales_discount']) : 0;

            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            if (! empty($request->input('enable_tooltip')) && $request->input('enable_tooltip') == 1) {
                $business_details['enable_tooltip'] = 1;
            } else {
                $business_details['enable_tooltip'] = 0;
            }

            $business_details['enable_product_expiry'] = ! empty($request->input('enable_product_expiry')) && $request->input('enable_product_expiry') == 1 ? 1 : 0;
            if ($business_details['on_product_expiry'] == 'keep_selling') {
                $business_details['stop_selling_before'] = null;
            }

            $business_details['stock_expiry_alert_days'] = ! empty($request->input('stock_expiry_alert_days')) ? $request->input('stock_expiry_alert_days') : 30;

            //Check for Purchase currency
            if (! empty($request->input('purchase_in_diff_currency')) && $request->input('purchase_in_diff_currency') == 1) {
                $business_details['purchase_in_diff_currency'] = 1;
                $business_details['purchase_currency_id'] = $request->input('purchase_currency_id');
                $business_details['p_exchange_rate'] = $request->input('p_exchange_rate');
            } else {
                $business_details['purchase_in_diff_currency'] = 0;
                $business_details['purchase_currency_id'] = null;
                $business_details['p_exchange_rate'] = 1;
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $checkboxes = ['enable_editing_product_from_purchase',
                'enable_inline_tax',
                'enable_brand', 'enable_category', 'enable_sub_category', 'enable_price_tax', 'enable_purchase_status',
                'enable_lot_number', 'enable_racks', 'enable_row', 'enable_position', 'enable_sub_units', ];
            foreach ($checkboxes as $value) {
                $business_details[$value] = ! empty($request->input($value)) && $request->input($value) == 1 ? 1 : 0;
            }

            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();

            //Update business settings
            if (! empty($business_details['logo'])) {
                $business->logo = $business_details['logo'];
            } else {
                unset($business_details['logo']);
            }

            //System settings
            $shortcuts = $request->input('shortcuts');
            $business_details['keyboard_shortcuts'] = json_encode($shortcuts);

            //pos_settings
            $pos_settings = $request->input('pos_settings');
            $default_pos_settings = $this->businessUtil->defaultPosSettings();
            foreach ($default_pos_settings as $key => $value) {
                if (! isset($pos_settings[$key])) {
                    $pos_settings[$key] = $value;
                }
            }
            $business_details['pos_settings'] = json_encode($pos_settings);

            $business_details['custom_labels'] = json_encode($business_details['custom_labels']);

            $business_details['common_settings'] = ! empty($request->input('common_settings')) ? $request->input('common_settings') : [];

            //Enabled modules
            $enabled_modules = $request->input('enabled_modules');
            $business_details['enabled_modules'] = ! empty($enabled_modules) ? $enabled_modules : null;
            $business->fill($business_details);
            $business->save();

            //update session data
            $request->session()->put('business', $business);

            //Update Currency details
            $currency = Currency::find($business->currency_id);
            $request->session()->put('currency', [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ]);

            //update current financial year to session
            $financial_year = $this->businessUtil->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);

            $output = ['success' => 1,
                'msg' => __('business.settings_updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('business/settings')->with('status', $output);
    }

    /**
     * Handles the validation email
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckEmail(Request $request)
    {
        $email = $request->input('email');

        $query = User::where('email', $email);

        if (! empty($request->input('user_id'))) {
            $user_id = $request->input('user_id');
            $query->where('id', '!=', $user_id);
        }

        $exists = $query->exists();
        if (! $exists) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    public function getEcomSettings()
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $settings = Business::where('id', $api_settings->business_id)
                        ->value('ecom_settings');

            $settings_array = ! empty($settings) ? json_decode($settings, true) : [];

            if (! empty($settings_array['slides'])) {
                foreach ($settings_array['slides'] as $key => $value) {
                    $settings_array['slides'][$key]['image_url'] = ! empty($value['image']) ? url('uploads/img/'.$value['image']) : '';
                }
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($settings_array);
    }

    /**
     * Handles the testing of email configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testEmailConfiguration(Request $request)
    {
        try {
            $email_settings = $request->input();

            $data['email_settings'] = $email_settings;
            \Notification::route('mail', $email_settings['mail_from_address'])
            ->notify(new TestEmailNotification($data));

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.email_tested_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Handles the testing of sms configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testSmsConfiguration(Request $request)
    {
        try {
            $sms_settings = $request->input();

            $data = [
                'sms_settings' => $sms_settings,
                'mobile_number' => $sms_settings['test_number'],
                'sms_body' => 'This is a test SMS',
            ];
            if (! empty($sms_settings['test_number'])) {
                $response = $this->businessUtil->sendSms($data);
            } else {
                $response = __('lang_v1.test_number_is_required');
            }

            $output = [
                'success' => 1,
                'msg' => $response,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }
}
