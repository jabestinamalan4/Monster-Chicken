<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PurchaseOrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $status = ['Created','Pending for Delivery','Out for Delivery','Delivered'];

        foreach($status as $key => $value){
            $purchaseOrderStatus = new PurchaseOrderStatus;

            $isExistsData = PurchaseOrderStatus::where('name',$value)->count();

            if($isExistsData==0){

                $purchaseOrderStatus->name = $value;
                $purchaseOrderStatus->status = $key;

                $purchaseOrderStatus->save();
            }
        }
    }
}
