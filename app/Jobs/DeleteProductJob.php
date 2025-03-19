<?php

namespace App\Jobs;

use App\Models\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    protected $cartItemId;

    public function __construct($cartItemId)
    {
        $this->cartItemId = $cartItemId;
    }

    public function handle()
    {
        $cartItem = CartItem::find($this->cartItemId);

        if ($cartItem) {
            $cartItem->delete();
        }
    }
}