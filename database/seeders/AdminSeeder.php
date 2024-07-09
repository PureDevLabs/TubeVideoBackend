<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $login = array(
            'name' => 'admin',
            'email' => 'admin@admin',
            'password' => '12345678',
            'password_confirmation' => '12345678'
        );
        $newUser = new CreateNewUser();
        $newUser->create($login);
    }
}
