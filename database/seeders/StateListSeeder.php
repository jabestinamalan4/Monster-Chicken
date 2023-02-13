<?php

namespace Database\Seeders;

use CountryState;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StateListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = CountryState::getStates('IN');

        foreach($states as $state){

            $stateData = new State;

            $stateData->state = $state;
            $stateData->status = 1;

            $stateData->save();
        }
    }
}