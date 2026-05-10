<template>
  <v-app :theme="appTheme">

    <!-- ══════════════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════════════════ -->
    <v-navigation-drawer
      v-model="drawer"
      permanent
      width="240"
      :class="['sidebar', { 'sidebar--dark': isDark }]"
    >
      <!-- ── Logo ─────────────────────────────────────────────────── -->
      <div class="sidebar-logo px-5 py-5">
        <div class="d-flex align-center" style="gap: 14px;">
          <div class="logo-wrapper">
            <img
              src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
              alt="Logo ISCAE"
              class="logo-img"
              draggable="false"
            />
          </div>
          <div class="logo-text">
            <div class="logo-title">ISCAE</div>
            <div class="logo-sub">ÉTUDIANT</div>
          </div>
        </div>
      </div>

      <div class="sidebar-divider" />

      <!-- ── Section PRINCIPAL ─────────────────────────────────────── -->
      <div class="px-3 pt-4 pb-1">
        <div class="nav-section-label">PRINCIPAL</div>
        <router-link
          v-for="item in mainNavItems"
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

      <div class="sidebar-divider mt-2" />

      <!-- ── Section COMPTE ────────────────────────────────────────── -->
      <div class="px-3 pt-3 pb-1">
        <div class="nav-section-label">COMPTE</div>
        <router-link
          v-for="item in accountNavItems"
          :key="item.name"
          :to="item.to"
          class="nav-link"
          :class="{ active: isActive(item) }"
        >
          <div class="nav-icon-wrapper">
            <v-icon size="18">{{ item.icon }}</v-icon>
          </div>
          <span class="nav-label">{{ item.title }}</span>
        </router-link>
      </div>

      <!-- ── Footer utilisateur ───────────────────────────────────── -->
      <template #append>
        <div class="sidebar-divider" />
        <div class="sidebar-footer px-4 py-3">
          <div class="d-flex align-center" style="gap: 10px;">
            <v-avatar size="34" class="user-avatar flex-shrink-0">
              <img
                v-if="user?.photo_url"
                :src="user.photo_url"
                :alt="user.name"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%"
              />
              <span v-else class="avatar-initials">{{ initials }}</span>
            </v-avatar>
            <div style="flex:1; min-width:0;">
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
      height="60"
      :class="['app-header', { 'app-header--dark': isDark }]"
    >
      <v-app-bar-nav-icon
        class="d-md-none"
        :color="isDark ? 'white' : '#374151'"
        @click="drawer = !drawer"
      />

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
          class="mr-1"
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
          class="mr-1"
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

        <!-- Email -->
        <div
          v-if="user?.email"
          :class="['header-email mr-2', { 'header-email--dark': isDark }]"
        >
          {{ user.email }}
        </div>

        <!-- Avatar -->
        <v-avatar
          size="36"
          class="mr-3 header-avatar"
          style="cursor:pointer"
          @click="router.push({ name: 'student.profile' })"
        >
          <img
            v-if="user?.photo_url"
            :src="user.photo_url"
            style="width:100%;height:100%;object-fit:cover;border-radius:50%"
          />
          <span v-else class="avatar-initials-header">{{ initials }}</span>
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

const isDark            = inject('isDark')
const toggleThemeGlobal = inject('toggleTheme')
const appTheme          = inject('appTheme')

const drawer     = ref(true)
const unread     = ref(0)
let   notifTimer = null

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
const mainNavItems = [
  {
    title : 'Tableau de bord',
    icon  : 'mdi-view-dashboard-outline',
    name  : 'student.dashboard',
    to    : { name: 'student.dashboard' },
  },
  {
    title : 'Mes Réclamations',
    icon  : 'mdi-message-alert-outline',
    name  : 'student.reclamations',
    to    : { name: 'student.reclamations' },
  },
  {
    title : 'Nouvelle Réclamation',
    icon  : 'mdi-plus-circle-outline',
    name  : 'student.reclamations.new',
    to    : { name: 'student.reclamations.new' },
  },
  {
    title : 'Notifications',
    icon  : 'mdi-bell-outline',
    name  : 'student.notifications',
    to    : { name: 'student.notifications' },
    badge : true,
  },
]

const accountNavItems = [
  {
    title : 'Mon Profil',
    icon  : 'mdi-account-circle-outline',
    name  : 'student.profile',
    to    : { name: 'student.profile' },
  },
]

const titleMap = {
  'student.dashboard'          : 'Tableau de bord',
  'student.reclamations'       : 'Mes Réclamations',
  'student.reclamations.new'   : 'Nouvelle Réclamation',
  'student.reclamation.detail' : 'Détail Réclamation',
  'student.notifications'      : 'Notifications',
  'student.profile'            : 'Mon Profil',
}
const pageTitle = computed(() => titleMap[route.name] ?? 'Espace Étudiant')

function isActive(item) {
  if (route.name === item.name) return true
  if (item.name === 'student.reclamations' &&
      route.name === 'student.reclamation.detail') return true
  return false
}

async function logout() {
  try   { await authStore.logout(); toast.success('Déconnexion réussie.') }
  catch { /* ignore */ }
  finally { router.push({ name: 'login' }) }
}

async function fetchUnread() {
  try   { const res = await api.get('/student/notifications/counts'); unread.value = res.data?.data?.unread ?? 0 }
  catch { unread.value = 0 }
}

onMounted(() => { fetchUnread(); notifTimer = setInterval(fetchUnread, 60_000) })
onUnmounted(() => { if (notifTimer) clearInterval(notifTimer) })
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════════
   SIDEBAR
════════════════════════════════════════════════════════════════════ */
.sidebar {
  background: #0F2D5E !important;
  border: none !important;
  box-shadow: 2px 0 16px rgba(0, 0, 0, 0.18) !important;
}
.sidebar--dark {
  background: #0a1f42 !important;
  box-shadow: 2px 0 16px rgba(0, 0, 0, 0.45) !important;
}

/* ── Logo zone ── */
.sidebar-logo {
  background: rgba(0, 0, 0, 0.18);
  min-height: 76px;
  display: flex;
  align-items: center;
}

/* ── Cercle blanc du logo ── */
.logo-wrapper {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow:
    0 0 0 3px rgba(255, 255, 255, 0.20),
    0 4px 18px rgba(0, 0, 0, 0.40);
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.logo-wrapper:hover {
  transform: scale(1.07);
  box-shadow:
    0 0 0 4px rgba(255, 255, 255, 0.38),
    0 6px 24px rgba(0, 0, 0, 0.50);
}

/* ── Image logo ── */
.logo-img {
  width: 44px;
  height: 44px;
  object-fit: contain;
  display: block;
  pointer-events: none;
  user-select: none;
}

/* ── Texte logo ── */
.logo-text {
  margin-left: 2px;
}
.logo-title {
  font-size: 17px;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.15;
  letter-spacing: 0.6px;
}
.logo-sub {
  font-size: 9px;
  color: rgba(255, 255, 255, 0.42);
  letter-spacing: 2.2px;
  text-transform: uppercase;
  margin-top: 2px;
}

/* ── Séparateur ── */
.sidebar-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
}

/* ── Label de section ── */
.nav-section-label {
  font-size: 9.5px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.28);
  letter-spacing: 1.6px;
  text-transform: uppercase;
  padding: 0 10px 7px 10px;
}

/* ── Liens nav ── */
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
  margin-bottom: 2px;
  transition: background 0.15s, color 0.15s, transform 0.12s;
  position: relative;
}
.nav-link:hover {
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.92);
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
.nav-link.active  .nav-icon-wrapper { background: rgba(59, 130, 246, 0.30); }
.nav-link:hover   .nav-icon-wrapper { background: rgba(255, 255, 255, 0.12); }

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

/* ── Footer ── */
.sidebar-footer  { background: rgba(0, 0, 0, 0.22); }

.user-avatar {
  background: linear-gradient(135deg, #2563EB, #1D4ED8) !important;
}
.avatar-initials {
  font-size: 12px;
  font-weight: 700;
  color: white;
}
.user-name {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.90);
  line-height: 1.2;
  max-width: 120px;
}
.user-role {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.38);
  margin-top: 1px;
}
.logout-btn       { color: rgba(255, 255, 255, 0.38) !important; transition: color 0.15s; }
.logout-btn:hover { color: #F87171 !important; }

/* ════════════════════════════════════════════════════════════════════
   HEADER
════════════════════════════════════════════════════════════════════ */
.app-header {
  background: #ffffff !important;
  border-bottom: 1px solid #E5E7EB !important;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06) !important;
}
.app-header--dark {
  background: #1E293B !important;
  border-bottom: 1px solid #334155 !important;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.30) !important;
}

.page-title       { font-size: 15px; font-weight: 700; color: #111827; }
.page-title--dark { color: #F1F5F9; }

.header-email {
  font-size: 12px;
  font-weight: 500;
  color: #6B7280;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.header-email--dark { color: #9CA3AF; }

.header-avatar {
  background: linear-gradient(135deg, #0F2D5E, #1D4ED8) !important;
}
.avatar-initials-header {
  font-size: 13px;
  font-weight: 700;
  color: white;
}

/* ════════════════════════════════════════════════════════════════════
   CONTENU
════════════════════════════════════════════════════════════════════ */
.main-content       { background: #F3F4F6 !important; transition: background 0.3s; }
.main-content--dark { background: #0F172A !important; }

/* ════════════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════════════ */
@media (max-width: 960px) {
  .sidebar       { position: fixed !important; z-index: 1000 !important; }
  .header-email  { display: none; }
}
</style>
