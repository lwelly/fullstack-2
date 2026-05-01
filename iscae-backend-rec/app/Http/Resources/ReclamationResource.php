<?php
// app/Http/Resources/ReclamationResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReclamationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'type'             => $this->type,
            'status'           => $this->status,
            'academic_year'    => $this->academic_year,
            'note_actuelle'    => $this->note_actuelle,
            'note_reclamee'    => $this->note_reclamee,
            'justification'    => $this->justification,
            'admin_response'   => $this->admin_response,
            'is_escalated'     => $this->is_escalated,
            'meeting_at'       => $this->meeting_scheduled_at?->format('Y-m-d H:i'),
            'meeting_location' => $this->meeting_location,
            'resolved_at'      => $this->resolved_at?->format('Y-m-d H:i'),
            'module'           => [
                'id'   => $this->module?->id,
                'code' => $this->module?->code,
                'name' => $this->module?->name,
            ],
            'semestre'         => [
                'id'    => $this->semestre?->id,
                'code'  => $this->semestre?->code,
                'label' => $this->semestre?->label,
            ],
            'student' => $this->when(
    request()->is('api/v1/admin/*'),
    fn() => [
        'id'        => $this->student?->id,
        'matricule' => $this->student?->matricule,
        'name'      => trim(($this->student?->prenom ?? '') . ' ' . ($this->student?->nom ?? '')),
        'email'     => $this->student?->email,
        'filiere'   => $this->student?->filiere?->name ?? $this->student?->filiere?->code ?? '—',
        'niveau'    => $this->student?->niveau?->name ?? $this->student?->niveau?->code ?? '—',
    ]
),

            'attachments'      => ReclamationAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),
            'history'          => ReclamationHistoryResource::collection(
                $this->whenLoaded('history')
            ),
            'created_at'       => $this->created_at->format('Y-m-d H:i'),
            'updated_at'       => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
