<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ceo@optomyze.io'],
            [
                'name' => 'Breno Castro',
                'phone' => '(62) 99522-5796',
                'email_verified_at' => now(),
                'password' => Hash::make('1234'),
                'role' => 'admin',
                'status' => 'active',
                'avatar' => null,
                'title' => 'Chief Executive Officer',

                // Optional profile/contact defaults
                'phone_secondary' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'country' => 'BR',

                // Employment
                'start_date' => now(),
                'end_date' => null,

                // 'department_id' => 2,
            ]
        );
    }
}
