<template>
  <v-container fluid class="pa-4 pa-md-6 surface-variant-bg" style="min-height:100vh;">

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

    <div v-if="loading" class="d-flex justify-center align-center py-16">
      <v-progress-circular indeterminate color="primary" size="40" />
    </div>

    <v-card
      v-else-if="!filteredList.length"
      rounded="lg" elevation="0" border class="pa-12 text-center"
    >
      <v-icon size="56" color="medium-emphasis" class="mb-3 opacity-60">mdi-bell-off-outline</v-icon>
      <div class="text-h6 font-weight-bold text-high-emphasis">Aucune notification</div>
      <div class="text-body-2 text-medium-emphasis mt-2">
        {{
          activeTab === 'unread'
            ? 'Vous avez lu toutes vos notifications.'
            : "Vous n'avez pas encore reçu de notifications."
        }}
      </div>
    </v-card>

    <div v-else class="d-flex flex-column ga-2">
      <template v-for="group in groupedList" :key="group.date">

        <div class="date-label text-medium-emphasis">{{ group.date }}</div>

        <v-card
          v-for="notif in group.items" :key="notif.id"
          rounded="lg" elevation="0" border
          class="notif-card"
          :class="{ 'notif-card--unread': !notif.read_at && !notif.is_read }"
          @click="handleClick(notif)"
        >
          <div class="d-flex align-start pa-4 gap-3">

            <div class="notif-icon flex-shrink-0" :style="{ background: notifBg(notif.type) }">
              <v-icon :color="notifColor(notif.type)" size="18">{{ notifIcon(notif.type) }}</v-icon>
            </div>

            <div class="flex-grow-1" style="min-width:0;">

              <!-- ✅ Titre : nom matière extrait du titre OU référence -->
              <div class="d-flex align-center justify-space-between gap-2 mb-1">
                <span class="notif-title text-high-emphasis">
                  {{ extractModuleName(notif) }}
                </span>
                <div class="d-flex align-center gap-1 flex-shrink-0">
                  <span v-if="!notif.read_at && !notif.is_read" class="unread-dot bg-primary" />
                  <v-chip
                    v-if="notif.status"
                    :color="statusColor(notif.status)"
                    variant="flat" size="x-small" label class="status-chip"
                  >
                    <v-icon start size="10">{{ statusIcon(notif.status) }}</v-icon>
                    {{ statusLabel(notif.status) }}
                  </v-chip>
                  <v-chip :color="notifColor(notif.type)" variant="tonal" size="x-small" label>
                    {{ notifTypeLabel(notif.type) }}
                  </v-chip>
                </div>
              </div>

              <!-- ✅ Pill : référence extraite de data.reference ou du titre -->
              <div v-if="extractReference(notif)" class="module-pill mb-2">
                <v-icon size="13" color="primary" class="mr-1">mdi-pound</v-icon>
                <span>{{ extractReference(notif) }}</span>
              </div>

              <p class="notif-message text-medium-emphasis mb-2">{{ notif.message }}</p>

              <div class="d-flex align-center justify-space-between gap-2 flex-wrap">
                <div v-if="notif.status" class="status-detail" :style="{ color: statusHex(notif.status) }">
                  <v-icon size="12" class="mr-1">{{ statusIcon(notif.status) }}</v-icon>
                  Statut : <strong class="ml-1">{{ statusLabel(notif.status) }}</strong>
                </div>
                <span v-else class="notif-time text-medium-emphasis opacity-70">
                  <v-icon size="11" class="mr-1">mdi-clock-outline</v-icon>
                  {{ fmtTime(notif.created_at) }}
                </span>
                <div class="d-flex align-center gap-1">
                  <span v-if="notif.status" class="notif-time text-medium-emphasis opacity-70">
                    <v-icon size="11" class="mr-1">mdi-clock-outline</v-icon>
                    {{ fmtTime(notif.created_at) }}
                  </span>
                  <v-btn
                    v-if="!notif.read_at && !notif.is_read"
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

    <div v-if="totalPages > 1" class="d-flex justify-center mt-6">
      <v-pagination
        v-model="page" :length="totalPages"
        :total-visible="5" rounded="lg" color="primary" density="compact"
      />
    </div>

    <v-dialog v-model="confirmClearAll" max-width="360">
      <v-card rounded="xl" elevation="0">
        <v-card-text class="pa-6 text-center">
          <v-icon size="48" color="error" class="mb-3">mdi-delete-sweep-outline</v-icon>
          <div class="text-h6 font-weight-bold text-high-emphasis mb-2">Effacer toutes les notifications ?</div>
          <div class="text-body-2 text-medium-emphasis">Cette action est irréversible.</div>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0 gap-2">
          <v-btn variant="tonal" rounded="lg" block @click="confirmClearAll = false">Annuler</v-btn>
          <v-btn color="error" variant="flat" rounded="lg" block :loading="clearingAll" @click="clearAll">Effacer</v-btn>
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

const notifications   = ref([])
const loading         = ref(true)
const markingAll      = ref(false)
const clearingAll     = ref(false)
const confirmClearAll = ref(false)
const activeTab       = ref('all')
const page            = ref(1)
const PER_PAGE        = 15

// ✅ Extrait le nom de matière depuis le titre (ex: "Réclamation #RECL-2026-000020 — Statut mis à jour")
// Retourne la référence formatée proprement : "RECL-2026-000020"
function extractReference(notif) {
  // 1. Depuis data.reference (champ direct)
  if (notif.data?.reference) return notif.data.reference

  // 2. Depuis le titre via regex
  const match = (notif.title ?? '').match(/#?(RECL-[\d-]+)/)
  if (match) return match[1]

  // 3. Depuis le message
  const matchMsg = (notif.message ?? '').match(/#?(RECL-[\d-]+)/)
  if (matchMsg) return matchMsg[1]

  return null
}

// ✅ Titre affiché : module_name si dispo, sinon référence, sinon titre original
function extractModuleName(notif) {
  if (notif.module_name) return notif.module_name

  const ref = extractReference(notif)
  if (ref) return ref

  return notif.title ?? 'Notification'
}

function isReclamationType(type) {
  if (!type) return false
  const t = type.toLowerCase()
  return (
    t.includes('reclamation') || t.includes('réclamation') ||
    t.includes('newreclam')   || t.includes('new_reclam')  ||
    t.includes('statuschange')|| t.includes('status')      ||
    t.includes('escalat')     || t.includes('resolv')      ||
    t.includes('reject')      || t.includes('meeting')
  )
}

const tabs = computed(() => [
  { value: 'all',         label: 'Toutes',        count: notifications.value.length },
  { value: 'unread',      label: 'Non lues',       count: notifications.value.filter(n => !n.read_at && !n.is_read).length },
  { value: 'reclamation', label: 'Réclamations',   count: notifications.value.filter(n => isReclamationType(n.type)).length },
])

const unreadCount = computed(() =>
  notifications.value.filter(n => !n.read_at && !n.is_read).length
)

const filteredList = computed(() => {
  if (activeTab.value === 'unread')
    return notifications.value.filter(n => !n.read_at && !n.is_read)
  if (activeTab.value === 'reclamation')
    return notifications.value.filter(n => isReclamationType(n.type))
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

onMounted(loadNotifications)

async function loadNotifications() {
  loading.value = true
  try {
    const res = await api.get('/student/notifications')
    const raw = res.data?.data ?? res.data ?? []
    notifications.value = raw.map(n => ({ ...n, _loading: false, _deleting: false }))
  } catch (e) {
    console.error('[Notifications] Erreur chargement:', e)
  } finally {
    loading.value = false
  }
}

async function markRead(notif) {
  notif._loading = true
  try {
    await api.put(`/student/notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
    notif.is_read = true
  } catch (e) {
    console.error(e)
  } finally {
    notif._loading = false
  }
}

async function markAllRead() {
  markingAll.value = true
  try {
    await api.put('/student/notifications/read-all')
    notifications.value.forEach(n => {
      if (!n.read_at) { n.read_at = new Date().toISOString(); n.is_read = true }
    })
  } catch (e) {
    console.error(e)
  } finally {
    markingAll.value = false
  }
}

async function deleteNotif(notif) {
  notif._deleting = true
  try {
    await api.delete(`/student/notifications/${notif.id}`)
    notifications.value = notifications.value.filter(n => n.id !== notif.id)
  } catch (e) {
    console.error(e)
  } finally {
    notif._deleting = false
  }
}

async function clearAll() {
  clearingAll.value = true
  try {
    await Promise.all(notifications.value.map(n => api.delete(`/student/notifications/${n.id}`)))
    notifications.value = []
    confirmClearAll.value = false
  } catch (e) {
    console.error(e)
  } finally {
    clearingAll.value = false
  }
}

async function handleClick(notif) {
  if (!notif.read_at && !notif.is_read) markRead(notif)

  const data = notif.data ?? {}
  const type = (notif.type ?? '').toLowerCase()

  if (isReclamationType(type)) {
    // 1. ID direct (si backend corrigé)
    const rid = notif.reclamation_id ?? data.reclamation_id ?? null
    if (rid) {
      router.push({ name: 'student.reclamation.detail', params: { id: rid } })
      return
    }

    // 2. ✅ Cherche via la référence unique de CETTE notification
    const ref = data.reference ?? data.reference_number ?? null

    if (ref) {
      try {
        const res = await api.get('/student/reclamations')
        const list = res.data?.data ?? res.data ?? []

        // ✅ Trouve la réclamation dont reference_number correspond à CETTE référence
        const found = list.find(r =>
          r.reference_number === ref ||
          r.reference_number === `RECL-${ref}` ||
          ref.includes(r.reference_number)
        )

        if (found?.id) {
          router.push({ name: 'student.reclamation.detail', params: { id: found.id } })
          return
        }
      } catch (e) {
        console.error('[Notifications] Erreur recherche réclamation:', e)
      }
    }

    // 3. Fallback
    router.push({ name: 'student.reclamations' })
    return
  }

  router.push({ name: 'student.dashboard' })
}
const STATUS_MAP = {
  submitted:         { label: 'Soumise',        color: 'blue',        hex: '#2563EB', icon: 'mdi-send-outline' },
  pending:           { label: 'En attente',      color: 'orange',      hex: '#D97706', icon: 'mdi-clock-outline' },
  new:               { label: 'Nouvelle',        color: 'blue',        hex: '#2563EB', icon: 'mdi-bell-outline' },
  received:          { label: 'Reçue',           color: 'cyan',        hex: '#0891B2', icon: 'mdi-inbox-arrow-down' },
  in_progress:       { label: 'En cours',        color: 'primary',     hex: '#4F46E5', icon: 'mdi-progress-clock' },
  in_review:         { label: 'En révision',     color: 'purple',      hex: '#7C3AED', icon: 'mdi-magnify' },
  processing:        { label: 'En traitement',   color: 'primary',     hex: '#4F46E5', icon: 'mdi-cog-outline' },
  under_review:      { label: 'En révision',     color: 'purple',      hex: '#7C3AED', icon: 'mdi-magnify' },
  escalated:         { label: 'Escaladée',       color: 'deep-orange', hex: '#EA580C', icon: 'mdi-arrow-up-circle-outline' },
  meeting_scheduled: { label: 'RDV planifié',    color: 'purple',      hex: '#7C3AED', icon: 'mdi-calendar-check-outline' },
  resolved:          { label: 'Résolue',         color: 'success',     hex: '#059669', icon: 'mdi-check-circle-outline' },
  approved:          { label: 'Approuvée',       color: 'success',     hex: '#059669', icon: 'mdi-check-decagram-outline' },
  closed:            { label: 'Clôturée',        color: 'teal',        hex: '#0F766E', icon: 'mdi-lock-check-outline' },
  rejected:          { label: 'Rejetée',         color: 'error',       hex: '#DC2626', icon: 'mdi-close-circle-outline' },
  cancelled:         { label: 'Annulée',         color: 'grey',        hex: '#64748B', icon: 'mdi-cancel' },
  invalid:           { label: 'Non recevable',   color: 'error',       hex: '#DC2626', icon: 'mdi-alert-circle-outline' },
  note_updated:      { label: 'Note modifiée',   color: 'teal',        hex: '#0F766E', icon: 'mdi-pencil-circle-outline' },
}

function statusLabel(s) { return STATUS_MAP[s]?.label ?? (s ? s.replace(/_/g, ' ') : '') }
function statusColor(s) { return STATUS_MAP[s]?.color ?? 'primary' }
function statusHex(s)   { return STATUS_MAP[s]?.hex   ?? '#4F46E5' }
function statusIcon(s)  { return STATUS_MAP[s]?.icon  ?? 'mdi-information-outline' }

function notifIcon(type) {
  const t = (type ?? '').toLowerCase()
  if (t.includes('newreclam') || t.includes('new_reclam')) return 'mdi-file-plus-outline'
  if (t.includes('reclam'))   return 'mdi-file-document-outline'
  if (t.includes('status'))   return 'mdi-swap-horizontal'
  if (t.includes('meeting'))  return 'mdi-calendar-check-outline'
  if (t.includes('escalat'))  return 'mdi-arrow-up-circle-outline'
  if (t.includes('resolv'))   return 'mdi-check-circle-outline'
  if (t.includes('reject'))   return 'mdi-close-circle-outline'
  if (t.includes('document')) return 'mdi-paperclip'
  return 'mdi-bell-outline'
}

function notifColor(type) {
  const t = (type ?? '').toLowerCase()
  if (t.includes('resolv'))   return 'success'
  if (t.includes('reject'))   return 'error'
  if (t.includes('escalat'))  return 'deep-orange'
  if (t.includes('meeting'))  return 'purple'
  if (t.includes('reclam'))   return 'primary'
  if (t.includes('document')) return 'teal'
  return 'blue-grey'
}

function notifBg(type) {
  const map = {
    success:       'rgba(5,150,105,0.12)',
    error:         'rgba(220,38,38,0.12)',
    'deep-orange': 'rgba(234,88,12,0.12)',
    purple:        'rgba(124,58,237,0.12)',
    primary:       'rgba(79,70,229,0.12)',
    teal:          'rgba(15,118,110,0.12)',
    'blue-grey':   'rgba(71,85,105,0.12)',
  }
  return map[notifColor(type)] ?? 'rgba(100,116,139,0.12)'
}

function notifTypeLabel(type) {
  const t = (type ?? '').toLowerCase()
  if (t.includes('reclam'))   return 'Réclamation'
  if (t.includes('meeting'))  return 'RDV'
  if (t.includes('document')) return 'Document'
  if (t.includes('system'))   return 'Système'
  return 'Info'
}

function dateLabel(dateStr) {
  if (!dateStr) return 'Date inconnue'
  const d       = new Date(dateStr)
  const now     = new Date()
  const dDate   = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const nowDate = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const diff    = Math.floor((nowDate - dDate) / 86_400_000)
  if (diff === 0) return "Aujourd'hui"
  if (diff === 1) return 'Hier'
  if (diff < 7)   return `Il y a ${diff} jours`
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(d)
}

function fmtTime(dateStr) {
  if (!dateStr) return ''
  const d    = new Date(dateStr)
  const now  = new Date()
  const diff = Math.floor((now - d) / 60_000)
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
  background-color: rgba(79,70,229,0.05) !important;
  border-left: 4px solid rgb(var(--v-theme-primary)) !important;
}

.notif-icon {
  width: 40px; height: 40px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

.notif-title {
  font-size: 0.88rem; font-weight: 700;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 200px;
}

.module-pill {
  display: inline-flex; align-items: center;
  background: rgba(79,70,229,0.08);
  color: rgb(var(--v-theme-primary));
  border-radius: 20px; padding: 2px 10px 2px 6px;
  font-size: 0.75rem; font-weight: 600;
  max-width: 100%; overflow: hidden;
  text-overflow: ellipsis; white-space: nowrap;
}

.notif-message {
  font-size: 0.8rem; line-height: 1.5; margin: 0;
  overflow: hidden; display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}

.notif-time {
  font-size: 0.72rem; display: inline-flex; align-items: center;
}

.unread-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}

.date-label {
  font-size: 0.75rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1px;
  padding: 8px 4px 4px;
}

.status-chip { font-size: 0.72rem !important; font-weight: 700 !important; }

.status-detail {
  font-size: 0.75rem; display: inline-flex;
  align-items: center; font-weight: 500;
}

@media (max-width: 600px) {
  .notif-title   { max-width: 130px; font-size: 0.82rem; }
  .notif-message { -webkit-line-clamp: 1; }
  .module-pill   { max-width: 160px; }
}
</style>