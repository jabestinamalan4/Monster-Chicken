<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $emailTemplate = [
                            [
                                'event' => 'add_user',
                                'title' => 'Account Registeration - Welcome mail',
                                'message' => '<p>Hi [USER_NAME],</p><p>Thank you for registering with Monster Chicken. We hope you will enjoy using our product.</p><p>Your Mobile USER_NAME & PASSWORD as mentioned below</p><br><p>USER_NAME : [LOGIN_NAME]</p><p>Password: [LOGIN_PASS]</p><p>If you have any questions or feedback, just reply to this mail, we will get back to you.  Monster Chicken Team!</p>'
                            ],

                            [
                                'event' => 'forget_password',
                                'title' => 'Account Verification - Forget Password Otp',
                                'message' => '<p>Hi [USER_NAME],</p><p>Thank you for using with Monster Chicken. We hope you will enjoy using our product.</p><p>Use the following OTP to reset your password.</p><br><p>OTP is [OTP_CODE].</p><p>If you have any questions or feedback, just reply to this mail, we will get back to you.</p>'
                            ]
                        ];

        foreach($emailTemplate as $template){

            $templateData = new EmailTemplate;

            $templateData->event = $template['event'];
            $templateData->title = $template['title'];
            $templateData->message = $template['message'];

            $templateData->save();
        }
    }
}
