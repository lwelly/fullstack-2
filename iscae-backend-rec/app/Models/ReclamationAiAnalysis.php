<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamationAiAnalysis extends Model
{
    protected $fillable = [
        'reclamation_id',
        'attachment_id',
        'pdf_extracted_text',
        'pdf_parsed_successfully',
        'pdf_parse_error',
        'prompt_sent',
        'raw_response',
        'decision',
        'confidence_score',
        'explanation',
        'recommendation',
        'note_from_pdf',
        'note_from_db',
        'note_claimed',
        'note_discrepancy_found',
        'discrepancy_details',
        'openai_model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'processing_time_ms',
        'analyzed_by',
    ];

    protected $casts = [
        'pdf_parsed_successfully' => 'boolean',
        'note_discrepancy_found'  => 'boolean',
        'confidence_score'        => 'decimal:2',
        'note_from_pdf'           => 'decimal:2',
        'note_from_db'            => 'decimal:2',
        'note_claimed'            => 'decimal:2',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(ReclamationAttachment::class, 'attachment_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    // ─── Accesseurs ──────────────────────────────────────────────────────────

    public function isFounded(): bool
    {
        return $this->decision === 'founded';
    }

    public function isUnfounded(): bool
    {
        return $this->decision === 'unfounded';
    }

    public function isUncertain(): bool
    {
        return $this->decision === 'uncertain';
    }

    public function getConfidencePercentAttribute(): int
    {
        return (int) round(($this->confidence_score ?? 0) * 100);
    }

    public function getDecisionLabelAttribute(): string
    {
        return match ($this->decision) {
            'founded'   => 'Réclamation fondée',
            'unfounded' => 'Réclamation non fondée',
            'uncertain' => 'Résultat incertain',
            default     => 'Non analysé',
        };
    }

    public function getDecisionColorAttribute(): string
    {
        return match ($this->decision) {
            'founded'   => 'success',
            'unfounded' => 'error',
            'uncertain' => 'warning',
            default     => 'grey',
        };
    }
}