<?php
// app/Services/OtpService.php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\Setting;
use App\Models\User;
use App\Models\StudentPreloaded;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OtpService
{
    /**
     * Génère et envoie un OTP
     * Peut être lié à un User existant OU à un StudentPreloaded (avant inscription)
     */
    public function generate(
        string  $type,
        ?User   $user       = null,
        ?StudentPreloaded $preloaded = null,
        ?string $ip         = null
    ): OtpCode {

        // Invalider les anciens OTP du même type
        $this->invalidatePrevious($type, $user, $preloaded);

        // Générer un code à 6 chiffres
        $plainCode = $this->generateCode();

        // Récupérer les paramètres
        $expiryMinutes = (int) Setting::getValue('otp_expiry_minutes', 10);
        $maxAttempts   = (int) Setting::getValue('otp_max_attempts', 5);

        // Créer l'OTP avec le code hashé
        $otp = OtpCode::create([
            'user_id'      => $user?->id,
            'preloaded_id' => $preloaded?->id,
            'type'         => $type,
            'code_hash'    => Hash::make($plainCode),
            'attempts'     => 0,
            'max_attempts' => $maxAttempts,
            'is_used'      => false,
            'expires_at'   => now()->addMinutes($expiryMinutes),
            'ip_address'   => $ip,
        ]);

        // Déterminer l'email destinataire
        $email    = $user?->email ?? $preloaded?->email;
        $fullName = $user
            ? ($user->profile?->full_name ?? $user->login_identifier)
            : ($preloaded ? $preloaded->prenom . ' ' . $preloaded->nom : 'Utilisateur');

        // Envoyer par email
        Mail::to($email)->send(new OtpMail(
            code:      $plainCode,
            fullName:  $fullName,
            type:      $type,
            expiresIn: $expiryMinutes
        ));

        return $otp;
    }

    /**
     * Vérifie un OTP soumis par l'utilisateur
     */
    public function verify(
        string  $plainCode,
        string  $type,
        ?User   $user       = null,
        ?StudentPreloaded $preloaded = null
    ): array {

        // Chercher l'OTP valide le plus récent
        $query = OtpCode::valid()->ofType($type);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($preloaded) {
            $query->where('preloaded_id', $preloaded->id);
        }

        $otp = $query->latest()->first();

        // Cas : aucun OTP trouvé
        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Aucun OTP valide trouvé. Veuillez en demander un nouveau.',
                'code'    => 'OTP_NOT_FOUND',
            ];
        }

        // Cas : trop de tentatives
        if ($otp->isExhausted()) {
            return [
                'success'  => false,
                'message'  => 'Nombre maximum de tentatives atteint. Veuillez demander un nouveau code.',
                'code'     => 'OTP_MAX_ATTEMPTS',
                'remaining'=> 0,
            ];
        }

        // Cas : expiré
        if ($otp->isExpired()) {
            return [
                'success' => false,
                'message' => 'Ce code OTP a expiré. Veuillez en demander un nouveau.',
                'code'    => 'OTP_EXPIRED',
            ];
        }

        // Vérification du code
        $isValid = $otp->verify($plainCode);

        if (!$isValid) {
            $remaining = $otp->getRemainingAttempts();
            return [
                'success'   => false,
                'message'   => "Code incorrect. Il vous reste {$remaining} tentative(s).",
                'code'      => 'OTP_INVALID',
                'remaining' => $remaining,
            ];
        }

        return [
            'success' => true,
            'message' => 'Code OTP vérifié avec succès.',
            'otp'     => $otp,
        ];
    }

    /**
     * Invalide tous les OTP précédents du même type
     */
    private function invalidatePrevious(
        string  $type,
        ?User   $user      = null,
        ?StudentPreloaded $preloaded = null
    ): void {
        $query = OtpCode::where('type', $type)
                        ->where('is_used', false);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($preloaded) {
            $query->where('preloaded_id', $preloaded->id);
        }

        $query->update([
            'is_used'    => true,
            'expires_at' => now(),
        ]);
    }


    /**
 * Générer un OTP pour l'inscription (lié à preloaded_id, pas user_id)
 */
public function generateForPreloaded(int $preloadedId, string $email): void
{
    $expiry  = (int) Setting::getValue('otp_expiry_minutes', 10);
    $maxAttempts = (int) Setting::getValue('otp_max_attempts', 5);

    // Invalider les anciens OTPs
    OtpCode::where('preloaded_id', $preloadedId)
        ->where('type', 'registration')
        ->where('is_used', false)
        ->update(['is_used' => true]);

    $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    OtpCode::create([
        'user_id'      => null,
        'preloaded_id' => $preloadedId,
        'type'         => 'registration',
        'code_hash'    => Hash::make($code),
        'max_attempts' => $maxAttempts,
        'expires_at'   => now()->addMinutes($expiry),
        'ip_address'   => request()->ip(),
    ]);

    // Envoyer par email (loggé si MAIL_MAILER=log)
    \Illuminate\Support\Facades\Mail::to($email)
        ->send(new \App\Mail\OtpMail($code, 'registration'));
}


    /**
     * Génère un code numérique à 6 chiffres sécurisé
     */
    private function generateCode(): string
    {
        return str_pad(
            (string) random_int(100000, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Masque un email pour l'affichage (ex: mo****@iscae.mr)
     */
    public function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $visible = substr($local, 0, 2);
        $masked  = str_repeat('*', max(strlen($local) - 2, 3));
        return $visible . $masked . '@' . $domain;
    }
}
