<template>
  <div>
    <!-- HEADER -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="d-flex align-center ga-3">
        <v-btn icon variant="text" color="primary" @click="router.back()">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <div>
          <h2 class="text-h5 font-weight-bold text-primary">
            Détail Réclamation
            <v-chip color="primary" size="small" variant="tonal" class="ml-2">
              {{ rec?.reference || `#${String(route.params.id).padStart(4,'0')}` }}
            </v-chip>
          </h2>
          <p class="text-body-2 text-grey mt-1">Suivez l'état de votre réclamation</p>
        </div>
      </div>
      <v-chip v-if="rec" :color="statusColor(rec.status)" size="large" variant="tonal">
        <v-icon start>{{ statusIcon(rec.status) }}</v-icon>
        {{ statusLabel(rec.status) }}
      </v-chip>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center pa-12">
      <v-progress-circular indeterminate color="primary" size="56" />
    </div>

    <v-row v-else-if="rec">
      <!-- LEFT COLUMN -->
      <v-col cols="12" md="8">

        <!-- Main Info Card -->
        <v-card rounded="xl" elevation="2" class="mb-4">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-file-document</v-icon>
            Informations de la Réclamation
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <v-row dense>
              <v-col cols="6" md="3">
                <p class="text-caption text-grey mb-1">Type</p>
                <v-chip :color="typeColor(rec.type)" size="small" variant="tonal">
                  <v-icon start size="12">{{ typeIcon(rec.type) }}</v-icon>
                  {{ typeLabel(rec.type) }}
                </v-chip>
              </v-col>
              <v-col cols="6" md="3">
                <p class="text-caption text-grey mb-1">Année académique</p>
                <p class="text-body-2 font-weight-medium">{{ rec.academic_year || '—' }}</p>
              </v-col>
              <v-col cols="6" md="3">
                <p class="text-caption text-grey mb-1">Note actuelle</p>
                <v-chip color="error" size="small" variant="tonal" class="font-weight-bold">
                  {{ rec.note_actuelle ?? '—' }} / 20
                </v-chip>
              </v-col>
              <v-col cols="6" md="3">
                <p class="text-caption text-grey mb-1">Note réclamée</p>
                <v-chip
                  v-if="rec.note_reclamee !== null && rec.note_reclamee !== undefined"
                  color="info" size="small" variant="tonal" class="font-weight-bold">
                  {{ rec.note_reclamee }} / 20
                </v-chip>
                <span v-else class="text-body-2 text-grey">—</span>
              </v-col>

              <v-col cols="12" class="mt-4">
                <p class="text-caption text-grey mb-1">Module</p>
                <p class="text-body-1 font-weight-bold">
                  {{ rec.module?.name || '—' }}
                  <span class="text-caption text-grey font-weight-regular ml-2">
                    {{ rec.module?.code || '' }}
                  </span>
                </p>
              </v-col>

              <v-col cols="12">
                <p class="text-caption text-grey mb-1">Semestre</p>
                <p class="text-body-2">
                  {{ rec.semestre?.label || '—' }}
                  <span class="text-caption text-grey ml-1">
                    {{ rec.semestre?.academic_year || '' }}
                  </span>
                </p>
              </v-col>

              <!-- Justification -->
              <v-col cols="12" class="mt-4">
                <v-divider class="mb-3" />
                <p class="text-caption text-grey mb-2 d-flex align-center ga-1">
                  <v-icon size="14">mdi-text-box</v-icon>
                  Votre justification
                </p>
                <v-sheet rounded="lg" color="grey-lighten-5" class="pa-4">
                  <p class="text-body-2" style="white-space: pre-wrap;">
                    {{ rec.justification || 'Aucune justification fournie.' }}
                  </p>
                </v-sheet>
              </v-col>

              <!-- Admin Response -->
              <v-col v-if="rec.admin_response" cols="12" class="mt-3">
                <p class="text-caption text-grey mb-2 d-flex align-center ga-1">
                  <v-icon size="14" color="primary">mdi-shield-account</v-icon>
                  Réponse de l'administration
                </p>
                <v-sheet rounded="lg" color="primary" class="pa-4">
                  <p class="text-body-2 text-white" style="white-space: pre-wrap;">
                    {{ rec.admin_response }}
                  </p>
                </v-sheet>
              </v-col>

              <!-- En attente -->
              <v-col
                v-else-if="['submitted','received','in_review'].includes(rec.status)"
                cols="12" class="mt-3">
                <v-alert type="info" variant="tonal" rounded="lg" icon="mdi-clock-outline">
                  Votre réclamation est en cours de traitement. Vous serez notifié(e)
                  dès qu'une décision sera prise.
                </v-alert>
              </v-col>
            </v-row>

            <!-- Escalation -->
            <v-alert v-if="rec.is_escalated" type="error" variant="tonal"
              rounded="lg" class="mt-4" icon="mdi-alert-circle">
              Votre réclamation a été escaladée pour examen approfondi.
            </v-alert>
          </v-card-text>
        </v-card>

        <!-- Pièces jointes -->
        <v-card v-if="rec.attachments?.length" rounded="xl" elevation="2" class="mb-4">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-paperclip</v-icon>
            Pièces Jointes ({{ rec.attachments.length }})
          </v-card-title>
          <v-divider />
          <v-list density="compact" class="pa-2">
            <v-list-item
              v-for="att in rec.attachments"
              :key="att.id"
              rounded="lg"
              :href="att.url"
              target="_blank"
              class="mb-1">
              <template #prepend>
                <v-icon :color="fileIcon(att.mime_type).color">
                  {{ fileIcon(att.mime_type).icon }}
                </v-icon>
              </template>
              <v-list-item-title class="text-body-2">{{ att.name }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ formatFileSize(att.file_size) }}
              </v-list-item-subtitle>
              <template #append>
                <v-icon size="16" color="grey">mdi-download</v-icon>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Timeline Historique -->
        <v-card rounded="xl" elevation="2">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-timeline</v-icon>Historique des Statuts
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <div v-if="history.length === 0" class="text-center text-grey pa-4">
              <v-icon size="40" color="grey-lighten-2">mdi-timeline-outline</v-icon>
              <p class="text-body-2 mt-2">Aucun historique disponible</p>
            </div>
            <v-timeline v-else density="compact" side="end">
              <v-timeline-item
                v-for="h in history"
                :key="h.id"
                :dot-color="statusColor(h.new_status)"
                size="small">
                <div class="d-flex align-center justify-space-between">
                  <div>
                    <p class="text-body-2 font-weight-medium">
                      {{ statusLabel(h.new_status) }}
                    </p>
                    <p v-if="h.comment" class="text-caption text-grey">
                      {{ h.comment }}
                    </p>
                  </div>
                  <!-- ✅ changed_at au lieu de created_at -->
                  <span class="text-caption text-grey ml-4">
                    {{ formatDate(h.changed_at || h.created_at) }}
                  </span>
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>

      </v-col>

      <!-- RIGHT COLUMN -->
      <v-col cols="12" md="4">

        <!-- Status Card -->
        <v-card rounded="xl" elevation="2" class="mb-4"
          :style="`border-top: 3px solid rgb(var(--v-theme-${statusColor(rec.status)}))`">
          <v-card-text class="pa-5 text-center">
            <v-avatar :color="statusColor(rec.status)" size="56" class="mb-3">
              <v-icon color="white" size="28">{{ statusIcon(rec.status) }}</v-icon>
            </v-avatar>
            <p class="text-h6 font-weight-bold" :class="`text-${statusColor(rec.status)}`">
              {{ statusLabel(rec.status) }}
            </p>
            <p class="text-body-2 text-grey mt-1">{{ statusDescription(rec.status) }}</p>
          </v-card-text>
        </v-card>

        <!-- Dates -->
        <v-card rounded="xl" elevation="2" class="mb-4">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-calendar</v-icon>Dates
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <div class="d-flex flex-column ga-3">
              <div>
                <p class="text-caption text-grey">Soumise le</p>
                <p class="text-body-2 font-weight-medium">{{ formatDate(rec.created_at) }}</p>
              </div>
              <div>
                <p class="text-caption text-grey">Dernière mise à jour</p>
                <p class="text-body-2 font-weight-medium">{{ formatDate(rec.updated_at) }}</p>
              </div>
              <div v-if="rec.resolved_at">
                <p class="text-caption text-grey">Résolue le</p>
                <p class="text-body-2 font-weight-medium text-success">
                  {{ formatDate(rec.resolved_at) }}
                </p>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Actions -->
        <v-card rounded="xl" elevation="2">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-gesture-tap</v-icon>Actions
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <div class="d-flex flex-column ga-2">
              <v-btn color="primary" variant="tonal" block
                prepend-icon="mdi-arrow-left"
                @click="router.push('/student/reclamations')">
                Retour aux réclamations
              </v-btn>
              <v-btn color="secondary" variant="tonal" block
                prepend-icon="mdi-plus"
                to="/student/reclamations/new">
                Nouvelle réclamation
              </v-btn>
            </div>
          </v-card-text>
        </v-card>

      </v-col>
    </v-row>

    <!-- Not Found -->
    <div v-else class="text-center pa-12 text-grey">
      <v-icon size="80" color="grey-lighten-2" class="mb-3">mdi-file-alert</v-icon>
      <p class="text-h6">Réclamation introuvable</p>
      <v-btn class="mt-4" color="primary" @click="router.back()">Retour</v-btn>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const router = useRouter()
const route  = useRoute()
const toast  = useToast()

const loading = ref(false)
const rec     = ref(null)
const history = ref([])

// ── Helpers ────────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

function formatFileSize(bytes) {
  if (!bytes) return ''
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}

function fileIcon(mimeType) {
  if (mimeType === 'application/pdf')
    return { icon: 'mdi-file-pdf-box', color: 'error' }
  if (mimeType?.startsWith('image/'))
    return { icon: 'mdi-file-image', color: 'primary' }
  return { icon: 'mdi-file', color: 'grey' }
}

function statusColor(s) {
  return {
    submitted:  'info',
    received:   'primary',
    in_review:  'warning',
    resolved:   'success',
    rejected:   'error',
    escalated:  'deep-orange',
  }[s] || 'grey'
}

function statusLabel(s) {
  return {
    submitted: 'Soumise',
    received:  'Reçue',
    in_review: 'En révision',
    resolved:  'Résolue',
    rejected:  'Rejetée',
    escalated: 'Escaladée',
  }[s] || s
}

function statusIcon(s) {
  return {
    submitted: 'mdi-send',
    received:  'mdi-inbox-arrow-down',
    in_review: 'mdi-eye-clock',
    resolved:  'mdi-check-circle',
    rejected:  'mdi-close-circle',
    escalated: 'mdi-alert-circle',
  }[s] || 'mdi-help'
}

function statusDescription(s) {
  return {
    submitted: 'Votre réclamation a été soumise et attend d\'être traitée.',
    received:  'Votre réclamation a été reçue par l\'administration.',
    in_review: 'Votre réclamation est en cours d\'examen.',
    resolved:  'Votre réclamation a été traitée et résolue.',
    rejected:  'Votre réclamation a été examinée et rejetée.',
    escalated: 'Votre réclamation a été transmise pour examen approfondi.',
  }[s] || ''
}

// ✅ Types mis à jour : cc, examen, rattrapage
function typeColor(t) {
  return {
    cc:         'purple',
    examen:     'indigo',
    rattrapage: 'orange',
    absence:    'red',
    other:      'grey',
  }[t] || 'grey'
}

function typeLabel(t) {
  return {
    cc:         'Devoir (CC)',
    examen:     'Examen',
    rattrapage: 'Rattrapage',
    absence:    'Absence',
    other:      'Autre',
  }[t] || t
}

function typeIcon(t) {
  return {
    cc:         'mdi-pencil',
    examen:     'mdi-file-check',
    rattrapage: 'mdi-refresh',
    absence:    'mdi-calendar-remove',
    other:      'mdi-help-circle',
  }[t] || 'mdi-help'
}

// ── Load data ──────────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true
  try {
    const res  = await api.get(`/student/reclamations/${route.params.id}`)
    const data = res.data.data || res.data
    rec.value     = data
    history.value = data.history || []
  } catch (e) {
    toast.error('Erreur de chargement de la réclamation.')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>
