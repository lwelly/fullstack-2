<?php
// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'channel',
        'is_read',
        'read_at',
        'notifiable_type',
        'notifiable_id',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'data'     => 'array',
            'is_read'  => 'boolean',
            'read_at'  => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}
