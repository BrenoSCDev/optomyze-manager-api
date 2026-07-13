<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_category_id',
        'client_id',
        'title',
        'description',
        'priority',
        'due_date',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * Scope a query to only include non-archived tasks.
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope a query to only include archived tasks.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user')
                    ->select(['users.id', 'users.name', 'users.email', 'users.avatar', 'users.title', 'users.phone']);
    }

    public function docs()
    {
        return $this->hasMany(TaskDoc::class, 'task_id');
    }

}
