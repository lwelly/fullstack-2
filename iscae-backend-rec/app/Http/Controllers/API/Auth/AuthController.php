<?php
// app/Http/Controllers/API/Auth/AuthController.php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // ── POST /auth/login ───────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $loginValue = trim($request->input('login'));

        // ── Chercher par login_identifier OU email ─────────────────────
        $user = User::where('login_identifier', $loginValue)
                    ->orWhere('email', $loginValue)
                    ->first();

        // ── Utilisateur introuvable ────────────────────────────────────
        if (! $user) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        // ── Mot de passe incorrect ─────────────────────────────────────
        if (! Hash::check($request->input('password'), $user->password)) {
            // Incrémenter le compteur d'échecs si la colonne existe
            if (isset($user->failed_login_count)) {
                $user->increment('failed_login_count');
            }
            return response()->json([
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        // ── Compte verrouillé ──────────────────────────────────────────
        if (! empty($user->locked_until) && now()->lt($user->locked_until)) {
            return response()->json([
                'message' => 'Compte temporairement verrouillé. Réessayez plus tard.',
            ], 423);
        }

        // ── Compte inactif ─────────────────────────────────────────────
        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'message' => 'Compte désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // ── 2FA activé ─────────────────────────────────────────────────
        if (! empty($user->two_factor_enabled)) {
            return response()->json([
                'requires_2fa' => true,
                'user_id'      => $user->id,
                'login_type'   => $user->role,
            ], 200);
        }

        // ── Réinitialiser le compteur d'échecs ─────────────────────────
        $updateData = ['last_login_at' => now()];
        if (isset($user->failed_login_count)) {
            $updateData['failed_login_count'] = 0;
        }
        $user->update($updateData);

        // ── Générer token Sanctum ──────────────────────────────────────
        $token = $user->createToken('auth_token')->plainTextToken;

        // ── Construire la réponse user ─────────────────────────────────
        $userData = $this->buildUserPayload($user);

        return response()->json([
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => $userData,
        ], 200);
    }

    // ── GET /auth/me ───────────────────────────────────────────────────
    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }
        return response()->json($this->buildUserPayload($user));
    }

    // ── POST /auth/logout ──────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    // ── POST /auth/2fa/verify ──────────────────────────────────────────
    public function verify2FA(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|integer',
            'otp_code'   => 'required|string',
            'login_type' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        // Vérification OTP (adapter selon votre implémentation 2FA)
        $valid = $user->two_factor_secret === $request->otp_code; // Exemple basique
        if (! $valid) {
            return response()->json(['message' => 'Code OTP invalide.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'message' => 'Authentification 2FA réussie.',
            'token'   => $token,
            'user'    => $this->buildUserPayload($user),
        ]);
    }

    // ── Helper : construire le payload user ───────────────────────────
    private function buildUserPayload(User $user): array
    {
        // Récupérer les données student si role = student
        $studentData = null;
        if ($user->role === 'student') {
            $student = \App\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $studentData = [
                    'id'            => $student->id,
                    'matricule'     => $student->matricule,
                    'nom'           => $student->nom,
                    'prenom'        => $student->prenom,
                    'filiere_id'    => $student->filiere_id,
                    'niveau_id'     => $student->niveau_id,
                    'academic_year' => $student->academic_year,
                    'photo_path'    => $student->photo_path,
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
