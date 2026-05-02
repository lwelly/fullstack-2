<?php
// app/Http/Controllers/API/Admin/ReclamationController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReclamationRequest;
use App\Http\Requests\Admin\ScheduleMeetingRequest;
use App\Http\Resources\ReclamationResource;
use App\Models\Reclamation;
use App\Models\Admin;
use App\Services\ReclamationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReclamationController extends Controller
{
    public function __construct(
        private ReclamationService $reclamationService
    ) {}

    // GET /api/v1/admin/reclamations
   public function index(Request $request): JsonResponse
{
    $query = Reclamation::with([
        'student.filiere', 'student.niveau', 'module', 'semestre', 'attachments'
    ])->whereNull('deleted_at');

    if ($request->filled('status'))     $query->where('status', $request->status);
    if ($request->filled('type'))       $query->where('type', $request->type);
    if ($request->filled('semestre_id')) $query->where('semestre_id', $request->semestre_id);
    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(function ($q) use ($s) {
            $q->where('reference_number', 'like', "%{$s}%")
              ->orWhereHas('student', fn($sq) =>
                  $sq->where('nom', 'like', "%{$s}%")
                     ->orWhere('prenom', 'like', "%{$s}%")
                     ->orWhere('matricule', 'like', "%{$s}%")
              );
        });
    }

    $reclamations = $query->orderByDesc('created_at')->paginate(
        $request->input('per_page', 15)
    );

    try {
        $data = ReclamationResource::collection($reclamations->items());
    } catch (\Exception $e) {
        \Log::error('[AdminReclamation@index] Resource error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }

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


    // GET /api/v1/admin/reclamations/{id}
    public function show(int $id): JsonResponse
    {
        $reclamation = Reclamation::with([
            'student.filiere', 'student.niveau',
            'module', 'semestre',
            'attachments', 'history.changedBy',
            'assignedAdmin', 'escalatedAdmin',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new ReclamationResource($reclamation),
        ]);
    }

    // PUT /api/v1/admin/reclamations/{id}/status
    public function updateStatus(UpdateReclamationRequest $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);

        $updated = $this->reclamationService->changeStatus(
            $reclamation,
            $request->input('status'),
            $request->user(),
            $request->input('comment'),
            $request->input('admin_response'),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'data'    => new ReclamationResource($updated),
        ]);
    }

    // POST /api/v1/admin/reclamations/{id}/escalate
    public function escalate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'escalated_to' => ['required', 'integer', 'exists:admins,id'],
            'reason'       => ['nullable', 'string', 'max:500'],
        ]);

        $reclamation = Reclamation::findOrFail($id);
        $escalateTo  = Admin::findOrFail($request->input('escalated_to'));

        $updated = $this->reclamationService->escalate(
            $reclamation,
            $escalateTo,
            $request->user(),
            $request->input('reason'),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Réclamation escaladée avec succès.',
            'data'    => new ReclamationResource($updated),
        ]);
    }

    // POST /api/v1/admin/reclamations/{id}/meeting
    public function scheduleMeeting(ScheduleMeetingRequest $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);

        $updated = $this->reclamationService->scheduleMeeting(
            $reclamation,
            $request->input('meeting_at'),
            $request->input('location'),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Réunion programmée avec succès.',
            'data'    => new ReclamationResource($updated),
        ]);
    }
}
