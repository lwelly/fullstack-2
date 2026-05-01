<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class SemestreController extends Controller
{
    public function index(): JsonResponse
    {
        $semestres = DB::table('semestres')
            ->orderBy('academic_year', 'desc')
            ->orderBy('order_index', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $semestres]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'niveau_id'     => 'required|exists:niveaux,id',
            'code'          => 'required|string|max:20',
            'label'         => 'required|string|max:100',
            'order_index'   => 'required|integer',
            'academic_year' => 'required|string|max:20',
        ]);

        $id = DB::table('semestres')->insertGetId(array_merge($data, [
            'is_open'             => false,
            'is_exam_open'        => false,
            'is_rattrapage_open'  => false,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]));

        $semestre = DB::table('semestres')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Semestre créé avec succès.',
            'data'    => $semestre,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();
        if (!$semestre) {
            return response()->json(['success' => false, 'message' => 'Semestre introuvable.'], 404);
        }

        $data = $request->validate([
            'niveau_id'     => 'sometimes|exists:niveaux,id',
            'code'          => 'sometimes|string|max:20',
            'label'         => 'sometimes|string|max:100',
            'order_index'   => 'sometimes|integer',
            'academic_year' => 'sometimes|string|max:20',
        ]);

        DB::table('semestres')->where('id', $id)->update(
            array_merge($data, ['updated_at' => now()])
        );

        $semestre = DB::table('semestres')->where('id', $id)->first();

        return response()->json(['success' => true, 'message' => 'Semestre mis à jour.', 'data' => $semestre]);
    }

    public function toggle($id)
{
    $semestre = DB::table('semestres')->find($id);
    $newValue = !$semestre->is_open;

    DB::table('semestres')
        ->where('id', $id)
        ->update([
            'is_open'    => $newValue,
            'updated_at' => now(),
        ]);

    return response()->json([
        'success'  => true,
        'is_open'  => $newValue,
        'message'  => $newValue ? 'Semestre ouvert aux réclamations.' : 'Semestre fermé.',
    ]);
}


    public function toggleExam($id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();
        if (!$semestre) {
            return response()->json(['success' => false, 'message' => 'Semestre introuvable.'], 404);
        }

        $newState = !(bool) $semestre->is_exam_open;

        DB::table('semestres')->where('id', $id)->update([
            'is_exam_open'  => $newState,
            'exam_open_at'  => $newState ? now() : ($semestre->exam_open_at ?? null),
            'exam_close_at' => !$newState ? now() : null,
            'updated_at'    => now(),
        ]);

        $semestre = DB::table('semestres')->where('id', $id)->first();
        $msg = $newState
            ? "Examens du semestre {$semestre->code} ouverts."
            : "Examens du semestre {$semestre->code} fermés.";

        return response()->json(['success' => true, 'message' => $msg, 'data' => $semestre]);
    }

    public function toggleRattrapage($id): JsonResponse
    {
        $semestre = DB::table('semestres')->where('id', $id)->first();
        if (!$semestre) {
            return response()->json(['success' => false, 'message' => 'Semestre introuvable.'], 404);
        }

        $newState = !(bool) $semestre->is_rattrapage_open;

        DB::table('semestres')->where('id', $id)->update([
            'is_rattrapage_open'  => $newState,
            'rattrapage_open_at'  => $newState ? now() : ($semestre->rattrapage_open_at ?? null),
            'rattrapage_close_at' => !$newState ? now() : null,
            'updated_at'          => now(),
        ]);

        $semestre = DB::table('semestres')->where('id', $id)->first();
        $msg = $newState
            ? "Rattrapage du semestre {$semestre->code} ouvert."
            : "Rattrapage du semestre {$semestre->code} fermé.";

        return response()->json(['success' => true, 'message' => $msg, 'data' => $semestre]);
    }
}
