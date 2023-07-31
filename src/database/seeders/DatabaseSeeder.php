<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'name' => 'user1',
            'username' => 'username1',
            'currency' => 'MYR',
            'password' => Hash::make('abc123'),
            'remember_token' => Str::random(10)
        ]);

        \App\Models\User::create([
            'name' => 'user2',
            'username' => 'username2',
            'currency' => 'MYR',
            'password' => Hash::make('abc123'),
            'remember_token' => Str::random(10)
        ]);

        \App\Models\Transaction::create([
            'user_id' => 1,
            'balance_before' => 0,
            'amount' => 1000,
            'balance_after' => 1000
        ]);

        \App\Models\Transaction::create([
            'user_id' => 2,
            'balance_before' => 0,
            'amount' => 0,
            'balance_after' => 0
        ]);
    }
}