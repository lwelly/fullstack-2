<template>
  <v-app :theme="appTheme">

    <!-- ══════════════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════════════════ -->
    <v-navigation-drawer
      v-model="drawer"
      permanent
      width="230"
      :class="['sidebar', { 'sidebar--dark': isDark }]"
    >
      <!-- ── Logo ─────────────────────────────────────────────────── -->
      <div class="sidebar-logo px-4 py-4">
        <div class="d-flex align-center gap-3">
         <div class="logo-wrapper">
  <img
    src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
    alt="Logo ISCAE"
    class="logo-img"
    draggable="false"
  />
</div>

          <div>
            <div class="logo-title">ISCAE</div>
            <div class="logo-sub">ÉTUDIANT</div>
          </div>
        </div>
      </div>

      <div class="sidebar-divider" />

      <!-- ── Navigation ───────────────────────────────────────────── -->
      <div class="px-3 py-2">
        <router-link
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          class="nav-link"
          :class="{ active: isActive(item) }"
        >
          <div class="nav-icon-wrapper">
            <v-icon size="18">{{ item.icon }}</v-icon>
          </div>
          <span class="nav-label">{{ item.title }}</span>
          <span v-if="item.badge && unread > 0" class="nav-badge">
            {{ unread > 99 ? '99+' : unread }}
          </span>
        </router-link>
      </div>

      <!-- ── Footer utilisateur ───────────────────────────────────── -->
      <template #append>
        <div class="sidebar-divider" />
        <div class="sidebar-footer px-3 py-3">
          <div class="d-flex align-center gap-2">
            <v-avatar size="32" class="user-avatar">
              <span class="avatar-initials">{{ initials }}</span>
            </v-avatar>
            <div class="flex-1 min-width-0">
              <div class="user-name text-truncate">{{ user?.name ?? 'Étudiant' }}</div>
              <div class="user-role">Étudiant</div>
            </div>
            <v-btn
              icon
              size="x-small"
              variant="text"
              class="logout-btn"
              title="Déconnexion"
              @click="logout"
            >
              <v-icon size="17">mdi-logout</v-icon>
            </v-btn>
          </div>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- ══════════════════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════════════════════ -->
    <v-app-bar
      flat
      height="58"
      :class="['app-header', { 'app-header--dark': isDark }]"
    >
      <!-- Bouton menu mobile -->
      <v-app-bar-nav-icon
        class="d-md-none"
        :color="isDark ? 'white' : '#374151'"
        @click="drawer = !drawer"
      />

      <!-- Titre page -->
      <v-app-bar-title>
        <span :class="['page-title', { 'page-title--dark': isDark }]">
          {{ pageTitle }}
        </span>
      </v-app-bar-title>

      <template #append>
        <!-- Toggle thème -->
        <v-btn
          icon
          variant="text"
          size="small"
          class="mr-1 theme-btn"
          :title="isDark ? 'Mode clair' : 'Mode sombre'"
          @click="toggleThemeGlobal"
        >
          <v-icon size="20" :color="isDark ? '#FCD34D' : '#6B7280'">
            {{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}
          </v-icon>
        </v-btn>

        <!-- Cloche notifications -->
        <v-btn
          icon
          variant="text"
          size="small"
          class="mr-1 notif-btn"
          :to="{ name: 'student.notifications' }"
        >
          <v-badge
            v-if="unread > 0"
            :content="unread > 99 ? '99+' : unread"
            color="error"
          >
            <v-icon size="20" :color="isDark ? '#D1D5DB' : '#6B7280'">
              mdi-bell-outline
            </v-icon>
          </v-badge>
          <v-icon v-else size="20" :color="isDark ? '#D1D5DB' : '#6B7280'">
            mdi-bell-outline
          </v-icon>
        </v-btn>

        <!-- Avatar -->
        <v-avatar
          size="34"
          class="mr-3 header-avatar"
          style="cursor: pointer"
          @click="router.push({ name: 'student.profile' })"
        >
          <span class="avatar-initials-header">{{ initials }}</span>
        </v-avatar>
      </template>
    </v-app-bar>

    <!-- ══════════════════════════════════════════════════════════════
         CONTENU PRINCIPAL
    ══════════════════════════════════════════════════════════════════ -->
    <v-main :class="['main-content', { 'main-content--dark': isDark }]">
      <v-container fluid class="pa-6">
        <router-view />
      </v-container>
    </v-main>

  </v-app>
</template>

<script setup>
import { ref, computed, inject, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter }                           from 'vue-router'
import { useAuthStore }                                  from '@/stores/auth'
import { useToast }                                      from 'vue-toastification'
import api                                               from '@/api/axios'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

// ── Thème injecté depuis App.vue ──────────────────────────────────────
const isDark            = inject('isDark')
const toggleThemeGlobal = inject('toggleTheme')
const appTheme          = inject('appTheme')

// ── État ──────────────────────────────────────────────────────────────
const drawer = ref(true)
const unread = ref(0)
let   notifTimer = null

// ── User ──────────────────────────────────────────────────────────────
const user = computed(() => authStore.user)

const initials = computed(() => {
  const name = user.value?.name ?? user.value?.email ?? 'ET'
  return name
    .split(' ')
    .map(n => n[0] ?? '')
    .join('')
    .toUpperCase()
    .slice(0, 2) || 'ET'
})

// ── Navigation ────────────────────────────────────────────────────────
const navItems = [
  {
    title: 'Tableau de bord',
    icon:  'mdi-view-dashboard-outline',
    name:  'student.dashboard',
    to:    { name: 'student.dashboard' },
  },
  {
    title: 'Mes Réclamations',
    icon:  'mdi-message-alert-outline',
    name:  'student.reclamations',
    to:    { name: 'student.reclamations' },
  },
  {
    title: 'Nouvelle Réclamation',
    icon:  'mdi-plus-circle-outline',
    name:  'student.reclamations.new',
    to:    { name: 'student.reclamations.new' },
  },
  {
    title: 'Notifications',
    icon:  'mdi-bell-outline',
    name:  'student.notifications',
    to:    { name: 'student.notifications' },
    badge: true,
  },
  {
    title: 'Mon Profil',
    icon:  'mdi-account-outline',
    name:  'student.profile',
    to:    { name: 'student.profile' },
  },
]

// ── Titre de page ─────────────────────────────────────────────────────
const titleMap = {
  'student.dashboard':          'Tableau de bord',
  'student.reclamations':       'Mes Réclamations',
  'student.reclamations.new':   'Nouvelle Réclamation',
  'student.reclamation.detail': 'Détail Réclamation',
  'student.notifications':      'Notifications',
  'student.profile':            'Mon Profil',
}
const pageTitle = computed(() => titleMap[route.name] ?? 'Espace Étudiant')

// ── Route active (inclut les sous-routes) ─────────────────────────────
function isActive(item) {
  if (route.name === item.name) return true
  // Marquer "Mes Réclamations" actif aussi sur la page détail
  if (item.name === 'student.reclamations' &&
      route.name === 'student.reclamation.detail') return true
  return false
}

// ── Logout ────────────────────────────────────────────────────────────
async function logout() {
  try {
    await authStore.logout()
    toast.success('Déconnexion réussie.')
  } catch {
    // ignore
  } finally {
    router.push({ name: 'login' })
  }
}

// ── Notifications non lues ────────────────────────────────────────────
async function fetchUnread() {
  try {
    const res = await api.get('/student/notifications/counts')
    unread.value = res.data?.data?.unread ?? 0
  } catch {
    unread.value = 0
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────
onMounted(() => {
  fetchUnread()
  notifTimer = setInterval(fetchUnread, 60000)
})

onUnmounted(() => {
  if (notifTimer) clearInterval(notifTimer)
})
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════════
   SIDEBAR — MODE CLAIR
════════════════════════════════════════════════════════════════════ */
.sidebar {
  background: #0F2D5E !important;
  border: none !important;
  box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15) !important;
}

/* MODE SOMBRE : sidebar légèrement plus claire */
.sidebar--dark {
  background: #0a1f42 !important;
  box-shadow: 2px 0 12px rgba(0, 0, 0, 0.4) !important;
}

/* ── Logo ── */
.sidebar-logo { background: rgba(0, 0, 0, 0.15); }

/* ── Logo wrapper : cercle blanc fixe ── */
.logo-wrapper {
  flex-shrink: 0;
  width: 46px !important;
  height: 46px !important;
  min-width: 46px !important;
  min-height: 46px !important;
  border-radius: 50%;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;           /* ← coupe tout ce qui dépasse */
  box-shadow: 0 0 0 2px rgba(255,255,255,0.25), 0 4px 16px rgba(0,0,0,0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.logo-wrapper:hover {
  transform: scale(1.06);
  box-shadow: 0 0 0 3px rgba(255,255,255,0.45), 0 6px 22px rgba(0,0,0,0.45);
}

/* ── Image contrainte dans le cercle ── */
.logo-img {
  width: 42px !important;
  height: 42px !important;
  max-width: 42px !important;
  max-height: 42px !important;
  object-fit: contain;        /* ← garde les proportions */
  border-radius: 50%;
  display: block;
  pointer-events: none;
  user-select: none;
}


.logo-title {
  font-size: 16px;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.1;
  letter-spacing: 0.5px;
}

.logo-sub {
  font-size: 9px;
  color: rgba(255, 255, 255, 0.45);
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-top: 1px;
}

.sidebar-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 0;
}

/* ── Liens de navigation ── */
.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 10px;
  color: rgba(255, 255, 255, 0.55);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 3px;
  transition: background 0.15s ease, color 0.15s ease, transform 0.1s ease;
  position: relative;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.9);
  transform: translateX(2px);
}

.nav-link.active {
  background: rgba(37, 99, 235, 0.35);
  color: #ffffff;
  font-weight: 600;
}

.nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: #3B82F6;
  border-radius: 0 3px 3px 0;
}

.nav-icon-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.06);
  transition: background 0.15s;
}

.nav-link.active .nav-icon-wrapper {
  background: rgba(59, 130, 246, 0.3);
}

.nav-link:hover .nav-icon-wrapper {
  background: rgba(255, 255, 255, 0.12);
}

.nav-label { flex: 1; }

.nav-badge {
  background: #DC2626;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
  line-height: 1.4;
}

/* ── Footer utilisateur ── */
.sidebar-footer {
  background: rgba(0, 0, 0, 0.2);
}

.user-avatar {
  background: linear-gradient(135deg, #2563EB, #1D4ED8) !important;
  flex-shrink: 0;
}

.avatar-initials {
  font-size: 12px;
  font-weight: 700;
  color: white;
}

.user-name {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.2;
  max-width: 110px;
}

.user-role {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.4);
  margin-top: 1px;
}

.logout-btn {
  color: rgba(255, 255, 255, 0.4) !important;
  transition: color 0.15s;
}
.logout-btn:hover {
  color: #F87171 !important;
}

/* ════════════════════════════════════════════════════════════════════
   HEADER
════════════════════════════════════════════════════════════════════ */
.app-header {
  background: #ffffff !important;
  border-bottom: 1px solid #E5E7EB !important;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05) !important;
}

/* MODE SOMBRE : header */
.app-header--dark {
  background: #1E293B !important;
  border-bottom: 1px solid #334155 !important;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3) !important;
}

.page-title {
  font-size: 15px;
  font-weight: 700;
  color: #111827;
}

.page-title--dark {
  color: #F1F5F9;
}

.header-avatar {
  background: linear-gradient(135deg, #0F2D5E, #1D4ED8) !important;
}

.avatar-initials-header {
  font-size: 12px;
  font-weight: 700;
  color: white;
}

/* ════════════════════════════════════════════════════════════════════
   CONTENU PRINCIPAL
════════════════════════════════════════════════════════════════════ */
.main-content {
  background: #F3F4F6 !important;
  transition: background 0.3s ease;
}

.main-content--dark {
  background: #0F172A !important;
}

/* ════════════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════════════ */
@media (max-width: 960px) {
  .sidebar { position: fixed !important; z-index: 1000 !important; }
}
</style>
