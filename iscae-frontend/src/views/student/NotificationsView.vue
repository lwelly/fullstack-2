<template>
  <v-container fluid class="pa-4 pa-md-6">

    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h2 class="text-h5 font-weight-bold">Mes Notifications</h2>
        <p class="text-body-2 text-medium-emphasis mt-1">
          {{ unreadCount }} notification(s) non lue(s)
        </p>
      </div>
      <div class="d-flex gap-2">
        <v-btn
          color="primary" variant="tonal" size="small"
          prepend-icon="mdi-check-all"
          :loading="markAllLoading"
          :disabled="unreadCount === 0"
          @click="markAllRead"
        >
          Tout marquer comme lu
        </v-btn>
        <v-btn
          color="primary" variant="tonal" size="small"
          prepend-icon="mdi-refresh"
          :loading="loading"
          @click="loadData"
        >
          Actualiser
        </v-btn>
      </div>
    </div>

    <!-- Tabs filtre -->
    <v-card rounded="xl" elevation="1" class="mb-4">
      <v-tabs v-model="filterTab" color="primary">
        <v-tab value="all">
          <v-icon start size="16">mdi-bell</v-icon>
          Toutes
          <v-chip size="x-small" class="ml-2" color="primary" variant="tonal">
            {{ notifications.length }}
          </v-chip>
        </v-tab>
        <v-tab value="unread">
          <v-icon start size="16">mdi-bell-badge</v-icon>
          Non lues
          <v-chip v-if="unreadCount > 0" size="x-small" class="ml-2" color="error" variant="tonal">
            {{ unreadCount }}
          </v-chip>
        </v-tab>
        <v-tab value="reclamation">
          <v-icon start size="16">mdi-file-document-edit</v-icon>
          Réclamations
        </v-tab>
        <v-tab value="note">
          <v-icon start size="16">mdi-school</v-icon>
          Notes
        </v-tab>
      </v-tabs>
    </v-card>

    <!-- Chargement -->
    <div v-if="loading" class="text-center py-12">
      <v-progress-circular indeterminate color="primary" size="56" />
    </div>

    <!-- Vide -->
    <div v-else-if="filteredNotifs.length === 0" class="text-center py-16">
      <v-icon size="80" color="grey-lighten-2" class="mb-3">mdi-bell-sleep</v-icon>
      <p class="text-h6 text-medium-emphasis">Aucune notification</p>
      <p class="text-body-2 text-medium-emphasis mt-2">
        {{ filterTab === 'unread' ? 'Vous avez lu toutes vos notifications.' : 'Aucune notification pour le moment.' }}
      </p>
    </div>

    <!-- Liste -->
    <div v-else>
      <v-card
        v-for="notif in filteredNotifs"
        :key="notif.id"
        rounded="xl"
        elevation="1"
        class="mb-3 notif-card"
        :class="{ 'unread-card': !notif.is_read }"
        @click="handleClick(notif)"
        style="cursor: pointer;"
      >
        <v-card-text class="pa-4">
          <div class="d-flex align-start gap-3">

            <!-- Icône -->
            <v-avatar :color="notifColor(notif.type)" size="46">
              <v-icon color="white" size="22">{{ notifIcon(notif.type) }}</v-icon>
            </v-avatar>

            <!-- Contenu -->
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex align-start justify-space-between gap-2">
                <div class="flex-grow-1">

                  <!-- Titre -->
                  <p class="text-body-1 font-weight-bold mb-1"
                     :class="notif.is_read ? 'text-medium-emphasis' : ''">
                    {{ notif.title || 'Notification' }}
                  </p>

                  <!-- Message -->
                  <p class="text-body-2 text-medium-emphasis mb-2">
                    {{ notif.message || buildMessage(notif) }}
                  </p>

                  <!-- Données contextuelles -->
                  <div v-if="notif.data && Object.keys(notif.data).length" class="mb-2">
                    <v-sheet rounded="lg" color="grey-lighten-4" class="pa-2 d-inline-block">
                      <div class="d-flex flex-wrap gap-2">
                        <span v-if="notif.data.reference" class="text-caption">
                          <v-icon size="12" class="mr-1">mdi-identifier</v-icon>
                          <strong>{{ notif.data.reference }}</strong>
                        </span>
                        <span v-if="notif.data.new_status" class="text-caption">
                          <v-icon size="12" class="mr-1">mdi-arrow-right</v-icon>
                          Statut :
                          <v-chip size="x-small" :color="statusColor(notif.data.new_status)" variant="flat" class="ml-1">
                            {{ statusLabel(notif.data.new_status) }}
                          </v-chip>
                        </span>
                        <span v-if="notif.data.semestre" class="text-caption">
                          <v-icon size="12" class="mr-1">mdi-calendar</v-icon>
                          {{ notif.data.semestre }}
                        </span>
                        <span v-if="notif.data.year" class="text-caption">
                          <v-icon size="12" class="mr-1">mdi-school</v-icon>
                          {{ notif.data.year }}
                        </span>
                      </div>
                    </v-sheet>
                  </div>

                  <!-- Chips + date -->
                  <div class="d-flex align-center gap-2 flex-wrap">
                    <v-chip :color="notifColor(notif.type)" size="x-small" variant="tonal">
                      {{ notifTypeLabel(notif.type) }}
                    </v-chip>
                    <span class="text-caption text-medium-emphasis">
                      <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                      {{ timeAgo(notif.created_at) }}
                    </span>
                  </div>

                </div>

                <!-- Badge non lu + bouton marquer lu -->
                <div class="d-flex flex-column align-center gap-1">
                  <v-badge v-if="!notif.is_read" dot color="error" inline />
                  <v-btn
                    v-if="!notif.is_read"
                    icon size="x-small" variant="text" color="grey"
                    @click.stop="markRead(notif)"
                  >
                    <v-icon size="16">mdi-check</v-icon>
                    <v-tooltip activator="parent">Marquer comme lu</v-tooltip>
                  </v-btn>
                </div>

              </div>

              <!-- Lien action -->
              <div v-if="getLink(notif)" class="mt-2">
                <v-btn
                  size="x-small" :color="notifColor(notif.type)" variant="tonal"
                  :prepend-icon="getLinkIcon(notif)"
                  @click.stop="handleClick(notif)"
                >
                  {{ getLinkLabel(notif) }}
                  <v-icon end size="14">mdi-arrow-right</v-icon>
                </v-btn>
              </div>

            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>

  </v-container>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useNotificationsStore } from '@/stores/notifications'

const router     = useRouter()
const toast      = useToast()
const notifStore = useNotificationsStore()

const filterTab      = ref('all')
const markAllLoading = ref(false)

// ── Computed ────────────────────────────────────────────
const loading       = computed(() => notifStore.loading)
const notifications = computed(() => notifStore.notifications)
const unreadCount   = computed(() => notifStore.unreadCount)

const filteredNotifs = computed(() => {
  switch (filterTab.value) {
    case 'unread':
      return notifications.value.filter(n => !n.is_read)
    case 'reclamation':
      return notifications.value.filter(n =>
        n.type?.toLowerCase().includes('reclamation') ||
        n.type?.toLowerCase().includes('status')
      )
    case 'note':
      return notifications.value.filter(n =>
        n.type?.toLowerCase().includes('note')
      )
    default:
      return notifications.value
  }
})

// ── Helpers ─────────────────────────────────────────────
function notifColor(type) {
  if (!type) return 'primary'
  const t = type.toLowerCase()
  if (t.includes('resolved') || t.includes('success'))  return 'success'
  if (t.includes('rejected') || t.includes('error'))    return 'error'
  if (t.includes('escalated') || t.includes('warning')) return 'warning'
  if (t.includes('note'))                               return 'info'
  if (t.includes('in_review'))                          return 'orange'
  if (t.includes('received'))                           return 'teal'
  return 'primary'
}

function notifIcon(type) {
  if (!type) return 'mdi-bell'
  const t = type.toLowerCase()
  if (t.includes('resolved'))   return 'mdi-check-circle'
  if (t.includes('rejected'))   return 'mdi-close-circle'
  if (t.includes('escalated'))  return 'mdi-alert-circle'
  if (t.includes('in_review'))  return 'mdi-eye-clock'
  if (t.includes('received'))   return 'mdi-inbox-arrow-down'
  if (t.includes('note'))       return 'mdi-school'
  return 'mdi-file-document-edit'
}

function notifTypeLabel(type) {
  if (!type) return 'Général'
  const t = type.toLowerCase()
  if (t.includes('resolved'))   return 'Résolue'
  if (t.includes('rejected'))   return 'Rejetée'
  if (t.includes('escalated'))  return 'Escaladée'
  if (t.includes('in_review'))  return 'En révision'
  if (t.includes('received'))   return 'Reçue'
  if (t.includes('status'))     return 'Statut changé'
  if (t.includes('note'))       return 'Notes'
  return 'Réclamation'
}

function statusColor(status) {
  const map = {
    submitted: 'blue', received: 'teal', in_review: 'orange',
    resolved: 'success', rejected: 'error', escalated: 'warning'
  }
  return map[status] ?? 'grey'
}

function statusLabel(status) {
  const map = {
    submitted: 'Soumise', received: 'Reçue', in_review: 'En révision',
    resolved: 'Résolue', rejected: 'Rejetée', escalated: 'Escaladée'
  }
  return map[status] ?? status
}

function buildMessage(notif) {
  const d = notif.data ?? {}
  const t = notif.type?.toLowerCase() ?? ''
  if (t.includes('status') && d.reference) {
    return `La réclamation ${d.reference} est passée au statut : ${statusLabel(d.new_status)}.`
  }
  if (t.includes('note') && d.semestre) {
    return `Les notes du ${d.semestre} (${d.year ?? ''}) ont été publiées.`
  }
  return ''
}

function timeAgo(dateStr) {
  if (!dateStr) return '—'
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1)  return 'À l\'instant'
  if (mins < 60) return `Il y a ${mins} min`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24)  return `Il y a ${hrs} h`
  const days = Math.floor(hrs / 24)
  if (days < 30) return `Il y a ${days} jour(s)`
  return new Date(dateStr).toLocaleDateString('fr-FR')
}

// ── Navigation ───────────────────────────────────────────
function getLink(notif) {
  const d = notif.data ?? {}
  const t = notif.type?.toLowerCase() ?? ''
  if (d.reclamation_id) return `/student/reclamations/${d.reclamation_id}`
  if (t.includes('status') && d.reference) return '/student/reclamations'
  if (t.includes('note')) return '/student/notes'
  return null
}

function getLinkIcon(notif) {
  return notif.type?.toLowerCase().includes('note') ? 'mdi-school' : 'mdi-file-document-edit'
}

function getLinkLabel(notif) {
  return notif.type?.toLowerCase().includes('note') ? 'Voir mes notes' : 'Voir la réclamation'
}

async function handleClick(notif) {
  if (!notif.is_read) {
    await notifStore.markRead(notif.id)
  }
  const link = getLink(notif)
  if (link) router.push(link)
}

// ── Actions ──────────────────────────────────────────────
async function markRead(notif) {
  if (!notif.is_read) await notifStore.markRead(notif.id)
}

async function markAllRead() {
  markAllLoading.value = true
  await notifStore.markAllRead()
  markAllLoading.value = false
  toast.success('Toutes les notifications ont été marquées comme lues.')
}

async function loadData() {
  await notifStore.fetchAll()
}

onMounted(loadData)

</script>

<style scoped>
.notif-card {
  transition: transform .15s, box-shadow .15s;
  border-left: 3px solid transparent;
}
.notif-card:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 20px rgba(0,0,0,.1) !important;
}
.unread-card {
  border-left: 3px solid rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.03);
}
</style>
