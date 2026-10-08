<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $name = env('SUPER_ADMIN_NAME');
        $username = env('SUPER_ADMIN_USERNAME');
        $phone = env('SUPER_ADMIN_PHONE');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (!$email || !$username || !$password) {
            throw new \RuntimeException(
                'Super Admin seed aborted: missing SUPER_ADMIN_EMAIL, SUPER_ADMIN_USERNAME or SUPER_ADMIN_PASSWORD.'
            );
        }

        $superAdmin = User::updateOrCreate(
            ['username' => $username],
            [
                'ref' => 'USR-SUPERADMIN',
                'name' => $name ?: 'Super Admin',
                'email' => $email,
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => $phone ?: null,
                'password' => Hash::make($password),
                'user_type' => 'super_admin',
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]
        );

        try {
            $superAdmin->syncRoles(['super_admin']);
        } catch (\Throwable $e) {
            // Ne pas bloquer le seed si les roles ne sont pas encore initialises.
        }

        echo "✅ Super Admin créé avec succès\n";
        echo "   Email: " . $superAdmin->email . "\n";
        echo "   Username: " . $superAdmin->username . "\n";
    }
}
