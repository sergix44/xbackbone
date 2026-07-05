<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use XBB\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        User::factory()->create([
            'name' => 'Sergio Admin',
            'email' => 'a@a.a',
            'password' => Hash::make('aaa'),
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'Alfredo Cortile User',
            'email' => 'b@b.b',
            'password' => Hash::make('bbb'),
        ]);

        User::factory()->create([
            'name' => 'Arnaldo Barile User',
            'email' => 'c@c.c',
            'password' => Hash::make('ccc'),
        ]);
    }
}
