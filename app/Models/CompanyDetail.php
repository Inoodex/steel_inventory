<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    use HasFactory;

    protected $table = 'company_details';

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Alias $company->name to $company->company_name for compatibility
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['company_name'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['company_name'] = $value;
    }

    /**
     * Alias $company->signature_image to $company->signature_path
     */
    public function getSignatureImageAttribute(): ?string
    {
        return $this->attributes['signature_path'] ?? null;
    }

    /**
     * Alias $company->logo to $company->logo_path
     */
    public function getLogoAttribute(): ?string
    {
        return $this->attributes['logo_path'] ?? null;
    }

    /**
     * Virtual is_active fallback (always true if record exists)
     */
    public function getIsActiveAttribute(): bool
    {
        return true;
    }

    // Scope for active company details
    public function scopeActive($query)
    {
        return $query;
    }

    // Scope for default company detail
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}