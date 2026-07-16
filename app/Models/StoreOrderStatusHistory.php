<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreOrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'store_order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'notes',
        'created_by',
    ];

    public function storeOrder()
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }
}
