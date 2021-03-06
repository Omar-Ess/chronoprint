<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            "name" => "Bacha Blaid",
            "email" => "contact@chronoprint.ma",
            "phone" => "0654-722288",
            "password" => bcrypt("password"),
            "role_id" => 1
        ]);
    }
}
