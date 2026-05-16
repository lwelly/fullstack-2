<template>
  <v-app :theme="currentTheme">

    <!-- ═══════════════════════════════════════════════════════════
         NAVIGATION DRAWER
    ════════════════════════════════════════════════════════════════ -->
    <v-navigation-drawer
      v-model="drawer"
      :rail="rail && !mobile"
      :temporary="mobile"
      :width="260"
      :rail-width="68"
      class="admin-drawer"
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
            <span class="logo-sub">Administration</span>
          </div>
        </transition>
      </div>

      <!-- ── Séparateur + label section ── -->
      <div class="drawer-section-label" :class="{ 'label-hidden': rail && !mobile }">
        <span>NAVIGATION</span>
      </div>

      <!-- ── Nav items ── -->
      <v-list density="compact" nav class="nav-list px-2">
        <template v-for="item in navItems" :key="item.to ?? item.label">

          <!-- Groupe avec enfants -->
          <template v-if="item.children">
            <div
              class="nav-group-header"
              :class="{ 'group-active': isGroupActive(item) }"
              @click="toggleGroup(item.label)"
            >
              <div class="nav-item-inner">
                <div class="nav-icon-wrap" :class="{ 'icon-active': isGroupActive(item) }">
                  <v-icon size="18">{{ item.icon }}</v-icon>
                </div>
                <transition name="label-fade">
                  <span v-if="!rail || mobile" class="nav-label">{{ item.label }}</span>
                </transition>
              </div>
              <transition name="label-fade">
                <v-icon v-if="!rail || mobile" size="16" class="group-arrow"
                        :class="{ 'arrow-open': openGroups.includes(item.label) }">
                  mdi-chevron-down
                </v-icon>
              </transition>
            </div>
            <v-expand-transition>
              <div v-if="openGroups.includes(item.label) && (!rail || mobile)" class="nav-children">
                <router-link
                  v-for="child in item.children" :key="child.to"
                  :to="child.to"
                  class="nav-child-item"
                  :class="{ 'child-active': $route.path === child.to || $route.path.startsWith(child.to + '/') }"
                >
                  <v-icon size="14" class="mr-2">{{ child.icon }}</v-icon>
                  {{ child.label }}
                </router-link>
              </div>
            </v-expand-transition>
          </template>

          <!-- Item simple -->
          <router-link
            v-else
            :to="item.to"
            class="nav-item-link"
            :class="{ 'link-active': isActive(item.to) }"
            :title="rail && !mobile ? item.label : undefined"
          >
            <div class="nav-item-inner">
              <div class="nav-icon-wrap" :class="{ 'icon-active': isActive(item.to) }">
                <v-icon size="18">{{ item.icon }}</v-icon>
              </div>
              <transition name="label-fade">
                <span v-if="!rail || mobile" class="nav-label">{{ item.label }}</span>
              </transition>
            </div>
            <transition name="label-fade">
              <v-badge
                v-if="item.badge && (!rail || mobile)"
                :content="item.badge"
                color="error"
                inline
              />
            </transition>
          </router-link>

        </template>
      </v-list>

      <!-- ── Footer drawer ── -->
      

    </v-navigation-drawer>

    <!-- ═══════════════════════════════════════════════════════════
         APP BAR
    ════════════════════════════════════════════════════════════════ -->
    <v-app-bar
      :color="isDark ? '#1E1E2E' : 'white'"
      elevation="0"
      class="admin-appbar"
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
        <span class="appbar-section">Admin</span>
        <v-icon size="14" :color="isDark ? '#565F89' : '#CBD5E1'" class="mx-1">mdi-chevron-right</v-icon>
        <span class="appbar-page" :class="{ 'page-dark': isDark }">{{ currentPageTitle }}</span>
      </div>

      <v-spacer />

      <!-- Recherche globale -->
      <v-text-field
        v-model="globalSearch"
        placeholder="Rechercher…"
        prepend-inner-icon="mdi-magnify"
        variant="solo-filled"
        density="compact"
        hide-details
        flat
        rounded="lg"
        class="appbar-search mr-3"
        :class="{ 'search-dark': isDark }"
        style="max-width:220px"
        @keyup.enter="onSearch"
      />

      <!-- Notifications -->
      
      <!-- Toggle thème -->
      <v-tooltip :text="isDark ? 'Mode clair' : 'Mode sombre'" location="bottom">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" icon class="mr-1" @click="toggleTheme">
            <v-icon :color="isDark ? '#A9B1D6' : '#475569'">
              {{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}
            </v-icon>
          </v-btn>
        </template>
      </v-tooltip>

      <!-- Menu admin -->
      <v-menu min-width="210" :theme="currentTheme">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" rounded="lg" class="mr-2 pa-1 user-btn">
            <v-avatar :color="adminAvatarColor" size="32">
              <span class="text-caption font-weight-bold text-white">{{ adminInitials }}</span>
            </v-avatar>
            <span v-if="!mobile" class="ml-2 user-btn-name" :class="{ 'name-dark': isDark }">
              {{ adminFirstName }}
            </span>
            <v-icon size="16" class="ml-1" :color="isDark ? '#565F89' : '#CBD5E1'">mdi-chevron-down</v-icon>
          </v-btn>
        </template>

        <v-list density="compact" rounded="xl" elevation="8" class="user-menu" :class="{ 'menu-dark': isDark }">
          <!-- Header user -->
          <div class="user-menu-header">
            <v-avatar :color="adminAvatarColor" size="42">
              <span class="text-body-2 font-weight-bold text-white">{{ adminInitials }}</span>
            </v-avatar>
            <div class="user-menu-info">
              <div class="user-menu-name">{{ adminFullName }}</div>
              <div class="user-menu-role">{{ roleLabel }}</div>
            </div>
          </div>
          <v-divider class="mb-1" />
          <v-list-item prepend-icon="mdi-account-circle" title="Mon profil"    to="/admin/profile"   rounded="lg" />
          <v-divider class="my-1" />
          <v-list-item prepend-icon="mdi-logout" title="Se déconnecter"
                       base-color="error" rounded="lg" @click="logout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- ═══════════════════════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════════════════════════ -->
    <v-main class="admin-main" :class="{ 'main-dark': isDark }">
      <div class="page-wrapper">
        <router-view />
      </div>
    </v-main>

  </v-app>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useDisplay, useTheme } from 'vuetify'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const router  = useRouter()
const route   = useRoute()
const auth    = useAuthStore()
const vuetifyTheme = useTheme()
const { mobile } = useDisplay()

/* ── État ── */
const drawer       = ref(true)
const rail         = ref(false)
const globalSearch = ref('')
const notifCount   = ref(0)
const openGroups   = ref([])

/* ── Thème ── */
const currentTheme = ref(localStorage.getItem('admin_theme') ?? 'light')
const isDark       = computed(() => currentTheme.value === 'dark')

function toggleTheme() {
  currentTheme.value = isDark.value ? 'light' : 'dark'
  localStorage.setItem('admin_theme', currentTheme.value)
  /* Synchronise Vuetify globalement */
  vuetifyTheme.global.name.value = currentTheme.value
}

/* ── Sidebar ── */
function toggleSidebar() {
  if (mobile.value) {
    drawer.value = !drawer.value
  } else {
    rail.value = !rail.value
  }
}

/* ── User ── */
const user = computed(() => auth.user ?? {})
const adminFullName = computed(() => {
  const a = user.value?.admin
  if (a) {
    const parts = [a.first_name ?? a.prenom ?? '', a.last_name ?? a.nom ?? ''].filter(Boolean)
    if (parts.length) return parts.join(' ')
  }
  return user.value?.email ?? 'Administrateur'
})
const adminFirstName = computed(() => adminFullName.value.split(' ')[0])
const adminInitials  = computed(() => {
  const n = adminFullName.value
  const p = n.trim().split(' ').filter(Boolean)
  return p.length >= 2 ? (p[0][0] + p[p.length-1][0]).toUpperCase() : n.substring(0,2).toUpperCase()
})
const adminAvatarColor = computed(() => {
  const colors = ['indigo-darken-1','blue-darken-2','teal-darken-1','purple-darken-1','deep-orange-darken-1']
  const n = adminFullName.value
  return n ? colors[n.charCodeAt(0) % colors.length] : 'indigo-darken-1'
})
const roleLabel = computed(() => {
  const r = user.value?.role
  return r === 'super_admin' ? 'Super Admin' : r === 'admin' ? 'Administrateur' : 'Staff'
})

/* ── Navigation ── */
const navItems = [
  { to: '/admin/dashboard',    label: 'Tableau de bord', icon: 'mdi-view-dashboard' },
  { to: '/admin/reclamations', label: 'Réclamations',    icon: 'mdi-file-document-multiple' },
  { to: '/admin/students',     label: 'Étudiants',       icon: 'mdi-account-group' },
  { to: '/admin/semestres',    label: 'Semestres',       icon: 'mdi-calendar-multiple' },
  { to: '/admin/profile',      label: 'Mon profil',      icon: 'mdi-account-circle' },
]

const currentPageTitle = computed(() => {
  const found = navItems.find(i => {
    if (i.children) return i.children.some(c => route.path.startsWith(c.to))
    return route.path.startsWith(i.to)
  })
  if (found?.children) {
    const child = found.children.find(c => route.path.startsWith(c.to))
    return child?.label ?? found.label
  }
  return found?.label ?? 'Dashboard'
})

function isActive(to) {
  return to ? (route.path === to || route.path.startsWith(to + '/')) : false
}
function isGroupActive(item) {
  return item.children?.some(c => route.path.startsWith(c.to)) ?? false
}
function toggleGroup(label) {
  const idx = openGroups.value.indexOf(label)
  if (idx === -1) openGroups.value.push(label)
  else openGroups.value.splice(idx, 1)
}

/* ── Recherche ── */
function onSearch() {
  if (!globalSearch.value.trim()) return
  router.push({ path: '/admin/reclamations', query: { search: globalSearch.value.trim() } })
  globalSearch.value = ''
}

/* ── Notifications ── */
async function fetchNotifCount() {
  try {
    const res = await api.get('/admin/notifications', { params: { per_page: 1, read: 0 } })
    const data = res.data?.data ?? res.data ?? {}
    notifCount.value = Array.isArray(data) ? data.length : (data.total ?? 0)
  } catch {}
}

/* ── Logout ── */
async function logout() {
  await auth.logout()     // ✅ appelle la méthode du store qui nettoie token + user
  router.push('/login')
}


/* ── Lifecycle ── */
onMounted(() => {
  /* Applique le thème sauvegardé au démarrage */
  vuetifyTheme.global.name.value = currentTheme.value
  fetchNotifCount()
  setInterval(fetchNotifCount, 60_000)

  /* Ferme le drawer sur mobile au démarrage */
  if (mobile.value) drawer.value = false
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
.admin-drawer {
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

.logo-icon-wrap:hover { background: rgba(255,255,255,.25); }
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

/* Link item */
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
}
.nav-item-link:hover {
  background: rgba(255,255,255,.1);
  color: #fff;
}
.nav-item-link.link-active {
  background: rgba(255,255,255,.16);
  color: #fff;
}

/* Group header */
.nav-group-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 8px;
  height: 44px;
  border-radius: 10px;
  margin-bottom: 3px;
  cursor: pointer;
  transition: background .15s;
  color: rgba(255,255,255,.7);
}
.nav-group-header:hover,
.nav-group-header.group-active { background: rgba(255,255,255,.1); color: #fff; }

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
.nav-item-link:hover .nav-icon-wrap,
.nav-group-header:hover .nav-icon-wrap { background: rgba(255,255,255,.12); color: #fff; }
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

/* Arrow groupe */
.group-arrow { color: rgba(255,255,255,.45); transition: transform .2s; }
.arrow-open  { transform: rotate(-180deg); }

/* Children */
.nav-children { padding: 2px 8px 4px 48px; display: flex; flex-direction: column; gap: 2px; }
.nav-child-item {
  display: flex;
  align-items: center;
  height: 36px;
  padding: 0 10px;
  border-radius: 8px;
  font-size: .825rem;
  color: rgba(255,255,255,.6);
  text-decoration: none;
  transition: background .12s, color .12s;
}
.nav-child-item:hover { background: rgba(255,255,255,.08); color: #fff; }
.nav-child-item.child-active { background: rgba(255,255,255,.14); color: #fff; font-weight: 600; }

/* Active indicator bar */
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
.nav-item-link { position: relative; }

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
.avatar-admin { border: 2px solid rgba(255,255,255,.25); }

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
.admin-appbar {
  border-bottom: 1px solid #E2E8F0 !important;
}
.appbar-dark {
  border-bottom-color: #2D3748 !important;
}

.appbar-breadcrumb { display: flex; align-items: center; }
.appbar-section    { font-size: .8rem; color: #94A3B8; font-weight: 500; }
.appbar-page       { font-size: .875rem; font-weight: 700; color: #0F172A; }
.page-dark         { color: #E2E8F0 !important; }

/* Recherche */
.appbar-search :deep(.v-field) {
  background: #F1F5F9 !important;
  font-size: .85rem;
}
.search-dark :deep(.v-field) {
  background: #2D3748 !important;
}
.search-dark :deep(.v-field__input)         { color: #E2E8F0 !important; }
.search-dark :deep(.v-field__prepend-inner) { color: #718096 !important; }
.search-dark :deep(input::placeholder)      { color: #718096 !important; }

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
.admin-main {
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
   DARK MODE — overrides globaux sur les enfants
════════════════════════════════════════════════════════════════ */

/* Cards en mode sombre */
:global(.v-theme--dark .v-card)    { background: #1A1A2E !important; border-color: #2D3748 !important; }
:global(.v-theme--dark .v-card-title),
:global(.v-theme--dark .v-list-item-title) { color: #E2E8F0 !important; }
:global(.v-theme--dark .text-medium-emphasis) { color: #94A3B8 !important; }

/* Inputs en mode sombre */
:global(.v-theme--dark .v-field) { background: #2D3748 !important; }
:global(.v-theme--dark .v-field__input) { color: #E2E8F0 !important; }
:global(.v-theme--dark .v-label)        { color: #94A3B8 !important; }

/* Table / dividers */
:global(.v-theme--dark .v-divider)      { border-color: #2D3748 !important; }
:global(.v-theme--dark .rec-head)       { background: #1E1E2E !important; }
:global(.v-theme--dark .rec-row:hover)  { background: #1E1E2E !important; }
:global(.v-theme--dark .kpi-card)       { background: #1A1A2E !important; }
:global(.v-theme--dark .sem-card)       { background: #1A1A2E !important; border-color: #2D3748 !important; }
:global(.v-theme--dark .filter-tab)     { background: #1A1A2E !important; border-color: #2D3748 !important; color: #94A3B8 !important; }
:global(.v-theme--dark .filter-tab:hover){ background: #2D3748 !important; }
:global(.v-theme--dark .pagination-bar) { background: #1E1E2E !important; border-color: #2D3748 !important; }
:global(.v-theme--dark .search-bar .v-field) { background: #2D3748 !important; }
:global(.v-theme--dark .page-title)     { color: #E2E8F0 !important; }
:global(.v-theme--dark .page-sub)       { color: #94A3B8 !important; }
:global(.v-theme--dark .welcome-banner) { background: linear-gradient(135deg, #0D3B26 0%, #134D33 100%) !important; }
:global(.v-theme--dark .detail-section) { border-bottom-color: #2D3748 !important; }
:global(.v-theme--dark .info-key)       { color: #718096 !important; }
:global(.v-theme--dark .info-val)       { color: #E2E8F0 !important; }
:global(.v-theme--dark .comment-box)    { background: #2D3748 !important; }
:global(.v-theme--dark .history-item)   { }
:global(.v-theme--dark .student-name)   { color: #E2E8F0 !important; }
:global(.v-theme--dark .card-label)     { color: #E2E8F0 !important; }
:global(.v-theme--dark .card-year)      { color: #718096 !important; }
:global(.v-theme--dark .days-badge)     { background: #3D3000 !important; color: #FCD34D !important; }
:global(.v-theme--dark .empty-state-table),
:global(.v-theme--dark .empty-state)    { }
:global(.v-theme--dark .form-dialog-head){ color: #A9B1D6 !important; }
</style>
