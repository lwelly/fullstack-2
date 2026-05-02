<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReclamationController extends Controller
{
    public function index(Request $request)
    {
        $user    = Auth::user();
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Étudiant introuvable.'], 404);
        }

        $reclamations = DB::table('reclamations')
            ->join('modules',   'reclamations.module_id',   '=', 'modules.id')
            ->join('semestres', 'reclamations.semestre_id', '=', 'semestres.id')
            ->where('reclamations.student_id', $student->id)
            ->whereNull('reclamations.deleted_at')
            ->select(
                'reclamations.*',
                'modules.name    as module_name',
                'modules.code    as module_code',
                'semestres.code  as semestre_code',
                'semestres.label as semestre_label',
                'semestres.academic_year as semestre_year',
                'semestres.is_open as semestre_is_open'
            )
            ->orderBy('reclamations.created_at', 'desc')
            ->get();

        $data = $reclamations->map(function ($r) {
            return [
                'id'             => $r->id,
                'reference'      => $r->reference_number,
                'type'           => $this->mapType($r->type),
                'status'         => $r->status,
                'note_actuelle'  => $r->note_actuelle,
                'note_reclamee'  => $r->note_reclamee,
                'justification'  => $r->justification,
                'admin_response' => $r->admin_response,
                'is_escalated'   => (bool) $r->is_escalated,
                'academic_year'  => $r->academic_year,
                'created_at'     => $r->created_at,
                'updated_at'     => $r->updated_at,
                'resolved_at'    => $r->resolved_at,
                'module'         => [
                    'id'   => $r->module_id,
                    'name' => $r->module_name,
                    'code' => $r->module_code,
                ],
                'semestre'       => [
                    'id'            => $r->semestre_id,
                    'code'          => $r->semestre_code,
                    'label'         => $r->semestre_label,
                    'academic_year' => $r->semestre_year,
                    'is_open'       => (bool) $r->semestre_is_open,
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show($id)
    {
        $user    = Auth::user();
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Étudiant introuvable.'], 404);
        }

        $r = DB::table('reclamations')
            ->join('modules',   'reclamations.module_id',   '=', 'modules.id')
            ->join('semestres', 'reclamations.semestre_id', '=', 'semestres.id')
            ->where('reclamations.id', $id)
            ->where('reclamations.student_id', $student->id)
            ->whereNull('reclamations.deleted_at')
            ->select(
                'reclamations.*',
                'modules.name    as module_name',
                'modules.code    as module_code',
                'semestres.code  as semestre_code',
                'semestres.label as semestre_label',
                'semestres.academic_year as semestre_year',
                'semestres.is_open as semestre_is_open'
            )
            ->first();

        if (!$r) {
            return response()->json(['message' => 'Réclamation introuvable.'], 404);
        }

        // Historique — utilise created_at (pas created_at)
        $history = DB::table('reclamation_history')
            ->where('reclamation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($h) => [
                'id'              => $h->id,
                'old_status'      => $h->old_status,
                'new_status'      => $h->new_status,
                'comment'         => $h->comment,
                'created_at'      => $h->created_at,  // alias created_at → created_at
                'changed_by_name' => 'Administration',
            ]);

        // Pièces jointes
        $attachments = DB::table('reclamation_attachments')
            ->where('reclamation_id', $id)
            ->select('id', 'original_name', 'storage_path', 'mime_type', 'file_size', 'created_at')
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'name'       => $a->original_name,
                'url'        => Storage::url($a->storage_path),
                'mime_type'  => $a->mime_type,
                'file_size'  => $a->file_size,
                'created_at' => $a->created_at,
            ]);

        return response()->json([
            'data' => [
                'id'             => $r->id,
                'reference'      => $r->reference_number,
                'type'           => $this->mapType($r->type),
                'status'         => $r->status,
                'note_actuelle'  => $r->note_actuelle,
                'note_reclamee'  => $r->note_reclamee,
                'justification'  => $r->justification,
                'admin_response' => $r->admin_response,
                'is_escalated'   => (bool) $r->is_escalated,
                'academic_year'  => $r->academic_year,
                'created_at'     => $r->created_at,
                'updated_at'     => $r->updated_at,
                'resolved_at'    => $r->resolved_at,
                'module'         => [
                    'id'   => $r->module_id,
                    'name' => $r->module_name,
                    'code' => $r->module_code,
                ],
                'semestre'       => [
                    'id'            => $r->semestre_id,
                    'code'          => $r->semestre_code,
                    'label'         => $r->semestre_label,
                    'academic_year' => $r->semestre_year,
                    'is_open'       => (bool) $r->semestre_is_open,
                ],
                'history'        => $history,
                'attachments'    => $attachments,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user    = Auth::user();
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Étudiant introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'module_id'     => 'required|integer|exists:modules,id',
            'semestre_id'   => 'required|integer|exists:semestres,id',
            'type'          => 'required|in:cc,examen,rattrapage',
            'note_actuelle' => 'required|numeric|min:0|max:20',
            'note_reclamee' => 'nullable|numeric|min:0|max:20',
            'justification' => 'required|string|min:20|max:1000',
            'document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->type === 'cc' && is_null($request->note_reclamee)) {
            return response()->json([
                'success' => false,
                'message' => 'La note réclamée est obligatoire pour un devoir (CC).',
            ], 422);
        }

        $semestre = DB::table('semestres')->find($request->semestre_id);
        if (!$semestre || !$semestre->is_open) {
            return response()->json([
                'success' => false,
                'message' => 'Ce semestre n\'est pas ouvert aux réclamations.',
            ], 422);
        }

        $existing = DB::table('reclamations')
            ->where('student_id',  $student->id)
            ->where('module_id',   $request->module_id)
            ->where('semestre_id', $request->semestre_id)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une réclamation active pour ce module.',
            ], 422);
        }

        $typeMap = [
            'cc'         => 'controle',
            'examen'     => 'examen',
            'rattrapage' => 'rattrapage',
        ];

        $count     = DB::table('reclamations')->count() + 1;
        $reference = 'RECL-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $noteReclamee = ($request->type === 'cc') ? $request->note_reclamee : null;

            $id = DB::table('reclamations')->insertGetId([
                'reference_number' => $reference,
                'student_id'       => $student->id,
                'module_id'        => $request->module_id,
                'semestre_id'      => $request->semestre_id,
                'academic_year'    => $student->academic_year ?? '2024-2025',
                'type'             => $typeMap[$request->type],
                'note_actuelle'    => $request->note_actuelle,
                'note_reclamee'    => $noteReclamee,
                'justification'    => $request->justification,
                'status'           => 'submitted',
                'is_escalated'     => false,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Historique — created_at
            DB::table('reclamation_history')->insert([
                'reclamation_id' => $id,
                'old_status'     => null,
                'new_status'     => 'submitted',
                'comment'        => 'Réclamation soumise par l\'étudiant.',
                'changed_by'     => $user->id,
                'ip_address'     => request()->ip(),
                'created_at' => now(),
            ]);

            if ($request->hasFile('document') && $request->file('document')->isValid()) {
                $file        = $request->file('document');
                $storedName  = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $storagePath = $file->storeAs('reclamations/attachments', $storedName, 'public');

                DB::table('reclamation_attachments')->insert([
                    'reclamation_id' => $id,
                    'original_name'  => $file->getClientOriginalName(),
                    'stored_name'    => $storedName,
                    'storage_path'   => $storagePath,
                    'mime_type'      => $file->getMimeType(),
                    'file_size'      => $file->getSize(),
                    'is_scanned'     => false,
                    'is_safe'        => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation soumise avec succès.',
                'data'    => ['id' => $id, 'reference' => $reference],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    private function mapType($type): string
    {
        $map = [
            'controle'   => 'cc',
            'examen'     => 'examen',
            'rattrapage' => 'rattrapage',
            'absence'    => 'absence',
            'autre'      => 'other',
        ];
        return $map[$type] ?? $type;
    }
}
