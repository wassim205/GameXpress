<?php

namespace App\Models;

use App\Notifications\StockLowNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Traits\HasRoles;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'stock',
        'status',
        'category_id'
    ];
   
}
