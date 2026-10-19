<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the local administrator account.
     *
     * Public registration never assigns this role. Change the password
     * after the first local run.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@zyro.test')],
            [
                'name' => env('ADMIN_NAME', 'Administrador ZYRO'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role' => UserRole::Admin,
            ]
        );
    }
}
