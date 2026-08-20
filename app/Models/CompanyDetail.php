<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Scope for active company details
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for default company detail
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}