<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_type',
        'status',
        'transaction_id',
        'amount', // Add this field if you want to track payment amount separately
    ];

    /**
     * Get the order associated with the payment
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
