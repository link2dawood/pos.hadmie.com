<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        Log::info('Creating new user account', [
            'email' => $data['email'],
            'name' => $data['name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Log::info('User account created successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);

        return $user;
    }

    /**
     * Handle a registration request for the application.
     * Override to clear any existing session before registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        Log::info('=== USER REGISTRATION STARTED ===', [
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log out any existing user and clear session
        if (Auth::check()) {
            $existingUserId = Auth::id();
            $existingUserEmail = Auth::user()->email ?? 'N/A';
            Log::warning('Existing user session found during registration - logging out', [
                'existing_user_id' => $existingUserId,
                'existing_user_email' => $existingUserEmail,
                'new_registration_email' => $request->input('email'),
            ]);
            Auth::logout();
        }
        
        Session::flush();
        Log::info('Session cleared before registration');

        try {
            // Call parent register method
            $response = parent::register($request);
            
            Log::info('=== USER REGISTRATION COMPLETED SUCCESSFULLY ===', [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
            ]);
            
            return $response;
        } catch (\Exception $e) {
            Log::error('=== USER REGISTRATION FAILED ===', [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
