<?php
// app/Http/Controllers/API/Admin/ReclamationController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use App\Services\ReclamationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;       // ✅ Import correct
use Illuminate\Support\Facades\Log;

class ReclamationController extends Controller
{
    public function __construct(
        private ReclamationService $reclamationService
    ) {}

    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/admin/reclamations
    // ══════════════════════════════════════════════════════════════════
    public function index(Request $request): JsonResponse
    {
        $query = Reclamation::with([
            'student.filiere',
            'student.niveau',
            'module',
            'semestre',
            'attachments',
        ])->whereNull('deleted_at');

        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('type'))        $query->where('type', $request->type);
        if ($request->filled('semestre_id')) $query->where('semestre_id', $request->semestre_id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference_number', 'like', "%{$s}%")
                  ->orWhereHas('student', fn($sq) =>
                      $sq->where('nom',       'like', "%{$s}%")
                         ->orWhere('prenom',   'like', "%{$s}%")
                         ->orWhere('matricule','like', "%{$s}%")
                  );
            });
        }

        $perPage      = (int) $request->input('per_page', 15);
        $reclamations = $query->orderByDesc('created_at')->paginate($perPage);

        // Formater sans Resource pour éviter les erreurs
        $data = $reclamations->getCollection()->map(fn($r) => $this->formatReclamation($r));

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'        => $reclamations->total(),
                'current_page' => $reclamations->currentPage(),
                'last_page'    => $reclamations->lastPage(),
                'per_page'     => $reclamations->perPage(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // GET /api/v1/admin/reclamations/{id}
    // ══════════════════════════════════════════════════════════════════
    public function show(int $id): JsonResponse
    {
        $reclamation = Reclamation::with([
            'student.filiere',
            'student.niveau',
            'module',
            'semestre',
            'attachments',
            'history.changedBy',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->formatReclamation($reclamation, true),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PUT /api/v1/admin/reclamations/{id}/status
    // ══════════════════════════════════════════════════════════════════
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'         => 'required|string|in:received,in_review,resolved,rejected,escalated,pending,cancelled',
            'admin_response' => 'nullable|string|max:2000',
            'comment'        => 'nullable|string|max:2000',
        ]);

        // ✅ Charger avec relations pour l'Observer
        $reclamation = Reclamation::with(['student', 'module'])->findOrFail($id);
        $oldStatus   = $reclamation->status;
        $newStatus   = $request->status;
        $adminId     = auth()->id();
        $response    = $request->admin_response ?? $request->comment ?? null;

        DB::beginTransaction();
        try {
            // ✅ Utiliser Eloquent update → déclenche ReclamationObserver::updated()
            $updateData = [
                'status'      => $newStatus,
                'changed_by'  => $adminId,
                'responded_by'=> $adminId,
                'responded_at'=> now(),
            ];

            if ($response) {
                $updateData['admin_response'] = $response;
            }

            if (in_array($newStatus, ['resolved', 'rejected'])) {
                $updateData['resolved_at'] = now();
            }

            // ✅ Eloquent update déclenche l'Observer
            $reclamation->update($updateData);

            // ── Historique ──────────────────────────────────────────
            if (DB::getSchemaBuilder()->hasTable('reclamation_history')) {
                DB::table('reclamation_history')->insert([
                    'reclamation_id' => $reclamation->id,
                    'old_status'     => $oldStatus,
                    'new_status'     => $newStatus,
                    'comment'        => $response ?? "Statut changé : {$oldStatus} → {$newStatus}",
                    'changed_by'     => $adminId,
                    'ip_address'     => $request->ip(),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Admin] updateStatus erreur', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage(),
            ], 500);
        }

        Log::info('[Admin] updateStatus', [
            'reclamation_id' => $id,
            'old'            => $oldStatus,
            'new'            => $newStatus,
            'admin_id'       => $adminId,
        ]);

        // Recharger la réclamation à jour
        $reclamation = Reclamation::with(['student.filiere', 'student.niveau', 'module', 'semestre'])->find($id);

        return response()->json([
            'success' => true,
            'message' => "Statut mis à jour : {$oldStatus} → {$newStatus}",
            'data'    => $this->formatReclamation($reclamation),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/v1/admin/reclamations/{id}/escalate
    // ══════════════════════════════════════════════════════════════════
    public function escalate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'escalated_to' => 'required|integer',
            'reason'       => 'nullable|string|max:1000',
        ]);

        $reclamation = Reclamation::with(['student', 'module'])->findOrFail($id);

        try {
            $updated = $this->reclamationService->escalate(
                $reclamation,
                $request->escalated_to,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Réclamation escaladée avec succès.',
                'data'    => $this->formatReclamation($updated),
            ]);

        } catch (\Throwable $e) {
            Log::error('[Admin] escalate erreur', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // POST /api/v1/admin/reclamations/{id}/meeting
    // ══════════════════════════════════════════════════════════════════
    public function scheduleMeeting(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'meeting_at' => 'required|date|after:now',
            'location'   => 'nullable|string|max:255',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $reclamation = Reclamation::with(['student', 'module'])->findOrFail($id);

        try {
            $updated = $this->reclamationService->scheduleMeeting(
                $reclamation,
                $request->meeting_at,
                $request->location,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Réunion programmée avec succès.',
                'data'    => $this->formatReclamation($updated),
            ]);

        } catch (\Throwable $e) {
            Log::error('[Admin] scheduleMeeting erreur', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER — Formater une réclamation
    // ══════════════════════════════════════════════════════════════════
    private function formatReclamation(Reclamation $r, bool $withHistory = false): array
    {
        $student = $r->relationLoaded('student') ? $r->student : null;
        $module  = $r->relationLoaded('module')  ? $r->module  : null;
        $semestre= $r->relationLoaded('semestre') ? $r->semestre: null;

        $data = [
            'id'               => $r->id,
            'reference_number' => $r->reference_number,
            'type'             => $r->type,
            'status'           => $r->status,
            'note_actuelle'    => $r->note_actuelle,
            'note_reclamee'    => $r->note_reclamee,
            'justification'    => $r->justification,
            'admin_response'   => $r->admin_response,
            'academic_year'    => $r->academic_year,
            'responded_at'     => $r->responded_at,
            'resolved_at'      => $r->resolved_at,
            'created_at'       => $r->created_at,
            'updated_at'       => $r->updated_at,

            // Relations
            'student' => $student ? [
                'id'         => $student->id,
                'matricule'  => $student->matricule,
                'nom'        => $student->nom,
                'prenom'     => $student->prenom,
                'full_name'  => trim(($student->prenom ?? '') . ' ' . ($student->nom ?? '')),
                'email'      => $student->email,
                'photo_url'  => $student->photo_path
                    ? \Illuminate\Support\Facades\Storage::url($student->photo_path)
                    : null,
                'filiere'    => $student->filiere?->nom   ?? null,
                'niveau'     => $student->niveau?->label  ?? null,
            ] : null,

            'module' => $module ? [
                'id'          => $module->id,
                'code'        => $module->code,
                'name'        => $module->name,
                'coefficient' => $module->coefficient,
            ] : null,

            'semestre' => $semestre ? [
                'id'    => $semestre->id,
                'code'  => $semestre->code,
                'label' => $semestre->label,
            ] : null,

            'attachments' => $r->relationLoaded('attachments')
                ? $r->attachments->map(fn($a) => [
                    'id'       => $a->id,
                    'filename' => $a->filename ?? $a->original_name ?? null,
                    'url'      => $a->file_path
                        ? \Illuminate\Support\Facades\Storage::url($a->file_path)
                        : null,
                ])->toArray()
                : [],
        ];

        // Historique (pour show uniquement)
        if ($withHistory && $r->relationLoaded('history')) {
            $data['history'] = $r->history->map(fn($h) => [
                'id'         => $h->id,
                'old_status' => $h->old_status,
                'new_status' => $h->new_status,
                'comment'    => $h->comment,
                'changed_by' => $h->changedBy?->email ?? null,
                'created_at' => $h->created_at,
            ])->toArray();
        }

        return $data;
    }
}
