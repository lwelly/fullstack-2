import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// ── Auth Views ──────────────────────────────────────────────────────────
const LoginView           = () => import('@/views/auth/LoginView.vue')
const TwoFactorView       = () => import('@/views/auth/TwoFactorView.vue')
const RegisterView        = () => import('@/views/auth/RegisterView.vue')
const ForgotPasswordView  = () => import('@/views/auth/ForgotPasswordView.vue')

// ── Layouts ─────────────────────────────────────────────────────────────
const AdminLayout    = () => import('@/layouts/AdminLayout.vue')
const StudentLayout  = () => import('@/layouts/StudentLayout.vue')

// ── Admin Views ─────────────────────────────────────────────────────────
const AdminDashboard         = () => import('@/views/admin/DashboardView.vue')
const AdminStudents          = () => import('@/views/admin/StudentsView.vue')
const AdminReclamations      = () => import('@/views/admin/ReclamationsView.vue')
const AdminReclamationDetail = () => import('@/views/admin/ReclamationDetailView.vue')
const AdminSemestres         = () => import('@/views/admin/SemestresView.vue')
const AdminSettings          = () => import('@/views/admin/SettingsView.vue')
const AdminProfile           = () => import('@/views/admin/ProfileView.vue')

// ── Student Views ────────────────────────────────────────────────────────
const StudentDashboard         = () => import('@/views/student/DashboardView.vue')
const StudentReclamations      = () => import('@/views/student/ReclamationsView.vue')
const StudentNewReclamation    = () => import('@/views/student/NewReclamationView.vue')
const StudentReclamationDetail = () => import('@/views/student/ReclamationDetailView.vue')
const StudentProfile           = () => import('@/views/student/ProfileView.vue')
const StudentNotifications     = () => import('@/views/student/NotificationsView.vue')

// ── Routes ───────────────────────────────────────────────────────────────
const routes = [
  { path: '/', redirect: '/login' },

  // ── Auth ────────────────────────────────────────────────────────────
  {
    path:      '/login',
    name:      'login',
    component: LoginView,
    meta:      { guest: true },
  },
  {
    path:      '/register',
    name:      'register',
    component: RegisterView,
    meta:      { guest: true, allowPostRegister: true },
  },
  {
    path:      '/2fa',
    name:      'verify-2fa',
    component: TwoFactorView,
    meta:      { guest: false, requiresAuth: false },
  },

  // ── ✅ Mot de passe oublié ──────────────────────────────────────────
  {
    path:      '/forgot-password',
    name:      'forgot-password',
    component: ForgotPasswordView,
    meta:      { guest: true },
  },

  // ── Admin ────────────────────────────────────────────────────────────
  {
    path:      '/admin',
    component: AdminLayout,
    meta:      { requiresAuth: true, role: 'admin' },
    children: [
      { path: '',                  redirect: '/admin/dashboard' },
      { path: 'dashboard',         name: 'admin.dashboard',          component: AdminDashboard },
      { path: 'students',          name: 'admin.students',           component: AdminStudents },
      { path: 'reclamations',      name: 'admin.reclamations',       component: AdminReclamations },
      { path: 'reclamations/:id',  name: 'admin.reclamation.detail', component: AdminReclamationDetail },
      { path: 'semestres',         name: 'admin.semestres',          component: AdminSemestres },
      { path: 'settings',          name: 'admin.settings',           component: AdminSettings },
      { path: 'profile',           name: 'admin.profile',            component: AdminProfile },
    ],
  },

  // ── Student ──────────────────────────────────────────────────────────
  {
    path:      '/student',
    component: StudentLayout,
    meta:      { requiresAuth: true, role: 'student' },
    children: [
      { path: '',                  redirect: '/student/dashboard' },
      { path: 'dashboard',         name: 'student.dashboard',          component: StudentDashboard },
      { path: 'reclamations',      name: 'student.reclamations',       component: StudentReclamations },
      { path: 'reclamations/new',  name: 'student.reclamations.new',   component: StudentNewReclamation },
      { path: 'reclamations/:id',  name: 'student.reclamation.detail', component: StudentReclamationDetail },
      { path: 'profile',           name: 'student.profile',            component: StudentProfile },
      { path: 'notifications',     name: 'student.notifications',      component: StudentNotifications },
    ],
  },

  // ── 404 ──────────────────────────────────────────────────────────────
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

// ── Router ────────────────────────────────────────────────────────────────
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

// ── Navigation Guard ──────────────────────────────────────────────────────
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Init une seule fois
  if (!auth.initialized) {
    await auth.init()
  }

  const isAuth = auth.isAuthenticated
  const role   = auth.user?.role

  // ── Route protégée ──────────────────────────────────────────────────
  if (to.meta.requiresAuth) {
    if (!isAuth) return { name: 'login' }
    if (to.meta.role && to.meta.role !== role) {
      return role === 'admin'
        ? { name: 'admin.dashboard' }
        : { name: 'student.dashboard' }
    }
    return true
  }

  // ── Route guest ─────────────────────────────────────────────────────
  if (to.meta.guest && isAuth) {
    // ✅ Exception post-inscription : laisser passer RegisterView
    if (to.meta.allowPostRegister && to.name === 'register') {
      return true
    }
    // ✅ Exception : laisser accéder forgot-password même si connecté
    // (cas rare où l'utilisateur veut changer son mot de passe)
    if (to.name === 'forgot-password') {
      return true
    }
    // Sinon rediriger vers le bon dashboard
    return role === 'admin'
      ? { name: 'admin.dashboard' }
      : { name: 'student.dashboard' }
  }

  // ── Page 2FA ─────────────────────────────────────────────────────────
  if (to.name === 'verify-2fa') {
    const hasQueryId   = !!to.query.user_id
    const hasSessionId = !!sessionStorage.getItem('2fa_user_id')
    const has2fa       = hasQueryId || hasSessionId

    if (isAuth) {
      return role === 'admin'
        ? { name: 'admin.dashboard' }
        : { name: 'student.dashboard' }
    }
    if (!has2fa) return { name: 'login' }
  }

  return true
})

export default router
