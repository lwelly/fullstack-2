<?php
// app/Services/ReclamationService.php

namespace App\Services;

use App\Models\Reclamation;
use App\Models\Student;
use App\Models\Module;
use App\Models\Semestre;
use App\Models\Note;
use App\Models\Admin;
use App\Models\User;
use App\Models\Setting;
use App\Exceptions\ReclamationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReclamationService
{
    public function __construct(
        private AuditService        $auditService,
        private NotificationService $notificationService
    ) {}

    // ==========================================
    // SOUMETTRE UNE RÉCLAMATION
    // ==========================================

    public function submit(
        Student         $student,
        array           $data,
        ?UploadedFile   $attachment = null,
        ?string         $ip = null
    ): Reclamation {

        // --- Vérifications métier ---
        $this->validateBusinessRules($student, $data);

        return DB::transaction(function () use ($student, $data, $attachment, $ip) {

            // Créer la réclamation
            $reclamation = Reclamation::create([
                'reference_number'  => Reclamation::generateReference(),
                'student_id'        => $student->id,
                'module_id'         => $data['module_id'],
                'semestre_id'       => $data['semestre_id'],
                'note_id'           => $data['note_id'] ?? null,
                'academic_year'     => Setting::getValue('current_academic_year', '2024-2025'),
                'type'              => $data['type'],
                'note_actuelle'     => $data['note_actuelle'] ?? null,
                'note_reclamee'     => $data['type'] === 'controle' ? ($data['note_reclamee'] ?? null) : null,
                'justification'     => $data['justification'],
                'status'            => Reclamation::STATUS_SUBMITTED,
            ]);

            // Enregistrer l'historique initial
            \App\Models\ReclamationHistory::create([
                'reclamation_id' => $reclamation->id,
                'changed_by'     => $student->user_id,
                'old_status'     => null,
                'new_status'     => Reclamation::STATUS_SUBMITTED,
                'comment'        => 'Réclamation soumise par l\'étudiant.',
                'ip_address'     => $ip,
                'changed_at'     => now(),
            ]);

            // Gérer la pièce jointe
            if ($attachment) {
                $this->storeAttachment($reclamation, $attachment);
            }

            // Notifier l'étudiant
            $this->notificationService->notifyReclamationSubmitted(
                $student->user,
                $reclamation->reference_number,
                $reclamation->module->name
            );

            // Notifier les admins
            $this->notifyAdmins($reclamation, $student);

            // Audit
            $this->auditService->logReclamationCreated(
                $student->user_id,
                $reclamation->id
            );

            return $reclamation->load(['module', 'semestre', 'attachments']);
        });
    }

    // ==========================================
    // CHANGER LE STATUT (admin)
    // ==========================================

    public function changeStatus(
        Reclamation $reclamation,
        string      $newStatus,
        User        $admin,
        ?string     $comment       = null,
        ?string     $adminResponse = null,
        ?string     $ip            = null
    ): Reclamation {

        $oldStatus = $reclamation->status;

        // Validation de la transition de statut
        $this->validateStatusTransition($oldStatus, $newStatus);

        DB::transaction(function () use (
            $reclamation, $newStatus, $admin,
            $comment, $adminResponse, $oldStatus, $ip
        ) {
            $updates = ['status' => $newStatus];

            if ($adminResponse) {
                $updates['admin_response'] = $adminResponse;
                $updates['responded_by']   = $admin->admin?->id;
                $updates['responded_at']   = now();
            }

            if (in_array($newStatus, [Reclamation::STATUS_RESOLVED, Reclamation::STATUS_REJECTED])) {
                $updates['resolved_at'] = now();
            }

            $reclamation->update($updates);

            // Historique
            \App\Models\ReclamationHistory::create([
                'reclamation_id' => $reclamation->id,
                'changed_by'     => $admin->id,
                'old_status'     => $oldStatus,
                'new_status'     => $newStatus,
                'comment'        => $comment,
                'ip_address'     => $ip,
                'changed_at'     => now(),
            ]);

            // Notifier l'étudiant
            $this->notificationService->notifyReclamationStatusChanged(
                $reclamation->student->user,
                $reclamation->reference_number,
                $oldStatus,
                $newStatus,
                $adminResponse
            );

            // Audit
            $this->auditService->logReclamationStatusChanged(
                $admin->id,
                $admin->role,
                $reclamation->id,
                $oldStatus,
                $newStatus
            );
        });

        return $reclamation->fresh(['student', 'module', 'semestre', 'history']);
    }

    // ==========================================
    // ESCALADER UNE RÉCLAMATION
    // ==========================================

    public function escalate(
        Reclamation $reclamation,
        Admin       $escalatedTo,
        User        $escalatedBy,
        ?string     $reason = null,
        ?string     $ip     = null
    ): Reclamation {

        if ($reclamation->is_escalated) {
            throw new ReclamationException(
                'Cette réclamation est déjà escaladée.',
                'ALREADY_ESCALATED'
            );
        }

        DB::transaction(function () use ($reclamation, $escalatedTo, $escalatedBy, $reason, $ip) {

            $reclamation->update([
                'status'       => Reclamation::STATUS_ESCALATED,
                'is_escalated' => true,
                'escalated_at' => now(),
                'escalated_to' => $escalatedTo->id,
            ]);

            \App\Models\ReclamationHistory::create([
                'reclamation_id' => $reclamation->id,
                'changed_by'     => $escalatedBy->id,
                'old_status'     => Reclamation::STATUS_IN_REVIEW,
                'new_status'     => Reclamation::STATUS_ESCALATED,
                'comment'        => $reason ?? 'Réclamation escaladée au chef de département.',
                'ip_address'     => $ip,
                'changed_at'     => now(),
            ]);

            // Notifier le chef de département
            $this->notificationService->notifyEscalated(
                $escalatedTo->user,
                $reclamation->reference_number,
                $reclamation->student->full_name,
                $reclamation->module->name
            );

            // Notifier l'étudiant
            $this->notificationService->notifyReclamationStatusChanged(
                $reclamation->student->user,
                $reclamation->reference_number,
                Reclamation::STATUS_IN_REVIEW,
                Reclamation::STATUS_ESCALATED
            );
        });

        return $reclamation->fresh();
    }

    // ==========================================
    // PROGRAMMER UNE RÉUNION
    // ==========================================

    public function scheduleMeeting(
        Reclamation $reclamation,
        string      $meetingAt,
        string      $location,
        User        $admin
    ): Reclamation {

        $reclamation->update([
            'meeting_scheduled_at' => $meetingAt,
            'meeting_location'     => $location,
        ]);

        $this->notificationService->notifyMeetingScheduled(
            $reclamation->student->user,
            $reclamation->reference_number,
            $meetingAt,
            $location
        );

        return $reclamation->fresh();
    }

    // ==========================================
    // Validation des règles métier
    // ==========================================

    private function validateBusinessRules(Student $student, array $data): void
    {
        $module   = Module::findOrFail($data['module_id']);
        $semestre = Semestre::findOrFail($data['semestre_id']);

        // 1. Le semestre doit être ouvert
        if (!$semestre->isAcceptingReclamations()) {
            throw new ReclamationException(
                'Les réclamations ne sont pas ouvertes pour ce semestre.',
                'SEMESTRE_CLOSED'
            );
        }

        // 2. Le module doit appartenir à la filière de l'étudiant
        if (!$student->canAccessModule($module)) {
            throw new ReclamationException(
                'Ce module n\'appartient pas à votre filière.',
                'MODULE_NOT_IN_FILIERE'
            );
        }

        // 3. Le semestre doit correspondre au niveau de l'étudiant
        $allowedCodes = $student->getAllowedSemestreCodes();
        if (!in_array($semestre->code, $allowedCodes)) {
            throw new ReclamationException(
                'Ce semestre ne correspond pas à votre niveau.',
                'SEMESTRE_LEVEL_MISMATCH'
            );
        }

        // 4. Les notes doivent être publiées
        $note = Note::where('student_id', $student->id)
                    ->where('module_id', $data['module_id'])
                    ->where('is_published', true)
                    ->first();

        if (!$note) {
            throw new ReclamationException(
                'Vos notes pour ce module ne sont pas encore publiées.',
                'NOTES_NOT_PUBLISHED'
            );
        }

        // 5. Pas de doublon (même module + type + année)
        $currentYear = Setting::getValue('current_academic_year', '2024-2025');
        $exists = Reclamation::where('student_id', $student->id)
                             ->where('module_id', $data['module_id'])
                             ->where('type', $data['type'])
                             ->where('academic_year', $currentYear)
                             ->exists();

        if ($exists) {
            throw new ReclamationException(
                'Vous avez déjà soumis une réclamation de ce type pour ce module.',
                'DUPLICATE_RECLAMATION'
            );
        }

                   
            // 6. Max réclamations actives
            $max = (int) Setting::getValue('reclamation_max_active', 3);
            $activeCount = Reclamation::where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'received', 'in_review', 'escalated'])
                ->count();
            if ($activeCount >= $max) {
                throw new ReclamationException(
                    "Vous avez atteint le nombre maximum de réclamations actives ({$max}).",
                    'MAX_RECLAMATIONS_REACHED'
                );
            }


        // 7. note_reclamee obligatoire pour controle
        if ($data['type'] === 'controle' && empty($data['note_reclamee'])) {
            throw new ReclamationException(
                'La note réclamée est obligatoire pour une réclamation de type contrôle.',
                'NOTE_RECLAMEE_REQUIRED'
            );
        }
    }

    // ==========================================
    // Validation des transitions de statut
    // ==========================================

   private function validateStatusTransition(string $from, string $to): void
{
    $allowed = [
        'submitted'  => ['received', 'in_review', 'resolved', 'rejected'],
        'received'   => ['in_review', 'resolved', 'rejected'],
        'in_review'  => ['resolved', 'rejected', 'escalated'],
        'escalated'  => ['resolved', 'rejected', 'in_review'],
        'resolved'   => [],
        'rejected'   => [],
    ];

    if (!in_array($to, $allowed[$from] ?? [])) {
        throw new \Exception("Transition de statut invalide : {$from} → {$to}.");
    }
}



    // ==========================================
    // Stocker la pièce jointe
    // ==========================================

    private function storeAttachment(Reclamation $reclamation, UploadedFile $file): void
    {
        $maxSizeMb  = (int) Setting::getValue('max_upload_size_mb', 10);
        $maxSizeB   = $maxSizeMb * 1024 * 1024;

        if ($file->getSize() > $maxSizeB) {
            throw new ReclamationException(
                "Le fichier dépasse la taille maximale autorisée ({$maxSizeMb} MB).",
                'FILE_TOO_LARGE'
            );
        }

        $allowedTypes = explode(',', Setting::getValue('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx'));
        $extension    = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedTypes)) {
            throw new ReclamationException(
                'Type de fichier non autorisé.',
                'FILE_TYPE_NOT_ALLOWED'
            );
        }

        // Nom de fichier sécurisé avec UUID
        $storedName = Str::uuid() . '.' . $extension;
        $path       = $file->storeAs(
            'reclamations/' . $reclamation->id,
            $storedName,
            'private'
        );

        \App\Models\ReclamationAttachment::create([
            'reclamation_id' => $reclamation->id,
            'original_name'  => $file->getClientOriginalName(),
            'stored_name'    => $storedName,
            'storage_path'   => $path,
            'mime_type'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
            'is_scanned'     => false,
            'is_safe'        => false,
        ]);
    }

    // ==========================================
    // Notifier les admins d'une nouvelle réclamation
    // ==========================================

    private function notifyAdmins(Reclamation $reclamation, Student $student): void
    {
        // Notifier les super_admins et admins du département concerné
        $filiereId = $student->filiere_id;
        $deptId    = $student->filiere?->department_id;

        $admins = Admin::whereHas('user', fn($q) => $q->where('is_active', true))
                       ->where(function ($q) use ($deptId) {
                           $q->where('role_label', 'super_admin')
                             ->orWhere(function ($q2) use ($deptId) {
                                 $q2->where('department_id', $deptId)
                                    ->whereIn('role_label', ['admin', 'department_head', 'staff']);
                             });
                       })
                       ->with('user')
                       ->get();

        foreach ($admins as $admin) {
            $this->notificationService->notifyAdminNewReclamation(
                $admin->user,
                $reclamation->reference_number,
                $student->full_name,
                $reclamation->module->name
            );
        }
    }
}
