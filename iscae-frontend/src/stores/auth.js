// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {
  const user        = ref(null)
  const token       = ref(localStorage.getItem('auth_token') ?? null)
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isStudent       = computed(() => user.value?.role === 'student')

  // ── Fingerprint appareil ──────────────────────────────────────────────
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

  // ── Set / Clear token ─────────────────────────────────────────────────
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

  // ── Init (appelé une fois au démarrage du router) ─────────────────────
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

  // ── Login ─────────────────────────────────────────────────────────────
  // Backend (LoginRequest) attend : { login, password, device_fingerprint }
  async function login(credentials) {
    const fingerprint = await getDeviceFingerprint()

    const res = await api.post('/auth/login', {
      login:              credentials.login ?? credentials.email,
      password:           credentials.password,
      device_fingerprint: credentials.device_fingerprint ?? fingerprint,
    })

    const data = res.data?.data ?? res.data

    // Cas 2FA requis → on retourne les infos pour la page OTP
    if (data?.requires_2fa) {
      return {
        requires_2fa:       true,
        user_id:            data.user_id,
        login_type:         data.login_type ?? 'student',
        device_fingerprint: fingerprint,
      }
    }

    // Connexion directe (appareil de confiance — pas d'OTP)
    const accessToken = data.token ?? data.access_token
    setToken(accessToken)
    user.value = data.user ?? data
    return {
      requires_2fa: false,
      user:         user.value,
      token:        accessToken,
    }
  }

  // ── Verify 2FA ────────────────────────────────────────────────────────
  // Backend (Verify2FARequest) attend :
  // { user_id, otp_code, login_type, device_fingerprint, trust_device }
  async function verify2FA(payload) {
    const fingerprint = payload.device_fingerprint ?? await getDeviceFingerprint()

    const res = await api.post('/auth/2fa/verify', {
      user_id:            payload.user_id,
      otp_code:           payload.otp_code ?? payload.otp,
      login_type:         payload.login_type ?? 'student',
      device_fingerprint: fingerprint,
      trust_device:       payload.trust_device ?? true,
    })

    const data = res.data?.data ?? res.data

    const accessToken = data.token ?? data.access_token
    setToken(accessToken)
    user.value = data.user ?? data

    return {
      user:  user.value,
      token: accessToken,
    }
  }

  // ── Resend OTP ────────────────────────────────────────────────────────
  async function resendOtp(userId, loginType = 'student') {
    const res = await api.post('/auth/2fa/resend', {
      user_id:    userId,
      login_type: loginType,
    })
    return res.data
  }

  // ── Register — étape 1 : vérifier matricule + email ───────────────────
  async function verifyPreloaded(matricule, email) {
    const res = await api.post('/auth/register/verify', { matricule, email })
    return res.data?.data ?? res.data
  }

  // ── Register — étape 2 : envoyer OTP ─────────────────────────────────
  async function sendRegistrationOtp(preloadedId) {
    const res = await api.post('/auth/register/send-otp', {
      preloaded_id: preloadedId,
    })
    return res.data
  }

  // ── Register — étape 3 : vérifier OTP ────────────────────────────────
  async function verifyRegistrationOtp(preloadedId, otpCode) {
    const res = await api.post('/auth/register/verify-otp', {
      preloaded_id: preloadedId,
      otp_code:     otpCode,
    })
    return res.data?.data ?? res.data
  }

  // ── Register — étape 4 : définir le mot de passe ──────────────────────
  async function setPassword(registrationToken, password, passwordConfirmation) {
    const res = await api.post('/auth/register/set-password', {
      registration_token:    registrationToken,
      password:              password,
      password_confirmation: passwordConfirmation,
    })
    const data = res.data?.data ?? res.data

    const accessToken = data.token ?? data.access_token
    if (accessToken) {
      setToken(accessToken)
      user.value = data.user ?? data
    }
    return data
  }
  // ── Fetch Profile ─────────────────────────────────────────────────────
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


  // ── Logout ────────────────────────────────────────────────────────────
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

  // ── Helpers ───────────────────────────────────────────────────────────
  function hasRole(role) {
    return user.value?.role === role
  }

  return {
  // State
  user,
  token,
  initialized,
  // Computed
  isAuthenticated,
  isAdmin,
  isStudent,
  // Actions
  init,
  login,
  verify2FA,
  resendOtp,
  verifyPreloaded,
  sendRegistrationOtp,
  verifyRegistrationOtp,
  setPassword,
  logout,
  setToken,
  fetchProfile,        // ← ajouter
  getDeviceFingerprint,
  hasRole,
}

})
