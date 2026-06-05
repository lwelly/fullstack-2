<template>
  <div class="resultats-page">

    <!-- ───── En-tête ───── -->
    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">Résultats PDF</h1>
        <p class="page-sub">
          <v-icon size="14" color="#6B7280" class="mr-1">mdi-file-pdf-box</v-icon>
          Gérez les PDF officiels de résultats par module — utilisés par l'IA pour analyser les réclamations
        </p>
      </div>
      <v-btn
        color="#0F2D5E"
        prepend-icon="mdi-plus"
        rounded="lg"
        @click="openUpload"
      >
        Uploader un PDF
      </v-btn>
    </div>

    <!-- ───── Liste ───── -->
    <v-card elevation="2" rounded="xl" class="overflow-hidden">

      <div v-if="loading" class="py-16 text-center">
        <v-progress-circular indeterminate color="#0F2D5E" size="40" />
        <p class="mt-4 text-body-2 text-medium-emphasis">Chargement…</p>
      </div>

      <div v-else-if="list.length === 0" class="empty-state">
        <div class="empty-icon-wrapper mb-4">
          <v-icon size="40" color="#93C5FD">mdi-file-pdf-box</v-icon>
        </div>
        <p class="empty-title">Aucun PDF de résultats</p>
        <p class="empty-sub">Uploadez le premier PDF pour activer l'analyse IA automatique</p>
        <v-btn color="#0F2D5E" class="mt-4" prepend-icon="mdi-plus" @click="openUpload">
          Uploader un PDF
        </v-btn>
      </div>

      <template v-else>
        <div class="pdf-head">
          <span>Module</span>
          <span>Semestre</span>
          <span>Type</span>
          <span>Année</span>
          <span>Fichier</span>
          <span>Uploadé le</span>
          <span class="text-center">Action</span>
        </div>

        <div v-for="p in list" :key="p.id" class="pdf-row">
          <div>
            <div class="module-name">{{ p.module?.name ?? '—' }}</div>
            <div class="module-code">{{ p.module?.code ?? '' }}</div>
          </div>
          <div class="text-body-2">{{ p.semestre?.label ?? p.semestre?.code ?? '—' }}</div>
          <div>
            <span class="type-badge" :class="`type-${p.type}`">{{ typeLabel(p.type) }}</span>
          </div>
          <div class="text-body-2">{{ p.academic_year }}</div>
          <div class="d-flex align-center gap-1">
            <v-icon size="16" color="red">mdi-file-pdf-box</v-icon>
            <span class="text-body-2" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              {{ p.original_name }}
            </span>
          </div>
          <div class="text-body-2 text-medium-emphasis">{{ fDate(p.created_at) }}</div>
          <div class="text-center">
            <v-btn
              icon
              size="small"
              variant="tonal"
              color="error"
              @click="confirmDelete(p)"
            >
              <v-icon size="16">mdi-delete-outline</v-icon>
            </v-btn>
          </div>
        </div>
      </template>
    </v-card>

    <!-- ══════════════════════════════════════
         DIALOG UPLOAD
    ══════════════════════════════════════ -->
    <v-dialog v-model="uploadDialog" max-width="560" persistent>
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex align-center gap-3 mb-5">
          <v-avatar color="blue-lighten-4" size="42">
            <v-icon color="#0F2D5E" size="22">mdi-file-upload-outline</v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">Uploader un PDF de résultats</div>
            <div class="text-caption text-medium-emphasis">
              Le PDF sera utilisé automatiquement par l'IA lors des réclamations
            </div>
          </div>
        </div>

        <!-- Semestre -->
        <v-select
          v-model="form.semestre_id"
          :items="semestres"
          item-title="label"
          item-value="id"
          label="Semestre *"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          class="mb-3"
          :loading="loadingSemestres"
          @update:model-value="onSemestreChange"
        />

        <!-- Module -->
        <v-select
          v-model="form.module_id"
          :items="modules"
          item-title="name"
          item-value="id"
          label="Module *"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          class="mb-3"
          :loading="loadingModules"
          :disabled="!form.semestre_id"
          no-data-text="Sélectionnez d'abord un semestre"
        />

        <!-- Type -->
        <v-select
          v-model="form.type"
          :items="typeItems"
          item-title="label"
          item-value="value"
          label="Type d'évaluation *"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          class="mb-3"
        />

        <!-- Année universitaire -->
        <v-text-field
          v-model="form.academic_year"
          label="Année universitaire *"
          placeholder="2025-2026"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          class="mb-4"
        />

        <!-- Zone upload PDF -->
        <div
          class="upload-zone mb-4"
          :class="{ 'upload-zone--active': isDragging }"
          @click="fileInput.click()"
          @dragover.prevent="isDragging = true"
          @dragleave="isDragging = false"
          @drop.prevent="onDrop"
        >
          <input
            ref="fileInput"
            type="file"
            accept="application/pdf"
            style="display:none"
            @change="onFileSelect"
          />
          <template v-if="!selectedFile">
            <v-icon size="36" color="#94A3B8" class="mb-2">mdi-file-pdf-box</v-icon>
            <p class="text-body-2 text-medium-emphasis">
              Glissez votre PDF ici ou <strong>cliquez pour choisir</strong>
            </p>
            <p class="text-caption text-medium-emphasis">PDF uniquement — max 20 Mo</p>
          </template>
          <template v-else>
            <v-icon size="32" color="red" class="mb-1">mdi-file-pdf-box</v-icon>
            <p class="text-body-2 font-weight-medium">{{ selectedFile.name }}</p>
            <p class="text-caption text-medium-emphasis">{{ formatSize(selectedFile.size) }}</p>
          </template>
        </div>

        <!-- Erreur -->
        <v-alert v-if="uploadError" type="error" variant="tonal" density="compact" class="mb-4">
          {{ uploadError }}
        </v-alert>

        <div class="d-flex gap-2 justify-end">
          <v-btn variant="text" color="grey" :disabled="uploading" @click="closeUpload">
            Annuler
          </v-btn>
          <v-btn
            color="#0F2D5E"
            rounded="lg"
            :loading="uploading"
            :disabled="!canUpload"
            @click="doUpload"
          >
            <v-icon start size="15">mdi-upload</v-icon>
            Uploader
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ══════════════════════════════════════
         DIALOG CONFIRMATION SUPPRESSION
    ══════════════════════════════════════ -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex align-center gap-3 mb-4">
          <v-avatar color="red-lighten-4" size="42">
            <v-icon color="red" size="22">mdi-delete-outline</v-icon>
          </v-avatar>
          <div class="text-h6 font-weight-bold">Supprimer le PDF ?</div>
        </div>
        <p class="text-body-2 text-medium-emphasis mb-5">
          Le PDF <strong>{{ toDelete?.original_name }}</strong> sera supprimé.
          L'IA ne pourra plus analyser automatiquement les réclamations de ce module.
        </p>
        <div class="d-flex gap-2 justify-end">
          <v-btn variant="text" color="grey" @click="deleteDialog = false">Annuler</v-btn>
          <v-btn color="error" rounded="lg" :loading="deleting" @click="doDelete">
            Supprimer
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ───── Snackbar ───── -->
    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3500" location="bottom right" rounded="lg">
      <v-icon class="mr-2">{{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
      {{ snack.text }}
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

const loading        = ref(true)
const list           = ref([])
const uploadDialog   = ref(false)
const deleteDialog   = ref(false)
const uploading      = ref(false)
const deleting       = ref(false)
const isDragging     = ref(false)
const selectedFile   = ref(null)
const fileInput      = ref(null)
const uploadError    = ref('')
const toDelete       = ref(null)
const semestres      = ref([])
const modules        = ref([])
const loadingSemestres = ref(false)
const loadingModules   = ref(false)
const snack          = ref({ show: false, text: '', color: 'success' })

const form = ref({
  semestre_id:   null,
  module_id:     null,
  type:          '',
  academic_year: '2025-2026',
})

const typeItems = [
  { label: 'Contrôle Continu', value: 'controle' },
  { label: 'Examen',           value: 'examen'   },
  { label: 'Rattrapage',       value: 'rattrapage'},
]

const canUpload = computed(() =>
  form.value.semestre_id &&
  form.value.module_id &&
  form.value.type &&
  form.value.academic_year &&
  selectedFile.value
)

const notify = (text, color = 'success') => { snack.value = { show: true, text, color } }

// ─── Charger la liste ─────────────────────────────────────────────────────────
async function loadList() {
  loading.value = true
  try {
    const res = await api.get('/admin/module-results')
    list.value  = res.data?.data ?? []
  } catch {
    notify('Impossible de charger la liste.', 'error')
  } finally {
    loading.value = false
  }
}

// ─── Charger semestres ────────────────────────────────────────────────────────
async function loadSemestres() {
  loadingSemestres.value = true
  try {
    const res   = await api.get('/admin/semestres')
    semestres.value = res.data?.data ?? []
  } catch { semestres.value = [] }
  finally { loadingSemestres.value = false }
}

// ─── Charger modules par semestre ─────────────────────────────────────────────
async function onSemestreChange(id) {
  form.value.module_id = null
  modules.value        = []
  if (!id) return
  loadingModules.value = true
  try {
    const res    = await api.get('/admin/modules', { params: { semestre_id: id } })
    modules.value = res.data?.data ?? []
  } catch { modules.value = [] }
  finally { loadingModules.value = false }
}

// ─── Upload ───────────────────────────────────────────────────────────────────
function openUpload() {
  uploadDialog.value = true
  uploadError.value  = ''
  selectedFile.value = null
  form.value = { semestre_id: null, module_id: null, type: '', academic_year: '2025-2026' }
}

function closeUpload() {
  uploadDialog.value = false
  selectedFile.value = null
  uploadError.value  = ''
}

function onFileSelect(e) {
  const f = e.target.files?.[0]
  if (f) validateAndSetFile(f)
}

function onDrop(e) {
  isDragging.value = false
  const f = e.dataTransfer.files?.[0]
  if (f) validateAndSetFile(f)
}

function validateAndSetFile(f) {
  uploadError.value = ''
  if (f.type !== 'application/pdf') {
    uploadError.value = 'Seuls les fichiers PDF sont acceptés.'
    return
  }
  if (f.size > 20 * 1024 * 1024) {
    uploadError.value = 'Le fichier est trop volumineux (max 20 Mo).'
    return
  }
  selectedFile.value = f
}

async function doUpload() {
  if (!canUpload.value) return
  uploading.value   = true
  uploadError.value = ''
  try {
    const fd = new FormData()
    fd.append('module_id',     form.value.module_id)
    fd.append('semestre_id',   form.value.semestre_id)
    fd.append('type',          form.value.type)
    fd.append('academic_year', form.value.academic_year)
    fd.append('pdf',           selectedFile.value)

    await api.post('/admin/module-results', fd, {
      headers: { 'Content-Type': undefined },
    })
    notify('PDF uploadé avec succès ✅')
    closeUpload()
    loadList()
  } catch (err) {
    uploadError.value = err.response?.data?.message ?? 'Erreur lors de l\'upload.'
  } finally {
    uploading.value = false
  }
}

// ─── Suppression ──────────────────────────────────────────────────────────────
function confirmDelete(p) {
  toDelete.value    = p
  deleteDialog.value = true
}

async function doDelete() {
  if (!toDelete.value) return
  deleting.value = true
  try {
    await api.delete(`/admin/module-results/${toDelete.value.id}`)
    notify('PDF supprimé.')
    deleteDialog.value = false
    loadList()
  } catch {
    notify('Erreur lors de la suppression.', 'error')
  } finally {
    deleting.value = false
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
const TYPE_LABELS = { controle: 'Contrôle', examen: 'Examen', rattrapage: 'Rattrapage' }
function typeLabel(v) { return TYPE_LABELS[v] ?? v }

function fDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatSize(bytes) {
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko'
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo'
}

onMounted(() => { loadList(); loadSemestres() })
</script>

<style scoped>
.resultats-page { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
.page-header    { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.page-title     { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
.page-sub       { font-size: 13px; color: #6B7280; margin: 4px 0 0; display: flex; align-items: center; }

.pdf-head, .pdf-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr 1fr 60px;
  align-items: center;
  padding: 12px 20px;
  font-size: 12px;
  gap: 8px;
}
.pdf-head {
  background: #F8FAFC; font-weight: 700; color: #64748B;
  text-transform: uppercase; font-size: 11px; letter-spacing: .5px;
  border-bottom: 2px solid #E2E8F0;
}
.pdf-row { border-bottom: 1px solid #F1F5F9; }
.pdf-row:last-child { border-bottom: none; }
.pdf-row:hover { background: #F0F7FF; }

.module-name { font-size: 13px; font-weight: 600; color: #111827; }
.module-code { font-size: 11px; color: #9CA3AF; font-family: monospace; }

.type-badge {
  display: inline-block; font-size: 10px; font-weight: 600;
  padding: 2px 8px; border-radius: 4px; text-transform: uppercase;
}
.type-controle   { background: #DBEAFE; color: #1D4ED8; }
.type-examen     { background: #EDE9FE; color: #6D28D9; }
.type-rattrapage { background: #FEF3C7; color: #B45309; }

.empty-state { padding: 64px 24px; text-align: center; }
.empty-icon-wrapper {
  width: 72px; height: 72px; border-radius: 50%; background: #FEF2F2;
  display: flex; align-items: center; justify-content: center; margin: 0 auto;
}
.empty-title { font-size: 15px; font-weight: 600; color: #374151; margin: 0 0 6px; }
.empty-sub   { font-size: 13px; color: #9CA3AF; margin: 0; }

.upload-zone {
  border: 2px dashed #CBD5E1; border-radius: 12px;
  padding: 32px 20px; text-align: center; cursor: pointer;
  transition: all .2s; background: #F8FAFC;
}
.upload-zone:hover, .upload-zone--active {
  border-color: #0F2D5E; background: #EFF6FF;
}
</style>