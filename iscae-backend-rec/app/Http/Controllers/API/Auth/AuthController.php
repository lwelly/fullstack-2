<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyPreloadedRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Http\Requests\Auth\Verify2FARequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    // ══════════════════════════════════════════
    // POST /api/v1/auth/login
    // ══════════════════════════════════════════
    public function login(LoginRequest $request): JsonResponse
{
    $result = $this->authService->login(
        $request->input('login'),
        $request->input('password'),
        $request->ip() ?? '127.0.0.1',
        $request->userAgent() ?? 'unknown',
        $request->input('device_fingerprint') // ✅ nouveau
    );

    if (!$result['success']) {
        return response()->json(['success' => false, 'message' => $result['message']], 401);
    }

    return response()->json([
        'success' => true,
        'message' => $result['message'],
        'data'    => $result['data'],
    ]);
}


    // ══════════════════════════════════════════
    // POST /api/v1/auth/register/verify
    // ══════════════════════════════════════════
    public function verifyPreloaded(VerifyPreloadedRequest $request): JsonResponse
    {
        $result = $this->authService->verifyPreloaded(
            $request->input('matricule'),
            $request->input('email')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ], 200);
    }

    // ══════════════════════════════════════════
    // POST /api/v1/auth/register/send-otp
    // ══════════════════════════════════════════
    public function sendRegistrationOtp(Request $request): JsonResponse
    {
        $request->validate([
            'preloaded_id' => ['required', 'integer', 'exists:students_preloaded,id'],
        ]);

        $result = $this->authService->sendRegistrationOtp(
            (int) $request->input('preloaded_id')
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => ['preloaded_id' => $request->input('preloaded_id')],
        ]);
    }

    // ══════════════════════════════════════════
    // POST /api/v1/auth/register/verify-otp
    // ══════════════════════════════════════════
    public function verifyRegistrationOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyRegistrationOtp(
            (int) $request->input('preloaded_id'),
            $request->input('otp_code')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP vérifié avec succès.',
            'data'    => $result['data'],
        ], 200);
    }

    // ══════════════════════════════════════════
    // POST /api/v1/auth/register/set-password
    // ══════════════════════════════════════════
    public function setPassword(SetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->setPassword(
            $request->input('registration_token'),
            $request->input('password'),
            $request->ip(),
            $request->userAgent()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès. Bienvenue à l\'ISCAE !',
            'data'    => $result['data'],
        ], 201);
    }

    // ══════════════════════════════════════════
    // POST /api/v1/auth/2fa/verify
    // ══════════════════════════════════════════
   public function verify2FA(Verify2FARequest $request): JsonResponse
{
    $result = $this->authService->verify2FA(
        (int) $request->input('user_id'),
        $request->input('otp_code'),
        $request->input('login_type') ?? 'student',
        $request->ip() ?? '127.0.0.1',
        $request->userAgent() ?? 'unknown',
        $request->input('device_fingerprint'), // ✅ nouveau
        (bool) $request->input('trust_device', true) // ✅ toujours faire confiance
    );

    if (!$result['success']) {
        return response()->json(['success' => false, 'message' => $result['message']], 422);
    }

    return response()->json([
        'success' => true,
        'message' => 'Authentification réussie.',
        'data'    => $result['data'],
    ]);
}


    // ══════════════════════════════════════════
    // POST /api/v1/auth/logout
    // ══════════════════════════════════════════
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    // ══════════════════════════════════════════
    // GET /api/v1/auth/me
    // ══════════════════════════════════════════
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'student.filiere',
            'student.niveau',
            'admin.department',
        ]);

        if ($user->isStudent()) {
            $profile = [
                'matricule'     => $user->student?->matricule,
                'full_name'     => $user->student?->full_name,
                'filiere'       => $user->student?->filiere?->code,
                'filiere_nom'   => $user->student?->filiere?->name,
                'niveau'        => $user->student?->niveau?->code,
                'photo_url'     => $user->student?->photo_url,
                'academic_year' => $user->student?->academic_year,
            ];
        } else {
            $profile = [
                'full_name'  => $user->admin?->full_name,
                'role_label' => $user->admin?->role_label_readable,
                'department' => $user->admin?->department?->code,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'status'     => $user->status,
                'profile'    => $profile,
                'last_login' => $user->last_login_at?->format('Y-m-d H:i'),
            ],
        ]);
    }
    // ══════════════════════════════════════════
// POST /api/v1/auth/2fa/resend
// ══════════════════════════════════════════
public function resendOtp(Request $request): JsonResponse
{
    $request->validate([
        'user_id'    => 'required|integer|exists:users,id',
        'login_type' => 'nullable|string|in:student,admin',
    ]);

    $result = $this->authService->resendOtp(
        (int) $request->input('user_id'),
        $request->input('login_type', 'student')
    );

    return response()->json([
        'success' => true,
        'message' => $result['message'] ?? 'Code OTP renvoyé.',
        // En dev uniquement
        'otp_dev' => app()->isLocal() ? ($result['otp_dev'] ?? null) : null,
    ]);
}

// ══════════════════════════════════════════
// POST /api/v1/auth/forgot-password
// ══════════════════════════════════════════
public function forgotPassword(Request $request): JsonResponse
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    // TODO: implémenter l'envoi d'email de réinitialisation
    return response()->json([
        'success' => true,
        'message' => 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.',
    ]);
}

    }
