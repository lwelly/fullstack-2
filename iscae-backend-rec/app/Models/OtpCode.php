<?php
// app/Models/OtpCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class OtpCode extends Model
{
    protected $table = 'otp_codes';

    protected $fillable = [
        'user_id',
        'preloaded_id',
        'type',
        'code_hash',
        'attempts',
        'max_attempts',
        'is_used',
        'expires_at',
        'ip_address',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_used'    => 'boolean',
            'attempts'   => 'integer',
            'max_attempts'=> 'integer',
            'expires_at' => 'datetime',
        ];
    }

    // ==========================================
    // Relations
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preloaded(): BelongsTo
    {
        return $this->belongsTo(StudentPreloaded::class, 'preloaded_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                     ->where('expires_at', '>', now());
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==========================================
    // Helpers
    // ==========================================

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function verify(string $plainCode): bool
    {
        if ($this->isExpired() || $this->isExhausted() || $this->is_used) {
            return false;
        }

        $this->increment('attempts');

        if (Hash::check($plainCode, $this->code_hash)) {
            $this->update(['is_used' => true]);
            return true;
        }

        return false;
    }

    public function getRemainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts);
    }
}
