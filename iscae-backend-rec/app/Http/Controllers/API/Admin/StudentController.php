<?php
// app/Http/Controllers/API/Admin/StudentController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    // GET /api/v1/admin/students
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['filiere', 'niveau', 'user']);

        if ($request->has('filiere_id')) {
            $query->where('filiere_id', $request->query('filiere_id'));
        }
        if ($request->has('niveau_id')) {
            $query->where('niveau_id', $request->query('niveau_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('nom')->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => StudentResource::collection($students->items()),
            'meta'    => [
                'total'        => $students->total(),
                'current_page' => $students->currentPage(),
                'last_page'    => $students->lastPage(),
            ],
        ]);
    }

    // GET /api/v1/admin/students/{id}
    public function show(int $id): JsonResponse
    {
        $student = Student::with([
            'filiere', 'niveau', 'user',
            'reclamations' => fn($q) => $q->orderByDesc('created_at')->limit(5),
            'documents',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new StudentResource($student),
        ]);
    }

    // PUT /api/v1/admin/students/{id}/status
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:active,suspended,graduated,expelled'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($id);
        $old     = $student->status;
        $student->update(['status' => $request->input('status')]);

        // Désactiver le compte si suspendu/expulsé
        if (in_array($request->input('status'), ['suspended', 'expelled'])) {
            $student->user->update(['is_active' => false]);
        } else {
            $student->user->update(['is_active' => true]);
        }

        $this->auditService->log(
            'student.status_changed',
            $request->user()->id,
            'admin',
            'Student',
            $student->id,
            ['status' => $old],
            ['status' => $request->input('status')]
        );

        return response()->json([
            'success' => true,
            'message' => 'Statut étudiant mis à jour.',
            'data'    => new StudentResource($student->fresh(['filiere', 'niveau'])),
        ]);
    }
}
