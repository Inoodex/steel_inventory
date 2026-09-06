<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerPayoutItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_payout_id',
        'sale_id',
        'charge_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payout()
    {
        return $this->belongsTo(WorkerPayout::class, 'worker_payout_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
