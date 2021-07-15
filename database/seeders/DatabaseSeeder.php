<?php

namespace Database\Seeders;

use App\Models\Google;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();
        $user = User::create([
            'name' => 'test',
            'password' => Hash::make('test'),
            'email' => 'rradic@hotmail.com'
        ]);
        Google::truncate();
        $google = Google::createFromConfig();
        $google->save();

    }
}
