<template>
  <v-container fluid class="pa-4 pa-md-6" style="background:#f5f7ff; min-height:100vh;">

    <!-- ═══ EN-TÊTE ══════════════════════════════════════════════════ -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-3">
      
      <!-- Actions -->
      <div class="d-flex align-center gap-2">
        <v-btn
          v-if="unreadCount > 0"
          variant="tonal"
          color="primary"
          rounded="lg"
          size="small"
          prepend-icon="mdi-check-all"
          :loading="markingAll"
          @click="markAllRead"
        >
          Tout marquer lu
        </v-btn>
        <v-btn
          variant="tonal"
          color="error"
          rounded="lg"
          size="small"
          prepend-icon="mdi-delete-sweep-outline"
          :disabled="!notifications.length"
          @click="confirmClearAll = true"
        >
          Tout effacer
        </v-btn>
      </div>
    </div>

    <!-- ═══ FILTRES ══════════════════════════════════════════════════ -->
    <v-card rounded="lg" elevation="0" border class="pa-3 mb-5">
      <div class="d-flex align-center gap-2 flex-wrap">
        <v-btn
          v-for="tab in tabs"
          :key="tab.value"
          :variant="activeTab === tab.value ? 'flat' : 'text'"
          :color="activeTab === tab.value ? 'primary' : 'grey'"
          rounded="lg"
          size="small"
          @click="activeTab = tab.value"
        >
          {{ tab.label }}
          <v-badge
            v-if="tab.count > 0"
            :content="tab.count"
            :color="activeTab === tab.value ? 'white' : 'primary'"
            inline
            class="ml-1"
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
      rounded="lg" elevation="0" border
      class="pa-12 text-center"
    >
      <v-icon size="56" color="blue-grey-lighten-3" class="mb-3">
        mdi-bell-off-outline
      </v-icon>
      <div style="font-size:1rem; font-weight:700; color:#455a64;">
        Aucune notification
      </div>
      <div style="font-size:0.82rem; color:#90a4ae; margin-top:6px;">
        {{
          activeTab === 'unread'
            ? 'Vous avez lu toutes vos notifications.'
            : 'Vous n\'avez pas encore reçu de notifications.'
        }}
      </div>
    </v-card>

    <!-- ═══ LISTE ═════════════════════════════════════════════════════ -->
    <div v-else class="d-flex flex-column ga-2">

      <!-- Groupe par date -->
      <template v-for="group in groupedList" :key="group.date">

        <!-- Label date -->
        <div class="date-label">{{ group.date }}</div>

        <!-- Notifications du groupe -->
        <v-card
          v-for="notif in group.items"
          :key="notif.id"
          rounded="lg"
          elevation="0"
          border
          class="notif-card"
          :class="{ 'notif-card--unread': !notif.read_at }"
          @click="handleClick(notif)"
        >
          <div class="d-flex align-start pa-4 gap-3">

            <!-- Icône -->
            <div
              class="notif-icon flex-shrink-0"
              :style="{ background: notifBg(notif.type) }"
            >
              <v-icon :color="notifColor(notif.type)" size="18">
                {{ notifIcon(notif.type) }}
              </v-icon>
            </div>

            <!-- Contenu -->
            <div class="flex-grow-1" style="min-width:0;">
              <!-- Titre + badge non lu -->
              <div class="d-flex align-center justify-space-between gap-2 mb-1">
                <span class="notif-title">
                  {{ notif.title ?? notif.data?.title ?? 'Notification' }}
                </span>
                <div class="d-flex align-center gap-2 flex-shrink-0">
                  <span
                    v-if="!notif.read_at"
                    class="unread-dot"
                  />
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
              <p class="notif-message mb-2">
                {{ notif.message ?? notif.data?.message ?? '' }}
              </p>

              <!-- Bas : heure + actions -->
              <div class="d-flex align-center justify-space-between">
                <span class="notif-time">
                  <v-icon size="11" class="mr-1">mdi-clock-outline</v-icon>
                  {{ fmtTime(notif.created_at) }}
                </span>
                <div class="d-flex align-center gap-1">
                  <v-btn
                    v-if="!notif.read_at"
                    variant="text"
                    size="x-small"
                    color="primary"
                    :loading="notif._loading"
                    @click.stop="markRead(notif)"
                  >
                    Marquer lu
                  </v-btn>
                  <v-btn
                    variant="text"
                    size="x-small"
                    color="error"
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
        v-model="page"
        :length="totalPages"
        :total-visible="5"
        rounded="lg"
        color="primary"
        density="compact"
      />
    </div>

    <!-- ═══ DIALOG CONFIRMATION EFFACER TOUT ════════════════════════ -->
    <v-dialog v-model="confirmClearAll" max-width="360">
      <v-card rounded="xl" elevation="0">
        <v-card-text class="pa-6 text-center">
          <v-icon size="48" color="error" class="mb-3">mdi-delete-sweep-outline</v-icon>
          <div style="font-size:1rem; font-weight:700; color:#263238;" class="mb-2">
            Effacer toutes les notifications ?
          </div>
          <div style="font-size:0.82rem; color:#90a4ae;">
            Cette action est irréversible.
          </div>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0 gap-2">
          <v-btn
            variant="tonal"
            rounded="lg"
            block
            @click="confirmClearAll = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="error"
            variant="flat"
            rounded="lg"
            block
            :loading="clearingAll"
            @click="clearAll"
          >
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

// ── State ──────────────────────────────────────────────────────────
const notifications  = ref([])
const loading        = ref(true)
const markingAll     = ref(false)
const clearingAll    = ref(false)
const confirmClearAll = ref(false)
const activeTab      = ref('all')
const page           = ref(1)
const PER_PAGE       = 15

// ── Tabs ────────────────────────────────────────────────────────────
const tabs = computed(() => [
  {
    value: 'all',
    label: 'Toutes',
    count: notifications.value.length,
  },
  {
    value: 'unread',
    label: 'Non lues',
    count: notifications.value.filter(n => !n.read_at).length,
  },
  {
    value: 'reclamation',
    label: 'Réclamations',
    count: notifications.value.filter(n => n.type?.includes('reclamation')).length,
  },
 
])

// ── Computed ────────────────────────────────────────────────────────
const unreadCount = computed(() =>
  notifications.value.filter(n => !n.read_at).length
)

const filteredList = computed(() => {
  if (activeTab.value === 'unread')
    return notifications.value.filter(n => !n.read_at)
  if (activeTab.value === 'reclamation')
    return notifications.value.filter(n => n.type?.includes('reclamation'))
  if (activeTab.value === 'system')
    return notifications.value.filter(n =>
      n.type?.includes('system') || n.type?.includes('general')
    )
  return notifications.value
})

const totalPages = computed(() =>
  Math.ceil(filteredList.value.length / PER_PAGE)
)

const paginatedList = computed(() => {
  const s = (page.value - 1) * PER_PAGE
  return filteredList.value.slice(s, s + PER_PAGE)
})

// Grouper par date
const groupedList = computed(() => {
  const groups = {}
  paginatedList.value.forEach(n => {
    const label = dateLabel(n.created_at)
    if (!groups[label]) groups[label] = []
    groups[label].push(n)
  })
  return Object.entries(groups).map(([date, items]) => ({ date, items }))
})

// ── Lifecycle ───────────────────────────────────────────────────────
onMounted(async () => {
  await loadNotifications()
})

async function loadNotifications() {
  loading.value = true
  try {
    const res = await api.get('/student/notifications')
    notifications.value = (res.data?.data ?? res.data ?? []).map(n => ({
      ...n,
      _loading:  false,
      _deleting: false,
    }))
  } catch (e) {
    console.error('[Notifications] Erreur:', e)
  } finally {
    loading.value = false
  }
}

// ── Actions ─────────────────────────────────────────────────────────
async function markRead(notif) {
  notif._loading = true
  try {
    await api.put(`/student/notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
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
      if (!n.read_at) n.read_at = new Date().toISOString()
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
    await Promise.all(
      notifications.value.map(n => api.delete(`/student/notifications/${n.id}`))
    )
    notifications.value = []
    confirmClearAll.value = false
  } catch (e) {
    console.error(e)
  } finally {
    clearingAll.value = false
  }
}

function handleClick(notif) {
  // ── Marquer comme lu ──────────────────────────────────────────────
  if (!notif.read_at) markRead(notif)

  // ── Extraire les données ──────────────────────────────────────────
  const data = notif.data ?? {}
  const type = notif.type ?? ''

  // ── 1. Lien direct si fourni par le backend ───────────────────────
  if (data.link) {
    // Lien interne Vue Router
    if (typeof data.link === 'object') {
      router.push(data.link)
      return
    }
    // Lien string ex: "/student/reclamations/12"
    if (typeof data.link === 'string' && data.link.startsWith('/')) {
      router.push(data.link)
      return
    }
  }

  // ── 2. Navigation selon le type de notification ───────────────────

  // Réclamation (status changé, escaladée, résolue, rejetée, meeting)
  if (
    type.includes('reclamation') ||
    type.includes('status')      ||
    type.includes('escalat')     ||
    type.includes('resolved')    ||
    type.includes('rejected')    ||
    type.includes('meeting')
  ) {
    const reclamationId = data.reclamation_id ?? data.id
    if (reclamationId) {
      router.push({
        name:   'student.reclamation.detail',
        params: { id: reclamationId },
      })
      return
    }
    // Fallback : liste des réclamations
    router.push({ name: 'student.reclamations' })
    return
  }

  // Document
  if (type.includes('document')) {
    const documentId = data.document_id ?? data.id
    if (documentId) {
      router.push({
        name:   'student.documents',
        params: { id: documentId },
      })
      return
    }
    router.push({ name: 'student.dashboard' })
    return
  }

  // Note
  if (type.includes('note')) {
    router.push({ name: 'student.dashboard' })
    return
  }

  // Système / général → dashboard
  if (type.includes('system') || type.includes('general')) {
    router.push({ name: 'student.dashboard' })
    return
  }

  // ── 3. Fallback → dashboard ───────────────────────────────────────
  router.push({ name: 'student.dashboard' })
}

// ── Helpers ─────────────────────────────────────────────────────────
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
    success:     'rgba(56,142,60,0.1)',
    error:       'rgba(211,47,47,0.1)',
    'deep-orange': 'rgba(230,74,25,0.1)',
    purple:      'rgba(156,39,176,0.1)',
    primary:     'rgba(57,73,171,0.1)',
    teal:        'rgba(0,150,136,0.1)',
    'blue-grey': 'rgba(96,125,139,0.1)',
  }
  return map[notifColor(type)] ?? 'rgba(96,125,139,0.1)'
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
  const d    = new Date(dateStr)
  const now  = new Date()
  const diff = Math.floor((now - d) / 86400000)
  if (diff === 0) return "Aujourd'hui"
  if (diff === 1) return 'Hier'
  if (diff < 7)   return `Il y a ${diff} jours`
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'long', year: 'numeric'
  }).format(d)
}

function fmtTime(dateStr) {
  if (!dateStr) return ''
  const d    = new Date(dateStr)
  const now  = new Date()
  const diff = Math.floor((now - d) / 60000)
  if (diff < 1)   return 'À l\'instant'
  if (diff < 60)  return `Il y a ${diff} min`
  if (diff < 1440) return new Intl.DateTimeFormat('fr-FR', {
    hour: '2-digit', minute: '2-digit'
  }).format(d)
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short'
  }).format(d)
}
</script>

<style scoped>
/* Carte notification */
.notif-card {
  cursor: pointer;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.notif-card:hover {
  box-shadow: 0 4px 18px rgba(26,35,126,0.09) !important;
  transform: translateY(-1px);
}

/* Non lue : fond légèrement bleuté + bordure gauche */
.notif-card--unread {
  background: #f0f4ff !important;
  border-left: 3px solid #3949ab !important;
}

/* Icône */
.notif-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Titre */
.notif-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: #263238;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 260px;
}

/* Message */
.notif-message {
  font-size: 0.8rem;
  color: #78909c;
  line-height: 1.5;
  margin: 0;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Heure */
.notif-time {
  font-size: 0.72rem;
  color: #b0bec5;
  display: inline-flex;
  align-items: center;
}

/* Point non lu */
.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #3949ab;
  flex-shrink: 0;
}

/* Label date groupe */
.date-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #90a4ae;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 8px 4px 4px;
}

@media (max-width: 600px) {
  .notif-title   { max-width: 160px; font-size: 0.82rem; }
  .notif-message { -webkit-line-clamp: 1; }
}
</style>
