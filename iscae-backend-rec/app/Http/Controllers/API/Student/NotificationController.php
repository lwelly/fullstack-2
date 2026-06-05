<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // ================================================================
    // GET /api/v1/student/notifications
    // ================================================================
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $query = Notification::forUser($user->id)->whereNull('deleted_at');

        // Filtre lu / non-lu
        if ($request->filled('read')) {
            $isRead = filter_var($request->read, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        $total       = (clone $query)->count();
        $unreadCount = (clone $query)->where('is_read', false)->count();

        $perPage     = min((int) $request->get('per_page', 15), 50);
        $currentPage = max((int) $request->get('page', 1), 1);
        $lastPage    = $total > 0 ? (int) ceil($total / $perPage) : 1;

        $rows = (clone $query)
            ->orderByDesc('created_at')
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $items = $rows->map(fn($n) => $this->normalize($n))->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'total'        => $total,
                'unread_count' => $unreadCount,
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'last_page'    => $lastPage,
            ],
        ]);
    }

    // ================================================================
    // GET /api/v1/student/notifications/counts
    // ================================================================
    public function counts(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $base = Notification::forUser($user->id)->whereNull('deleted_at');

        return response()->json([
            'success' => true,
            'data'    => [
                'total'  => (clone $base)->count(),
                'unread' => (clone $base)->where('is_read', false)->count(),
                'read'   => (clone $base)->where('is_read', true)->count(),
            ],
        ]);
    }

    // ================================================================
    // PUT /api/v1/student/notifications/read-all
    // ================================================================
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $count = Notification::forUser($user->id)
            ->whereNull('deleted_at')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marquée(s) comme lue(s).",
            'updated' => $count,
        ]);
    }

    // ================================================================
    // PUT /api/v1/student/notifications/{id}/read
    // ================================================================
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $notif = Notification::forUser($user->id)
            ->whereNull('deleted_at')
            ->find($id);

        if (! $notif) {
            return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);
        }

        $notif->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marquée comme lue.']);
    }

    // ================================================================
    // DELETE /api/v1/student/notifications/{id}
    // ================================================================
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $notif = Notification::forUser($user->id)
            ->whereNull('deleted_at')
            ->find($id);

        if (! $notif) {
            return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);
        }

        $notif->delete(); // SoftDeletes → remplit deleted_at

        return response()->json(['success' => true, 'message' => 'Notification supprimée.']);
    }

    // ================================================================
    // Helper : normaliser un Model Notification
    // ================================================================
    private function normalize(Notification $n): array
    {
        // data est déjà casté en array grâce au Model
        $data = is_array($n->data) ? $n->data : [];

        $reclamationId = $data['reclamation_id'] ?? null;

        // ── Statut ───────────────────────────────────────────────────
        $status = $data['status']
            ?? $data['reclamation_status']
            ?? $data['new_status']
            ?? null;

        // ── Nom du module ────────────────────────────────────────────
        // 1. Directement dans data (envoyé par toArray() de la Notification)
        $moduleName = $data['module']
            ?? $data['module_name']
            ?? $data['module_nom']
            ?? null;

        // 2. Sinon jointure via reclamation_id
        if (! $moduleName && $reclamationId) {
            $rec = DB::table('reclamations')
                ->leftJoin('modules', 'modules.id', '=', 'reclamations.module_id')
                ->where('reclamations.id', $reclamationId)
                ->select(
                    'modules.nom  as module_nom',
                    'modules.name as module_name',
                    'modules.code as module_code',
                )
                ->first();

            if ($rec) {
                $moduleName = $rec->module_nom
                    ?? $rec->module_name
                    ?? ($rec->module_code ? "Module {$rec->module_code}" : null);
            }
        }

        // ── Type lisible (class basename si FQCN) ───────────────────
        $type = $n->type ?? '';
        if (str_contains($type, '\\')) {
            $type = class_basename($type);
        }
        // Fusionner avec data['type'] si plus lisible
        $type = $data['type'] ?? $type;

        // ── Titre lisible ────────────────────────────────────────────
        $title = $n->title
            ?? $data['title']
            ?? $this->buildTitle($type, $moduleName);

        // ── Message ──────────────────────────────────────────────────
        $message = $n->body
            ?? $data['message']
            ?? $data['body']
            ?? '';

        return [
            'id'             => $n->id,
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'status'         => $status,
            'module_name'    => $moduleName,
            'reclamation_id' => $reclamationId,
            'reference'      => $data['reference_number'] ?? null,
            'is_read'        => (bool) $n->is_read,
            'read_at'        => $n->read_at,
            'data'           => $data,
            'created_at'     => $n->created_at,
        ];
    }

    // ── Construit un titre si absent ─────────────────────────────────
    private function buildTitle(string $type, ?string $moduleName): string
    {
        $t = strtolower($type);
        $m = $moduleName ? " — {$moduleName}" : '';

        return match (true) {
            str_contains($t, 'newreclam')     => "Réclamation soumise{$m}",
            str_contains($t, 'statuschange')  => "Statut mis à jour{$m}",
            str_contains($t, 'escalat')       => "Réclamation escaladée{$m}",
            str_contains($t, 'resolv')        => "Réclamation résolue{$m}",
            str_contains($t, 'reject')        => "Réclamation rejetée{$m}",
            str_contains($t, 'meeting')       => "Réunion planifiée{$m}",
            str_contains($t, 'new_reclam')    => "Réclamation soumise{$m}",
            str_contains($t, 'reclam')        => "Réclamation{$m}",
            default                           => 'Notification',
        };
    }
}