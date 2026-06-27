<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class SetupAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-admin-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the default admin user requested by the owner';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'm4tinbeigi@gmail.com';
        $phone = '09127047813';
        $password = 'Admin@1234';

        // Ensure admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);

        $user = User::firstOrNew(['email' => $email]);
        $user->name = 'Matin Beigi';
        $user->phone_e164 = $phone;
        
        if (!$user->exists) {
            $user->password = Hash::make($password);
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->phone_verified_at = now();
            $this->info("Creating new admin user: {$email}");
        } else {
            // Update password just in case
            $user->password = Hash::make($password);
            $this->info("Updating existing user: {$email}");
        }

        $user->save();

        if (!$user->hasRole('admin')) {
            $user->assignRole($adminRole);
            $this->info("Assigned 'admin' role to the user.");
        }

        $this->info("Admin user setup complete.");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
    }
}
