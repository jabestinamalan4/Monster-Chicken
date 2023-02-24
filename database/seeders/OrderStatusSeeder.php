<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $status = ['Created','Order Submitted','Packing for Delivery','Out for Delivery','Delivered'];

        foreach($status as $key => $value){
            $orderStatus = new OrderStatus;

            $isExistsData = OrderStatus::where('name',$value)->count();

            if($isExistsData==0){

                $orderStatus->name = $value;
                $orderStatus->status = $key;

                $orderStatus->save();
            }
        }
    }
}
