<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'avatar',
        'title',
        'department_id',
        'phone_secondary',
        'address',
        'city',
        'state',
        'country',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    /**
     * Get the user's full name with title
     */

    public function getFullNameAttribute(): string
    {
        return $this->title ? "{$this->name} - {$this->title}" : $this->name;
    }

    /**
     * Get user initials for avatar fallback
     */
    public function getInitialsAttribute(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        return substr($initials, 0, 2);
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) return null;
        
        // Simple US phone formatting
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6)
            );
        }
        return $this->phone;
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for active users only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for admins only
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope for agents only
     */
    public function scopeAgents($query)
    {
        return $query->where('role', 'agent');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is agent
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get user's avatar URL or default
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            // If it's a full URL, return as is
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            // Otherwise, prepend storage path
            return asset('storage/' . $this->avatar);
        }
        
        // Default avatar using UI Avatars service
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&background=000000&color=ffffff";
    }

    // ============================================
    // RELATIONSHIPS (for future entities)
    // ============================================

    /**
     * Get clients assigned to this user
     */
    // public function clients()
    // {
    //     return $this->hasMany(Client::class, 'assigned_to');
    // }

    /**
     * Get tasks assigned to this user
     */
    // public function tasks()
    // {
    //     return $this->hasMany(Task::class, 'assigned_to');
    // }

    /**
     * Get user department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get prospects assigned to this user
     */
    // public function prospects()
    // {
    //     return $this->hasMany(Prospect::class, 'assigned_to');
    // }
}