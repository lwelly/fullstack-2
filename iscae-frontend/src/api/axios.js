import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
  },
})

// ── Intercepteur requête : injecter le token ───────────────────────────────
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// ── Intercepteur réponse : gérer 401 ──────────────────────────────────────
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Nettoyer tout le storage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('student')
      sessionStorage.removeItem('2fa_user_id')
      sessionStorage.removeItem('2fa_login_type')
      sessionStorage.removeItem('2fa_email')
      // Rediriger seulement si pas déjà sur /login
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api
