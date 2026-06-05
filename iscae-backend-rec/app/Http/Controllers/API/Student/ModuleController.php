<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        $user    = Auth::user();
        $student = DB::table('students')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        $semestreId = $request->input('semestre_id');

        if (!$semestreId) {
            return response()->json([
                'success' => false,
                'message' => 'semestre_id est requis.',
                'data'    => [],
            ], 422);
        }

        $query = DB::table('modules')
            ->where('semestre_id', $semestreId)
            ->where('is_active', true);

        if ($student && $student->filiere_id) {
            $query->where('filiere_id', $student->filiere_id);
        }

        $modules = $query
            ->select('id', 'name', 'code', 'semestre_id', 'filiere_id', 'coefficient', 'credits')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $modules,
            'debug'   => [
                'student_id'  => $student?->id,
                'filiere_id'  => $student?->filiere_id,
                'semestre_id' => $semestreId,
                'count'       => $modules->count(),
            ],
        ]);
    }
}