<?php
// app/Models/ReclamationAttachment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamationAttachment extends Model
{
    protected $table = 'reclamation_attachments';

    public $timestamps = false;

    protected $fillable = [
        'reclamation_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'disk',
        'is_scanned',
        'scanned_at',
        'is_safe',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_scanned' => 'boolean',
            'is_safe'    => 'boolean',
            'file_size'  => 'integer',
        ];
    }

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->storage_path);
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }
}
