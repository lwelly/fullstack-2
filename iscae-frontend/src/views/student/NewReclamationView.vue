<template>
  <v-container class="reclamation-container" max-width="860">

    <!-- ───── En-tête ───── -->
    <div class="page-header mb-6">
      <div class="d-flex align-center gap-3">
        <v-btn icon variant="text" @click="$router.back()">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <div>
          <h1 class="text-h5 font-weight-bold">Réclamation</h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Étape {{ step }} sur 3 — {{ stepTitles[step - 1] }}
          </p>
        </div>
      </div>

      <!-- Barre de progression -->
      <v-progress-linear
        :model-value="(step / 3) * 100"
        color="primary"
        height="4"
        rounded
        class="mt-4"
      />
      <div class="d-flex justify-space-between mt-1">
        <span
          v-for="(title, i) in stepTitles"
          :key="i"
          class="text-caption"
          :class="step > i ? 'text-primary font-weight-bold' : 'text-medium-emphasis'"
        >
          {{ title }}
        </span>
      </div>
    </div>

    <!-- ───── Alerte niveau ───── -->
    <v-alert
      v-if="niveauCode"
      type="info"
      variant="tonal"
      density="compact"
      class="mb-4"
      icon="mdi-school-outline"
    >
      Vous êtes en <strong>{{ niveauCode }}</strong> 
    </v-alert>

    <!-- ───── Alerte si aucun semestre ouvert ───── -->
    <v-alert
      v-if="!loadingSemestres && semestres.length === 0"
      type="warning"
      variant="tonal"
      class="mb-4"
      icon="mdi-calendar-remove-outline"
    >
      Aucun semestre n'est actuellement ouvert aux réclamations pour votre niveau
      <strong>{{ niveauCode }}</strong>. Contactez l'administration.
    </v-alert>

    <!-- ───── Alerte globale erreur ───── -->
    <v-alert
      v-if="globalError"
      type="error"
      variant="tonal"
      closable
      class="mb-4"
      @click:close="globalError = ''"
    >
      {{ globalError }}
    </v-alert>

    <!-- ══════════════════════════════════════════════════════════
         ÉTAPE 1 — Type, Semestre, Module, Notes
    ══════════════════════════════════════════════════════════ -->
    <v-card v-if="step === 1" variant="elevated" rounded="lg" class="pa-6 mb-4">
      <div class="text-h6 font-weight-bold mb-1">Informations de la réclamation</div>
      <div class="text-body-2 text-medium-emphasis mb-5">
        Sélectionnez le semestre, le type et le module concerné
      </div>

      <!-- Semestre -->
      <v-select
        v-model="form.semestre_id"
        :items="semestres"
        item-title="label"
        item-value="id"
        label="Semestre *"
        variant="outlined"
        density="comfortable"
        :loading="loadingSemestres"
        :error-messages="errors.semestre_id"
        prepend-inner-icon="mdi-calendar-clock"
        class="mb-4"
        clearable
        no-data-text="Aucun semestre disponible pour votre niveau"
      >
        <template #item="{ props, item }">
          <v-list-item v-bind="props">
            <template #append>
              <div class="d-flex gap-1 flex-wrap">
                <v-chip
                  v-for="t in item.raw.available_types"
                  :key="t"
                  size="x-small"
                  :color="typeChipColor(t)"
                  variant="tonal"
                >
                  {{ typeLabel(t) }}
                </v-chip>
              </div>
            </template>
          </v-list-item>
        </template>
      </v-select>

      <!-- Type de réclamation -->
      <div class="mb-4">
        <div class="text-body-2 font-weight-medium mb-2">
          Type de réclamation *
          <span v-if="errors.type" class="text-error text-caption ml-2">{{ errors.type }}</span>
        </div>
        <div
          v-if="!form.semestre_id"
          class="text-body-2 text-medium-emphasis pa-3 rounded border"
        >
          <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
          Sélectionnez d'abord un semestre
        </div>
        <div v-else-if="reclamationTypes.length === 0" class="text-body-2 text-error pa-3 rounded border">
          <v-icon size="small" class="mr-1">mdi-alert-circle-outline</v-icon>
          Aucun type disponible pour ce semestre
        </div>
        <div v-else class="type-grid">
          <div
            v-for="t in reclamationTypes"
            :key="t.value"
            class="type-card"
            :class="{ 'type-card--active': form.type === t.value }"
            :style="form.type === t.value ? typeChipStyle(t.value) : {}"
            @click="selectType(t.value)"
          >
            <!-- L'icône passe en blanc uniquement s'il est sélectionné, sinon garde sa couleur -->
            <v-icon :color="form.type === t.value ? 'white' : t.color" size="28" class="mb-1">
              {{ t.icon }}
            </v-icon>
            <div class="type-card__label">{{ t.label }}</div>
            <div class="type-card__desc">{{ t.desc }}</div>
          </div>
        </div>
      </div>

      <!-- Module -->
      <v-select
        v-model="form.module_id"
        :items="modules"
        item-title="name"
        item-value="id"
        label="Module concerné *"
        variant="outlined"
        density="comfortable"
        :loading="loadingModules"
        :disabled="!form.semestre_id"
        :error-messages="errors.module_id"
        prepend-inner-icon="mdi-book-open-variant"
        class="mb-4"
        clearable
        no-data-text="Aucun module disponible"
      >
        <template #item="{ props, item }">
          <v-list-item v-bind="props" :subtitle="item.raw.code" />
        </template>
      </v-select>

      <!-- Note actuelle -->
      <v-text-field
        v-model="form.note_actuelle"
        label="Note actuelle *"
        type="number"
        min="0"
        max="20"
        step="0.25"
        :rules="noteRules"
        :error-messages="errors.note_actuelle"
        variant="outlined"
        density="comfortable"
        suffix="/ 20"
        hint="Entre 0 et 20 (ex: 12.5)"
        persistent-hint
        prepend-inner-icon="mdi-numeric"
        class="mb-4"
        @input="form.note_actuelle = clampNote($event.target.value)"
      />

      <!-- Note réclamée (CC uniquement) -->
      <v-text-field
        v-if="form.type === 'cc'"
        v-model="form.note_reclamee"
        label="Note réclamée"
        type="number"
        min="0"
        max="20"
        step="0.25"
        :rules="noteReclameRules"
        :error-messages="errors.note_reclamee"
        variant="outlined"
        density="comfortable"
        suffix="/ 20"
        hint="Note que vous estimez mériter (optionnel)"
        persistent-hint
        prepend-inner-icon="mdi-numeric-positive-1"
        class="mb-2"
        @input="form.note_reclamee = clampNote($event.target.value)"
      />
    </v-card>

    <!-- ══════════════════════════════════════════════════════════
         ÉTAPE 2 — Justification & Document
    ══════════════════════════════════════════════════════════ -->
    <v-card v-if="step === 2" variant="elevated" rounded="lg" class="pa-6 mb-4">
      <div class="text-h6 font-weight-bold mb-1">Justification</div>
      <div class="text-body-2 text-medium-emphasis mb-5">
        Expliquez clairement le motif de votre réclamation.
      </div>

      <!-- Justification -->
      <v-textarea
        v-model="form.justification"
        label="Justification *"
        variant="outlined"
        rows="5"
        counter="1000"
        maxlength="1000"
        :rules="justifRules"
        :error-messages="errors.justification"
        prepend-inner-icon="mdi-text-box-edit-outline"
        hint="Minimum 20 caractères"
        persistent-hint
        class="mb-5"
        auto-grow
      />
    </v-card>

    <!-- ══════════════════════════════════════════════════════════
         ÉTAPE 3 — Confirmation
    ══════════════════════════════════════════════════════════ -->
    <v-card v-if="step === 3" variant="elevated" rounded="lg" class="pa-6 mb-4">
      <div class="text-h6 font-weight-bold mb-1">Récapitulatif</div>
      <div class="text-body-2 text-medium-emphasis mb-5">
        Vérifiez les informations avant de soumettre
      </div>

      <div class="recap-grid mb-5">
        <div class="recap-item">
          <span class="recap-label">Semestre</span>
          <span class="recap-value">{{ semLabel }}</span>
        </div>
        <div class="recap-item">
          <span class="recap-label">Type</span>
          <v-chip size="small" :color="typeChipColor(form.type)" variant="tonal">
            {{ typeLabel(form.type) }}
          </v-chip>
        </div>
        <div class="recap-item">
          <span class="recap-label">Module</span>
          <span class="recap-value">{{ moduleLabel }}</span>
        </div>
        <div class="recap-item">
          <span class="recap-label">Note actuelle</span>
          <span class="recap-value font-weight-bold">{{ form.note_actuelle }} / 20</span>
        </div>
        <div
          v-if="form.type === 'cc' && form.note_reclamee !== '' && form.note_reclamee !== null"
          class="recap-item"
        >
          <span class="recap-label">Note réclamée</span>
          <span class="recap-value font-weight-bold">{{ form.note_reclamee }} / 20</span>
        </div>
        <div class="recap-item recap-item--full">
          <span class="recap-label">Justification</span>
          <span class="recap-value">{{ form.justification }}</span>
        </div>
        <div v-if="docFile" class="recap-item">
          <span class="recap-label">Pièce jointe</span>
          <span class="recap-value">
            <v-icon size="small" :color="fileIconColor(docFile)">{{ fileIcon(docFile) }}</v-icon>
            {{ docFile.name }} ({{ formatSize(docFile.size) }})
          </span>
        </div>
      </div>

      <!-- Confirmation obligatoire -->
      <v-checkbox
        v-model="confirmed"
        color="primary"
        :error="!confirmed && submitting"
        class="mb-2"
      >
        <template #label>
          <span class="text-body-2">
            Je confirme que les informations ci-dessus sont exactes et je prends
            la responsabilité de cette réclamation.
          </span>
        </template>
      </v-checkbox>
    </v-card>

    <!-- ───── Navigation ───── -->
    <div class="nav-bar">
      <v-btn
        v-if="step > 1"
        variant="outlined"
        color="primary"
        prepend-icon="mdi-arrow-left"
        :disabled="submitting"
        @click="step--"
      >
        Précédent
      </v-btn>
      <v-spacer />
      <v-btn
        v-if="step < 3"
        color="primary"
        append-icon="mdi-arrow-right"
        :disabled="!canNext"
        @click="goNext"
      >
        Suivant
      </v-btn>
      <v-btn
        v-else
        color="success"
        append-icon="mdi-check-circle"
        :loading="submitting"
        :disabled="!confirmed || submitting"
        @click="submit"
      >
        Soumettre
      </v-btn>
    </div>

    <!-- ───── Snackbar succès ───── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      :timeout="snack.color === 'success' ? 2000 : 5000"
      location="top right"
      rounded="lg"
    >
      <v-icon class="mr-2">
        {{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
      </v-icon>
      {{ snack.text }}
    </v-snackbar>

  </v-container>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router = useRouter()

// ── État ──────────────────────────────────────────────────────────────────────
const step          = ref(1)
const stepTitles    = ['Type & Module', 'Justification', 'Confirmation']
const submitting    = ref(false)
const confirmed     = ref(false)
const isDragging    = ref(false)
const globalError   = ref('')
const errors        = ref({})
const fileInput     = ref(null)
const docFile       = ref(null)
const snack         = ref({ show: false, text: '', color: 'success' })
const niveauCode    = ref('')

const semestres        = ref([])
const modules          = ref([])
const loadingSemestres = ref(false)
const loadingModules   = ref(false)

const form = ref({
  semestre_id   : null,
  type          : '',
  module_id     : null,
  note_actuelle : '',
  note_reclamee : '',
  justification : '',
})

const NIVEAU_SEMESTRES = {
  L1: ['S1', 'S2'],
  L2: ['S1', 'S2', 'S3', 'S4'],
  L3: ['S3', 'S4', 'S5', 'S6'],
}

// ── Types disponibles ─────────────────────────────────────────────────────────
// Les couleurs des bordures actives utilisent des hexadécimaux standards. 
// Vuetify appliquera sa propre couleur de texte adéquate.
const ALL_TYPES = [
  {
    value : 'cc',
    label : 'Devoir',
    desc  : 'Contrôle continu',
    icon  : 'mdi-pencil-circle-outline',
    color : 'blue',
    bg    : '#1565C0',
  },
  {
    value : 'examen',
    label : 'Examen',
    desc  : 'Examen de fin de semestre',
    icon  : 'mdi-file-document-edit-outline',
    color : 'orange',
    bg    : '#E65100',
  },
  {
    value : 'rattrapage',
    label : 'Rattrapage',
    desc  : 'Session de rattrapage',
    icon  : 'mdi-refresh-circle',
    color : 'purple',
    bg    : '#6A1B9A',
  },
]

// ── Computed ──────────────────────────────────────────────────────────────────
const currentSemestre = computed(() =>
  semestres.value.find(s => s.id === form.value.semestre_id) ?? null
)

const reclamationTypes = computed(() => {
  if (!currentSemestre.value) return []
  const avail = currentSemestre.value.available_types ?? []
  return ALL_TYPES.filter(t => avail.includes(t.value))
})

const semLabel = computed(() =>
  currentSemestre.value?.label ?? '—'
)

const moduleLabel = computed(() =>
  modules.value.find(m => m.id === form.value.module_id)?.name ?? '—'
)

const allowedSemestreLabels = computed(() => {
  const codes = NIVEAU_SEMESTRES[niveauCode.value] ?? []
  return codes.join(', ')
})

const canNext = computed(() => {
  if (step.value === 1) {
    const noteOk = form.value.note_actuelle !== '' &&
                   !isNaN(Number(form.value.note_actuelle)) &&
                   Number(form.value.note_actuelle) >= 0 &&
                   Number(form.value.note_actuelle) <= 20
    const noteRecOk = form.value.type !== 'cc' ||
                      form.value.note_reclamee === '' ||
                      form.value.note_reclamee === null ||
                      (!isNaN(Number(form.value.note_reclamee)) &&
                       Number(form.value.note_reclamee) >= 0 &&
                       Number(form.value.note_reclamee) <= 20)
    return !!form.value.semestre_id &&
           !!form.value.type &&
           !!form.value.module_id &&
           noteOk &&
           noteRecOk
  }
  if (step.value === 2) {
    return form.value.justification.trim().length >= 20
  }
  return true
})

// ── Règles de validation ──────────────────────────────────────────────────────
const noteRules = [
  v => (v !== '' && v !== null && v !== undefined) || 'La note est obligatoire',
  v => !isNaN(Number(v))                           || 'Doit être un nombre',
  v => Number(v) >= 0                              || 'La note minimale est 0',
  v => Number(v) <= 20                             || 'La note maximale est 20',
]

const noteReclameRules = [
  v => (v === '' || v === null || !isNaN(Number(v))) || 'Doit être un nombre',
  v => (v === '' || v === null || Number(v) >= 0)    || 'La note minimale est 0',
  v => (v === '' || v === null || Number(v) <= 20)   || 'La note maximale est 20',
]

const justifRules = [
  v => (v && v.trim().length >= 20)   || 'Minimum 20 caractères requis',
  v => (v && v.trim().length <= 1000) || 'Maximum 1000 caractères',
]

function clampNote(val) {
  if (val === '' || val === null || val === undefined) return val
  const n = parseFloat(val)
  if (isNaN(n)) return ''
  if (n < 0)    return '0'
  if (n > 20)   return '20'
  return String(Math.round(n * 100) / 100)
}

function typeLabel(val)     { return ALL_TYPES.find(t => t.value === val)?.label ?? val }
function typeChipColor(val) { return ALL_TYPES.find(t => t.value === val)?.color ?? 'grey' }
function typeChipStyle(val) {
  const t = ALL_TYPES.find(x => x.value === val)
  return t ? { background: t.bg, color: '#ffffff' } : {}
}
function selectType(val) {
  form.value.type = val
  if (val !== 'cc') form.value.note_reclamee = ''
  delete errors.value.type
}

function fileIcon(f) {
  if (!f) return 'mdi-file'
  if (f.type === 'application/pdf')  return 'mdi-file-pdf-box'
  if (f.type.startsWith('image/'))   return 'mdi-file-image'
  return 'mdi-file'
}
// Ajustement subtil pour le mode sombre
function fileIconColor(f) {
  if (!f) return 'grey'
  if (f.type === 'application/pdf')  return 'red-accent-2'
  if (f.type.startsWith('image/'))   return 'blue-accent-2'
  return 'grey'
}
function formatSize(bytes) {
  if (bytes < 1024)        return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}

const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
const MAX_SIZE      = 5 * 1024 * 1024

function addFile(file) {
  if (!ALLOWED_TYPES.includes(file.type)) {
    errors.value.document = 'Type non accepté (PDF, JPG, PNG uniquement)'
    return
  }
  if (file.size > MAX_SIZE) {
    errors.value.document = 'Fichier trop volumineux (max 5 Mo)'
    return
  }
  delete errors.value.document
  docFile.value = file
}
function handleFileSelect(e) {
  const f = e.target.files?.[0]
  if (f) addFile(f)
}
function handleDrop(e) {
  isDragging.value = false
  const f = e.dataTransfer.files?.[0]
  if (f) addFile(f)
}
function removeDoc() {
  docFile.value = null
  delete errors.value.document
  if (fileInput.value) fileInput.value.value = ''
}

function notify(text, color = 'success') {
  snack.value = { show: true, text, color }
}

async function loadSemestres() {
  loadingSemestres.value = true
  try {
    const res = await api.get('/student/semestres')
    semestres.value  = res.data?.data    ?? []
    niveauCode.value = res.data?.niveau  ?? ''
  } catch {
    notify('Impossible de charger les semestres', 'error')
  } finally {
    loadingSemestres.value = false
  }
}

async function loadModules(semestreId) {
  if (!semestreId) { modules.value = []; return }
  loadingModules.value = true
  try {
    const res = await api.get('/student/modules', { params: { semestre_id: semestreId } })
    modules.value = res.data?.data ?? []
  } catch {
    notify('Impossible de charger les modules', 'error')
  } finally {
    loadingModules.value = false
  }
}

watch(() => form.value.semestre_id, (id) => {
  form.value.type          = ''
  form.value.module_id     = null
  form.value.note_reclamee = ''
  errors.value             = {}
  loadModules(id)
})

function goNext() {
  const e = {}
  if (step.value === 1) {
    if (!form.value.semestre_id) e.semestre_id = 'Sélectionnez un semestre'
    if (!form.value.type)        e.type        = 'Sélectionnez un type'
    if (!form.value.module_id)   e.module_id   = 'Sélectionnez un module'

    const na = Number(form.value.note_actuelle)
    if (form.value.note_actuelle === '' || form.value.note_actuelle === null) {
      e.note_actuelle = 'La note actuelle est obligatoire'
    } else if (isNaN(na) || na < 0 || na > 20) {
      e.note_actuelle = 'La note doit être entre 0 et 20'
    }

    if (form.value.type === 'cc' &&
        form.value.note_reclamee !== '' &&
        form.value.note_reclamee !== null) {
      const nr = Number(form.value.note_reclamee)
      if (isNaN(nr) || nr < 0 || nr > 20) {
        e.note_reclamee = 'La note réclamée doit être entre 0 et 20'
      }
    }
  }
  if (step.value === 2) {
    if (!form.value.justification || form.value.justification.trim().length < 20) {
      e.justification = 'Minimum 20 caractères requis'
    }
  }
  errors.value = e
  if (Object.keys(e).length === 0) step.value++
}

async function submit() {
  if (!confirmed.value) {
    notify('Veuillez cocher la case de confirmation.', 'warning')
    return
  }
  submitting.value  = true
  globalError.value = ''

  const fd = new FormData()
  fd.append('semestre_id',   form.value.semestre_id)
  fd.append('module_id',     form.value.module_id)
  fd.append('type',          form.value.type)
  fd.append('note_actuelle', form.value.note_actuelle)
  fd.append('justification', form.value.justification.trim())

  if (form.value.type === 'cc' && form.value.note_reclamee !== '' && form.value.note_reclamee !== null) {
    fd.append('note_reclamee', form.value.note_reclamee)
  }
  if (docFile.value) {
    fd.append('document', docFile.value)
  }

  try {
    const res = await api.post('/student/reclamations', fd, {
      headers: { 'Content-Type': undefined },
    })
    const payload = res.data?.data ?? res.data ?? {}
    const newId   = payload.id ?? payload.reclamation_id ?? null

    notify('Réclamation soumise avec succès ! Redirection…', 'success')
    setTimeout(() => {
      if (newId) {
        router.push({ name: 'student.reclamation.detail', params: { id: String(newId) } })
      } else {
        router.push({ name: 'student.reclamations' })
      }
    }, 1200)
  } catch (err) {
    const status = err.response?.status
    const body   = err.response?.data

    if (status === 422) {
      const valErrors = body?.errors ?? {}
      globalError.value = body?.message || 'Veuillez corriger les erreurs ci-dessous.'
      errors.value = Object.fromEntries(
        Object.entries(valErrors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      )
      const step1 = ['semestre_id', 'module_id', 'type', 'note_actuelle', 'note_reclamee']
      const step2 = ['justification', 'document']
      if (step1.some(f => errors.value[f]))      step.value = 1
      else if (step2.some(f => errors.value[f])) step.value = 2
    } else if (status === 409) {
      globalError.value = body?.message ?? 'Une réclamation active existe déjà pour ce module.'
      step.value = 1
    } else if (status === 401) {
      router.push('/login')
    } else {
      globalError.value = body?.message ?? 'Une erreur inattendue est survenue. Veuillez réessayer.'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(loadSemestres)
</script>

<style scoped>
.reclamation-container {
  padding-top: 24px;
  padding-bottom: 80px;
}

.page-header { padding: 0 4px; }

/* ── Grille types ─────────────────────────────────────────────────────────── */
.type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
}

.type-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 12px;
  border-radius: 12px;
  /* Utilisation d'une bordure translucide adaptative au thème */
  border: 2px solid rgba(var(--v-border-color), var(--v-border-opacity, 0.12));
  cursor: pointer;
  transition: all 0.2s ease;
  /* Utilise la couleur de fond de la carte Vuetify en cours (blanche ou sombre) */
  background: var(--v-theme-surface); 
  text-align: center;
  user-select: none;
}
.type-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.4);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.type-card--active {
  border-color: transparent;
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
}
.type-card__label { font-weight: 600; font-size: 0.85rem; margin-bottom: 2px; }
.type-card__desc  { font-size: 0.72rem; opacity: 0.70; }

/* ── Zone upload ──────────────────────────────────────────────────────────── */
.upload-zone {
  border: 2px dashed rgba(var(--v-theme-primary), 0.35);
  border-radius: 12px;
  padding: 36px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: rgba(var(--v-theme-primary), 0.04);
}
.upload-zone:hover,
.upload-zone--dragging {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}

/* ── Aperçu fichier ───────────────────────────────────────────────────────── */
.file-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.05);
  border: 1px solid rgba(var(--v-theme-primary), 0.15);
}
.file-preview__info  { flex: 1; min-width: 0; }
.file-preview__name  { font-weight: 500; font-size: 0.875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.file-preview__size  { font-size: 0.75rem; opacity: 0.6; }

/* ── Récapitulatif ────────────────────────────────────────────────────────── */
.recap-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.recap-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 14px;
  border-radius: 8px;
  /* Fond translucide s'adaptant à l'arrière-plan de l'application (clair ou sombre) */
  background: rgba(var(--v-border-color), 0.04);
  border: 1px solid rgba(var(--v-border-color), 0.08);
}
.recap-item--full { grid-column: 1 / -1; }
.recap-label {
  font-size: 0.72rem;
  opacity: 0.6;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.recap-value { font-size: 0.9rem; font-weight: 500; word-break: break-word; }

.nav-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 0;
}
</style>