<template>
  <v-container fluid class="pa-4 pa-md-6 surface-variant-bg" style="min-height:100vh;">

    <!-- ═══ EN-TÊTE ══════════════════════════════════════════════════ -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-3">
      <h1 class="text-h5 font-weight-bold text-high-emphasis">Notifications</h1>
      <div class="d-flex align-center gap-2">
        <v-btn
          v-if="unreadCount > 0"
          variant="tonal" color="primary" rounded="lg" size="small"
          prepend-icon="mdi-check-all"
          :loading="markingAll"
          @click="markAllRead"
        >Tout marquer lu</v-btn>
        <v-btn
          variant="tonal" color="error" rounded="lg" size="small"
          prepend-icon="mdi-delete-sweep-outline"
          :disabled="!notifications.length"
          @click="confirmClearAll = true"
        >Tout effacer</v-btn>
      </div>
    </div>

    <!-- ═══ FILTRES ══════════════════════════════════════════════════ -->
    <v-card rounded="lg" elevation="0" border class="pa-3 mb-5">
      <div class="d-flex align-center gap-2 flex-wrap">
        <v-btn
          v-for="tab in tabs" :key="tab.value"
          :variant="activeTab === tab.value ? 'flat' : 'text'"
          :color="activeTab === tab.value ? 'primary' : 'medium-emphasis'"
          rounded="lg" size="small"
          @click="activeTab = tab.value; page = 1"
        >
          {{ tab.label }}
          <v-badge
            v-if="tab.count > 0"
            :content="tab.count"
            :color="activeTab === tab.value ? 'on-primary' : 'primary'"
            inline class="ml-1"
          />
        </v-btn>
      </div>
    </v-card>

    <!-- ═══ LOADING ══════════════════════════════════════════════════ -->
    <div v-if="loading" class="d-flex justify-center align-center py-16">
      <v-progress-circular indeterminate color="primary" size="40" />
    </div>

    <!-- ═══ VIDE ══════════════════════════════════════════════════════ -->
    <v-card
      v-else-if="!filteredList.length"
      rounded="lg" elevation="0" border class="pa-12 text-center"
    >
      <v-icon size="56" color="medium-emphasis" class="mb-3 opacity-60">
        mdi-bell-off-outline
      </v-icon>
      <div class="text-h6 font-weight-bold text-high-emphasis">Aucune notification</div>
      <div class="text-body-2 text-medium-emphasis mt-2">
        {{
          activeTab === 'unread'
            ? 'Vous avez lu toutes vos notifications.'
            : 'Vous n\'avez pas encore reçu de notifications.'
        }}
      </div>
    </v-card>

    <!-- ═══ LISTE ═════════════════════════════════════════════════════ -->
    <div v-else class="d-flex flex-column ga-2">
      <template v-for="group in groupedList" :key="group.date">

        <div class="date-label text-medium-emphasis">{{ group.date }}</div>

        <v-card
          v-for="notif in group.items" :key="notif.id"
          rounded="lg" elevation="0" border
          class="notif-card"
          :class="{ 'notif-card--unread': !notif.read_at }"
          @click="handleClick(notif)"
        >
          <div class="d-flex align-start pa-4 gap-3">

            <!-- Icône -->
            <div class="notif-icon flex-shrink-0" :style="{ background: notifBg(notif.type) }">
              <v-icon :color="notifColor(notif.type)" size="18">
                {{ notifIcon(notif.type) }}
              </v-icon>
            </div>

            <!-- Contenu -->
            <div class="flex-grow-1" style="min-width:0;">

              <!-- Ligne 1 : titre + badges -->
              <div class="d-flex align-center justify-space-between gap-2 mb-1">
                <span class="notif-title text-high-emphasis">
                  {{ notif.title ?? notif.data?.title ?? 'Notification' }}
                </span>
                <div class="d-flex align-center gap-1 flex-shrink-0">
                  <span v-if="!notif.read_at" class="unread-dot bg-primary" />

                  <!-- ── Badge STATUT (priorité haute) ── -->
                  <v-chip
                    v-if="resolveStatus(notif)"
                    :color="statusColor(resolveStatus(notif))"
                    variant="flat"
                    size="x-small"
                    label
                    class="status-chip"
                  >
                    <v-icon start size="10">{{ statusIcon(resolveStatus(notif)) }}</v-icon>
                    {{ statusLabel(resolveStatus(notif)) }}
                  </v-chip>

                  <!-- Badge type -->
                  <v-chip
                    :color="notifColor(notif.type)"
                    variant="tonal"
                    size="x-small"
                    label
                  >
                    {{ notifTypeLabel(notif.type) }}
                  </v-chip>
                </div>
              </div>

              <!-- Message -->
              <p class="notif-message text-medium-emphasis mb-2">
                {{ notif.message ?? notif.data?.message ?? '' }}
              </p>

              <!-- Ligne 3 : détails statut (si réclamation) + heure + actions -->
              <div class="d-flex align-center justify-space-between gap-2 flex-wrap">

                <!-- Détail statut inline -->
                <div
                  v-if="resolveStatus(notif)"
                  class="status-detail"
                  :style="{ color: `rgb(var(--v-theme-${statusColor(resolveStatus(notif))}))` }"
                >
                  <v-icon size="12" class="mr-1">{{ statusIcon(resolveStatus(notif)) }}</v-icon>
                  <span>Statut : <strong>{{ statusLabel(resolveStatus(notif)) }}</strong></span>
                </div>
                <span v-else class="notif-time text-medium-emphasis opacity-70">
                  <v-icon size="11" class="mr-1">mdi-clock-outline</v-icon>
                  {{ fmtTime(notif.created_at) }}
                </span>

                <div class="d-flex align-center gap-1">
                  <!-- Heure (si statut déjà affiché à gauche) -->
                  <span v-if="resolveStatus(notif)" class="notif-time text-medium-emphasis opacity-70">
                    <v-icon size="11" class="mr-1">mdi-clock-outline</v-icon>
                    {{ fmtTime(notif.created_at) }}
                  </span>
                  <v-btn
                    v-if="!notif.read_at"
                    variant="text" size="x-small" color="primary"
                    :loading="notif._loading"
                    @click.stop="markRead(notif)"
                  >Marquer lu</v-btn>
                  <v-btn
                    variant="text" size="x-small" color="error"
                    icon="mdi-delete-outline"
                    :loading="notif._deleting"
                    @click.stop="deleteNotif(notif)"
                  />
                </div>
              </div>

            </div>
          </div>
        </v-card>

      </template>
    </div>

    <!-- ═══ PAGINATION ════════════════════════════════════════════════ -->
    <div v-if="totalPages > 1" class="d-flex justify-center mt-6">
      <v-pagination
        v-model="page" :length="totalPages"
        :total-visible="5" rounded="lg" color="primary" density="compact"
      />
    </div>

    <!-- ═══ DIALOG CONFIRMATION EFFACER TOUT ════════════════════════ -->
    <v-dialog v-model="confirmClearAll" max-width="360">
      <v-card rounded="xl" elevation="0">
        <v-card-text class="pa-6 text-center">
          <v-icon size="48" color="error" class="mb-3">mdi-delete-sweep-outline</v-icon>
          <div class="text-h6 font-weight-bold text-high-emphasis mb-2">
            Effacer toutes les notifications ?
          </div>
          <div class="text-body-2 text-medium-emphasis">Cette action est irréversible.</div>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0 gap-2">
          <v-btn variant="tonal" rounded="lg" block @click="confirmClearAll = false">Annuler</v-btn>
          <v-btn color="error" variant="flat" rounded="lg" block :loading="clearingAll" @click="clearAll">
            Effacer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router = useRouter()

// ── State ───────────────────────────────────────────────────────────
const notifications   = ref([])
const loading         = ref(true)
const markingAll      = ref(false)
const clearingAll     = ref(false)
const confirmClearAll = ref(false)
const activeTab       = ref('all')
const page            = ref(1)
const PER_PAGE        = 15

// ── Tabs ─────────────────────────────────────────────────────────────
const tabs = computed(() => [
  { value: 'all',         label: 'Toutes',       count: notifications.value.length },
  { value: 'unread',      label: 'Non lues',     count: notifications.value.filter(n => !n.read_at).length },
  { value: 'reclamation', label: 'Réclamations', count: notifications.value.filter(n => n.type?.includes('reclamation')).length },
])

// ── Computed ─────────────────────────────────────────────────────────
const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)

const filteredList = computed(() => {
  if (activeTab.value === 'unread')      return notifications.value.filter(n => !n.read_at)
  if (activeTab.value === 'reclamation') return notifications.value.filter(n => n.type?.includes('reclamation'))
  if (activeTab.value === 'system')      return notifications.value.filter(n => n.type?.includes('system') || n.type?.includes('general'))
  return notifications.value
})

const totalPages = computed(() => Math.ceil(filteredList.value.length / PER_PAGE))

const paginatedList = computed(() => {
  const s = (page.value - 1) * PER_PAGE
  return filteredList.value.slice(s, s + PER_PAGE)
})

const groupedList = computed(() => {
  const groups = {}
  paginatedList.value.forEach(n => {
    const label = dateLabel(n.created_at)
    if (!groups[label]) groups[label] = []
    groups[label].push(n)
  })
  return Object.entries(groups).map(([date, items]) => ({ date, items }))
})

// ── Lifecycle ─────────────────────────────────────────────────────────
onMounted(loadNotifications)

async function loadNotifications() {
  loading.value = true
  try {
    const res = await api.get('/student/notifications')
    notifications.value = (res.data?.data ?? res.data ?? []).map(n => ({
      ...n,
      // Compatibilité : is_read → read_at
      read_at:   n.read_at ?? (n.is_read ? new Date().toISOString() : null),
      _loading:  false,
      _deleting: false,
    }))
  } catch (e) {
    console.error('[Notifications] Erreur:', e)
  } finally {
    loading.value = false
  }
}

// ── Actions ───────────────────────────────────────────────────────────
async function markRead(notif) {
  notif._loading = true
  try {
    await api.put(`/student/notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
  } catch (e) { console.error(e) }
  finally { notif._loading = false }
}

async function markAllRead() {
  markingAll.value = true
  try {
    await api.put('/student/notifications/read-all')
    notifications.value.forEach(n => { if (!n.read_at) n.read_at = new Date().toISOString() })
  } catch (e) { console.error(e) }
  finally { markingAll.value = false }
}

async function deleteNotif(notif) {
  notif._deleting = true
  try {
    await api.delete(`/student/notifications/${notif.id}`)
    notifications.value = notifications.value.filter(n => n.id !== notif.id)
  } catch (e) { console.error(e) }
  finally { notif._deleting = false }
}

async function clearAll() {
  clearingAll.value = true
  try {
    await Promise.all(notifications.value.map(n => api.delete(`/student/notifications/${n.id}`)))
    notifications.value = []
    confirmClearAll.value = false
  } catch (e) { console.error(e) }
  finally { clearingAll.value = false }
}

function handleClick(notif) {
  if (!notif.read_at) markRead(notif)
  const data = notif.data ?? {}
  const type = notif.type ?? ''

  if (data.link) {
    const l = data.link
    if (typeof l === 'object' || (typeof l === 'string' && l.startsWith('/'))) {
      router.push(l); return
    }
  }

  if (type.includes('reclamation') || type.includes('status') || type.includes('escalat') ||
      type.includes('resolved')    || type.includes('rejected') || type.includes('meeting')) {
    const rid = data.reclamation_id ?? data.id
    router.push(rid ? { name: 'student.reclamation.detail', params: { id: rid } } : { name: 'student.reclamations' })
    return
  }

  if (type.includes('document')) {
    const did = data.document_id ?? data.id
    router.push(did ? { name: 'student.documents', params: { id: did } } : { name: 'student.dashboard' })
    return
  }

  router.push({ name: 'student.dashboard' })
}

// ── Statut ────────────────────────────────────────────────────────────

/**
 * Résout le statut depuis les différents champs possibles du backend.
 * Priorité : notif.status > data.status > data.reclamation_status > data.new_status
 */
function resolveStatus(notif) {
  return notif.status
    || notif.data?.status
    || notif.data?.reclamation_status
    || notif.data?.new_status
    || null
}

const STATUS_MAP = {
  // Soumis / en attente
  submitted:   { label: 'Soumise',          color: 'blue',        icon: 'mdi-send-outline' },
  pending:     { label: 'En attente',        color: 'orange',      icon: 'mdi-clock-outline' },
  new:         { label: 'Nouvelle',          color: 'blue',        icon: 'mdi-bell-outline' },

  // En cours
  in_progress: { label: 'En cours',          color: 'primary',     icon: 'mdi-progress-clock' },
  processing:  { label: 'En traitement',     color: 'primary',     icon: 'mdi-cog-outline' },
  under_review:{ label: 'En révision',       color: 'purple',      icon: 'mdi-magnify' },
  escalated:   { label: 'Escaladée',         color: 'deep-orange', icon: 'mdi-arrow-up-circle-outline' },
  meeting_scheduled: { label: 'RDV planifié',color: 'purple',      icon: 'mdi-calendar-check-outline' },

  // Terminé
  resolved:    { label: 'Résolue',           color: 'success',     icon: 'mdi-check-circle-outline' },
  approved:    { label: 'Approuvée',         color: 'success',     icon: 'mdi-check-decagram-outline' },
  closed:      { label: 'Clôturée',          color: 'teal',        icon: 'mdi-lock-check-outline' },

  // Rejeté / annulé
  rejected:    { label: 'Rejetée',           color: 'error',       icon: 'mdi-close-circle-outline' },
  cancelled:   { label: 'Annulée',           color: 'grey',        icon: 'mdi-cancel' },
  invalid:     { label: 'Non recevable',     color: 'error',       icon: 'mdi-alert-circle-outline' },

  // Note modifiée
  note_updated:{ label: 'Note modifiée',     color: 'teal',        icon: 'mdi-pencil-circle-outline' },
}

function statusLabel(status) {
  if (!status) return ''
  return STATUS_MAP[status]?.label ?? status.replace(/_/g, ' ')
}

function statusColor(status) {
  if (!status) return 'primary'
  return STATUS_MAP[status]?.color ?? 'primary'
}

function statusIcon(status) {
  if (!status) return 'mdi-information-outline'
  return STATUS_MAP[status]?.icon ?? 'mdi-information-outline'
}

// ── Helpers type ──────────────────────────────────────────────────────
function notifIcon(type) {
  if (type?.includes('reclamation')) return 'mdi-file-document-outline'
  if (type?.includes('status'))      return 'mdi-swap-horizontal'
  if (type?.includes('meeting'))     return 'mdi-calendar-check-outline'
  if (type?.includes('escalat'))     return 'mdi-arrow-up-circle-outline'
  if (type?.includes('resolved'))    return 'mdi-check-circle-outline'
  if (type?.includes('rejected'))    return 'mdi-close-circle-outline'
  if (type?.includes('document'))    return 'mdi-paperclip'
  return 'mdi-bell-outline'
}

function notifColor(type) {
  if (type?.includes('resolved'))    return 'success'
  if (type?.includes('rejected'))    return 'error'
  if (type?.includes('escalat'))     return 'deep-orange'
  if (type?.includes('meeting'))     return 'purple'
  if (type?.includes('reclamation')) return 'primary'
  if (type?.includes('document'))    return 'teal'
  return 'blue-grey'
}

function notifBg(type) {
  const map = {
    success:       'rgb(var(--v-theme-success), 0.12)',
    error:         'rgb(var(--v-theme-error), 0.12)',
    'deep-orange': 'rgb(var(--v-theme-deep-orange), 0.12)',
    purple:        'rgb(var(--v-theme-purple), 0.12)',
    primary:       'rgb(var(--v-theme-primary), 0.12)',
    teal:          'rgb(var(--v-theme-teal), 0.12)',
    'blue-grey':   'rgb(var(--v-theme-blue-grey), 0.12)',
  }
  return map[notifColor(type)] ?? 'rgb(var(--v-theme-surface-variant), 0.24)'
}

function notifTypeLabel(type) {
  if (type?.includes('reclamation')) return 'Réclamation'
  if (type?.includes('meeting'))     return 'RDV'
  if (type?.includes('document'))    return 'Document'
  if (type?.includes('system'))      return 'Système'
  return 'Info'
}

function dateLabel(dateStr) {
  if (!dateStr) return 'Date inconnue'
  const d       = new Date(dateStr)
  const now     = new Date()
  const dDate   = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const nowDate = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const diff    = Math.floor((nowDate - dDate) / 86400000)
  if (diff === 0) return "Aujourd'hui"
  if (diff === 1) return 'Hier'
  if (diff < 7)   return `Il y a ${diff} jours`
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(d)
}

function fmtTime(dateStr) {
  if (!dateStr) return ''
  const d    = new Date(dateStr)
  const now  = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1)    return "À l'instant"
  if (diff < 60)   return `Il y a ${diff} min`
  if (diff < 1440) return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(d)
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short' }).format(d)
}
</script>

<style scoped>
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }

.surface-variant-bg { background-color: rgb(var(--v-theme-background)); }

.notif-card {
  cursor: pointer;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
  background-color: rgb(var(--v-theme-surface));
}
.notif-card:hover {
  box-shadow: 0 4px 18px rgba(0,0,0,0.12) !important;
  transform: translateY(-1px);
}
.notif-card--unread {
  background-color: rgb(var(--v-theme-primary), 0.05) !important;
  border-left: 4px solid rgb(var(--v-theme-primary)) !important;
}

.notif-icon {
  width: 40px; height: 40px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.notif-title {
  font-size: 0.88rem; font-weight: 700;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 220px;
}

.notif-message {
  font-size: 0.8rem; line-height: 1.5; margin: 0;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.notif-time {
  font-size: 0.72rem;
  display: inline-flex; align-items: center;
}

.unread-dot {
  width: 8px; height: 8px;
  border-radius: 50%; flex-shrink: 0;
}

.date-label {
  font-size: 0.75rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1px;
  padding: 8px 4px 4px;
}

/* Badge statut : police légèrement plus grande pour lisibilité */
.status-chip { font-size: 0.72rem !important; font-weight: 700 !important; }

/* Ligne détail statut sous le message */
.status-detail {
  font-size: 0.75rem;
  display: inline-flex; align-items: center;
  font-weight: 500;
}

@media (max-width: 600px) {
  .notif-title   { max-width: 140px; font-size: 0.82rem; }
  .notif-message { -webkit-line-clamp: 1; }
}
</style>