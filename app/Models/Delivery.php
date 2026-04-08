<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable =[
        'order_id',
        'user_id',
        'status',
        'assigned_at',
        'accepted_at',
        'delivered_at'
    ];
    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DELIVERED = 'delivered';


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
