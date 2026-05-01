<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // ==========================================
    // Méthode principale
    // ==========================================

    /**
     * Envoie une notification à un utilisateur
     * (in-app et/ou email selon le canal)
     */
    public function send(
        User    $recipient,
        string  $type,
        string  $title,
        string  $body,
        array   $data        = [],
        string  $channel     = 'both',
        ?User   $sender      = null,
        ?string $notifiableType = null,
        ?int    $notifiableId   = null
    ): Notification {

        // Créer la notification in-app
        $notification = Notification::create([
            'user_id'         => $recipient->id,
            'type'            => $type,
            'title'           => $title,
            'body'            => $body,
            'data'            => $data,
            'channel'         => $channel,
            'is_read'         => false,
            'notifiable_type' => $notifiableType,
            'notifiable_id'   => $notifiableId,
            'sent_by'         => $sender?->id,
        ]);

        // Envoyer par email si requis
        if (in_array($channel, ['email', 'both'])) {
            $this->sendEmail($recipient->email, $title, $body, $data);
        }

        return $notification;
    }

    /**
     * Envoie une notification à plusieurs utilisateurs
     */
    public function sendBulk(
        array  $userIds,
        string $type,
        string $title,
        string $body,
        array  $data    = [],
        string $channel = 'in_app'
    ): int {

        $count = 0;
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            try {
                $this->send($user, $type, $title, $body, $data, $channel);
                $count++;
            } catch (\Exception $e) {
                Log::error('NotificationService::sendBulk error', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // ==========================================
    // Notifications métier prédéfinies
    // ==========================================

    /**
     * Notifie l'étudiant quand sa réclamation est soumise
     */
    public function notifyReclamationSubmitted(
        User   $student,
        string $reference,
        string $moduleName
    ): void {
        $this->send(
            recipient: $student,
            type:      'reclamation.submitted',
            title:     'Réclamation soumise avec succès',
            body:      "Votre réclamation #{$reference} pour le module \"{$moduleName}\" a été reçue. "
                     . "Nous la traiterons dans les meilleurs délais.",
            data:      ['reference' => $reference, 'module' => $moduleName],
            channel:   'both'
        );
    }

    /**
     * Notifie l'étudiant du changement de statut de sa réclamation
     */
    public function notifyReclamationStatusChanged(
        User   $student,
        string $reference,
        string $oldStatus,
        string $newStatus,
        ?string $adminResponse = null
    ): void {

        $statusLabels = [
            'submitted'  => 'Soumise',
            'received'   => 'Reçue',
            'in_review'  => 'En cours d\'examen',
            'resolved'   => 'Résolue',
            'rejected'   => 'Rejetée',
            'escalated'  => 'Escaladée',
        ];

        $newLabel  = $statusLabels[$newStatus]  ?? $newStatus;
        $bodyText  = "Le statut de votre réclamation #{$reference} est maintenant : **{$newLabel}**.";

        if ($adminResponse) {
            $bodyText .= "\n\nRéponse de l'administration : {$adminResponse}";
        }

        $this->send(
            recipient: $student,
            type:      'reclamation.status_changed',
            title:     "Réclamation #{$reference} — Statut mis à jour",
            body:      $bodyText,
            data:      [
                'reference'  => $reference,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            channel: 'both'
        );
    }

    /**
     * Notifie l'étudiant quand ses notes sont publiées
     */
    public function notifyNotesPublished(
        User   $student,
        string $semestreLabel,
        string $academicYear
    ): void {
        $this->send(
            recipient: $student,
            type:      'notes.published',
            title:     'Vos notes sont disponibles',
            body:      "Les notes du {$semestreLabel} ({$academicYear}) ont été publiées. "
                     . "Connectez-vous pour les consulter.",
            data:      ['semestre' => $semestreLabel, 'year' => $academicYear],
            channel:   'both'
        );
    }

    /**
     * Notifie l'étudiant qu'un document est disponible
     */
    public function notifyDocumentReady(
        User   $student,
        string $docType,
        string $docTitle
    ): void {
        $this->send(
            recipient: $student,
            type:      'document.ready',
            title:     'Document disponible',
            body:      "Votre document \"{$docTitle}\" est maintenant disponible en téléchargement.",
            data:      ['doc_type' => $docType, 'title' => $docTitle],
            channel:   'both'
        );
    }

    /**
     * Notifie l'étudiant d'une réunion programmée
     */
    public function notifyMeetingScheduled(
        User    $student,
        string  $reference,
        string  $meetingDate,
        string  $location
    ): void {
        $this->send(
            recipient: $student,
            type:      'reclamation.meeting_scheduled',
            title:     'Réunion programmée pour votre réclamation',
            body:      "Une réunion concernant votre réclamation #{$reference} est programmée "
                     . "le {$meetingDate} à {$location}.",
            data:      [
                'reference'    => $reference,
                'meeting_date' => $meetingDate,
                'location'     => $location,
            ],
            channel: 'both'
        );
    }

    /**
     * Notifie l'étudiant que son compte est bloqué
     */
    public function notifyAccountLocked(
        User   $user,
        int    $lockMinutes
    ): void {
        $this->send(
            recipient: $user,
            type:      'security.account_locked',
            title:     'Compte temporairement bloqué',
            body:      "Votre compte a été temporairement bloqué pendant {$lockMinutes} minutes "
                     . "suite à plusieurs tentatives de connexion échouées.",
            data:      ['lock_minutes' => $lockMinutes],
            channel:   'email'
        );
    }

    /**
     * Notifie l'admin d'une nouvelle réclamation
     */
    public function notifyAdminNewReclamation(
        User   $admin,
        string $reference,
        string $studentName,
        string $moduleName
    ): void {
        $this->send(
            recipient: $admin,
            type:      'admin.new_reclamation',
            title:     'Nouvelle réclamation reçue',
            body:      "L'étudiant {$studentName} a soumis une réclamation #{$reference} "
                     . "pour le module \"{$moduleName}\".",
            data:      [
                'reference'    => $reference,
                'student_name' => $studentName,
                'module'       => $moduleName,
            ],
            channel: 'in_app'
        );
    }

    /**
     * Notifie le chef de département d'une réclamation escaladée
     */
    public function notifyEscalated(
        User   $deptHead,
        string $reference,
        string $studentName,
        string $moduleName
    ): void {
        $this->send(
            recipient: $deptHead,
            type:      'admin.reclamation_escalated',
            title:     'Réclamation escaladée — Action requise',
            body:      "La réclamation #{$reference} de l'étudiant {$studentName} "
                     . "pour le module \"{$moduleName}\" vous a été escaladée pour décision finale.",
            data:      [
                'reference'    => $reference,
                'student_name' => $studentName,
                'module'       => $moduleName,
            ],
            channel: 'both'
        );
    }

    // ==========================================
    // Email privé
    // ==========================================

    private function sendEmail(
        string $to,
        string $subject,
        string $body,
        array  $data = []
    ): void {
        try {
            Mail::send(
                'emails.notification',
                ['subject' => $subject, 'body' => $body, 'data' => $data],
                function ($message) use ($to, $subject) {
                    $message->to($to)->subject($subject);
                }
            );
        } catch (\Exception $e) {
            Log::error('NotificationService::sendEmail failed', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
