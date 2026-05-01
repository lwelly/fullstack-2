<template>
  <div class="new-rec-page">

    <!-- En-tête -->
    <div class="page-header mb-6">
      <div class="d-flex align-center gap-3">
        <v-btn icon="mdi-arrow-left" variant="text" size="small"
          @click="router.push({ name: 'student.reclamations' })" />
        <div>
          <h1 class="page-title">Nouvelle Réclamation</h1>
          <p class="page-sub">Étape {{ step }} sur 3 — {{ stepTitles[step - 1] }}</p>
        </div>
      </div>
    </div>

    <!-- Barre de progression -->
    <div class="progress-wrap mb-6">
      <div class="progress-track">
        <div class="progress-fill" :style="{ width: (step / 3 * 100) + '%' }" />
      </div>
    </div>

    <!-- Stepper -->
    <div class="stepper-row mb-6">
      <template v-for="(title, i) in stepTitles" :key="i">
        <div class="step-pill"
          :class="{ 'pill-active': step === i+1, 'pill-done': step > i+1 }">
          <div class="pill-circle">
            <v-icon v-if="step > i+1" size="13" color="white">mdi-check</v-icon>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <span class="pill-label">{{ title }}</span>
        </div>
        <div v-if="i < 2" class="pill-connector"
          :class="{ 'connector-done': step > i+1 }" />
      </template>
    </div>

    <!-- Carte -->
    <v-card rounded="xl" elevation="0" border class="form-card">

      <!-- ══════════ ÉTAPE 1 — Type & Module ══════════ -->
      <div v-if="step === 1" class="step-body">
        <h2 class="step-title">Type & Module concerné</h2>
        <p class="step-desc">
          Sélectionnez le type de réclamation, le semestre et le module.
        </p>

        <!-- Type — uniquement cc / examen / rattrapage -->
        <div class="field-block">
          <label class="field-label">
            Type de réclamation <span class="req">*</span>
          </label>
          <div class="type-grid">
            <button
              v-for="t in reclamationTypes"
              :key="t.value"
              class="type-btn"
              :class="{ 'type-selected': form.type === t.value }"
              type="button"
              @click="selectType(t.value)"
            >
              <v-icon
                :color="form.type === t.value ? '#2563EB' : '#9CA3AF'"
                size="26"
              >{{ t.icon }}</v-icon>
              <span class="type-name">{{ t.label }}</span>
              <span class="type-desc">{{ t.desc }}</span>
            </button>
          </div>
          <p v-if="errors.type" class="err-msg">{{ errors.type }}</p>
        </div>

        <!-- Semestre — is_open = true -->
        <div class="field-block">
          <label class="field-label">
            Semestre <span class="req">*</span>
            <span class="badge-open">
              <v-icon size="11">mdi-lock-open-outline</v-icon>
              Semestres ouverts aux réclamations
            </span>
          </label>

          <div v-if="loadingSemestres" class="loading-inline">
            <v-progress-circular indeterminate size="16" color="#2563EB" width="2" />
            <span>Chargement des semestres…</span>
          </div>

          <v-alert v-else-if="semestres.length === 0" type="warning"
            variant="tonal" density="compact" rounded="lg" class="mb-2">
            Aucun semestre ouvert aux réclamations actuellement.
            Contactez votre administration.
          </v-alert>

          <v-select
            v-else
            v-model="form.semestre_id"
            :items="semestres"
            item-title="label"
            item-value="id"
            placeholder="Choisissez votre semestre"
            variant="outlined"
            density="compact"
            rounded="lg"
            hide-details="auto"
            :error-messages="errors.semestre_id"
            prepend-inner-icon="mdi-calendar-month-outline"
          >
            <template #item="{ props, item }">
              <v-list-item v-bind="props">
                <template #append>
                  <v-chip size="x-small" color="success" variant="tonal">
                    Ouvert
                  </v-chip>
                </template>
              </v-list-item>
            </template>
          </v-select>
        </div>

        <!-- Module — chargé dynamiquement selon semestre -->
        <div class="field-block">
          <label class="field-label">
            Module concerné <span class="req">*</span>
          </label>

          <div v-if="loadingModules" class="loading-inline">
            <v-progress-circular indeterminate size="16" color="#2563EB" width="2" />
            <span>Chargement des modules…</span>
          </div>

          <v-alert v-else-if="form.semestre_id && modules.length === 0 && !loadingModules"
            type="info" variant="tonal" density="compact" rounded="lg" class="mb-2">
            Aucun module disponible pour ce semestre.
          </v-alert>

          <v-select
            v-else
            v-model="form.module_id"
            :items="modules"
            item-title="name"
            item-value="id"
            placeholder="Choisissez votre module"
            variant="outlined"
            density="compact"
            rounded="lg"
            hide-details="auto"
            :error-messages="errors.module_id"
            :disabled="!form.semestre_id"
            prepend-inner-icon="mdi-book-open-outline"
            :no-data-text="form.semestre_id ? 'Aucun module' : 'Sélectionnez d\'abord un semestre'"
          >
            <template #item="{ props, item }">
              <v-list-item v-bind="props">
                <template #prepend>
                  <span class="mod-code-badge">{{ item.raw.code ?? '—' }}</span>
                </template>
              </v-list-item>
            </template>
          </v-select>
        </div>

        <!-- Note actuelle — OBLIGATOIRE -->
        <div class="field-block">
          <label class="field-label">
            Note actuelle obtenue <span class="req">*</span>
          </label>
          <v-text-field
            v-model="form.note_actuelle"
            type="number"
            min="0" max="20" step="0.25"
            placeholder="Ex : 8.50"
            variant="outlined"
            density="compact"
            rounded="lg"
            suffix="/ 20"
            hide-details="auto"
            :error-messages="errors.note_actuelle"
            style="max-width: 220px"
          />
        </div>

        <!-- Note réclamée — OBLIGATOIRE si type = cc -->
        <div v-if="form.type === 'cc'" class="field-block">
          <label class="field-label">
            Note réclamée <span class="req">*</span>
            <span class="field-hint">Obligatoire pour un Contrôle Continu</span>
          </label>
          <v-text-field
            v-model="form.note_reclamee"
            type="number"
            min="0" max="20" step="0.25"
            placeholder="Ex : 12"
            variant="outlined"
            density="compact"
            rounded="lg"
            suffix="/ 20"
            hide-details="auto"
            :error-messages="errors.note_reclamee"
            style="max-width: 220px"
          />
        </div>
      </div>

      <!-- ══════════ ÉTAPE 2 — Justification ══════════ -->
      <div v-if="step === 2" class="step-body">
        <h2 class="step-title">Justification</h2>
        <p class="step-desc">
          Expliquez précisément les raisons de votre réclamation (minimum 20 caractères).
        </p>

        <!-- Récap étape 1 -->
        <div class="recap-mini mb-4">
          <div class="recap-item">
            <v-icon size="14" color="#6B7280">mdi-tag-outline</v-icon>
            <span>{{ typeLabel(form.type) }}</span>
          </div>
          <div class="recap-sep">·</div>
          <div class="recap-item">
            <v-icon size="14" color="#6B7280">mdi-calendar</v-icon>
            <span>{{ semLabel }}</span>
          </div>
          <div class="recap-sep">·</div>
          <div class="recap-item">
            <v-icon size="14" color="#6B7280">mdi-book-open</v-icon>
            <span>{{ moduleLabel }}</span>
          </div>
          <div class="recap-sep">·</div>
          <div class="recap-item">
            <v-icon size="14" color="#6B7280">mdi-numeric</v-icon>
            <span>{{ form.note_actuelle }} / 20</span>
          </div>
        </div>

        <!-- Justification — champ principal -->
        <div class="field-block">
          <label class="field-label">
            Justification détaillée <span class="req">*</span>
          </label>
          <v-textarea
            v-model="form.justification"
            placeholder="Décrivez précisément les raisons de votre réclamation : points contestés, erreurs constatées, arguments…"
            variant="outlined"
            rounded="lg"
            rows="6"
            hide-details="auto"
            :error-messages="errors.justification"
            counter="1000"
            maxlength="1000"
          />
          <div class="char-hint" :class="{ 'char-ok': form.justification.length >= 20 }">
            <v-icon size="12">
              {{ form.justification.length >= 20 ? 'mdi-check-circle' : 'mdi-alert-circle-outline' }}
            </v-icon>
            {{ form.justification.length >= 20
              ? 'Longueur suffisante'
              : `Encore ${20 - form.justification.length} caractère(s) requis` }}
          </div>
        </div>

        <!-- Document — 1 seul fichier -->
        <div class="field-block">
          <label class="field-label">
            Document justificatif
            <span class="field-hint">PDF, JPG, PNG — max 5 Mo (optionnel)</span>
          </label>

          <div
            class="upload-zone"
            :class="{ 'zone-drag': isDragging, 'zone-filled': !!document }"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleDrop"
            @click="$refs.fileInput.click()"
          >
            <template v-if="!document">
              <v-icon size="30" color="#9CA3AF" class="mb-2">mdi-cloud-upload-outline</v-icon>
              <p class="upload-text">Glissez un fichier ou <strong>cliquez pour parcourir</strong></p>
              <p class="upload-hint">PDF, JPG, JPEG, PNG — max 5 Mo</p>
            </template>
            <template v-else>
              <div class="file-preview">
                <v-icon size="28" :color="fileIconColor(document.type)">
                  {{ fileIconName(document.type) }}
                </v-icon>
                <div class="file-info">
                  <span class="file-name">{{ document.name }}</span>
                  <span class="file-size">{{ formatSize(document.size) }}</span>
                </div>
                <v-btn icon size="x-small" variant="text" color="#EF4444"
                  @click.stop="removeDoc">
                  <v-icon size="16">mdi-close</v-icon>
                </v-btn>
              </div>
            </template>
          </div>

          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            style="display:none"
            @change="handleFileSelect"
          />
          <p v-if="errors.document" class="err-msg">{{ errors.document }}</p>
        </div>
      </div>

      <!-- ══════════ ÉTAPE 3 — Confirmation ══════════ -->
      <div v-if="step === 3" class="step-body">
        <h2 class="step-title">Confirmation</h2>
        <p class="step-desc">Vérifiez toutes les informations avant de soumettre.</p>

        <div class="recap-card">

          <!-- Section 1 : Infos académiques -->
          <div class="recap-section">
            <h3 class="recap-sec-title">
              <v-icon size="15" color="#2563EB" class="mr-1">mdi-school-outline</v-icon>
              Informations académiques
            </h3>
            <div class="recap-grid">
              <div class="recap-row">
                <span class="rkey">Type</span>
                <span class="chip" :style="typeChipStyle(form.type)">
                  {{ typeLabel(form.type) }}
                </span>
              </div>
              <div class="recap-row">
                <span class="rkey">Semestre</span>
                <span class="rval">{{ semLabel }}</span>
              </div>
              <div class="recap-row">
                <span class="rkey">Module</span>
                <span class="rval">{{ moduleLabel }}</span>
              </div>
              <div class="recap-row">
                <span class="rkey">Note actuelle</span>
                <span class="rval note-badge note-bad">{{ form.note_actuelle }} / 20</span>
              </div>
              <div v-if="form.type === 'cc'" class="recap-row">
                <span class="rkey">Note réclamée</span>
                <span class="rval note-badge note-good">{{ form.note_reclamee }} / 20</span>
              </div>
            </div>
          </div>

          <v-divider class="my-3" />

          <!-- Section 2 : Justification -->
          <div class="recap-section">
            <h3 class="recap-sec-title">
              <v-icon size="15" color="#2563EB" class="mr-1">mdi-text-box-outline</v-icon>
              Justification
            </h3>
            <div class="justif-box">{{ form.justification }}</div>
          </div>

          <!-- Section 3 : Document -->
          <template v-if="document">
            <v-divider class="my-3" />
            <div class="recap-section">
              <h3 class="recap-sec-title">
                <v-icon size="15" color="#2563EB" class="mr-1">mdi-paperclip</v-icon>
                Document joint
              </h3>
              <div class="file-preview compact">
                <v-icon size="20" :color="fileIconColor(document.type)">
                  {{ fileIconName(document.type) }}
                </v-icon>
                <span class="file-name">{{ document.name }}</span>
                <span class="file-size">{{ formatSize(document.size) }}</span>
              </div>
            </div>
          </template>
        </div>

        <!-- Erreur globale -->
        <v-alert v-if="globalError" type="error" variant="tonal" rounded="lg"
          class="mt-4" closable @click:close="globalError = ''">
          {{ globalError }}
        </v-alert>

        <!-- Engagement -->
        <v-checkbox v-model="confirmed" color="#2563EB" hide-details class="mt-4">
          <template #label>
            <span style="font-size:13px;color:#374151">
              Je certifie que les informations fournies sont exactes et complètes.
            </span>
          </template>
        </v-checkbox>
      </div>

      <!-- Barre navigation -->
      <div class="nav-bar">
        <v-btn v-if="step > 1" variant="outlined" rounded="lg"
          :disabled="submitting" @click="step--">
          <v-icon start>mdi-arrow-left</v-icon> Précédent
        </v-btn>
        <v-spacer />

        <v-btn v-if="step < 3" color="#0F2D5E" rounded="lg"
          :disabled="!canNext" @click="goNext">
          Suivant <v-icon end>mdi-arrow-right</v-icon>
        </v-btn>

        <v-btn v-else color="success" rounded="lg"
          :disabled="!confirmed || submitting"
          :loading="submitting"
          @click="submit">
          <v-icon start>mdi-send</v-icon> Soumettre
        </v-btn>
      </div>

    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter }   from 'vue-router'
import { useToast }    from 'vue-toastification'
import api             from '@/api/axios'

const router = useRouter()
const toast  = useToast()

const step       = ref(1)
const stepTitles = ['Type & Module', 'Justification', 'Confirmation']

const form = ref({
  type:          '',
  semestre_id:   null,
  module_id:     null,
  note_actuelle: '',
  note_reclamee: '',
  justification: '',
})
const document    = ref(null)
const confirmed   = ref(false)
const isDragging  = ref(false)
const globalError = ref('')
const errors      = ref({})
const submitting  = ref(false)

const semestres        = ref([])
const modules          = ref([])
const loadingSemestres = ref(false)
const loadingModules   = ref(false)

/* ─── Tous les types possibles ────────────────────────────── */
const ALL_TYPES = [
  { value: 'cc',         label: 'Contrôle Continu', desc: 'CC, Devoir, Test',          icon: 'mdi-pencil-box-outline'    },
  { value: 'examen',     label: 'Examen Final',      desc: 'Examen de fin de semestre', icon: 'mdi-file-document-outline' },
  { value: 'rattrapage', label: 'Rattrapage',         desc: 'Session de rattrapage',     icon: 'mdi-redo-variant'          },
]

/* ─── Types filtrés selon le semestre sélectionné ─────────── */
const reclamationTypes = computed(() => {
  if (!form.value.semestre_id) return ALL_TYPES

  const sem = semestres.value.find(s => s.id === form.value.semestre_id)
  if (!sem?.available_types?.length) return ALL_TYPES

  return ALL_TYPES.filter(t => sem.available_types.includes(t.value))
})

/* ─── Chargement semestres ────────────────────────────────── */
async function loadSemestres () {
  loadingSemestres.value = true
  try {
    const res = await api.get('/student/semestres')
    console.log('[NewRec] /student/semestres:', res.data)

    const raw = res.data?.data ?? res.data ?? []
    semestres.value = Array.isArray(raw) ? raw : []

    console.log('[NewRec] Semestres disponibles:', semestres.value.length)
  } catch (err) {
    console.error('[NewRec] Erreur semestres:', err.response?.status, err.response?.data)
    semestres.value = []
  } finally {
    loadingSemestres.value = false
  }
}

/* ─── Chargement modules ──────────────────────────────────── */
async function loadModules (semestreId) {
  if (!semestreId) { modules.value = []; return }
  loadingModules.value = true
  try {
    const res = await api.get('/student/modules', {
      params: { semestre_id: semestreId }
    })
    const raw = res.data?.data ?? res.data ?? []
    modules.value = Array.isArray(raw) ? raw : []
    console.log('[NewRec] Modules chargés:', modules.value.length)
  } catch (err) {
    console.error('[NewRec] Erreur modules:', err.response?.status, err.response?.data)
    modules.value = []
  } finally {
    loadingModules.value = false
  }
}

/* ─── Watch semestre → reset type + module + recharger ────── */
watch(() => form.value.semestre_id, (newId) => {
  form.value.module_id = null
  form.value.type      = ''   // reset car les types changent selon semestre
  modules.value        = []
  if (newId) loadModules(newId)
})

/* ─── Sélection type ──────────────────────────────────────── */
function selectType (val) {
  form.value.type = val
  if (val !== 'cc') form.value.note_reclamee = ''
}

/* ─── Labels calculés ─────────────────────────────────────── */
const semLabel    = computed(() =>
  semestres.value.find(s => s.id === form.value.semestre_id)?.label ?? '—'
)
const moduleLabel = computed(() =>
  modules.value.find(m => m.id === form.value.module_id)?.name ?? '—'
)
const typeLabel = (v) => ALL_TYPES.find(t => t.value === v)?.label ?? v ?? '—'

/* ─── Validation ──────────────────────────────────────────── */
const canNext = computed(() => {
  if (step.value === 1) {
    const base = !!form.value.type &&
                 !!form.value.semestre_id &&
                 !!form.value.module_id &&
                 form.value.note_actuelle !== ''
    if (form.value.type === 'cc') {
      return base && form.value.note_reclamee !== ''
    }
    return base
  }
  if (step.value === 2) return form.value.justification.trim().length >= 20
  return true
})

function goNext () {
  errors.value = {}
  if (step.value === 1) {
    if (!form.value.type)          errors.value.type          = 'Sélectionnez un type.'
    if (!form.value.semestre_id)   errors.value.semestre_id   = 'Sélectionnez un semestre.'
    if (!form.value.module_id)     errors.value.module_id     = 'Sélectionnez un module.'
    if (form.value.note_actuelle === '') errors.value.note_actuelle = 'Note actuelle obligatoire.'
    if (form.value.type === 'cc' && form.value.note_reclamee === '')
      errors.value.note_reclamee = 'Note réclamée obligatoire pour un CC.'
    if (Object.keys(errors.value).length) return
  }
  if (step.value === 2) {
    if (form.value.justification.trim().length < 20) {
      errors.value.justification = 'Minimum 20 caractères requis.'
      return
    }
  }
  step.value++
}

/* ─── Fichier ─────────────────────────────────────────────── */
function handleFileSelect (e) {
  const f = e.target.files[0]; if (f) addFile(f); e.target.value = ''
}
function handleDrop (e) {
  isDragging.value = false; const f = e.dataTransfer.files[0]; if (f) addFile(f)
}
function addFile (f) {
  const ok = ['application/pdf','image/jpeg','image/png','image/jpg']
  errors.value.document = ''
  if (!ok.includes(f.type)) { errors.value.document = 'Format non autorisé (PDF, JPG, PNG).'; return }
  if (f.size > 5*1024*1024) { errors.value.document = 'Fichier trop volumineux (max 5 Mo).'; return }
  document.value = f
}
function removeDoc () { document.value = null }

/* ─── Soumission ──────────────────────────────────────────── */
async function submit () {
  globalError.value = ''
  submitting.value  = true
  try {
    const fd = new FormData()
    fd.append('semestre_id',   form.value.semestre_id)
    fd.append('module_id',     form.value.module_id)
    fd.append('type',          form.value.type)
    fd.append('note_actuelle', form.value.note_actuelle)
    fd.append('justification', form.value.justification.trim())
    if (form.value.type === 'cc' && form.value.note_reclamee !== '')
      fd.append('note_reclamee', form.value.note_reclamee)
    if (document.value)
      fd.append('document', document.value)

    const res = await api.post('/student/reclamations', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const data = res.data?.data ?? res.data
    toast.success(res.data?.message ?? 'Réclamation soumise avec succès !')
    router.push(data?.id
      ? { name: 'student.reclamation.detail', params: { id: data.id } }
      : { name: 'student.reclamations' }
    )
  } catch (err) {
    const status = err.response?.status
    const data   = err.response?.data
    if (status === 422) {
      const errs = data?.errors ?? {}
      globalError.value = Object.values(errs).flat()[0] ?? data?.message ?? 'Erreur de validation.'
      const s1 = ['type','semestre_id','module_id','note_actuelle','note_reclamee']
      const s2 = ['justification','document']
      const k  = Object.keys(errs)[0]
      if (k && s1.includes(k)) step.value = 1
      else if (k && s2.includes(k)) step.value = 2
    } else {
      globalError.value = data?.message ?? 'Une erreur est survenue.'
    }
  } finally {
    submitting.value = false
  }
}

/* ─── Helpers fichier ─────────────────────────────────────── */
function fileIconName  (t) { return t==='application/pdf' ? 'mdi-file-pdf-box' : t?.startsWith('image/') ? 'mdi-file-image' : 'mdi-file-outline' }
function fileIconColor (t) { return t==='application/pdf' ? '#EF4444' : t?.startsWith('image/') ? '#3B82F6' : '#6B7280' }
function formatSize (b)    { return b<1024 ? b+' o' : b<1024*1024 ? (b/1024).toFixed(1)+' Ko' : (b/(1024*1024)).toFixed(1)+' Mo' }

const TYPE_CHIP = {
  cc:{ bg:'#DBEAFE',color:'#1D4ED8' },
  examen:{ bg:'#EDE9FE',color:'#6D28D9' },
  rattrapage:{ bg:'#FEF3C7',color:'#B45309' }
}
function typeChipStyle (v) { const c=TYPE_CHIP[v]??{bg:'#F3F4F6',color:'#6B7280'}; return `background:${c.bg};color:${c.color}` }

onMounted(loadSemestres)
</script>

<style scoped>
.new-rec-page { max-width: 740px; margin: 0 auto; }

/* Header */
.page-header { display:flex; align-items:center; }
.page-title  { font-size:20px; font-weight:700; color:#111827; margin:0; }
.page-sub    { font-size:13px; color:#6B7280; margin:2px 0 0; }

/* Progress bar */
.progress-wrap  { }
.progress-track { height:4px; background:#E5E7EB; border-radius:2px; overflow:hidden; }
.progress-fill  { height:100%; background:#2563EB; border-radius:2px; transition:width .3s ease; }

/* Stepper */
.stepper-row      { display:flex; align-items:center; }
.step-pill        { display:flex; align-items:center; gap:8px; }
.pill-circle      { width:28px; height:28px; border-radius:50%; border:2px solid #D1D5DB; background:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#9CA3AF; flex-shrink:0; }
.pill-label       { font-size:12px; font-weight:500; color:#9CA3AF; white-space:nowrap; }
.pill-active .pill-circle { border-color:#2563EB; color:#2563EB; }
.pill-active .pill-label  { color:#2563EB; font-weight:600; }
.pill-done   .pill-circle { border-color:#16A34A; background:#16A34A; }
.pill-done   .pill-label  { color:#16A34A; }
.pill-connector { flex:1; height:2px; background:#E5E7EB; margin:0 8px; min-width:20px; }
.connector-done { background:#16A34A; }

/* Carte */
.form-card   { overflow:hidden; }
.step-body   { padding:28px 28px 8px; }
.step-title  { font-size:17px; font-weight:700; color:#111827; margin:0 0 4px; }
.step-desc   { font-size:13px; color:#6B7280; margin:0 0 24px; }

/* Champs */
.field-block  { margin-bottom:22px; }
.field-label  { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:#374151; margin-bottom:8px; flex-wrap:wrap; }
.req          { color:#EF4444; }
.field-hint   { font-size:11px; color:#9CA3AF; font-weight:400; display:flex; align-items:center; gap:3px; }
.badge-open   { display:inline-flex; align-items:center; gap:3px; font-size:11px; color:#16A34A; background:#DCFCE7; padding:2px 8px; border-radius:20px; font-weight:500; }
.err-msg      { font-size:12px; color:#EF4444; margin-top:4px; }

/* Loading inline */
.loading-inline { display:flex; align-items:center; gap:8px; font-size:13px; color:#6B7280; padding:8px 0; }

/* Type grid */
.type-grid   { display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; }
.type-btn    {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding:16px 8px; border:2px solid #E5E7EB; border-radius:12px;
  background:#fff; cursor:pointer; transition:all .15s; text-align:center; gap:4px;
}
.type-btn:hover    { border-color:#93C5FD; background:#F0F7FF; }
.type-selected     { border-color:#2563EB !important; background:#EFF6FF !important; }
.type-name         { font-size:12px; font-weight:600; color:#374151; line-height:1.2; }
.type-desc         { font-size:11px; color:#9CA3AF; line-height:1.2; }
.mod-code-badge    { font-size:10px; background:#F3F4F6; color:#6B7280; padding:2px 6px; border-radius:4px; font-family:monospace; margin-right:8px; }

/* Char hint */
.char-hint { display:flex; align-items:center; gap:4px; font-size:11px; color:#9CA3AF; margin-top:4px; }
.char-ok   { color:#16A34A; }

/* Upload zone */
.upload-zone {
  border:2px dashed #D1D5DB; border-radius:10px;
  padding:24px; text-align:center; cursor:pointer;
  transition:all .15s; background:#FAFAFA;
}
.upload-zone:hover, .zone-drag { border-color:#2563EB; background:#F0F7FF; }
.zone-filled { border-color:#16A34A; background:#F0FDF4; border-style:solid; }
.upload-text { font-size:13px; color:#374151; margin:0 0 4px; }
.upload-hint { font-size:11px; color:#9CA3AF; margin:0; }

/* File preview */
.file-preview { display:flex; align-items:center; gap:10px; }
.file-preview.compact { padding:8px; background:#F9FAFB; border-radius:8px; }
.file-info    { display:flex; flex-direction:column; gap:1px; flex:1; text-align:left; }
.file-name    { font-size:13px; color:#111827; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.file-size    { font-size:11px; color:#9CA3AF; }

/* Récap mini (étape 2) */
.recap-mini { display:flex; align-items:center; flex-wrap:wrap; gap:6px; background:#F0F7FF; border:1px solid #DBEAFE; border-radius:8px; padding:10px 14px; }
.recap-item { display:flex; align-items:center; gap:4px; font-size:12px; color:#374151; font-weight:500; }
.recap-sep  { color:#D1D5DB; }

/* Récap confirmation */
.recap-card      { background:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px; padding:20px; }
.recap-section   { }
.recap-sec-title { display:flex; align-items:center; font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin:0 0 12px; }
.recap-grid      { display:flex; flex-direction:column; gap:8px; }
.recap-row       { display:flex; align-items:center; gap:12px; }
.rkey            { font-size:12px; color:#6B7280; min-width:120px; flex-shrink:0; }
.rval            { font-size:13px; color:#111827; font-weight:500; }
.chip            { display:inline-block; font-size:12px; font-weight:600; padding:3px 10px; border-radius:20px; }
.note-badge      { padding:3px 10px; border-radius:6px; font-size:13px; font-weight:700; }
.note-bad        { background:#FEE2E2; color:#B91C1C; }
.note-good       { background:#DCFCE7; color:#15803D; }
.justif-box      { background:#fff; border:1px solid #E5E7EB; border-radius:8px; padding:12px; font-size:13px; color:#374151; line-height:1.7; white-space:pre-wrap; }

/* Nav bar */
.nav-bar { display:flex; align-items:center; padding:16px 28px 24px; border-top:1px solid #F3F4F6; margin-top:12px; background:#FAFAFA; }
</style>
