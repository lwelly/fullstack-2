// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {

  // ── State ─────────────────────────────────────────────────────────────
  const user        = ref(null)
  const token       = ref(localStorage.getItem('auth_token') ?? null)
  const initialized = ref(false)

  // ── Computed ──────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isStudent       = computed(() => user.value?.role === 'student')

  // ══════════════════════════════════════════════════════════════════════
  // DEVICE FINGERPRINT — Générer ou réutiliser
  // ══════════════════════════════════════════════════════════════════════
  async function getDeviceFingerprint() {
    // ✅ Réutilise le même fingerprint s'il existe déjà
    const saved = localStorage.getItem('device_fingerprint')
    if (saved) return saved

    try {
      const raw = [
        navigator.userAgent,
        navigator.language,
        screen.width + 'x' + screen.height,
        screen.colorDepth,
        Intl.DateTimeFormat().resolvedOptions().timeZone,
        navigator.hardwareConcurrency ?? 0,
      ].join('|')

      const buf = await crypto.subtle.digest(
        'SHA-256',
        new TextEncoder().encode(raw)
      )
      const fp = Array.from(new Uint8Array(buf))
        .map(b => b.toString(16).padStart(2, '0'))
        .join('')

      localStorage.setItem('device_fingerprint', fp)
      return fp
    } catch {
      const fallback = 'browser-' + Math.random().toString(36).slice(2, 18)
      localStorage.setItem('device_fingerprint', fallback)
      return fallback
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // SET / CLEAR TOKEN
  // ══════════════════════════════════════════════════════════════════════
  function setToken(t) {
    token.value = t
    if (t) {
      localStorage.setItem('auth_token', t)
      api.defaults.headers.common['Authorization'] = `Bearer ${t}`
    } else {
      localStorage.removeItem('auth_token')
      delete api.defaults.headers.common['Authorization']
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // INIT — Appelé une fois au démarrage du router
  // ══════════════════════════════════════════════════════════════════════
  async function init() {
    if (initialized.value) return
    initialized.value = true

    const savedToken = localStorage.getItem('auth_token')
    if (!savedToken) return

    setToken(savedToken)
    try {
      const res  = await api.get('/auth/me')
      user.value = res.data?.data ?? null
    } catch {
      setToken(null)
      user.value = null
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // LOGIN — avec reconnaissance d'appareil
  // ══════════════════════════════════════════════════════════════════════
  async function login(credentials) {
    const fingerprint = await getDeviceFingerprint()

    const res = await api.post('/auth/login', {
      login:              credentials.login ?? credentials.email,
      password:           credentials.password,
      device_fingerprint: fingerprint,
    })

    const resData = res.data

    // ── Nouvel appareil détecté → OTP requis ──────────────────────────
    if (resData?.requires_device_otp) {
      return {
        requiresDeviceOtp: true,
        userId:            resData.data?.user_id,
        maskedEmail:       resData.data?.masked_email,
      }
    }

    // ── 2FA classique requis ──────────────────────────────────────────
    if (resData?.requires_2fa || resData?.data?.requires_2fa) {
      const d = resData?.data ?? resData
      return {
        requires2FA:       true,
        requiresDeviceOtp: false,
        user_id:           d.user_id,
        login_type:        d.login_type ?? 'student',
        device_fingerprint: fingerprint,
      }
    }

    // ── Connexion directe (appareil reconnu) ──────────────────────────
    const data        = resData?.data ?? resData
    const accessToken = data?.token ?? data?.access_token
    if (accessToken) {
      setToken(accessToken)
      user.value = data?.user ?? data
    }

    return {
      success:           true,
      requiresDeviceOtp: false,
      requires2FA:       false,
      user:              user.value,
      token:             accessToken,
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // VERIFY DEVICE OTP — Valider OTP + enregistrer l'appareil
  // ══════════════════════════════════════════════════════════════════════
  async function verifyDeviceOtp(payload) {
    const fingerprint = await getDeviceFingerprint()

    const res = await api.post('/auth/verify-device-otp', {
      user_id:            payload.userId,
      otp_code:           payload.otpCode,
      device_fingerprint: fingerprint,
      device_name:        navigator.platform ?? 'Navigateur',
    })

    const data        = res.data?.data ?? res.data
    const accessToken = data?.token ?? data?.access_token

    if (accessToken) {
      setToken(accessToken)
      user.value = data?.user ?? null
    }

    // 2FA peut encore être requis après la vérification de l'appareil
    if (res.data?.requires_2fa || data?.requires_2fa) {
      return {
        requires2FA: true,
        user_id:     data?.user_id,
        login_type:  data?.login_type ?? 'student',
      }
    }

    return {
      success: true,
      user:    user.value,
      token:   accessToken,
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // RESEND DEVICE OTP — Renvoyer le code de vérification appareil
  // ══════════════════════════════════════════════════════════════════════
  async function resendDeviceOtp(userId) {
    const res = await api.post('/auth/resend-device-otp', {
      user_id: userId,
    })
    return res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // VERIFY 2FA — Code OTP classique
  // ══════════════════════════════════════════════════════════════════════
  async function verify2FA(payload) {
    const fingerprint = payload.device_fingerprint ?? await getDeviceFingerprint()

    const res = await api.post('/auth/2fa/verify', {
      user_id:            payload.user_id,
      otp_code:           payload.otp_code ?? payload.otp,
      login_type:         payload.login_type ?? 'student',
      device_fingerprint: fingerprint,
      trust_device:       payload.trust_device ?? true,
    })

    const data        = res.data?.data ?? res.data
    const accessToken = data?.token ?? data?.access_token

    if (accessToken) {
      setToken(accessToken)
      user.value = data?.user ?? null
    }

    return {
      user:  user.value,
      token: accessToken,
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // RESEND OTP — 2FA classique
  // ══════════════════════════════════════════════════════════════════════
  async function resendOtp(userId, loginType = 'student') {
    const res = await api.post('/auth/2fa/resend', {
      user_id:    userId,
      login_type: loginType,
    })
    return res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // FETCH PROFILE — Rafraîchir les données utilisateur
  // ══════════════════════════════════════════════════════════════════════
  async function fetchProfile() {
    try {
      const res  = await api.get('/auth/me')
      user.value = res.data?.data ?? res.data ?? null
      return user.value
    } catch (e) {
      console.error('[auth] fetchProfile error:', e)
      return null
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // INSCRIPTION — Étape 1 : Vérifier matricule + email
  // ══════════════════════════════════════════════════════════════════════
  async function verifyPreloaded(matricule, email) {
    const res = await api.post('/auth/verify-identity', { matricule, email })
    return res.data?.data ?? res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // INSCRIPTION — Étape 2 : Envoyer OTP
  // ══════════════════════════════════════════════════════════════════════
  async function sendRegistrationOtp(studentId, email) {
    const res = await api.post('/auth/send-otp', {
      student_id: studentId,
      email:      email,
    })
    return res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // INSCRIPTION — Étape 3 : Vérifier OTP
  // ══════════════════════════════════════════════════════════════════════
  async function verifyRegistrationOtp(studentId, otpCode) {
    const res = await api.post('/auth/verify-otp', {
      student_id: studentId,
      otp_code:   otpCode,
    })
    return res.data?.data ?? res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // INSCRIPTION — Étape 4 : Définir le mot de passe
  // ══════════════════════════════════════════════════════════════════════
  async function setPassword(studentId, password, passwordConfirmation) {
    const res = await api.post('/auth/register', {
      student_id:            studentId,
      password:              password,
      password_confirmation: passwordConfirmation,
    })

    const data        = res.data?.data ?? res.data
    const accessToken = data?.token ?? res.data?.token ?? data?.access_token

    if (accessToken) {
      setToken(accessToken)
      user.value = data?.user ?? null
    }

    return {
      token: accessToken,
      user:  data?.user ?? null,
      data,
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // MOT DE PASSE OUBLIÉ — Étape 1 : Envoyer OTP par email
  // ══════════════════════════════════════════════════════════════════════
  async function forgotPassword(email) {
    const res = await api.post('/auth/forgot-password', { email })
    return res.data?.data ?? res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // MOT DE PASSE OUBLIÉ — Étape 2 : Vérifier OTP
  // ══════════════════════════════════════════════════════════════════════
  async function forgotVerifyOtp(userId, otpCode) {
    const res = await api.post('/auth/forgot-password/verify-otp', {
      user_id:  userId,
      otp_code: otpCode,
    })
    return res.data?.data ?? res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // MOT DE PASSE OUBLIÉ — Étape 3 : Réinitialiser le mot de passe
  // ══════════════════════════════════════════════════════════════════════
  async function resetPassword(resetToken, password, passwordConfirmation) {
    const res = await api.post('/auth/reset-password', {
      reset_token:           resetToken,
      password:              password,
      password_confirmation: passwordConfirmation,
    })
    return res.data
  }

  // ══════════════════════════════════════════════════════════════════════
  // LOGOUT
  // ══════════════════════════════════════════════════════════════════════
  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore — on nettoie quand même
    }
    setToken(null)
    user.value        = null
    initialized.value = false
  }

  // ══════════════════════════════════════════════════════════════════════
  // HELPERS
  // ══════════════════════════════════════════════════════════════════════
  function hasRole(role) {
    return user.value?.role === role
  }

  // ══════════════════════════════════════════════════════════════════════
  // RETURN — Exposer toutes les fonctions et états
  // ══════════════════════════════════════════════════════════════════════
  return {
    // ── State ────────────────────────────────────────────────────────
    user,
    token,
    initialized,

    // ── Computed ─────────────────────────────────────────────────────
    isAuthenticated,
    isAdmin,
    isStudent,

    // ── Auth de base ──────────────────────────────────────────────────
    init,
    setToken,
    fetchProfile,
    logout,
    hasRole,
    getDeviceFingerprint,

    // ── Login + Device OTP ────────────────────────────────────────────
    login,
    verifyDeviceOtp,
    resendDeviceOtp,

    // ── 2FA classique ─────────────────────────────────────────────────
    verify2FA,
    resendOtp,

    // ── Inscription ───────────────────────────────────────────────────
    verifyPreloaded,
    sendRegistrationOtp,
    verifyRegistrationOtp,
    setPassword,

    // ── Mot de passe oublié ───────────────────────────────────────────
    forgotPassword,
    forgotVerifyOtp,
    resetPassword,
  }
})
