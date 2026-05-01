<template>
  <v-app :theme="appTheme">

    <!-- ── SIDEBAR ─────────────────────────────────────────────────── -->
    <v-navigation-drawer
      v-model="drawer"
      permanent
      width="220"
      style="background:#0F2D5E; border:none;"
    >
      <!-- Logo -->
      <div class="px-4 py-4">
        <div class="d-flex align-center gap-2">
          <v-icon color="white" size="28">mdi-school</v-icon>
          <div>
            <div class="text-white font-weight-bold" style="font-size:16px;line-height:1">ISCAE</div>
            <div style="font-size:10px;color:rgba(255,255,255,0.5);letter-spacing:1px">ÉTUDIANT</div>
          </div>
        </div>
      </div>

      <v-divider style="border-color:rgba(255,255,255,0.1)" />

      <!-- Nav -->
      <div class="px-2 py-3">
        <router-link
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          class="nav-link"
          :class="{ active: route.name === item.name }"
        >
          <v-icon size="18">{{ item.icon }}</v-icon>
          <span>{{ item.title }}</span>
          <span v-if="item.badge && unread > 0" class="badge">{{ unread }}</span>
        </router-link>
      </div>

      <!-- Footer -->
      <template #append>
        <v-divider style="border-color:rgba(255,255,255,0.1)" />
        <div class="d-flex align-center justify-space-between px-3 py-3">
          <div class="d-flex align-center gap-2">
            <v-avatar size="30" color="#2563EB">
              <span style="font-size:11px;font-weight:700;color:white">{{ initials }}</span>
            </v-avatar>
            <div>
              <div
                style="font-size:12px;font-weight:600;color:white;max-width:100px"
                class="text-truncate"
              >
                {{ user?.name ?? 'Étudiant' }}
              </div>
              <div style="font-size:10px;color:rgba(255,255,255,0.5)">Étudiant</div>
            </div>
          </div>
          <v-btn icon size="x-small" variant="text" @click="logout">
            <v-icon color="rgba(255,255,255,0.5)" size="18">mdi-logout</v-icon>
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- ── HEADER ──────────────────────────────────────────────────── -->
    <v-app-bar flat height="56" color="white" style="border-bottom:1px solid #E5E7EB;">
      <v-app-bar-nav-icon class="d-md-none" @click="drawer = !drawer" />
      <v-app-bar-title>
        <span style="font-size:15px;font-weight:700;color:#111827">{{ pageTitle }}</span>
      </v-app-bar-title>
      <template #append>
        <!-- Toggle thème -->
        <v-btn icon variant="text" size="small" class="mr-1" @click="toggleThemeGlobal">
          <v-icon size="20">{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
        </v-btn>
        <!-- Notifications -->
        <v-btn
          icon variant="text" size="small" class="mr-2"
          :to="{ name: 'student.notifications' }"
        >
          <v-badge v-if="unread > 0" :content="unread" color="error">
            <v-icon size="20">mdi-bell-outline</v-icon>
          </v-badge>
          <v-icon v-else size="20">mdi-bell-outline</v-icon>
        </v-btn>
        <!-- Avatar -->
        <v-avatar
          size="32" color="#0F2D5E" class="mr-3"
          style="cursor:pointer"
          @click="router.push({ name: 'student.profile' })"
        >
          <span style="font-size:11px;font-weight:700;color:white">{{ initials }}</span>
        </v-avatar>
      </template>
    </v-app-bar>

    <!-- ── MAIN ────────────────────────────────────────────────────── -->
    <v-main>
      <v-container fluid class="pa-6">
        <router-view />
      </v-container>
    </v-main>

  </v-app>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRoute, useRouter }              from 'vue-router'
import { useAuthStore }                     from '@/stores/auth'
import { useToast }                         from 'vue-toastification'
import api                                  from '@/api/axios'

const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

// ── Thème global injecté depuis App.vue ───────────────────────────────
const isDark            = inject('isDark')
const toggleThemeGlobal = inject('toggleTheme')
const appTheme          = inject('appTheme')

// ── État ──────────────────────────────────────────────────────────────
const drawer = ref(true)
const unread = ref(0)

// ── User ──────────────────────────────────────────────────────────────
const user = computed(() => authStore.user)

const initials = computed(() =>
  (user.value?.name ?? 'ET')
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
)

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

// ── Logout ────────────────────────────────────────────────────────────
async function logout() {
  await authStore.logout()
  toast.success('Déconnexion réussie.')
  router.push({ name: 'login' })
}

// ── Notifications non lues ────────────────────────────────────────────
async function fetchUnread() {
  try {
    const res  = await api.get('/student/notifications')
    const list = res.data?.data ?? []
    unread.value = list.filter(n => !n.is_read && !n.read_at).length
  } catch {
    unread.value = 0
  }
}

onMounted(fetchUnread)
</script>

<style scoped>
.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 8px;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 2px;
  transition: background .15s, color .15s;
}
.nav-link:hover {
  background: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.9);
}
.nav-link.active {
  background: rgba(255,255,255,0.12);
  color: #fff;
  border-left: 3px solid #2563EB;
  padding-left: 9px;
}
.badge {
  margin-left: auto;
  background: #DC2626;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 10px;
}
</style>
