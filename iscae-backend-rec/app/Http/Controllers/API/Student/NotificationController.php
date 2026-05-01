<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // ── GET /student/notifications ───────────────────────
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $notifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($n) => $this->format($n));

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    // ── GET /student/notifications/{id} ──────────────────
    public function show($id): JsonResponse
    {
        $user = Auth::user();

        $notif = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Notification introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->format($notif)]);
    }

    // ── PUT /student/notifications/{id}/read ─────────────
    public function markAsRead($id): JsonResponse
    {
        $user = Auth::user();

        $notif = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Notification introuvable.'], 404);
        }

        DB::table('notifications')->where('id', $id)->update([
            'is_read'    => true,
            'read_at'    => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Notification marquée comme lue.']);
    }

    // ── PUT /student/notifications/read-all ──────────────
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read'    => true,
                'read_at'    => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'Toutes les notifications ont été marquées comme lues.']);
    }

    // ── Helper ────────────────────────────────────────────
    private function format($n): array
    {
        $data = [];
        if ($n->data) {
            $decoded = json_decode($n->data, true);
            $data    = is_array($decoded) ? $decoded : [];
        }

        return [
            'id'         => $n->id,
            'type'       => $n->type,
            'title'      => $n->title ?? 'Notification',
            'message'    => $n->body ?? '',
            'data'       => $data,
            'is_read'    => (bool) $n->is_read,
            'read_at'    => $n->read_at,
            'created_at' => $n->created_at,
        ];
    }
}
