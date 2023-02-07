<?php

namespace App\Jobs;

use App\Mail\NotifyMail;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Http\Traits\HelperTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,HelperTrait;

    protected $details;
    protected $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details,$event)
    {
        $this->details = $details;
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = EmailTemplate::where('event',$this->event)->first();

        $details = $this->details;
        $details->event = $this->event;

        Log::alert('detail : '.json_encode($details));
        Log::alert('message : '.json_encode($message));

        if(isset($message->id)){
            if(json_decode($message->message) != null){
                $messageContent = $this->shortCode(json_decode($message->message),$details);
            }
            else{
                $messageContent = $this->shortCode($message->message,$details);
            }
        }
        Log::alert('messageContent : '.json_encode($messageContent));

        Mail::to($details->email)->send(new NotifyMail($messageContent));
    }
}
