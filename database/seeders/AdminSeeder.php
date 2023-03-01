<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User;
        $user->name = 'Primary Admin';
        $user->email = 'admin@monster.chicken';
        $user->number = 9585811800;
        $user->password = Hash::make('Monster@23Chicken');
        $user->status = 1;
        $user->save();

        $user->assignRole('admin');
    }
}
