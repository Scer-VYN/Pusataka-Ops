<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('DatabaseSeeder may only run in the testing environment. Create local accounts through the application or use an environment-specific seeder.');
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@stack01.test'],
            [
                'name' => 'Library Administrator',
                'password' => 'password',
                'role' => 'pustakawan',
            ],
        );
        User::query()->updateOrCreate(
            ['email' => 'user@stack01.test'],
            [
                'name' => 'Library Member',
                'password' => 'password',
                'role' => 'anggota',
            ],
        );
    }
}
