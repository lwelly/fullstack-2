import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {
  const user        = ref(null)
  const token       = ref(localStorage.getItem('token') ?? null)
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  function setToken(t) {
    token.value = t
    if (t) {
      localStorage.setItem('token', t)
      api.defaults.headers.common['Authorization'] = `Bearer ${t}`
    } else {
      localStorage.removeItem('token')
      delete api.defaults.headers.common['Authorization']
    }
  }

  async function init() {
    if (initialized.value) return
    initialized.value = true

    const savedToken = localStorage.getItem('token')
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

  // ── Login ──────────────────────────────────────────────────────────────
  // Le backend (LoginRequest) attend : { login, password }
  async function login(credentials) {
    const res  = await api.post('/auth/login', {
      login:    credentials.login ?? credentials.email,  // ✅ compatibilité
      password: credentials.password,
    })
    const data = res.data?.data ?? res.data

    // Cas 2FA → retourne tout l'objet (contient user_id, email, login_type…)
    if (data?.requires_2fa) return data

    // Connexion directe (pas de 2FA)
    setToken(data.token ?? data.access_token)
    user.value = data.user ?? data
    return data
  }

  // ── Verify 2FA ────────────────────────────────────────────────────────
  // Le backend (Verify2FARequest) attend : { user_id, otp_code, login_type }
  async function verify2FA(payload) {
    const res  = await api.post('/auth/2fa/verify', {
      user_id:    payload.user_id,
      otp_code:   payload.otp_code ?? payload.otp,  // ✅ compatibilité
      login_type: payload.login_type ?? 'student',
    })
    const data = res.data?.data ?? res.data

    setToken(data.token ?? data.access_token)
    user.value = data.user ?? data
    return data
  }

  // ── Register ──────────────────────────────────────────────────────────
  async function register(payload) {
    const res  = await api.post('/auth/register', payload)
    const data = res.data?.data ?? res.data
    setToken(data.token ?? data.access_token)
    user.value = data.user ?? data
    return data
  }

  // ── Logout ────────────────────────────────────────────────────────────
  async function logout() {
    try { await api.post('/auth/logout') } catch { /* ignore */ }
    setToken(null)
    user.value = null
  }

  return {
    user, token, initialized, isAuthenticated,
    init, login, verify2FA, register, logout, setToken,
  }
})
