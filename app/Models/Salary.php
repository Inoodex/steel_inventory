<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'month_year',
        'basic_salary',
        'advance',
        'advance_deduction',
        'allowance',
        'deduction',
        'tada_amount',
        'net_salary',
        'payment_status',
        'status',
        'payment_date',
        'payment_method',
        'note',
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}