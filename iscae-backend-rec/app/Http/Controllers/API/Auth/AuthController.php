<?php
// app/Http/Controllers/API/Auth/AuthController.php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Student;

class AuthController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // INSCRIPTION — Étape 1 : Vérifier matricule + email
    // POST /api/v1/auth/verify-identity
    // ══════════════════════════════════════════════════════════════════
public function verifyPreloaded(Request $request): JsonResponse
{
    $request->validate([
        'matricule' => 'required|string|max:20',
        'email'     => 'required|email|max:255',
    ]);

    $matricule = strtoupper(trim($request->matricule));
    $email     = strtolower(trim($request->email));

    // 1️⃣ Chercher d'abord dans students (déjà migré)
    $student = \App\Models\Student::whereRaw(
        'UPPER(matricule) = ? AND LOWER(email) = ? AND deleted_at IS NULL',
        [$matricule, $email]
    )->first();

    // 2️⃣ Si pas trouvé, chercher dans students_preloaded
    if (! $student) {
        $preloaded = DB::table('students_preloaded')
            ->whereRaw('UPPER(matricule) = ?', [$matricule])
            ->whereRaw('LOWER(email) = ?',     [$email])
            ->where('is_registered', 0)
            ->first();

        if (! $preloaded) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun étudiant trouvé avec ce matricule et cet email. Contactez l\'administration.',
            ], 404);
        }

        // 3️⃣ Migrer vers la table students
        $studentId = DB::table('students')->insertGetId([
            'matricule'     => strtoupper($preloaded->matricule),
            'nni'           => $preloaded->nni           ?? null,
            'nom'           => $preloaded->nom           ?? '',
            'prenom'        => $preloaded->prenom        ?? '',
            'email'         => strtolower($preloaded->email),
            'filiere_id'    => $preloaded->filiere_id    ?? null,
            'niveau_id'     => $preloaded->niveau_id     ?? null,
            'academic_year' => $preloaded->academic_year ?? null,
            'preloaded_id'  => $preloaded->id,
            'status'        => 'active',
            'user_id'       => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $student = \App\Models\Student::find($studentId);

        Log::info('[verifyPreloaded] Migré depuis students_preloaded', [
            'preloaded_id' => $preloaded->id,
            'student_id'   => $studentId,
        ]);
    }

    // Compte déjà créé
    if ($student->user_id && \App\Models\User::find($student->user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'Un compte existe déjà. Veuillez vous connecter.',
        ], 409);
    }

    return response()->json([
        'success' => true,
        'message' => 'Identité vérifiée avec succès.',
        'data'    => [
            'student_id' => $student->id,
            'matricule'  => $student->matricule,
            'full_name'  => trim(($student->prenom ?? '') . ' ' . ($student->nom ?? '')) ?: 'Étudiant',
            'email'      => $student->email,
            'filiere'    => $student->filiere?->nom ?? null,
            'niveau'     => $student->niveau?->nom  ?? null,
        ],
    ]);
}


    // ══════════════════════════════════════════════════════════════════
    // INSCRIPTION — Étape 2 : Vérifier le code OTP envoyé par email
    // POST /api/v1/auth/verify-otp
    // ══════════════════════════════════════════════════════════════════
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer',
            'otp_code'   => 'required|string|size:6',
        ]);

        $student = Student::find($request->student_id);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        // Vérifier le code OTP en base (table password_reset_tokens ou otp_codes)
        $otpRecord = DB::table('password_reset_tokens')
                       ->where('email', $student->email)
                       ->first();

        if (! $otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun code envoyé. Recommencez l\'étape 1.',
            ], 422);
        }

        // Vérifier expiration (10 minutes)
        if (now()->diffInMinutes($otpRecord->created_at) > 10) {
            DB::table('password_reset_tokens')->where('email', $student->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Code expiré. Recommencez l\'étape 1.',
            ], 422);
        }

        // Vérifier le code
        if (! Hash::check($request->otp_code, $otpRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect.',
            ], 422);
        }

        // Supprimer le code utilisé
        DB::table('password_reset_tokens')->where('email', $student->email)->delete();

        Log::info('[Auth] verifyOtp OK', ['student_id' => $student->id]);

        return response()->json([
            'success' => true,
            'message' => 'Code vérifié. Choisissez votre mot de passe.',
            'data'    => [
                'student_id' => $student->id,
                'email'      => $student->email,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // INSCRIPTION — Étape 3 : Créer le compte (mot de passe)
    // POST /api/v1/auth/register
    // ══════════════════════════════════════════════════════════════════
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'student_id'            => 'required|integer',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $student = Student::find($request->student_id);
        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable.',
            ], 404);
        }

        // Vérifier qu'il n'a pas déjà un compte
        if ($student->user_id && User::find($student->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte existe déjà. Connectez-vous.',
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Créer le User
            $user = User::create([
                'email'            => $student->email,
                'login_identifier' => $student->matricule,
                'password'         => Hash::make($request->password),
                'role'             => 'student',
                'is_active'        => true,
            ]);

            // Lier l'étudiant au user
            $student->update(['user_id' => $user->id]);

            DB::commit();

            // Générer le token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('[Auth] register OK', [
                'user_id'    => $user->id,
                'student_id' => $student->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès.',
                'token'   => $token,
                'user'    => $this->buildUserPayload($user),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Auth] register error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // CONNEXION
    // POST /api/v1/auth/login
    // ══════════════════════════════════════════════════════════════════
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login'    => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $loginValue = trim($request->input('login'));

        // Chercher par login_identifier OU email
        $user = User::where('login_identifier', $loginValue)
                    ->orWhere('email', $loginValue)
                    ->first();

        // Utilisateur introuvable
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        // Mot de passe incorrect
        if (! Hash::check($request->input('password'), $user->password)) {
            if (isset($user->failed_login_count)) {
                $user->increment('failed_login_count');
            }
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        // Compte verrouillé
        if (! empty($user->locked_until) && now()->lt($user->locked_until)) {
            return response()->json([
                'success' => false,
                'message' => 'Compte temporairement verrouillé. Réessayez plus tard.',
            ], 423);
        }

        // Compte inactif
        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Compte désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // 2FA activé
        if (! empty($user->two_factor_enabled)) {
            return response()->json([
                'success'      => true,
                'requires_2fa' => true,
                'user_id'      => $user->id,
                'login_type'   => $user->role,
            ], 200);
        }

        // Réinitialiser le compteur d'échecs
        $updateData = ['last_login_at' => now()];
        if (isset($user->failed_login_count)) {
            $updateData['failed_login_count'] = 0;
        }
        $user->update($updateData);

        // Générer token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('[Auth] login OK', [
            'user_id' => $user->id,
            'role'    => $user->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user),
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // PROFIL CONNECTÉ
    // GET /api/v1/auth/me
    // ══════════════════════════════════════════════════════════════════
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }
        return response()->json([
            'success' => true,
            'data'    => $this->buildUserPayload($user),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // DÉCONNEXION
    // POST /api/v1/auth/logout
    // ══════════════════════════════════════════════════════════════════
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // 2FA — Vérifier le code
    // POST /api/v1/auth/2fa/verify
    // ══════════════════════════════════════════════════════════════════
    public function verify2FA(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'    => 'required|integer',
            'otp_code'   => 'required|string',
            'login_type' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // Vérification OTP
        $valid = $user->two_factor_secret === $request->otp_code;
        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Authentification 2FA réussie.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user),
        ]);
    }
   // ══════════════════════════════════════════════════════════════════
// ENVOYER OTP
// POST /api/v1/auth/send-otp
// ══════════════════════════════════════════════════════════════════
public function sendOtp(Request $request): JsonResponse
{
    $request->validate([
        'student_id' => 'required|integer',
        'email'      => 'required|email',
    ]);

    $student = \App\Models\Student::find($request->student_id);
    if (! $student) {
        return response()->json([
            'success' => false,
            'message' => 'Étudiant introuvable.',
        ], 404);
    }

    // Générer code 6 chiffres
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Sauvegarder code hashé
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $student->email],
        [
            'token'      => Hash::make($code),
            'created_at' => now(),
        ]
    );

    // Envoyer email
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Bonjour {$student->prenom},\n\n" .
            "Votre code de vérification ISCAE est :\n\n" .
            "  {$code}\n\n" .
            "Ce code expire dans 10 minutes.\n\n" .
            "Si vous n'avez pas demandé ce code, ignorez cet email.\n\n" .
            "— L'équipe ISCAE",
            function ($m) use ($student, $code) {
                $m->to($student->email)
                  ->subject('🔐 Code de vérification ISCAE : ' . $code);
            }
        );

        Log::info('[Auth] sendOtp envoyé', [
            'student_id' => $student->id,
            'email'      => $student->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Code envoyé par email.',
        ]);

    } catch (\Throwable $e) {
        Log::error('[Auth] sendOtp ERREUR', [
            'error' => $e->getMessage(),
            'email' => $student->email,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur envoi email : ' . $e->getMessage(),
        ], 500);
    }
}


    // ══════════════════════════════════════════════════════════════════
    // HELPER PRIVÉ — Construire le payload user
    // ══════════════════════════════════════════════════════════════════
    private function buildUserPayload(User $user): array
    {
        $studentData = null;

        if ($user->role === 'student') {
            $student = Student::with(['filiere', 'niveau'])
                              ->where('user_id', $user->id)
                              ->first();

            if ($student) {
                // Construire l'URL de la photo
                $photoUrl = null;
                if ($student->photo_path) {
                    $photoUrl = \Illuminate\Support\Facades\Storage::url($student->photo_path);
                }

                $studentData = [
                    'id'            => $student->id,
                    'matricule'     => $student->matricule,
                    'nom'           => $student->nom,
                    'prenom'        => $student->prenom,
                    'full_name'     => trim(($student->prenom ?? '') . ' ' . ($student->nom ?? ''))
                                      ?: null,
                    'filiere_id'    => $student->filiere_id,
                    'filiere'       => $student->filiere?->nom ?? null,
                    'niveau_id'     => $student->niveau_id,
                    'niveau'        => $student->niveau?->nom  ?? null,
                    'academic_year' => $student->academic_year,
                    'photo_path'    => $student->photo_path,
                    'photo_url'     => $photoUrl,  // ← URL complète pour le front
                    'status'        => $student->status ?? 'active',
                ];
            }
        }

        return array_filter([
            'id'               => $user->id,
            'email'            => $user->email,
            'login_identifier' => $user->login_identifier,
            'role'             => $user->role,
            'is_active'        => $user->is_active ?? true,
            'last_login_at'    => $user->last_login_at,
            'student'          => $studentData,
        ], fn($v) => $v !== null);
    }
}
