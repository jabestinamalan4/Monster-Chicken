<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderItemsStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseOrderItemsStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $status = ['','Requested','Assigned','Out for Delivery','Delivered','Received With Change In Quantity'];

        foreach($status as $key => $value){
            if($key!=0)
            {
                $purchaseOrderItemsStatus = new PurchaseOrderItemsStatus;

                $isExistsData = PurchaseOrderItemsStatus::where('name',$value)->count();

                if($isExistsData==0){

                    $purchaseOrderItemsStatus->name = $value;
                    $purchaseOrderItemsStatus->status = $key;

                    $purchaseOrderItemsStatus->save();
                }
            }
        }
    }
}
