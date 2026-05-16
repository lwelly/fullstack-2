<template>
  <v-app :theme="appTheme">

    <!-- ═══════════════════════════════════════════════════════════
         NAVIGATION DRAWER
    ════════════════════════════════════════════════════════════════ -->
    <v-navigation-drawer
      v-model="drawer"
      :rail="rail && !mobile"
      :temporary="mobile"
      :width="260"
      :rail-width="68"
      class="student-drawer"
      :class="{ 'drawer-dark': isDark }"
    >

      <!-- ── Logo ── -->
      <div class="drawer-logo" :class="{ 'logo-collapsed': rail && !mobile }">
        <div class="logo-wrapper">
          <img
            src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
            alt="Logo ISCAE"
            class="logo-img"
            draggable="false"
          />
        </div>
        <transition name="logo-slide">
          <div v-if="!rail || mobile" class="logo-texts">
            <span class="logo-app">ISCAE</span>
            <span class="logo-sub">Espace Étudiant</span>
          </div>
        </transition>
      </div>

      <!-- ── Label section ── -->
      <div class="drawer-section-label" :class="{ 'label-hidden': rail && !mobile }">
        <span>NAVIGATION</span>
      </div>

      <!-- ── Nav items principal ── -->
      <v-list density="compact" nav class="nav-list px-2">
        <router-link
          v-for="item in mainNavItems"
          :key="item.name"
          :to="item.to"
          class="nav-item-link"
          :class="{ 'link-active': isActive(item) }"
          :title="rail && !mobile ? item.title : undefined"
        >
          <div class="nav-item-inner">
            <div class="nav-icon-wrap" :class="{ 'icon-active': isActive(item) }">
              <v-icon size="18">{{ item.icon }}</v-icon>
            </div>
            <transition name="label-fade">
              <span v-if="!rail || mobile" class="nav-label">{{ item.title }}</span>
            </transition>
          </div>
          <transition name="label-fade">
            <v-badge
              v-if="item.badge && unread > 0 && (!rail || mobile)"
              :content="unread > 99 ? '99+' : unread"
              color="error"
              inline
            />
          </transition>
        </router-link>
      </v-list>

      <!-- ── Label section COMPTE ── -->
      <div class="drawer-section-label mt-2" :class="{ 'label-hidden': rail && !mobile }">
        <span>COMPTE</span>
      </div>

      <!-- ── Nav items compte ── -->
      <v-list density="compact" nav class="nav-list px-2">
        <router-link
          v-for="item in accountNavItems"
          :key="item.name"
          :to="item.to"
          class="nav-item-link"
          :class="{ 'link-active': isActive(item) }"
          :title="rail && !mobile ? item.title : undefined"
        >
          <div class="nav-item-inner">
            <div class="nav-icon-wrap" :class="{ 'icon-active': isActive(item) }">
              <v-icon size="18">{{ item.icon }}</v-icon>
            </div>
            <transition name="label-fade">
              <span v-if="!rail || mobile" class="nav-label">{{ item.title }}</span>
            </transition>
          </div>
        </router-link>
      </v-list>

      <!-- ── Footer drawer ── -->
      <template #append>
        
      </template>

    </v-navigation-drawer>

    <!-- ═══════════════════════════════════════════════════════════
         APP BAR
    ════════════════════════════════════════════════════════════════ -->
    <v-app-bar
      :color="isDark ? '#1E1E2E' : 'white'"
      elevation="0"
      class="student-appbar"
      :class="{ 'appbar-dark': isDark }"
    >
      <!-- Toggle sidebar -->
      <v-btn
        :icon="rail && !mobile ? 'mdi-menu-open' : 'mdi-menu'"
        variant="text"
        :color="isDark ? '#A9B1D6' : '#0F2D5E'"
        class="ml-1"
        @click="toggleSidebar"
      />

      <!-- Breadcrumb / titre -->
      <div class="appbar-breadcrumb ml-2">
        <span class="appbar-section">Étudiant</span>
        <v-icon size="14" :color="isDark ? '#565F89' : '#CBD5E1'" class="mx-1">mdi-chevron-right</v-icon>
        <span class="appbar-page" :class="{ 'page-dark': isDark }">{{ currentPageTitle }}</span>
      </div>

      <v-spacer />

      <!-- Toggle thème -->
      <v-tooltip :text="isDark ? 'Mode clair' : 'Mode sombre'" location="bottom">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" icon class="mr-1" @click="toggleTheme">
            <v-icon :color="isDark ? '#FCD34D' : '#475569'">
              {{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}
            </v-icon>
          </v-btn>
        </template>
      </v-tooltip>

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
          <v-icon size="20" :color="isDark ? '#D1D5DB' : '#475569'">mdi-bell-outline</v-icon>
        </v-badge>
        <v-icon v-else size="20" :color="isDark ? '#D1D5DB' : '#475569'">mdi-bell-outline</v-icon>
      </v-btn>

      <!-- Menu étudiant -->
      <v-menu min-width="210" :theme="appTheme">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" rounded="lg" class="mr-2 pa-1 user-btn">
            <v-avatar color="blue-darken-2" size="32">
              <img
                v-if="user?.photo_url"
                :src="user.photo_url"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%"
              />
              <span v-else class="text-caption font-weight-bold text-white">{{ initials }}</span>
            </v-avatar>
            <span v-if="!mobile" class="ml-2 user-btn-name" :class="{ 'name-dark': isDark }">
              {{ firstName }}
            </span>
            <v-icon size="16" class="ml-1" :color="isDark ? '#565F89' : '#CBD5E1'">mdi-chevron-down</v-icon>
          </v-btn>
        </template>

        <v-list density="compact" rounded="xl" elevation="8" class="user-menu" :class="{ 'menu-dark': isDark }">
          <!-- Header user -->
          <div class="user-menu-header">
            <v-avatar color="blue-darken-2" size="42">
              <img
                v-if="user?.photo_url"
                :src="user.photo_url"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%"
              />
              <span v-else class="text-body-2 font-weight-bold text-white">{{ initials }}</span>
            </v-avatar>
            <div class="user-menu-info">
              <div class="user-menu-name">{{ user?.name ?? 'Étudiant' }}</div>
              <div class="user-menu-role">{{ user?.email ?? 'Étudiant' }}</div>
            </div>
          </div>
          <v-divider class="mb-1" />
          <v-list-item prepend-icon="mdi-account-circle" title="Mon profil" :to="{ name: 'student.profile' }" rounded="lg" />
          <v-divider class="my-1" />
          <v-list-item prepend-icon="mdi-logout" title="Se déconnecter"
                       base-color="error" rounded="lg" @click="logout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- ═══════════════════════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════════════════════════ -->
    <v-main class="student-main" :class="{ 'main-dark': isDark }">
      <div class="page-wrapper">
        <router-view />
      </div>
    </v-main>

  </v-app>
</template>

<script setup>
import { ref, computed, watch, inject, onMounted, onUnmounted } from 'vue'
import { useDisplay } from 'vuetify'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const router    = useRouter()
const route     = useRoute()
const authStore = useAuthStore()
const toast     = useToast()
const { mobile } = useDisplay()

/* ── État ── */
const drawer = ref(true)
const rail   = ref(false)
const unread = ref(0)
let    notifTimer = null

/* ── Thème — injecté depuis App.vue ── */
const isDark      = inject('isDark')       // ref<boolean>
const toggleTheme = inject('toggleTheme') // function
const appTheme    = inject('appTheme')    // computed<'iscaeLight'|'iscaeDark'>

/* ── Sidebar ── */
function toggleSidebar() {
  if (mobile.value) {
    drawer.value = !drawer.value
  } else {
    rail.value = !rail.value
  }
}

/* ── User ── */
const user      = computed(() => authStore.user ?? {})
const firstName = computed(() => (user.value?.name ?? 'Étudiant').split(' ')[0])
const initials  = computed(() => {
  const name = user.value?.name ?? user.value?.email ?? 'ET'
  const parts = name.trim().split(' ').filter(Boolean)
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase()
})

/* ── Navigation ── */
const mainNavItems = [
  { title: 'Tableau de bord',    icon: 'mdi-view-dashboard',          name: 'student.dashboard',        to: { name: 'student.dashboard' } },
  { title: 'Mes Réclamations',   icon: 'mdi-file-document-multiple',  name: 'student.reclamations',     to: { name: 'student.reclamations' } },
  { title: 'Nouvelle Réclamation', icon: 'mdi-plus-circle-outline',   name: 'student.reclamations.new', to: { name: 'student.reclamations.new' } },
  { title: 'Notifications',      icon: 'mdi-bell-outline',            name: 'student.notifications',    to: { name: 'student.notifications' }, badge: true },
]

const accountNavItems = [
  { title: 'Mon Profil', icon: 'mdi-account-circle', name: 'student.profile', to: { name: 'student.profile' } },
]

const titleMap = {
  'student.dashboard'          : 'Tableau de bord',
  'student.reclamations'       : 'Mes Réclamations',
  'student.reclamations.new'   : 'Nouvelle Réclamation',
  'student.reclamation.detail' : 'Détail Réclamation',
  'student.notifications'      : 'Notifications',
  'student.profile'            : 'Mon Profil',
}

const currentPageTitle = computed(() => titleMap[route.name] ?? 'Espace Étudiant')

function isActive(item) {
  if (route.name === item.name) return true
  if (item.name === 'student.reclamations' && route.name === 'student.reclamation.detail') return true
  return false
}

/* ── Notifications ── */
async function fetchUnread() {
  try {
    const res = await api.get('/student/notifications/counts')
    unread.value = res.data?.data?.unread ?? 0
  } catch {
    unread.value = 0
  }
}

/* ── Logout ── */
async function logout() {
  try   { await authStore.logout(); toast.success('Déconnexion réussie.') }
  catch { /* ignore */ }
  finally { router.push({ name: 'login' }) }
}

/* ── Lifecycle ── */
onMounted(() => {
  fetchUnread()
  notifTimer = setInterval(fetchUnread, 60_000)
  if (mobile.value) drawer.value = false
})

onUnmounted(() => {
  if (notifTimer) clearInterval(notifTimer)
})

watch(mobile, v => {
  if (v) { drawer.value = false; rail.value = false }
  else   { drawer.value = true }
})
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════
   DRAWER — Mode clair
════════════════════════════════════════════════════════════════ */
.student-drawer {
  background: #0F2D5E !important;
  border-right: none !important;
  box-shadow: 4px 0 24px rgba(15, 45, 94, .18) !important;
}

/* ── Logo ── */
.drawer-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 22px 16px 18px;
  transition: padding .25s;
}
.logo-collapsed {
  justify-content: center;
  padding: 22px 8px 18px;
}
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
  overflow: hidden;
  box-shadow: 0 0 0 2px rgba(255,255,255,0.25), 0 4px 16px rgba(0,0,0,0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.logo-wrapper:hover {
  transform: scale(1.06);
  box-shadow: 0 0 0 3px rgba(255,255,255,0.45), 0 6px 22px rgba(0,0,0,0.45);
}
.logo-img {
  width: 42px !important;
  height: 42px !important;
  max-width: 42px !important;
  max-height: 42px !important;
  object-fit: contain;
  border-radius: 50%;
  display: block;
  pointer-events: none;
  user-select: none;
}
.logo-texts {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.logo-app {
  font-size: .95rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: .08em;
  line-height: 1.1;
}
.logo-sub {
  font-size: .68rem;
  color: rgba(255,255,255,.55);
  letter-spacing: .04em;
  margin-top: 1px;
}

/* ── Label section ── */
.drawer-section-label {
  padding: 4px 18px 6px;
  font-size: .62rem;
  font-weight: 700;
  color: rgba(255,255,255,.35);
  letter-spacing: .1em;
  text-transform: uppercase;
  transition: opacity .2s;
}
.label-hidden { opacity: 0; pointer-events: none; height: 0; padding: 0; overflow: hidden; }

/* ── Nav list ── */
.nav-list { padding-top: 0 !important; }

.nav-item-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 8px;
  height: 44px;
  border-radius: 10px;
  margin-bottom: 3px;
  text-decoration: none;
  cursor: pointer;
  transition: background .15s;
  color: rgba(255,255,255,.7);
  position: relative;
}
.nav-item-link:hover {
  background: rgba(255,255,255,.1);
  color: #fff;
}
.nav-item-link.link-active {
  background: rgba(255,255,255,.16);
  color: #fff;
}
.nav-item-link.link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 20px;
  background: #fff;
  border-radius: 0 3px 3px 0;
}

/* Inner row */
.nav-item-inner {
  display: flex;
  align-items: center;
  gap: 12px;
  overflow: hidden;
}

/* Icon wrap */
.nav-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: transparent;
  transition: background .15s;
  color: rgba(255,255,255,.7);
}
.nav-item-link:hover .nav-icon-wrap { background: rgba(255,255,255,.12); color: #fff; }
.nav-icon-wrap.icon-active {
  background: rgba(255,255,255,.2);
  color: #fff;
}

/* Label */
.nav-label {
  font-size: .875rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: inherit;
}
.link-active .nav-label { font-weight: 600; }

/* Divider + footer */
.drawer-divider { height: 1px; background: rgba(255,255,255,.1); margin: 0 16px 8px; }
.drawer-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px 20px;
}
.footer-collapsed { justify-content: center; padding: 12px 8px 20px; }
.footer-info { display: flex; flex-direction: column; overflow: hidden; }
.footer-name {
  font-size: .82rem;
  font-weight: 600;
  color: rgba(255,255,255,.9);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.footer-role { font-size: .68rem; color: rgba(255,255,255,.5); margin-top: 1px; }
.avatar-student { border: 2px solid rgba(255,255,255,.25); }

/* ════════════════════════════════════════════════════════════════
   DRAWER — Mode sombre
════════════════════════════════════════════════════════════════ */
.drawer-dark {
  background: #16213E !important;
  box-shadow: 4px 0 24px rgba(0,0,0,.4) !important;
}

/* ════════════════════════════════════════════════════════════════
   APP BAR
════════════════════════════════════════════════════════════════ */
.student-appbar {
  border-bottom: 1px solid #E2E8F0 !important;
}
.appbar-dark {
  border-bottom-color: #2D3748 !important;
}

.appbar-breadcrumb { display: flex; align-items: center; }
.appbar-section    { font-size: .8rem; color: #94A3B8; font-weight: 500; }
.appbar-page       { font-size: .875rem; font-weight: 700; color: #0F172A; }
.page-dark         { color: #E2E8F0 !important; }

/* User btn */
.user-btn      { text-transform: none !important; }
.user-btn-name { font-size: .85rem; font-weight: 600; color: #334155; }
.name-dark     { color: #CBD5E1 !important; }

/* User menu */
.user-menu {
  border-radius: 16px !important;
  border: 1px solid #E2E8F0;
  overflow: hidden;
}
.menu-dark { border-color: #2D3748 !important; }
.user-menu-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
}
.user-menu-name { font-size: .9rem; font-weight: 700; color: #1E293B; }
.user-menu-role { font-size: .75rem; color: #64748B; }

/* ════════════════════════════════════════════════════════════════
   MAIN
════════════════════════════════════════════════════════════════ */
.student-main {
  background: #F8FAFC !important;
  min-height: 100vh;
  transition: background .25s;
}
.main-dark {
  background: #0F0F1A !important;
}
.page-wrapper {
  max-width: 1300px;
  margin: 0 auto;
  padding: 24px 20px;
}

/* ════════════════════════════════════════════════════════════════
   TRANSITIONS
════════════════════════════════════════════════════════════════ */
.logo-slide-enter-active,
.logo-slide-leave-active { transition: opacity .2s, transform .2s; }
.logo-slide-enter-from,
.logo-slide-leave-to     { opacity: 0; transform: translateX(-8px); }

.label-fade-enter-active,
.label-fade-leave-active { transition: opacity .15s; }
.label-fade-enter-from,
.label-fade-leave-to     { opacity: 0; }

/* ════════════════════════════════════════════════════════════════
   DARK MODE — overrides globaux
════════════════════════════════════════════════════════════════ */
:global(.v-theme--dark .v-card)    { background: #1A1A2E !important; border-color: #2D3748 !important; }
:global(.v-theme--dark .v-card-title),
:global(.v-theme--dark .v-list-item-title) { color: #E2E8F0 !important; }
:global(.v-theme--dark .text-medium-emphasis) { color: #94A3B8 !important; }
:global(.v-theme--dark .v-field) { background: #2D3748 !important; }
:global(.v-theme--dark .v-field__input) { color: #E2E8F0 !important; }
:global(.v-theme--dark .v-label)        { color: #94A3B8 !important; }
:global(.v-theme--dark .v-divider)      { border-color: #2D3748 !important; }
:global(.v-theme--dark .page-title)     { color: #E2E8F0 !important; }
:global(.v-theme--dark .page-sub)       { color: #94A3B8 !important; }
:global(.v-theme--dark .user-menu-name) { color: #E2E8F0 !important; }
:global(.v-theme--dark .user-menu-role) { color: #94A3B8 !important; }
</style>