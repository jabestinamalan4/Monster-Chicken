<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Rolesseeder2 extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = $this->data();

        foreach ($data as $value) {
            $role = Role::create([
                'name' => $value['name'],
                'guard_name' => 'api',
            ]);
        }
    }

    public function data()
    {
        return [
            ['name' => 'cuttingCenter'],
            ['name' => 'retailer'],
            ['name' => 'devilery']
        ];
    }
}
