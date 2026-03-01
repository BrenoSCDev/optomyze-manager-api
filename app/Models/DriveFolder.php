<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriveFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'parent_id',
        'name',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id'  => 'integer',
        'parent_id'  => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class, 'parent_id');
    }

    public function subfolders(): HasMany
    {
        return $this->hasMany(DriveFolder::class, 'parent_id');
    }

    /**
     * Eager-loadable recursive subfolders tree.
     * Usage: DriveFolder::with('recursiveSubfolders')->find($id)
     */
    public function recursiveSubfolders(): HasMany
    {
        return $this->subfolders()->with('recursiveSubfolders');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DriveFile::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope to root-level folders (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope by company (multi-tenancy ready).
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to folders associated with a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Build the breadcrumb trail from root to this folder.
     * Returns ordered array of ancestor folders (root first).
     *
     * Note: This traverses via loaded parent relationships.
     * For deep trees, consider a Closure Table or Materialized Path pattern.
     */
    public function breadcrumbs(): array
    {
        $ancestors = [];
        $current   = $this;

        while ($current->parent_id !== null) {
            $current = $current->parent()->with('parent')->first();
            if (! $current) {
                break;
            }
            array_unshift($ancestors, [
                'id'   => $current->id,
                'name' => $current->name,
            ]);
        }

        $ancestors[] = [
            'id'   => $this->id,
            'name' => $this->name,
        ];

        return $ancestors;
    }

    /**
     * Collect all descendant folder IDs (for bulk operations / cycle detection).
     * Avoids recursion limit by using an iterative approach.
     */
    public function descendantIds(): array
    {
        $ids    = [];
        $stack  = [$this->id];

        while (! empty($stack)) {
            $parentId   = array_pop($stack);
            $children   = DriveFolder::where('parent_id', $parentId)->pluck('id')->toArray();

            foreach ($children as $childId) {
                $ids[]   = $childId;
                $stack[] = $childId;
            }
        }

        return $ids;
    }
}
