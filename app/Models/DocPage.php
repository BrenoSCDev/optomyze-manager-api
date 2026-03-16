<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocPage extends Model
{
    protected $fillable = [
        'folder_id',
        'parent_id',
        'title',
        'icon',
        'cover_url',
        'content',
        'is_archived',
        'archived_at',
        'position',
        'created_by',
    ];

    protected $casts = [
        'content'     => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocFolder::class, 'folder_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocPage::class, 'parent_id');
    }

    /** Direct child pages (one level deep). */
    public function children(): HasMany
    {
        return $this->hasMany(DocPage::class, 'parent_id')
            ->where('is_archived', false)
            ->orderBy('position')
            ->orderBy('title');
    }

    /** Recursive eager-loadable subtree. */
    public function recursiveChildren(): HasMany
    {
        return $this->children()->with('recursiveChildren');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'doc_favorites', 'page_id', 'user_id')
            ->withTimestamps();
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Walk up the ancestor chain and return an ordered breadcrumb array.
     * Stops at root (no parent) or at folder boundary.
     */
    public function breadcrumbs(): array
    {
        $crumbs  = [];
        $current = $this;

        while ($current !== null) {
            array_unshift($crumbs, [
                'id'    => $current->id,
                'title' => $current->title,
                'icon'  => $current->icon,
            ]);
            $current = $current->parent_id
                ? DocPage::find($current->parent_id)
                : null;
        }

        return $crumbs;
    }

    /**
     * Collect all descendant page IDs (iterative BFS).
     * Used to guard against moving a page into its own subtree.
     */
    public function descendantIds(): array
    {
        $ids   = [];
        $stack = $this->children()->pluck('id')->toArray();

        while (! empty($stack)) {
            $id      = array_pop($stack);
            $ids[]   = $id;
            $children = DocPage::where('parent_id', $id)->pluck('id')->toArray();
            $stack   = array_merge($stack, $children);
        }

        return $ids;
    }

    /**
     * Whether the given user has favorited this page.
     */
    public function isFavoritedBy(int $userId): bool
    {
        return $this->favoritedBy()->where('user_id', $userId)->exists();
    }
}
