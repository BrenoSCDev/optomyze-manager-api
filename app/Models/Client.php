<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{

    protected $fillable = [
        'company_name',
        'legal_name',
        'industry',
        'employees',
        'tax_id',
        'contact_name',
        'position',
        'email',
        'phone',
        'secondary_phone',
        'website',
        'instagram',
        'linkedin',
        'facebook',
        'twitter_x',
        'youtube',
        'tiktok',
        'country',
        'state',
        'city',
        'address',
        'zip_code',
        'value',
        'status',
        'source',
        'priority',
        'notes',
        'crm_active',
        'prospect_folder_id',
        'prospect_tags' => 'array',
        'client_tags' => 'array',
        'closed_at',
        'lost_at',
        'lost_reason'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'crm_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (Reusable Methods)
    |--------------------------------------------------------------------------
    */

    // Only active in CRM
    public function scopeActiveCRM($query)
    {
        return $query->where('crm_active', true);
    }

    // Only prospects
    public function scopeProspects($query)
    {
        return $query->where('status', 'prospect');
    }

    // Only active clients
    public function scopeActiveClients($query)
    {
        return $query->where('status', 'active');
    }

    // Only leads
    public function scopeLeads($query)
    {
        return $query->where('status', 'lead');
    }

    // Only inactive clients
    public function scopeInactiveClients($query)
    {
        return $query->where('status', 'inactive');
    }

    // High priority
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    // Search (name, company, email)
    public function scopeSearch($query, $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('company_name', 'like', "%$term%")
              ->orWhere('contact_name', 'like', "%$term%")
              ->orWhere('email', 'like', "%$term%");
        });
    }

    public function contracts()
    {
        return $this->hasMany(ClientContract::class);
    }

    public function payments()
    {
        return $this->hasMany(ClientPayment::class);
    }

    public function prospectFolder()
    {
        return $this->belongsTo(ProspectFolder::class);
    }

    public function prospectContacts()
    {
        return $this->hasMany(ProspectContact::class);
    }
}
