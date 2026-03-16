<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocFolder extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'icon',
        'position',
        'created_by',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocFolder::class, 'parent_id');
    }

    public function subfolders(): HasMany
    {
        return $this->hasMany(DocFolder::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    /** Recursive eager-loadable subtree. */
    public function recursiveSubfolders(): HasMany
    {
        return $this->subfolders()->with('recursiveSubfolders');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocPage::class, 'folder_id')
            ->whereNull('parent_id')
            ->where('is_archived', false)
            ->orderBy('position')
            ->orderBy('title');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Return all descendant folder IDs (breadth-first, iterative).
     * Used by moveFolder() to guard against moving into own subtree.
     */
    public function descendantIds(): array
    {
        $ids   = [];
        $stack = $this->subfolders()->pluck('id')->toArray();

        while (! empty($stack)) {
            $id      = array_pop($stack);
            $ids[]   = $id;
            $children = DocFolder::where('parent_id', $id)->pluck('id')->toArray();
            $stack   = array_merge($stack, $children);
        }

        return $ids;
    }
}
