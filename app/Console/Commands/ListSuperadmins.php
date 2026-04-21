<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class ListSuperadmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superadmin:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all superadmin users in the system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $administrator_usernames = config('constants.administrator_usernames');
        
        if (empty($administrator_usernames)) {
            $this->warn('No administrator usernames configured in ADMINISTRATOR_USERNAMES environment variable.');
            return 0;
        }

        $admin_usernames = array_map('trim', explode(',', $administrator_usernames));
        
        $this->info('Superadmin Configuration:');
        $this->line('ADMINISTRATOR_USERNAMES: ' . $administrator_usernames);
        $this->newLine();

        $this->info('Superadmin Users:');
        $this->newLine();

        $headers = ['ID', 'Username', 'Email', 'Name', 'Status', 'Business ID'];
        $data = [];

        foreach ($admin_usernames as $username) {
            $user = User::where('username', $username)->first();
            
            if ($user) {
                $data[] = [
                    $user->id,
                    $user->username,
                    $user->email ?? 'N/A',
                    $user->first_name . ' ' . $user->last_name,
                    $user->status ?? 'active',
                    $user->business_id ?? 'N/A',
                ];
            } else {
                $data[] = [
                    'N/A',
                    $username,
                    'User not found',
                    'N/A',
                    'N/A',
                    'N/A',
                ];
            }
        }

        $this->table($headers, $data);

        $this->newLine();
        $this->info('Note: Superadmin access is granted based on username matching ADMINISTRATOR_USERNAMES.');
        $this->info('To add a superadmin, add their username to the ADMINISTRATOR_USERNAMES environment variable.');

        return 0;
    }
}

