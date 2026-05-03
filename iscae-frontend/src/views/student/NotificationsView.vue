<template>
  <div class="notif-page">

    <!-- HEADER -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <v-icon color="primary" class="mr-2">mdi-bell-outline</v-icon>
          Notifications
        </h1>
        <p class="page-subtitle">
          {{ meta.unread_count > 0
              ? `${meta.unread_count} non lue${meta.unread_count > 1 ? 's' : ''}`
              : 'Toutes les notifications sont lues' }}
        </p>
      </div>
      <v-btn
        v-if="meta.unread_count > 0"
        variant="tonal"
        color="primary"
        size="small"
        :loading="markingAll"
        prepend-icon="mdi-check-all"
        @click="markAllRead"
      >
        Tout marquer lu
      </v-btn>
    </div>

    <!-- FILTRES -->
    <div class="filter-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="filter-tab"
        :class="{ 'filter-tab--active': activeFilter === tab.key }"
        @click="setFilter(tab.key)"
      >
        <v-icon size="16" class="mr-1">{{ tab.icon }}</v-icon>
        {{ tab.label }}
        <span class="tab-count" :class="{ 'tab-count--active': activeFilter === tab.key }">
          {{ tab.key === 'all'
              ? meta.total
              : tab.key === 'unread'
                ? meta.unread_count
                : (meta.total - meta.unread_count) }}
        </span>
      </button>
    </div>

    <!-- DEBUG PANEL (visible uniquement si erreur ou vide) -->
    <v-alert
      v-if="debugInfo"
      type="info"
      variant="tonal"
      density="compact"
      class="mb-4"
      closable
      @click:close="debugInfo = ''"
    >
      <pre style="font-size:0.75rem; white-space:pre-wrap;">{{ debugInfo }}</pre>
    </v-alert>

    <!-- LOADING -->
    <div v-if="loading" class="loading-state">
      <v-progress-circular indeterminate color="primary" size="48" />
      <p>Chargement des notifications…</p>
    </div>

    <!-- EMPTY STATE -->
    <div v-else-if="filteredList.length === 0" class="empty-state">
      <v-icon size="72" color="grey-lighten-2">mdi-bell-off-outline</v-icon>
      <p class="empty-title">Aucune notification</p>
      <p class="empty-subtitle">
        {{ activeFilter === 'unread'
            ? "Vous n'avez aucune notification non lue."
            : "Vous n'avez pas encore reçu de notification." }}
      </p>
      <!-- Bouton refresh pour recharger -->
      <v-btn
        variant="outlined"
        color="primary"
        size="small"
        class="mt-3"
        prepend-icon="mdi-refresh"
        @click="load(1)"
      >
        Actualiser
      </v-btn>
    </div>

    <!-- LISTE -->
    <div v-else class="notif-list">

      <!-- Groupe Aujourd'hui -->
      <div v-if="groupToday.length" class="notif-group">
        <div class="group-label">
          <v-icon size="14" class="mr-1">mdi-calendar-today</v-icon>
          Aujourd'hui
        </div>
        <div
          v-for="n in groupToday"
          :key="n.id"
          @click="handleClick(n)"
        >
          <NotifItem :notif="n" @mark-read="markRead" @delete="deleteNotif" />
        </div>
      </div>

      <!-- Groupe Cette semaine -->
      <div v-if="groupWeek.length" class="notif-group">
        <div class="group-label">
          <v-icon size="14" class="mr-1">mdi-calendar-week</v-icon>
          Cette semaine
        </div>
        <div
          v-for="n in groupWeek"
          :key="n.id"
          @click="handleClick(n)"
        >
          <NotifItem :notif="n" @mark-read="markRead" @delete="deleteNotif" />
        </div>
      </div>

      <!-- Groupe Plus ancien -->
      <div v-if="groupOlder.length" class="notif-group">
        <div class="group-label">
          <v-icon size="14" class="mr-1">mdi-calendar-range</v-icon>
          Plus ancien
        </div>
        <div
          v-for="n in groupOlder"
          :key="n.id"
          @click="handleClick(n)"
        >
          <NotifItem :notif="n" @mark-read="markRead" @delete="deleteNotif" />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="pagination">
        <v-btn
          icon="mdi-chevron-left"
          variant="text"
          size="small"
          :disabled="meta.current_page <= 1"
          @click="loadPage(meta.current_page - 1)"
        />
        <span
          v-for="p in paginationPages"
          :key="p"
          class="page-btn"
          :class="{
            'page-btn--active':   p === meta.current_page,
            'page-btn--ellipsis': p === '…',
          }"
          @click="p !== '…' && loadPage(p)"
        >{{ p }}</span>
        <v-btn
          icon="mdi-chevron-right"
          variant="text"
          size="small"
          :disabled="meta.current_page >= meta.last_page"
          @click="loadPage(meta.current_page + 1)"
        />
      </div>
    </div>

    <!-- DIALOG DÉTAIL -->
    <v-dialog v-model="detailDialog" max-width="520" rounded="lg">
      <v-card v-if="selectedNotif" class="detail-card">
        <div
          class="detail-header"
          :style="{ background: notifColor(selectedNotif.type) }"
        >
          <v-icon color="white" size="26" class="mr-3">
            {{ notifIcon(selectedNotif.type) }}
          </v-icon>
          <div class="detail-header-text">
            <div class="detail-title">{{ selectedNotif.title }}</div>
            <div class="detail-date">{{ fDateTime(selectedNotif.created_at) }}</div>
          </div>
          <v-btn
            icon="mdi-close"
            variant="text"
            color="white"
            size="small"
            @click="detailDialog = false"
          />
        </div>
        <v-card-text class="detail-body">
          <p class="detail-message">{{ selectedNotif.body }}</p>
          <v-btn
            v-if="selectedNotif.reclamation_id"
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="mdi-eye"
            class="mt-3"
            @click="goToReclamation(selectedNotif.reclamation_id)"
          >
            Voir la réclamation
          </v-btn>
        </v-card-text>
        <v-card-actions class="detail-actions">
          <v-btn
            v-if="!selectedNotif.is_read"
            variant="text"
            color="primary"
            size="small"
            prepend-icon="mdi-check"
            @click="markRead(selectedNotif); detailDialog = false"
          >
            Marquer comme lue
          </v-btn>
          <v-spacer />
          <v-btn variant="text" size="small" @click="detailDialog = false">Fermer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- SNACKBAR -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      location="top right"
      :timeout="3000"
      rounded="lg"
    >
      <v-icon start>
        {{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
      </v-icon>
      {{ snack.text }}
      <template #actions>
        <v-btn icon="mdi-close" variant="text" size="small" @click="snack.show = false" />
      </template>
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, defineComponent, h } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router = useRouter()

// ── Composant interne NotifItem ───────────────────────────────────────
const NotifItem = defineComponent({
  name : 'NotifItem',
  props: { notif: { type: Object, required: true } },
  emits: ['mark-read', 'delete'],
  setup(props, { emit }) {
    const TYPE_MAP = {
      resolved      : { color: '#059669', icon: 'mdi-check-circle',        label: 'Résolu' },
      rejected      : { color: '#DC2626', icon: 'mdi-close-circle',        label: 'Rejeté' },
      status_changed: { color: '#2563EB', icon: 'mdi-bell-ring',           label: 'Mise à jour' },
      escalated     : { color: '#EA580C', icon: 'mdi-arrow-up-bold-circle',label: 'Escaladé' },
      received      : { color: '#0891B2', icon: 'mdi-inbox-arrow-down',    label: 'Reçu' },
      response      : { color: '#7C3AED', icon: 'mdi-message-reply',       label: 'Réponse' },
      info          : { color: '#64748B', icon: 'mdi-information',         label: 'Info' },
    }

    function getColor(type) { return TYPE_MAP[type]?.color ?? '#64748B' }
    function getIcon(type)  { return TYPE_MAP[type]?.icon  ?? 'mdi-bell' }
    function getLabel(type) { return TYPE_MAP[type]?.label ?? type }

    function timeAgo(raw) {
      if (! raw) return ''
      const diff = Date.now() - new Date(raw).getTime()
      const m = Math.floor(diff / 60_000)
      if (m < 1)  return "À l'instant"
      if (m < 60) return `Il y a ${m} min`
      const h = Math.floor(m / 60)
      if (h < 24) return `Il y a ${h}h`
      const d = Math.floor(h / 24)
      if (d < 7)  return `Il y a ${d}j`
      return new Date(raw).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
    }

    return () => {
      const n     = props.notif
      const color = getColor(n.type)
      const icon  = getIcon(n.type)
      const label = getLabel(n.type)

      return h('div', {
        class: ['notif-card', ! n.is_read ? 'notif-card--unread' : ''],
        style: 'position:relative',
      }, [
        // Point non-lu
        ! n.is_read ? h('div', { class: 'unread-dot' }) : null,

        // Icône
        h('div', {
          class: 'notif-icon-box',
          style: { background: color + '18', border: `1.5px solid ${color}35` },
        }, [
          h('v-icon', { color, size: 20 }, () => icon),
        ]),

        // Contenu
        h('div', { class: 'notif-content' }, [
          // Ligne titre + heure
          h('div', { class: 'notif-title-row' }, [
            h('span', {
              class: ['notif-title', ! n.is_read ? 'notif-title--bold' : ''],
            }, n.title || 'Notification'),
            h('span', { class: 'notif-time' }, timeAgo(n.created_at || n.sent_at)),
          ]),

          // Corps
          h('p', { class: 'notif-body' }, n.body || ''),

          // Actions
          h('div', { class: 'notif-actions-row' }, [
            h('v-chip', { size: 'x-small', color, variant: 'tonal' }, () => label),
            ! n.is_read
              ? h('v-btn', {
                  size: 'x-small', variant: 'text', color: 'primary',
                  onClick: (e) => { e.stopPropagation(); emit('mark-read', n) },
                }, () => [h('v-icon', { size: 14, class: 'mr-1' }, () => 'mdi-check'), 'Lu'])
              : null,
            h('v-btn', {
              size: 'x-small', variant: 'text', color: 'error',
              onClick: (e) => { e.stopPropagation(); emit('delete', n) },
            }, () => [h('v-icon', { size: 14 }, () => 'mdi-delete-outline')]),
          ]),
        ]),
      ])
    }
  },
})

// ── État ──────────────────────────────────────────────────────────────
const loading      = ref(true)
const markingAll   = ref(false)
const activeFilter = ref('all')
const detailDialog = ref(false)
const selectedNotif= ref(null)
const debugInfo    = ref('')
const list         = ref([])
const meta         = reactive({
  total        : 0,
  unread_count : 0,
  per_page     : 15,
  current_page : 1,
  last_page    : 1,
})
const snack = reactive({ show: false, text: '', color: 'success' })
let   refreshTimer = null

// ── Tabs ──────────────────────────────────────────────────────────────
const tabs = [
  { key: 'all',    label: 'Toutes',   icon: 'mdi-bell-outline' },
  { key: 'unread', label: 'Non lues', icon: 'mdi-bell-badge-outline' },
  { key: 'read',   label: 'Lues',     icon: 'mdi-bell-check-outline' },
]

// ── Filtrage local ────────────────────────────────────────────────────
const filteredList = computed(() => {
  if (activeFilter.value === 'unread') return list.value.filter(n => ! n.is_read)
  if (activeFilter.value === 'read')   return list.value.filter(n =>   n.is_read)
  return list.value
})

// ── Groupes temporels ─────────────────────────────────────────────────
const groupToday = computed(() => {
  const start = new Date()
  start.setHours(0, 0, 0, 0)
  return filteredList.value.filter(n => {
    const d = new Date(n.created_at || n.sent_at)
    return d >= start
  })
})
const groupWeek = computed(() => {
  const endToday  = new Date(); endToday.setHours(0, 0, 0, 0)
  const startWeek = new Date(endToday)
  startWeek.setDate(startWeek.getDate() - 7)
  return filteredList.value.filter(n => {
    const d = new Date(n.created_at || n.sent_at)
    return d >= startWeek && d < endToday
  })
})
const groupOlder = computed(() => {
  const limit = new Date()
  limit.setHours(0, 0, 0, 0)
  limit.setDate(limit.getDate() - 7)
  return filteredList.value.filter(n => {
    const d = new Date(n.created_at || n.sent_at)
    return d < limit
  })
})

// ── Pagination ────────────────────────────────────────────────────────
const paginationPages = computed(() => {
  const total   = meta.last_page
  const current = meta.current_page
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const set    = new Set([1, total, current - 1, current, current + 1].filter(p => p >= 1 && p <= total))
  const sorted = [...set].sort((a, b) => a - b)
  const result = []; let prev = 0
  for (const p of sorted) {
    if (p - prev > 1) result.push('…')
    result.push(p); prev = p
  }
  return result
})

// ── Helpers couleur / icône ───────────────────────────────────────────
function notifColor(type) {
  const MAP = {
    resolved      : '#059669',
    rejected      : '#DC2626',
    status_changed: '#2563EB',
    escalated     : '#EA580C',
    received      : '#0891B2',
    response      : '#7C3AED',
    info          : '#64748B',
  }
  return MAP[type] ?? '#64748B'
}
function notifIcon(type) {
  const MAP = {
    resolved      : 'mdi-check-circle',
    rejected      : 'mdi-close-circle',
    status_changed: 'mdi-bell-ring',
    escalated     : 'mdi-arrow-up-bold-circle',
    received      : 'mdi-inbox-arrow-down',
    response      : 'mdi-message-reply',
    info          : 'mdi-information',
  }
  return MAP[type] ?? 'mdi-bell'
}
function fDateTime(raw) {
  if (! raw) return ''
  return new Date(raw).toLocaleString('fr-FR', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
function notify(text, color = 'success') {
  snack.text  = text
  snack.color = color
  snack.show  = true
}

// ── Charger les notifications ─────────────────────────────────────────
async function load(page = 1) {
  loading.value = true
  debugInfo.value = ''

  try {
    const params = { page, per_page: 15 }
    if (activeFilter.value === 'unread') params.read = '0'
    if (activeFilter.value === 'read')   params.read = '1'

    console.log('[Notifications] GET /student/notifications params:', params)

    const res  = await api.get('/student/notifications', { params })

    console.log('[Notifications] response status :', res.status)
    console.log('[Notifications] response data   :', JSON.stringify(res.data))

    const body = res.data

    // ── Extraire les données (supporter plusieurs formats) ────────────
    // Format 1 : { success, data: [...], meta: {...} }
    // Format 2 : { data: [...], meta: {...} }
    // Format 3 : [...]
    let items = []
    let metaData = {}

    if (Array.isArray(body)) {
      // Format tableau direct
      items    = body
      metaData = {}
    } else if (Array.isArray(body?.data)) {
      items    = body.data
      metaData = body.meta ?? {}
    } else if (body?.data && typeof body.data === 'object') {
      // Parfois Laravel paginator : { data: [...], current_page, total, ... }
      items    = body.data?.data ?? body.data ?? []
      metaData = {
        total        : body.data?.total        ?? body.total        ?? 0,
        current_page : body.data?.current_page ?? body.current_page ?? 1,
        last_page    : body.data?.last_page    ?? body.last_page    ?? 1,
        per_page     : body.data?.per_page     ?? body.per_page     ?? 15,
        unread_count : body.meta?.unread_count ?? body.unread_count ?? 0,
      }
    }

    console.log('[Notifications] items extraits :', items.length)
    console.log('[Notifications] premier item   :', items[0] ?? 'aucun')

    list.value = items

    meta.total         = metaData.total         ?? items.length
    meta.unread_count  = metaData.unread_count  ?? items.filter(n => ! n.is_read).length
    meta.per_page      = metaData.per_page      ?? 15
    meta.current_page  = metaData.current_page  ?? page
    meta.last_page     = metaData.last_page     ?? 1

    // Afficher debug si vide
    if (items.length === 0) {
      debugInfo.value = `API réponse reçue mais vide.\nURL: /student/notifications\nParams: ${JSON.stringify(params)}\nRaw: ${JSON.stringify(body).substring(0, 300)}`
    }

  } catch (err) {
    console.error('[Notifications] ERREUR:', err)
    console.error('[Notifications] response:', err.response?.data)

    const msg = err.response?.data?.message ?? err.message ?? 'Erreur inconnue'
    debugInfo.value = `Erreur API: ${msg}\nStatus: ${err.response?.status}\nURL: /student/notifications`
    notify('Impossible de charger les notifications.', 'error')
  } finally {
    loading.value = false
  }
}

async function loadPage(page) {
  await load(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ── Marquer comme lu ──────────────────────────────────────────────────
async function markRead(notif) {
  try {
    await api.put(`/student/notifications/${notif.id}/read`)
    const found = list.value.find(n => n.id === notif.id)
    if (found) {
      found.is_read = true
      found.read_at = new Date().toISOString()
      meta.unread_count = Math.max(0, meta.unread_count - 1)
    }
  } catch (err) {
    console.error('[Notifications] markRead error:', err)
    notify('Erreur lors de la mise à jour.', 'error')
  }
}

// ── Tout marquer lu ───────────────────────────────────────────────────
async function markAllRead() {
  markingAll.value = true
  try {
    const res = await api.put('/student/notifications/read-all')
    notify(res.data?.message ?? 'Toutes les notifications sont lues.', 'success')
    list.value.forEach(n => { n.is_read = true; n.read_at = new Date().toISOString() })
    meta.unread_count = 0
  } catch (err) {
    console.error('[Notifications] markAllRead error:', err)
    notify('Erreur lors de la mise à jour.', 'error')
  } finally {
    markingAll.value = false
  }
}

// ── Supprimer ─────────────────────────────────────────────────────────
async function deleteNotif(notif) {
  try {
    await api.delete(`/student/notifications/${notif.id}`)
    list.value        = list.value.filter(n => n.id !== notif.id)
    meta.total        = Math.max(0, meta.total - 1)
    if (! notif.is_read) meta.unread_count = Math.max(0, meta.unread_count - 1)
    notify('Notification supprimée.', 'success')
  } catch (err) {
    console.error('[Notifications] delete error:', err)
    notify('Erreur lors de la suppression.', 'error')
  }
}

// ── Navigation ────────────────────────────────────────────────────────
function goToReclamation(id) {
  detailDialog.value = false
  router.push({ name: 'student.reclamation.detail', params: { id: String(id) } })
}
function handleClick(notif) {
  selectedNotif.value = notif
  detailDialog.value  = true
  if (! notif.is_read) markRead(notif)
}
function setFilter(key) {
  activeFilter.value = key
  load(1)
}

// ── Lifecycle ─────────────────────────────────────────────────────────
onMounted(() => {
  load(1)
  refreshTimer = setInterval(() => load(meta.current_page), 60_000)
})
onUnmounted(() => { if (refreshTimer) clearInterval(refreshTimer) })
</script>

<style scoped>
.notif-page { padding: 24px; max-width: 860px; margin: 0 auto; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.page-title  { font-size: 1.5rem; font-weight: 700; color: #1E293B; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.85rem; color: #64748B; margin: 4px 0 0; }

/* Filtres */
.filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-tab  { display: flex; align-items: center; gap: 4px; padding: 8px 16px; border-radius: 24px; border: 1.5px solid #E2E8F0; background: #fff; cursor: pointer; font-size: 0.85rem; font-weight: 500; color: #64748B; transition: all 0.2s; }
.filter-tab:hover { border-color: #94A3B8; color: #334155; }
.filter-tab--active { background: #1E3A5F; border-color: #1E3A5F; color: #fff; }
.tab-count  { background: #F1F5F9; color: #64748B; border-radius: 12px; padding: 1px 7px; font-size: 0.75rem; font-weight: 700; }
.tab-count--active { background: rgba(255,255,255,0.25); color: #fff; }

/* Loading / Empty */
.loading-state, .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 260px; gap: 12px; color: #94A3B8; text-align: center; }
.empty-title    { font-size: 1.1rem; font-weight: 600; color: #64748B; margin: 0; }
.empty-subtitle { font-size: 0.85rem; color: #94A3B8; margin: 0; }

/* Groupes */
.notif-group { margin-bottom: 20px; }
.group-label { display: flex; align-items: center; font-size: 0.75rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; padding: 0 4px; }

/* Carte notification */
:deep(.notif-card) { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border-radius: 12px; border: 1.5px solid #F1F5F9; background: #fff; cursor: pointer; transition: all 0.2s; margin-bottom: 8px; }
:deep(.notif-card:hover) { border-color: #CBD5E1; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
:deep(.notif-card--unread) { background: #F8FAFF; border-color: #DBEAFE; }
:deep(.unread-dot) { position: absolute; top: 14px; right: 14px; width: 8px; height: 8px; border-radius: 50%; background: #2563EB; }
:deep(.notif-icon-box) { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
:deep(.notif-content) { flex: 1; min-width: 0; }
:deep(.notif-title-row) { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 3px; }
:deep(.notif-title) { font-size: 0.9rem; color: #1E293B; }
:deep(.notif-title--bold) { font-weight: 700; }
:deep(.notif-time)  { font-size: 0.75rem; color: #94A3B8; white-space: nowrap; flex-shrink: 0; }
:deep(.notif-body)  { font-size: 0.83rem; color: #64748B; margin: 0 0 6px; line-height: 1.5; }
:deep(.notif-actions-row) { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 4px; margin-top: 20px; flex-wrap: wrap; }
.page-btn   { min-width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 500; cursor: pointer; border: 1.5px solid #E2E8F0; color: #334155; transition: all 0.15s; padding: 0 8px; }
.page-btn:hover:not(.page-btn--ellipsis) { border-color: #94A3B8; }
.page-btn--active   { background: #1E3A5F; color: #fff; border-color: #1E3A5F; }
.page-btn--ellipsis { border: none; cursor: default; color: #94A3B8; }

/* Dialog */
.detail-card    { border-radius: 16px !important; overflow: hidden; }
.detail-header  { display: flex; align-items: center; padding: 20px; gap: 12px; }
.detail-header-text { flex: 1; }
.detail-title   { font-size: 1rem; font-weight: 700; color: #fff; }
.detail-date    { font-size: 0.78rem; color: rgba(255,255,255,0.75); margin-top: 2px; }
.detail-body    { padding: 20px 24px !important; }
.detail-message { font-size: 0.95rem; color: #334155; line-height: 1.7; margin: 0; }
.detail-actions { padding: 12px 20px 16px !important; }

@media (max-width: 600px) {
  .notif-page  { padding: 12px; }
  .page-header { flex-direction: column; }
  .filter-tab  { padding: 6px 12px; font-size: 0.8rem; }
}
</style>
