<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'reclamation_id'          => $this->reclamation_id,

            // Décision
            'decision'                => $this->decision,
            'decision_label'          => $this->decision_label,
            'decision_color'          => $this->decision_color,
            'confidence_score'        => (float) $this->confidence_score,
            'confidence_percent'      => $this->confidence_percent,
            'explanation'             => $this->explanation,
            'recommendation'          => $this->recommendation,

            // Comparaison des notes
            'notes' => [
                'from_pdf'          => $this->note_from_pdf ? (float) $this->note_from_pdf : null,
                'from_db'           => $this->note_from_db ? (float) $this->note_from_db : null,
                'claimed'           => $this->note_claimed ? (float) $this->note_claimed : null,
                'discrepancy_found' => $this->note_discrepancy_found,
                'discrepancy_details' => $this->discrepancy_details,
            ],

            // Extraction PDF
            'pdf' => [
                'parsed_successfully' => $this->pdf_parsed_successfully,
                'parse_error'         => $this->pdf_parse_error,
                'text_available'      => ! empty($this->pdf_extracted_text),
            ],

            // Métadonnées
            'metadata' => [
                'model'           => $this->openai_model,
                'total_tokens'    => $this->total_tokens,
                'processing_time' => $this->processing_time_ms ? $this->processing_time_ms . 'ms' : null,
                'analyzed_by'     => $this->analyst?->name,
                'analyzed_at'     => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}