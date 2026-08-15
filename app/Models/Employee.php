<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'name',
        'email',
        'phone',
        'designation',
        'join_date',
        'salary',
        'image',
        'status',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

    public function tadas()
    {
        return $this->hasMany(TaDa::class, 'employee_id', 'id');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class, 'employee_id', 'id');
    }

    public function dailyExpenses()
    {
        return $this->hasMany(DailyExpense::class, 'employee_id', 'id');
    }
}