<?php

namespace Database\Seeders;

use App\Models\OrderDetailsStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $status = ['Requested','Assigned','Out for Delivery','Delivered','Received With Change In Quantity'];

        foreach($status as $key => $value){

                $purchaseOrderItemsStatus = new OrderDetailsStatus;

                $isExistsData = OrderDetailsStatus::where('name',$value)->count();

                if($isExistsData==0){

                    $purchaseOrderItemsStatus->name = $value;
                    $purchaseOrderItemsStatus->status = $key+1;

                    $purchaseOrderItemsStatus->save();
                }
        }
    }
}
