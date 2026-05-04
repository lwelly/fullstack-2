<template>
  <div class="notif-page">

    <!-- ══════════════════════════════════════════════════════════════
         EN-TÊTE
    ══════════════════════════════════════════════════════════════════ -->
    <div class="page-header mb-6">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3">
        <div>
          <h1 class="page-title">
            <span class="title-icon-wrapper">
              <v-icon size="22" color="white">mdi-bell</v-icon>
            </span>
            Notifications
          </h1>
          <p class="page-subtitle">
            {{ meta.unread_count > 0
                ? `${meta.unread_count} notification${meta.unread_count > 1 ? 's' : ''} non lue${meta.unread_count > 1 ? 's' : ''}`
                : 'Toutes les notifications sont lues ✓' }}
          </p>
        </div>
        <v-btn
          v-if="meta.unread_count > 0"
          variant="tonal"
          color="primary"
          size="small"
          :loading="markingAll"
          prepend-icon="mdi-check-all"
          rounded="lg"
          @click="markAllRead"
        >
          Tout marquer lu
        </v-btn>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         FILTRES
    ══════════════════════════════════════════════════════════════════ -->
    <div class="filter-tabs mb-5">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="filter-tab"
        :class="{ 'filter-tab--active': activeFilter === tab.key }"
        @click="setFilter(tab.key)"
      >
        <v-icon size="15" class="mr-1">{{ tab.icon }}</v-icon>
        {{ tab.label }}
        <span class="tab-badge">
          {{ tab.key === 'all'
              ? meta.total
              : tab.key === 'unread'
                ? meta.unread_count
                : (meta.total - meta.unread_count) }}
        </span>
      </button>
    </div>

    <!-- Debug -->
    <v-alert
      v-if="debugInfo"
      type="info"
      variant="tonal"
      density="compact"
      class="mb-4"
      closable
      rounded="lg"
      @click:close="debugInfo = ''"
    >
      <pre style="font-size:0.72rem;white-space:pre-wrap;">{{ debugInfo }}</pre>
    </v-alert>

    <!-- ══════════════════════════════════════════════════════════════
         CHARGEMENT
    ══════════════════════════════════════════════════════════════════ -->
    <div v-if="loading" class="state-box">
      <v-progress-circular indeterminate color="primary" size="44" width="3" />
      <p class="state-text mt-3">Chargement des notifications…</p>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         VIDE
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else-if="filteredList.length === 0" class="state-box">
      <div class="empty-icon">
        <v-icon size="40" color="#94A3B8">mdi-bell-off-outline</v-icon>
      </div>
      <p class="empty-title mt-3">Aucune notification</p>
      <p class="state-text">
        {{ activeFilter === 'unread'
            ? "Aucune notification non lue."
            : "Vous n'avez pas encore reçu de notification." }}
      </p>
      <v-btn
        variant="tonal"
        color="primary"
        size="small"
        class="mt-3"
        prepend-icon="mdi-refresh"
        rounded="lg"
        @click="load(1)"
      >
        Actualiser
      </v-btn>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         LISTE
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else class="notif-list">

      <!-- Aujourd'hui -->
      <template v-if="groupToday.length">
        <div class="group-label">
          <v-icon size="13" class="mr-1">mdi-calendar-today</v-icon>
          Aujourd'hui
        </div>
        <div
          v-for="n in groupToday" :key="n.id"
          class="notif-card"
          :class="{ 'notif-card--unread': !n.is_read }"
          @click="handleClick(n)"
        >
          <NotifRow :notif="n" @mark-read="markRead" @delete="deleteNotif" />
        </div>
      </template>

      <!-- Cette semaine -->
      <template v-if="groupWeek.length">
        <div class="group-label mt-4">
          <v-icon size="13" class="mr-1">mdi-calendar-week</v-icon>
          Cette semaine
        </div>
        <div
          v-for="n in groupWeek" :key="n.id"
          class="notif-card"
          :class="{ 'notif-card--unread': !n.is_read }"
          @click="handleClick(n)"
        >
          <NotifRow :notif="n" @mark-read="markRead" @delete="deleteNotif" />
        </div>
      </template>

      <!-- Plus ancien -->
      <template v-if="groupOlder.length">
        <div class="group-label mt-4">
          <v-icon size="13" class="mr-1">mdi-calendar-range</v-icon>
          Plus ancien
        </div>
        <div
          v-for="n in groupOlder" :key="n.id"
          class="notif-card"
          :class="{ 'notif-card--unread': !n.is_read }"
          @click="handleClick(n)"
        >
          <NotifRow :notif="n" @mark-read="markRead" @delete="deleteNotif" />
        </div>
      </template>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="pagination mt-5">
        <button
          class="page-arrow"
          :disabled="meta.current_page <= 1"
          @click="loadPage(meta.current_page - 1)"
        >
          <v-icon size="18">mdi-chevron-left</v-icon>
        </button>
        <button
          v-for="p in paginationPages"
          :key="p"
          class="page-btn"
          :class="{
            'page-btn--active':    p === meta.current_page,
            'page-btn--ellipsis':  p === '…',
          }"
          :disabled="p === '…'"
          @click="p !== '…' && loadPage(p)"
        >{{ p }}</button>
        <button
          class="page-arrow"
          :disabled="meta.current_page >= meta.last_page"
          @click="loadPage(meta.current_page + 1)"
        >
          <v-icon size="18">mdi-chevron-right</v-icon>
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         DIALOG DÉTAIL
    ══════════════════════════════════════════════════════════════════ -->
    <v-dialog v-model="detailDialog" max-width="540" rounded="xl">
      <v-card v-if="selectedNotif" class="detail-card" rounded="xl">

        <!-- Bandeau coloré -->
        <div
          class="detail-banner"
          :style="{ background: `linear-gradient(135deg, ${typeColor(selectedNotif.type)}, ${typeColor(selectedNotif.type)}cc)` }"
        >
          <div class="detail-banner-icon">
            <v-icon color="white" size="28">{{ typeIcon(selectedNotif.type) }}</v-icon>
          </div>
          <div class="detail-banner-text">
            <div class="detail-type-label">{{ typeLabel(selectedNotif.type) }}</div>
            <div class="detail-title">{{ selectedNotif.title }}</div>
            <div class="detail-date">
              <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
              {{ fDateTime(selectedNotif.created_at) }}
            </div>
          </div>
          <v-btn
            icon="mdi-close"
            variant="text"
            color="white"
            size="small"
            class="detail-close"
            @click="detailDialog = false"
          />
        </div>

        <!-- Corps -->
        <v-card-text class="detail-body pa-5">

          <!-- Message -->
          <div class="detail-message-box">
            <v-icon size="16" color="#94A3B8" class="mr-2">mdi-message-text-outline</v-icon>
            <p class="detail-message">{{ selectedNotif.body }}</p>
          </div>

          <!-- Données supplémentaires -->
          <div
            v-if="selectedNotif.data && Object.keys(selectedNotif.data).length > 0"
            class="detail-meta mt-4"
          >
            <p class="detail-meta-title">
              <v-icon size="14" class="mr-1">mdi-information-outline</v-icon>
              Détails
            </p>
            <div class="detail-meta-grid">
              <template v-for="(val, key) in selectedNotif.data" :key="key">
                <div v-if="val && key !== 'reclamation_id'" class="meta-row">
                  <span class="meta-key">{{ dataKeyLabel(key) }}</span>
                  <v-chip
                    v-if="key.includes('status')"
                    :color="statusColor(val)"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ statusLabel(val) }}
                  </v-chip>
                  <span v-else class="meta-val">{{ val }}</span>
                </div>
              </template>
            </div>
          </div>

          <!-- Statut lecture -->
          <div class="detail-read-status mt-4">
            <v-icon
              size="15"
              :color="selectedNotif.is_read ? 'success' : 'warning'"
              class="mr-1"
            >
              {{ selectedNotif.is_read ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
            <span class="text-caption text-medium-emphasis">
              {{ selectedNotif.is_read
                  ? `Lu le ${fDateTime(selectedNotif.read_at)}`
                  : 'Non lu' }}
            </span>
          </div>
        </v-card-text>

        <!-- Actions -->
        <v-divider />
        <v-card-actions class="pa-4 gap-2">
          <v-btn
            v-if="selectedNotif.data?.reclamation_id"
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="mdi-file-document-outline"
            rounded="lg"
            @click="goToReclamation(selectedNotif.data.reclamation_id)"
          >
            Voir la réclamation
          </v-btn>
          <v-spacer />
          <v-btn
            v-if="!selectedNotif.is_read"
            color="success"
            variant="tonal"
            size="small"
            prepend-icon="mdi-check"
            rounded="lg"
            @click="markRead(selectedNotif); detailDialog = false"
          >
            Marquer lue
          </v-btn>
          <v-btn
            color="error"
            variant="text"
            size="small"
            prepend-icon="mdi-delete-outline"
            @click="deleteFromDialog(selectedNotif)"
          >
            Supprimer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ── Snackbar ────────────────────────────────────────────────── -->
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

// ════════════════════════════════════════════════════════════════════
// Composant interne NotifRow
// ════════════════════════════════════════════════════════════════════
const NotifRow = defineComponent({
  name: 'NotifRow',
  props: { notif: { type: Object, required: true } },
  emits: ['mark-read', 'delete'],
  setup(props, { emit }) {

    function typeColor(type) {
      const m = {
        'reclamation.submitted':        '#2563EB',
        'reclamation.status_changed':   '#D97706',
        'reclamation.meeting_scheduled':'#7C3AED',
        'admin.new_reclamation':        '#0891B2',
        'admin.reclamation_escalated':  '#DC2626',
        'notes.published':              '#16A34A',
        'document.ready':               '#0F2D5E',
        'security.account_locked':      '#DC2626',
      }
      // Fallback par mots-clés
      if (type?.includes('resolved'))  return '#16A34A'
      if (type?.includes('rejected'))  return '#DC2626'
      if (type?.includes('escalat'))   return '#EA580C'
      if (type?.includes('status'))    return '#D97706'
      if (type?.includes('submitted')) return '#2563EB'
      return m[type] ?? '#64748B'
    }

    function typeIcon(type) {
      if (type?.includes('resolved'))  return 'mdi-check-circle-outline'
      if (type?.includes('rejected'))  return 'mdi-close-circle-outline'
      if (type?.includes('escalat'))   return 'mdi-arrow-up-bold-circle-outline'
      if (type?.includes('status'))    return 'mdi-file-refresh-outline'
      if (type?.includes('submitted')) return 'mdi-file-plus-outline'
      if (type?.includes('meeting'))   return 'mdi-calendar-clock'
      if (type?.includes('notes'))     return 'mdi-school-outline'
      if (type?.includes('document'))  return 'mdi-file-download-outline'
      if (type?.includes('lock'))      return 'mdi-lock-alert-outline'
      return 'mdi-bell-outline'
    }

    function timeAgo(raw) {
      if (!raw) return ''
      const diff = Date.now() - new Date(raw).getTime()
      const m = Math.floor(diff / 60000)
      if (m < 1)  return "À l'instant"
      if (m < 60) return `Il y a ${m} min`
      const hh = Math.floor(m / 60)
      if (hh < 24) return `Il y a ${hh}h`
      const d = Math.floor(hh / 24)
      if (d < 7)  return `Il y a ${d}j`
      return new Date(raw).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
    }

    function truncate(s, len) {
      if (!s) return ''
      return s.length > len ? s.slice(0, len) + '…' : s
    }

    return () => {
      const n     = props.notif
      const color = typeColor(n.type)
      const icon  = typeIcon(n.type)

      return h('div', { class: 'notif-inner' }, [
        // Point non-lu
        h('div', { class: 'unread-indicator' }, [
          !n.is_read ? h('div', { class: 'unread-dot' }) : null
        ]),

        // Icône
        h('div', {
          class: 'notif-icon',
          style: { background: color + '15', border: `1.5px solid ${color}30` }
        }, [
          h('v-icon', { color, size: 19 }, () => icon)
        ]),

        // Contenu
        h('div', { class: 'notif-content' }, [
          h('div', { class: 'notif-row-top' }, [
            h('span', {
              class: 'notif-title',
              style: { fontWeight: n.is_read ? '500' : '700' }
            }, n.title || 'Notification'),
            h('span', { class: 'notif-time' }, timeAgo(n.created_at || n.sent_at))
          ]),
          h('p', { class: 'notif-body-text' }, truncate(n.body || '', 90)),
        ]),

        // Boutons
        h('div', { class: 'notif-btns' }, [
          !n.is_read ? h('button', {
            class: 'notif-btn notif-btn--check',
            title: 'Marquer comme lue',
            onClick: (e) => { e.stopPropagation(); emit('mark-read', n) }
          }, [ h('v-icon', { size: 15 }, () => 'mdi-check') ]) : null,

          h('button', {
            class: 'notif-btn notif-btn--delete',
            title: 'Supprimer',
            onClick: (e) => { e.stopPropagation(); emit('delete', n) }
          }, [ h('v-icon', { size: 15 }, () => 'mdi-delete-outline') ])
        ])
      ])
    }
  }
})

// ════════════════════════════════════════════════════════════════════
// État
// ════════════════════════════════════════════════════════════════════
const loading       = ref(true)
const markingAll    = ref(false)
const activeFilter  = ref('all')
const detailDialog  = ref(false)
const selectedNotif = ref(null)
const debugInfo     = ref('')
const list          = ref([])
const meta          = reactive({
  total: 0, unread_count: 0, per_page: 15, current_page: 1, last_page: 1
})
const snack = reactive({ show: false, text: '', color: 'success' })
let   refreshTimer = null

const tabs = [
  { key: 'all',    label: 'Toutes',   icon: 'mdi-bell-outline'       },
  { key: 'unread', label: 'Non lues', icon: 'mdi-bell-badge-outline'  },
  { key: 'read',   label: 'Lues',     icon: 'mdi-bell-check-outline'  },
]

// ── Filtrage ──────────────────────────────────────────────────────────
const filteredList = computed(() => {
  if (activeFilter.value === 'unread') return list.value.filter(n => !n.is_read)
  if (activeFilter.value === 'read')   return list.value.filter(n =>  n.is_read)
  return list.value
})

// ── Groupes ───────────────────────────────────────────────────────────
const groupToday = computed(() => {
  const s = new Date(); s.setHours(0,0,0,0)
  return filteredList.value.filter(n => new Date(n.created_at || n.sent_at) >= s)
})
const groupWeek = computed(() => {
  const end = new Date(); end.setHours(0,0,0,0)
  const start = new Date(end); start.setDate(start.getDate() - 7)
  return filteredList.value.filter(n => {
    const d = new Date(n.created_at || n.sent_at)
    return d >= start && d < end
  })
})
const groupOlder = computed(() => {
  const limit = new Date(); limit.setHours(0,0,0,0); limit.setDate(limit.getDate() - 7)
  return filteredList.value.filter(n => new Date(n.created_at || n.sent_at) < limit)
})

// ── Pagination ────────────────────────────────────────────────────────
const paginationPages = computed(() => {
  const total = meta.last_page, cur = meta.current_page
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const set = new Set([1, total, cur-1, cur, cur+1].filter(p => p >= 1 && p <= total))
  const sorted = [...set].sort((a,b) => a-b)
  const result = []; let prev = 0
  for (const p of sorted) { if (p - prev > 1) result.push('…'); result.push(p); prev = p }
  return result
})

// ── Helpers ───────────────────────────────────────────────────────────
function typeColor(type) {
  if (type?.includes('resolved'))  return '#16A34A'
  if (type?.includes('rejected'))  return '#DC2626'
  if (type?.includes('escalat'))   return '#EA580C'
  if (type?.includes('status'))    return '#D97706'
  if (type?.includes('submitted')) return '#2563EB'
  if (type?.includes('meeting'))   return '#7C3AED'
  if (type?.includes('notes'))     return '#16A34A'
  if (type?.includes('document'))  return '#0F2D5E'
  return '#64748B'
}
function typeIcon(type) {
  if (type?.includes('resolved'))  return 'mdi-check-circle'
  if (type?.includes('rejected'))  return 'mdi-close-circle'
  if (type?.includes('escalat'))   return 'mdi-arrow-up-bold-circle'
  if (type?.includes('status'))    return 'mdi-bell-ring'
  if (type?.includes('submitted')) return 'mdi-file-plus'
  if (type?.includes('meeting'))   return 'mdi-calendar-clock'
  if (type?.includes('notes'))     return 'mdi-school'
  if (type?.includes('document'))  return 'mdi-file-download'
  return 'mdi-bell'
}
function typeLabel(type) {
  if (type?.includes('resolved'))  return 'Réclamation résolue'
  if (type?.includes('rejected'))  return 'Réclamation rejetée'
  if (type?.includes('escalat'))   return 'Réclamation escaladée'
  if (type?.includes('status'))    return 'Mise à jour statut'
  if (type?.includes('submitted')) return 'Réclamation soumise'
  if (type?.includes('meeting'))   return 'Réunion programmée'
  if (type?.includes('notes'))     return 'Notes publiées'
  if (type?.includes('document'))  return 'Document disponible'
  if (type?.includes('admin'))     return 'Administration'
  return 'Notification'
}
function statusLabel(s) {
  const m = { submitted:'Soumise', received:'Reçue', in_review:'En cours',
              resolved:'Résolue', rejected:'Rejetée', escalated:'Escaladée', cancelled:'Annulée' }
  return m[s] ?? s
}
function statusColor(s) {
  const m = { submitted:'blue', received:'cyan', in_review:'orange',
              resolved:'success', rejected:'error', escalated:'deep-orange', cancelled:'grey' }
  return m[s] ?? 'grey'
}
function dataKeyLabel(key) {
  const m = { reference:'Référence', old_status:'Ancien statut', new_status:'Nouveau statut',
              module:'Module', semestre:'Semestre', meeting_date:'Date réunion', location:'Lieu', year:'Année' }
  return m[key] ?? key
}
function fDateTime(raw) {
  if (!raw) return ''
  return new Date(raw).toLocaleString('fr-FR', {
    day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'
  })
}
function notify(text, color = 'success') { snack.text = text; snack.color = color; snack.show = true }

// ── API ───────────────────────────────────────────────────────────────
async function load(page = 1) {
  loading.value = true; debugInfo.value = ''
  try {
    const params = { page, per_page: 15 }
    if (activeFilter.value === 'unread') params.read = '0'
    if (activeFilter.value === 'read')   params.read = '1'
    const res  = await api.get('/student/notifications', { params })
    const body = res.data
    let items = Array.isArray(body) ? body : (Array.isArray(body?.data) ? body.data : [])
    const m   = body?.meta ?? {}
    list.value        = items
    meta.total        = m.total        ?? items.length
    meta.unread_count = m.unread_count ?? items.filter(n => !n.is_read).length
    meta.per_page     = m.per_page     ?? 15
    meta.current_page = m.current_page ?? page
    meta.last_page    = m.last_page    ?? 1
    if (items.length === 0) {
      debugInfo.value = `API réponse reçue mais vide.\nURL: /student/notifications\nParams: ${JSON.stringify(params)}\nRaw: ${JSON.stringify(body).substring(0,300)}`
    }
  } catch (err) {
    debugInfo.value = `Erreur API: ${err.response?.data?.message ?? err.message}\nStatus: ${err.response?.status}`
    notify('Impossible de charger les notifications.', 'error')
  } finally {
    loading.value = false
  }
}

async function loadPage(p) { await load(p); window.scrollTo({ top: 0, behavior: 'smooth' }) }

async function markRead(notif) {
  try {
    await api.put(`/student/notifications/${notif.id}/read`)
    const f = list.value.find(n => n.id === notif.id)
    if (f) { f.is_read = true; f.read_at = new Date().toISOString() }
    if (selectedNotif.value?.id === notif.id) {
      selectedNotif.value.is_read = true
      selectedNotif.value.read_at = new Date().toISOString()
    }
    meta.unread_count = Math.max(0, meta.unread_count - 1)
  } catch { notify('Erreur lors de la mise à jour.', 'error') }
}

async function markAllRead() {
  markingAll.value = true
  try {
    await api.put('/student/notifications/read-all')
    list.value.forEach(n => { n.is_read = true; n.read_at = new Date().toISOString() })
    meta.unread_count = 0
    notify('Toutes les notifications sont lues.')
  } catch { notify('Erreur lors de la mise à jour.', 'error') }
  finally { markingAll.value = false }
}

async function deleteNotif(notif) {
  try {
    await api.delete(`/student/notifications/${notif.id}`)
    list.value = list.value.filter(n => n.id !== notif.id)
    meta.total = Math.max(0, meta.total - 1)
    if (!notif.is_read) meta.unread_count = Math.max(0, meta.unread_count - 1)
    notify('Notification supprimée.')
  } catch { notify('Erreur lors de la suppression.', 'error') }
}

async function deleteFromDialog(notif) {
  detailDialog.value = false
  await deleteNotif(notif)
}

function goToReclamation(id) {
  detailDialog.value = false
  router.push({ name: 'student.reclamation.detail', params: { id: String(id) } })
}

function handleClick(notif) {
  selectedNotif.value = { ...notif }
  detailDialog.value  = true
  if (!notif.is_read) markRead(notif)
}

function setFilter(key) { activeFilter.value = key; load(1) }

onMounted(() => { load(1); refreshTimer = setInterval(() => load(meta.current_page), 60000) })
onUnmounted(() => { if (refreshTimer) clearInterval(refreshTimer) })
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════════════
   PAGE
════════════════════════════════════════════════════════════════════ */
.notif-page { max-width: 860px; margin: 0 auto; }

/* ── En-tête ── */
.page-title {
  font-size: 1.45rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}
.title-icon-wrapper {
  width: 38px; height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0F2D5E, #2563EB);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.page-subtitle { font-size: 0.85rem; color: #6B7280; margin: 4px 0 0; }

:deep(.v-theme--dark) .page-title   { color: #F1F5F9; }
:deep(.v-theme--dark) .page-subtitle{ color: #94A3B8; }

/* ════════════════════════════════════════════════════════════════════
   FILTRES
════════════════════════════════════════════════════════════════════ */
.filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }

.filter-tab {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 16px; border-radius: 24px;
  border: 1.5px solid #E5E7EB;
  background: #fff;
  font-size: 13px; font-weight: 500; color: #6B7280;
  cursor: pointer;
  transition: all 0.15s ease;
}
.filter-tab:hover {
  border-color: #0F2D5E;
  color: #0F2D5E;
  background: #EFF6FF;
}
.filter-tab--active {
  background: #0F2D5E;
  border-color: #0F2D5E;
  color: #fff;
  box-shadow: 0 2px 8px rgba(15,45,94,0.25);
}

.tab-badge {
  background: rgba(0,0,0,0.08);
  border-radius: 12px;
  font-size: 11px; font-weight: 700;
  padding: 1px 7px; min-width: 20px;
  text-align: center;
}
.filter-tab--active .tab-badge { background: rgba(255,255,255,0.25); }

:deep(.v-theme--dark) .filter-tab {
  background: #1E293B; border-color: #334155; color: #94A3B8;
}
:deep(.v-theme--dark) .filter-tab:hover {
  background: #1D3461; border-color: #3B82F6; color: #93C5FD;
}
:deep(.v-theme--dark) .filter-tab--active {
  background: #1D4ED8; border-color: #1D4ED8; color: #fff;
}

/* ════════════════════════════════════════════════════════════════════
   ÉTATS
════════════════════════════════════════════════════════════════════ */
.state-box {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 60px 20px;
  background: #fff; border: 1px solid #E5E7EB;
  border-radius: 16px; text-align: center;
}
:deep(.v-theme--dark) .state-box {
  background: #1E293B; border-color: #334155;
}

.empty-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: #F3F4F6;
  display: flex; align-items: center; justify-content: center;
}
:deep(.v-theme--dark) .empty-icon { background: #334155; }

.empty-title { font-size: 1rem; font-weight: 600; color: #374151; margin: 0; }
.state-text  { font-size: 0.85rem; color: #9CA3AF; margin: 4px 0 0; }
:deep(.v-theme--dark) .empty-title { color: #E2E8F0; }

/* ════════════════════════════════════════════════════════════════════
   GROUPES
════════════════════════════════════════════════════════════════════ */
.group-label {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.07em;
  color: #9CA3AF;
  margin-bottom: 8px; padding: 0 2px;
}
:deep(.v-theme--dark) .group-label { color: #64748B; }

/* ════════════════════════════════════════════════════════════════════
   CARTE NOTIFICATION
════════════════════════════════════════════════════════════════════ */
.notif-card {
  background: #fff;
  border: 1.5px solid #E5E7EB;
  border-radius: 12px;
  margin-bottom: 8px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.18s ease;
}
.notif-card:hover {
  border-color: #BFDBFE;
  box-shadow: 0 4px 16px rgba(37,99,235,0.08);
  transform: translateY(-1px);
}
.notif-card--unread {
  border-left: 3px solid #2563EB;
  background: #F8FAFF;
}

:deep(.v-theme--dark) .notif-card {
  background: #1E293B;
  border-color: #334155;
}
:deep(.v-theme--dark) .notif-card:hover {
  border-color: #3B82F6;
  box-shadow: 0 4px 16px rgba(59,130,246,0.12);
}
:deep(.v-theme--dark) .notif-card--unread {
  background: #172036;
  border-left-color: #3B82F6;
}

/* ── Ligne intérieure ── */
.notif-inner {
  display: flex; align-items: flex-start;
  gap: 12px; padding: 14px 16px;
}

.unread-indicator { width: 10px; flex-shrink: 0; padding-top: 4px; }
.unread-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #2563EB;
}

.notif-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.notif-content { flex: 1; min-width: 0; }

.notif-row-top {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 8px;
  margin-bottom: 4px;
}

.notif-title {
  font-size: 13.5px; color: #111827;
  overflow: hidden; text-overflow: ellipsis;
  white-space: nowrap; flex: 1;
}
:deep(.v-theme--dark) .notif-title { color: #E2E8F0; }

.notif-time {
  font-size: 11px; color: #9CA3AF;
  white-space: nowrap; flex-shrink: 0;
}

.notif-body-text {
  font-size: 12.5px; color: #6B7280;
  margin: 0; line-height: 1.5;
  overflow: hidden; text-overflow: ellipsis;
  white-space: nowrap;
}
:deep(.v-theme--dark) .notif-body-text { color: #94A3B8; }

/* ── Boutons actions ── */
.notif-btns {
  display: flex; flex-direction: column;
  gap: 4px; flex-shrink: 0;
}

.notif-btn {
  width: 28px; height: 28px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  border: none; cursor: pointer;
  transition: all 0.15s ease;
}
.notif-btn--check {
  background: #ECFDF5; color: #16A34A;
}
.notif-btn--check:hover { background: #D1FAE5; }

.notif-btn--delete {
  background: #FEF2F2; color: #DC2626;
}
.notif-btn--delete:hover { background: #FEE2E2; }

:deep(.v-theme--dark) .notif-btn--check  { background: rgba(22,163,74,0.15);  color: #86EFAC; }
:deep(.v-theme--dark) .notif-btn--delete { background: rgba(220,38,38,0.15);  color: #FCA5A5; }

/* ════════════════════════════════════════════════════════════════════
   PAGINATION
════════════════════════════════════════════════════════════════════ */
.pagination {
  display: flex; align-items: center;
  justify-content: center; gap: 5px;
  flex-wrap: wrap;
}

.page-arrow {
  width: 34px; height: 34px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  border: 1.5px solid #E5E7EB; background: #fff;
  color: #6B7280; cursor: pointer;
  transition: all 0.15s;
}
.page-arrow:hover:not(:disabled) { border-color: #0F2D5E; color: #0F2D5E; }
.page-arrow:disabled { opacity: 0.4; cursor: default; }

.page-btn {
  min-width: 34px; height: 34px; padding: 0 8px;
  border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  border: 1.5px solid #E5E7EB; background: #fff;
  font-size: 13px; font-weight: 500; color: #374151;
  cursor: pointer; transition: all 0.15s;
}
.page-btn:hover:not(.page-btn--ellipsis):not(.page-btn--active) {
  border-color: #0F2D5E; color: #0F2D5E;
}
.page-btn--active {
  background: #0F2D5E; border-color: #0F2D5E;
  color: #fff;
  box-shadow: 0 2px 8px rgba(15,45,94,0.25);
}
.page-btn--ellipsis { border: none; cursor: default; color: #9CA3AF; background: transparent; }

:deep(.v-theme--dark) .page-arrow,
:deep(.v-theme--dark) .page-btn {
  background: #1E293B; border-color: #334155; color: #94A3B8;
}
:deep(.v-theme--dark) .page-btn--active {
  background: #1D4ED8; border-color: #1D4ED8; color: #fff;
}

/* ════════════════════════════════════════════════════════════════════
   DIALOG DÉTAIL
════════════════════════════════════════════════════════════════════ */
.detail-card { overflow: hidden; }

.detail-banner {
  display: flex; align-items: flex-start;
  gap: 14px; padding: 22px;
  position: relative;
}
.detail-banner-icon {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.detail-banner-text { flex: 1; }
.detail-type-label {
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.08em;
  color: rgba(255,255,255,0.7); margin-bottom: 4px;
}
.detail-title {
  font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.3;
}
.detail-date {
  font-size: 11.5px; color: rgba(255,255,255,0.7);
  margin-top: 5px; display: flex; align-items: center;
}
.detail-close { position: absolute; top: 14px; right: 14px; }

.detail-body { background: #fff; }
:deep(.v-theme--dark) .detail-body { background: #1E293B; }

.detail-message-box {
  display: flex; align-items: flex-start; gap: 8px;
  background: #F9FAFB; border: 1px solid #E5E7EB;
  border-radius: 12px; padding: 14px 16px;
}
:deep(.v-theme--dark) .detail-message-box {
  background: #162032; border-color: #334155;
}

.detail-message {
  font-size: 0.95rem; color: #374151;
  line-height: 1.7; margin: 0; white-space: pre-line;
}
:deep(.v-theme--dark) .detail-message { color: #CBD5E1; }

.detail-meta {
  background: #F9FAFB; border: 1px solid #E5E7EB;
  border-radius: 12px; padding: 14px 16px;
}
:deep(.v-theme--dark) .detail-meta { background: #162032; border-color: #334155; }

.detail-meta-title {
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.07em;
  color: #9CA3AF; margin: 0 0 10px;
  display: flex; align-items: center;
}
.detail-meta-grid { display: flex; flex-direction: column; gap: 8px; }

.meta-row {
  display: flex; align-items: center;
  justify-content: space-between;
  padding-bottom: 7px;
  border-bottom: 1px solid #F3F4F6;
}
.meta-row:last-child { border-bottom: none; padding-bottom: 0; }
:deep(.v-theme--dark) .meta-row { border-color: #1E293B; }

.meta-key { font-size: 12px; color: #9CA3AF; font-weight: 500; }
.meta-val { font-size: 13px; color: #111827; font-weight: 600; }
:deep(.v-theme--dark) .meta-val { color: #E2E8F0; }

.detail-read-status {
  display: flex; align-items: center;
  font-size: 12px; color: #9CA3AF;
}

/* ════════════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════════════ */
@media (max-width: 600px) {
  .notif-page { padding: 0; }
  .page-title { font-size: 1.2rem; }
  .filter-tab { padding: 6px 12px; font-size: 12px; }
  .notif-inner { padding: 12px; }
  .notif-title { font-size: 13px; }
}
</style>
