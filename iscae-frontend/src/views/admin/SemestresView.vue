<template>
  <v-container fluid class="pa-6">

    <!-- En-tête -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Gestion des Semestres</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Gérez les périodes de réclamations, examens et rattrapages
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        Nouveau Semestre
      </v-btn>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="text-center py-12">
      <v-progress-circular indeterminate color="primary" size="60" />
    </div>

    <!-- Grille des semestres -->
    <v-row v-else>
      <v-col v-for="s in semestres" :key="s.id" cols="12" md="6" xl="4">
        <v-card
          rounded="xl"
          elevation="2"
          class="semestre-card h-100"
          :style="{ borderTop: `4px solid ${s.is_open ? '#4caf50' : '#f44336'}` }"
        >
          <!-- En-tête carte -->
          <v-card-title class="d-flex align-center justify-space-between pt-4 px-5">
            <div class="d-flex align-center gap-2">
              <v-avatar :color="s.is_open ? 'success' : 'error'" size="36">
                <v-icon color="white" size="20">
                  {{ s.is_open ? 'mdi-lock-open' : 'mdi-lock' }}
                </v-icon>
              </v-avatar>
              <span class="text-h6 font-weight-bold">{{ s.code }}</span>
            </div>
            <div class="d-flex align-center gap-2">
              <v-chip
                :color="s.is_open ? 'success' : 'error'"
                size="small"
                variant="flat"
              >
                {{ s.is_open ? 'Ouvert' : 'Fermé' }}
              </v-chip>
              <span class="text-caption text-medium-emphasis">{{ s.academic_year?.replace('-', ' ') }}</span>
            </div>
          </v-card-title>

          <v-card-text class="px-5 pb-2">

            <!-- Dates réclamations -->
            <div class="info-row mb-1">
              <v-icon size="14" class="mr-1" color="medium-emphasis">mdi-calendar-start</v-icon>
              <span class="text-caption text-medium-emphasis">Début réclamations</span>
              <span class="text-caption font-weight-medium ml-auto">{{ formatDate(s.open_at) }}</span>
            </div>
            <div class="info-row mb-3">
              <v-icon size="14" class="mr-1" color="medium-emphasis">mdi-calendar-end</v-icon>
              <span class="text-caption text-medium-emphasis">Fin réclamations</span>
              <span class="text-caption font-weight-medium ml-auto">{{ formatDate(s.close_at) }}</span>
            </div>

            <!-- Barre de progression -->
            <div class="mb-1 d-flex justify-space-between">
              <span class="text-caption text-medium-emphasis">Progression</span>
              <span class="text-caption font-weight-bold" :class="calcProgress(s) >= 100 ? 'text-error' : 'text-primary'">
                {{ calcProgress(s) }}%
              </span>
            </div>
            <v-progress-linear
              :model-value="calcProgress(s)"
              :color="calcProgress(s) >= 100 ? 'error' : 'primary'"
              rounded
              height="6"
              class="mb-2"
            />
            <p class="text-caption text-medium-emphasis mb-3">
              <template v-if="calcProgress(s) >= 100">⏱ Période terminée</template>
              <template v-else-if="daysRemaining(s) > 0">⏳ {{ daysRemaining(s) }} jour(s) restant(s)</template>
            </p>

            <!-- Séparateur -->
            <v-divider class="mb-3" />

            <!-- Status Examens -->
            <div class="period-block mb-2 pa-2 rounded-lg" :class="s.exam_is_open ? 'exam-open' : 'exam-closed'">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                  <v-icon size="16" :color="s.exam_is_open ? 'warning' : 'grey'">mdi-file-document-edit</v-icon>
                  <span class="text-caption font-weight-medium">Examens</span>
                </div>
                <v-chip :color="s.exam_is_open ? 'warning' : 'grey'" size="x-small" variant="flat">
                  {{ s.exam_is_open ? 'Ouvert' : 'Fermé' }}
                </v-chip>
              </div>
              <div v-if="s.exam_open_at || s.exam_close_at" class="text-caption text-medium-emphasis mt-1 ml-6">
                <span v-if="s.exam_is_open && s.exam_open_at">Depuis : {{ formatDate(s.exam_open_at) }}</span>
                <span v-else-if="s.exam_close_at">Fermé le : {{ formatDate(s.exam_close_at) }}</span>
              </div>
            </div>

            <!-- Status Rattrapage -->
            <div class="period-block pa-2 rounded-lg" :class="s.rattrapage_is_open ? 'rattr-open' : 'rattr-closed'">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                  <v-icon size="16" :color="s.rattrapage_is_open ? 'deep-purple' : 'grey'">mdi-refresh-circle</v-icon>
                  <span class="text-caption font-weight-medium">Rattrapage</span>
                </div>
                <v-chip :color="s.rattrapage_is_open ? 'deep-purple' : 'grey'" size="x-small" variant="flat">
                  {{ s.rattrapage_is_open ? 'Ouvert' : 'Fermé' }}
                </v-chip>
              </div>
              <div v-if="s.rattrapage_open_at || s.rattrapage_close_at" class="text-caption text-medium-emphasis mt-1 ml-6">
                <span v-if="s.rattrapage_is_open && s.rattrapage_open_at">Depuis : {{ formatDate(s.rattrapage_open_at) }}</span>
                <span v-else-if="s.rattrapage_close_at">Fermé le : {{ formatDate(s.rattrapage_close_at) }}</span>
              </div>
            </div>

            <!-- Chips stats réclamations -->
            <div class="d-flex gap-2 mt-3 flex-wrap">
              <v-chip size="small" color="blue" variant="tonal" prepend-icon="mdi-message-text">
                {{ s.reclamations_count ?? 0 }} réclamations
              </v-chip>
              <v-chip size="small" color="orange" variant="tonal" prepend-icon="mdi-clock-outline">
                {{ s.pending_count ?? 0 }} en attente
              </v-chip>
            </div>

          </v-card-text>

          <v-divider class="mx-4" />

          <!-- Actions -->
          <v-card-actions class="px-4 py-3 flex-wrap gap-2">

            <!-- Toggle Réclamations -->
            <v-btn
              :color="s.is_open ? 'error' : 'success'"
              size="small"
              variant="flat"
              :loading="toggleLoading[`recl_${s.id}`]"
              @click="askToggle(s, 'reclamation')"
              prepend-icon="mdi-message-alert"
            >
              {{ s.is_open ? 'FERMER' : 'OUVRIR' }}
            </v-btn>

            <!-- Toggle Examens -->
            <v-btn
              :color="s.exam_is_open ? 'error' : 'warning'"
              size="small"
              variant="tonal"
              :loading="toggleLoading[`exam_${s.id}`]"
              @click="askToggle(s, 'exam')"
              prepend-icon="mdi-file-document-edit"
            >
              {{ s.exam_is_open ? 'Fermer Exam' : 'Ouvrir Exam' }}
            </v-btn>

            <!-- Toggle Rattrapage -->
            <v-btn
              :color="s.rattrapage_is_open ? 'error' : 'deep-purple'"
              size="small"
              variant="tonal"
              :loading="toggleLoading[`rattr_${s.id}`]"
              @click="askToggle(s, 'rattrapage')"
              prepend-icon="mdi-refresh-circle"
            >
              {{ s.rattrapage_is_open ? 'Fermer Rattr' : 'Ouvrir Rattr' }}
            </v-btn>

            <v-spacer />

            <!-- Modifier -->
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              color="primary"
              @click="openEditDialog(s)"
            />

          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Dialog confirmation -->
    <v-dialog v-model="confirmDialog.show" max-width="440">
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-5 px-6">
          <v-icon :color="confirmDialog.color" class="mr-2">{{ confirmDialog.icon }}</v-icon>
          {{ confirmDialog.title }}
        </v-card-title>
        <v-card-text class="px-6">{{ confirmDialog.message }}</v-card-text>
        <v-card-actions class="px-6 pb-5">
          <v-spacer />
          <v-btn variant="text" @click="confirmDialog.show = false">Annuler</v-btn>
          <v-btn
            :color="confirmDialog.color"
            variant="flat"
            :loading="confirmDialog.loading"
            @click="confirmToggle"
          >
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog créer / modifier -->
    <v-dialog v-model="formDialog" max-width="520">
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-5 px-6">
          {{ editId ? 'Modifier le semestre' : 'Nouveau semestre' }}
        </v-card-title>
        <v-card-text class="px-6">
          <v-row dense>
            <v-col cols="6">
              <v-text-field v-model="form.code" label="Code (ex: S1)" variant="outlined" density="compact" />
            </v-col>
            <v-col cols="6">
              <v-text-field v-model="form.academic_year" label="Année (ex: 2024-2025)" variant="outlined" density="compact" />
            </v-col>
            <v-col cols="12">
              <v-text-field v-model="form.label" label="Libellé" variant="outlined" density="compact" />
            </v-col>
            <v-col cols="6">
              <v-text-field v-model.number="form.ordre" label="Ordre" type="number" variant="outlined" density="compact" />
            </v-col>
            <v-col cols="6">
              <v-select
                v-model="form.niveau_id"
                :items="niveaux"
                item-title="label"
                item-value="id"
                label="Niveau"
                variant="outlined"
                density="compact"
              />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="px-6 pb-5">
          <v-spacer />
          <v-btn variant="text" @click="formDialog = false">Annuler</v-btn>
          <v-btn color="primary" variant="flat" :loading="formLoading" @click="submitForm">
            {{ editId ? 'Modifier' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000" location="bottom right" rounded="xl">
      {{ snack.text }}
    </v-snackbar>

  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── State ───────────────────────────────────────────────
const loading      = ref(false)
const semestres    = ref([])
const niveaux      = ref([])
const toggleLoading = ref({})

const confirmDialog = ref({
  show: false, loading: false,
  title: '', message: '', color: 'primary', icon: '',
  semestre: null, kind: ''
})

const formDialog  = ref(false)
const formLoading = ref(false)
const editId      = ref(null)
const form        = ref({ code: '', label: '', academic_year: '', ordre: 1, niveau_id: null })
const snack       = ref({ show: false, text: '', color: 'success' })

// ── Computed ────────────────────────────────────────────
const currentYear = computed(() => new Date().getFullYear())

// ── Helpers ─────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function calcProgress(s) {
  if (!s.open_at || !s.close_at) return 0
  const start = new Date(s.open_at).getTime()
  const end   = new Date(s.close_at).getTime()
  const now   = Date.now()
  if (now >= end)   return 100
  if (now <= start) return 0
  return Math.round(((now - start) / (end - start)) * 100)
}

function daysRemaining(s) {
  if (!s.close_at) return 0
  const diff = new Date(s.close_at).getTime() - Date.now()
  return Math.max(0, Math.ceil(diff / 86400000))
}

function notify(text, color = 'success') {
  snack.value = { show: true, text, color }
}

// ── Chargement ──────────────────────────────────────────
async function loadData() {
  loading.value = true
  try {
    const [sRes, nRes] = await Promise.all([
      api.get('/admin/semestres'),
      api.get('/admin/niveaux').catch(() => ({ data: { data: [] } }))
    ])
    semestres.value = sRes.data?.data ?? sRes.data ?? []
    niveaux.value   = nRes.data?.data ?? nRes.data ?? []
  } catch (e) {
    notify('Erreur lors du chargement.', 'error')
  } finally {
    loading.value = false
  }
}

// ── Toggle ──────────────────────────────────────────────
function askToggle(s, kind) {
  const isOpen = kind === 'reclamation' ? s.is_open
               : kind === 'exam'        ? s.exam_is_open
               :                          s.rattrapage_is_open

  const meta = {
    reclamation: { label: 'Réclamations', icon: 'mdi-message-alert',      color: isOpen ? 'error' : 'success'     },
    exam:        { label: 'Examens',       icon: 'mdi-file-document-edit', color: isOpen ? 'error' : 'warning'     },
    rattrapage:  { label: 'Rattrapage',    icon: 'mdi-refresh-circle',     color: isOpen ? 'error' : 'deep-purple' },
  }[kind]

  confirmDialog.value = {
    show: true, loading: false,
    semestre: s, kind,
    color:   meta.color,
    icon:    meta.icon,
    title:   `${isOpen ? 'Fermer' : 'Ouvrir'} – ${meta.label}`,
    message: `Confirmer l'action sur le semestre ${s.code} (${s.academic_year}) ?`
  }
}

async function confirmToggle() {
  const { semestre, kind } = confirmDialog.value
  const loadKey  = kind === 'reclamation' ? `recl_${semestre.id}` : kind === 'exam' ? `exam_${semestre.id}` : `rattr_${semestre.id}`
  const endpoint = kind === 'reclamation' ? `/admin/semestres/${semestre.id}/toggle`
                 : kind === 'exam'        ? `/admin/semestres/${semestre.id}/toggle-exam`
                 :                          `/admin/semestres/${semestre.id}/toggle-rattrapage`

  confirmDialog.value.loading    = true
  toggleLoading.value[loadKey]   = true

  try {
    const res     = await api.put(endpoint)
    const updated = res.data?.data
    if (updated) {
      const idx = semestres.value.findIndex(s => s.id === semestre.id)
      if (idx !== -1) semestres.value[idx] = { ...semestres.value[idx], ...updated }
    }
    notify(res.data?.message ?? 'Mis à jour avec succès.')
    confirmDialog.value.show = false
  } catch (e) {
    notify(e.response?.data?.message ?? 'Erreur lors de la mise à jour.', 'error')
  } finally {
    confirmDialog.value.loading  = false
    toggleLoading.value[loadKey] = false
  }
}

// ── Formulaire ──────────────────────────────────────────
function openCreateDialog() {
  editId.value = null
  form.value   = { code: '', label: '', academic_year: '', ordre: 1, niveau_id: null }
  formDialog.value = true
}

function openEditDialog(s) {
  editId.value = s.id
  form.value   = {
    code:          s.code,
    label:         s.label,
    academic_year: s.academic_year,
    ordre:         s.order_index,
    niveau_id:     s.niveau_id
  }
  formDialog.value = true
}

async function submitForm() {
  formLoading.value = true
  const payload = {
    code:          form.value.code,
    label:         form.value.label,
    academic_year: form.value.academic_year,
    order_index:   form.value.ordre,
    niveau_id:     form.value.niveau_id,
  }
  try {
    if (editId.value) {
      await api.put(`/admin/semestres/${editId.value}`, payload)
      notify('Semestre mis à jour.')
    } else {
      await api.post('/admin/semestres', payload)
      notify('Semestre créé.')
    }
    formDialog.value = false
    await loadData()
  } catch (e) {
    notify(e.response?.data?.message ?? 'Erreur lors de la sauvegarde.', 'error')
  } finally {
    formLoading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.semestre-card {
  transition: box-shadow .2s, transform .2s;
}
.semestre-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,.14) !important;
  transform: translateY(-2px);
}
.info-row {
  display: flex;
  align-items: center;
}
.period-block {
  border: 1px solid rgba(0,0,0,.08);
}
.exam-open   { background: rgba(255, 152, 0, .08); }
.exam-closed { background: rgba(0,0,0,.03); }
.rattr-open  { background: rgba(103, 58, 183, .08); }
.rattr-closed{ background: rgba(0,0,0,.03); }
</style>
