<?php

namespace App\Listeners;

use App\Events\PaymentSuccessful;
use App\Jobs\SendAdminOrderNotificationEmail;
use App\Jobs\SendCustomerInvoiceEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceEmails
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessful $event): void
    {
        $user = $event->order->user;

        SendCustomerInvoiceEmail::dispatch($event->order, $event->payment, $user);

        SendAdminOrderNotificationEmail::dispatch($event->order, $event->payment);
    }
}
