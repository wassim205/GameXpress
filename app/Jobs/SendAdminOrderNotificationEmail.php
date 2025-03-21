<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminOrderNotification;

class SendAdminOrderNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $payment;
    

    public function __construct(Order $order, Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
    }

    public function handle()
    {
        // You could get admin emails from a setting or role
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin)
            ->send(new AdminOrderNotification($this->order, $this->payment, $admin));
        }
    }
}