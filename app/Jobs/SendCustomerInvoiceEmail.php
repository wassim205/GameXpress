<?php

namespace App\Jobs;

use App\Mail\CustomerInvoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCustomerInvoiceEmail implements ShouldQueue
{
    use Queueable,InteractsWithQueue,SerializesModels,Dispatchable;

    protected $order;
    protected $payment;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, Payment $payment,User $user)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->user = $order->user;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(new CustomerInvoice($this->order, $this->payment, $this->user));
    }
}
