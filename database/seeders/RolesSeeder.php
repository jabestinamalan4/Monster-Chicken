<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
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

            if ($value['name'] != 'customer') {

                $allowedList = ['product','stock','franchise','supplier','order'];

                if ($value['name'] != 'admin'){
                    if (($key = array_search('product', $allowedList)) !== false) {
                        unset($allowedList[$key]);
                    }

                    if (($key = array_search('franchise', $allowedList)) !== false) {
                        unset($allowedList[$key]);
                    }

                    if (($key = array_search('supplier', $allowedList)) !== false) {
                        unset($allowedList[$key]);
                    }
                }

                $permissions = Permission::all();
                foreach($permissions as $key => $permission){
                    $inArray = false;
                    if ($value['name'] != 'customer') {
                        $nameArray = explode(' ', $permission->name);
                        foreach($nameArray as $name){
                            if(in_array($name, $allowedList) == true){
                                $inArray = true;
                            }
                        }
                        if($inArray == true){
                            $role->givePermissionTo($permission);
                        }
                    }
                }
            }
        }
    }

    public function data()
    {
        return [
            ['name' => 'admin'],
            ['name' => 'franchise'],
            ['name' => 'customer']
        ];
    }
}