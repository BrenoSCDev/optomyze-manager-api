<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_datetime',
        'end_datetime',
        'is_all_day',
        'color',
        'recurrence',
        'recurrence_ends_on',
        'created_by',
    ];

    protected $casts = [
        'start_datetime'     => 'datetime',
        'end_datetime'       => 'datetime',
        'recurrence_ends_on' => 'date',
        'is_all_day'         => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_event_user')
                    ->select(['users.id', 'users.name', 'users.email', 'users.avatar', 'users.title']);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeInRange($query, string $from, string $to)
    {
        return $query->where('start_datetime', '>=', $from)
                     ->where('end_datetime',   '<=', $to);
    }
}
