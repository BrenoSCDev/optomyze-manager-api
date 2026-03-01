<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DriveFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'folder_id',
        'client_id',
        'original_name',
        'stored_name',
        'disk',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'company_id'  => 'integer',
        'folder_id'   => 'integer',
        'client_id'   => 'integer',
        'size'        => 'integer',
        'uploaded_by' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Fields that must never be returned in API responses.
     */
    protected $hidden = [
        'stored_name',
        'disk',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Human-readable file size (e.g. "2.4 MB").
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }

        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Check whether the backing file still exists on the configured disk.
     */
    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->stored_name);
    }
}
