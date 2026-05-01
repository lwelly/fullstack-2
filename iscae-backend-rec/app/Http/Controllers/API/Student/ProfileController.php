<?php

namespace App\Http\Controllers\API\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        $user    = Auth::user();
        $student = DB::table('students')
            ->join('filieres', 'students.filiere_id', '=', 'filieres.id')
            ->join('niveaux',  'students.niveau_id',  '=', 'niveaux.id')
            ->where('students.user_id', $user->id)
            ->select(
                'students.*',
                'filieres.name as filiere_nom',
                'filieres.code as filiere_code',
                'niveaux.code  as niveau_code',
                'niveaux.label as niveau_label'
            )
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Profil introuvable.'], 404);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id'                => $user->id,
                    'name'              => $student->prenom . ' ' . $student->nom,
                    'email'             => $user->email,
                    'role'              => $user->role,
                    'is_active'         => (bool) $user->is_active,
                    'last_login_at'     => $user->last_login_at,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'student' => [
                    'id'           => $student->id,
                    'matricule'    => $student->matricule,
                    'nom'          => $student->nom,
                    'prenom'       => $student->prenom,
                    'filiere'      => $student->filiere_code,
                    'filiere_nom'  => $student->filiere_nom,
                    'niveau'       => $student->niveau_code,
                    'niveau_label' => $student->niveau_label,
                    'academic_year'=> $student->academic_year,
                    'telephone'    => $student->telephone,
                    'status'       => $student->status,
                ],
            ]
        ]);
    }

    public function update(Request $request)
    {
        return response()->json(['message' => 'Mise à jour non disponible.'], 403);
    }

    public function changePassword(Request $request)
    {
        return response()->json(['message' => 'Changement de mot de passe non disponible.'], 403);
    }
}
