<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:test-user
                            {--business-id=2 : Business ID to attach the test user to}
                            {--username=testadmin : Username for the test user}
                            {--password=test123456 : Password for the test user}
                            {--email= : Email for the test user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or refresh a local test admin user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $businessId = (int) $this->option('business-id');
        $username = trim((string) $this->option('username'));
        $password = (string) $this->option('password');
        $email = $this->option('email') ?: $username . '+' . $businessId . '@local.test';
        $roleName = 'Admin#' . $businessId;

        if (empty($username) || empty($password)) {
            $this->error('Username and password are required.');

            return self::FAILURE;
        }

        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

        if (! $role) {
            $this->error("Role [{$roleName}] was not found. Pick a business that already has admin roles.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($businessId, $username, $password, $email, $role) {
            $user = User::where('username', $username)->first();

            if (! $user) {
                $user = new User();
            }

            $user->user_type = 'user';
            $user->surname = 'Mr';
            $user->first_name = 'Test';
            $user->last_name = 'Admin';
            $user->username = $username;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->language = 'en';
            $user->business_id = $businessId;
            $user->allow_login = 1;
            $user->status = 'active';
            $user->is_cmmsn_agnt = 0;
            $user->cmmsn_percent = 0;
            $user->selected_contacts = 0;
            $user->save();

            $user->syncRoles([$role]);
        });

        $this->info('Test user is ready.');
        $this->table(
            ['Business ID', 'Username', 'Password', 'Email', 'Role'],
            [[
                $businessId,
                $username,
                $password,
                $email,
                $roleName,
            ]]
        );

        return self::SUCCESS;
    }
}
