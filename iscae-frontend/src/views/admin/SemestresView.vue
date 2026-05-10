<template>
  <div class="semestres-page">

    <!-- ─── En-tête ─────────────────────────────────────────────── -->
    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">Gérez les semestres, les périodes de réclamation, d'examen et de rattrapage.</h1>
        
      </div>
     
    </div>

    <!-- ─── Chargement ───────────────────────────────────────────── -->
    <div v-if="loading" class="loading-center">
      <v-progress-circular indeterminate color="#0F2D5E" size="48" />
    </div>

    <!-- ─── Vide ─────────────────────────────────────────────────── -->
    <div v-else-if="!semestres.length" class="empty-state">
      <v-icon size="64" color="#CBD5E1">mdi-calendar-blank</v-icon>
      <p class="empty-title">Aucun semestre</p>
      <p class="empty-sub">Créez votre premier semestre pour démarrer.</p>
      <v-btn color="#0F2D5E" prepend-icon="mdi-plus" @click="openCreateDialog">
        Créer un semestre
      </v-btn>
    </div>

    <!-- ─── Grille de cartes ─────────────────────────────────────── -->
    <div v-else class="cards-grid">
      <div v-for="s in semestres" :key="s.id" class="sem-card">

        <!-- Header carte -->
        <div class="card-top">
          <div class="card-meta">
            <span class="card-code">{{ s.code }}</span>
            <span class="card-label">{{ s.label }}</span>
            <span class="card-year">
              <v-icon size="12" color="#94A3B8">mdi-calendar</v-icon>
              {{ s.academic_year }}
            </span>
          </div>
          <div class="card-chips">
            <v-chip size="x-small" :color="s.is_open ? 'success' : 'error'" variant="flat">
              {{ s.is_open ? 'Ouvert' : 'Fermé' }}
            </v-chip>
            <v-chip v-if="s.niveau" size="x-small" color="indigo" variant="tonal">
              {{ s.niveau.label ?? s.niveau.code }}
            </v-chip>
          </div>
        </div>

        <!-- Dates -->
        <div class="card-dates">
          <v-icon size="13" color="#6B7280">mdi-calendar-arrow-left</v-icon>
          <span>{{ formatDate(s.open_at) }}</span>
          <v-icon size="11" color="#D1D5DB">mdi-arrow-right-thin</v-icon>
          <v-icon size="13" color="#6B7280">mdi-calendar-arrow-right</v-icon>
          <span>{{ formatDate(s.close_at) }}</span>
          <span v-if="daysRemaining(s.close_at) >= 0" class="days-badge">
            {{ daysRemaining(s.close_at) }}j
          </span>
        </div>

        <!-- Barre de progression -->
        <v-progress-linear
          :model-value="calcProgress(s)"
          color="#0F2D5E"
          height="5"
          rounded
          bg-color="#E2E8F0"
          class="my-3"
        />

        <!-- Périodes -->
        <div class="periods-grid">
          <!-- Examen -->
          <div class="period-row">
            <v-icon size="14" :color="s.is_exam_open ? '#22C55E' : '#CBD5E1'">
              {{ s.is_exam_open ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
            <span class="period-lbl">Examen</span>
            <v-btn
              density="compact" size="x-small"
              :color="s.is_exam_open ? 'error' : 'success'"
              variant="tonal"
              @click="askToggle(s, 'exam')"
            >{{ s.is_exam_open ? 'Fermer' : 'Ouvrir' }}</v-btn>
          </div>
          <!-- Rattrapage -->
          <div class="period-row">
            <v-icon size="14" :color="s.is_rattrapage_open ? '#22C55E' : '#CBD5E1'">
              {{ s.is_rattrapage_open ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
            <span class="period-lbl">Rattrapage</span>
            <v-btn
              density="compact" size="x-small"
              :color="s.is_rattrapage_open ? 'error' : 'success'"
              variant="tonal"
              @click="askToggle(s, 'rattrapage')"
            >{{ s.is_rattrapage_open ? 'Fermer' : 'Ouvrir' }}</v-btn>
          </div>
        </div>

        <!-- Actions carte -->
        <v-divider class="my-3" />
        <div class="card-actions">
          <v-btn
            size="small"
            :color="s.is_open ? 'error' : 'success'"
            variant="tonal"
            :prepend-icon="s.is_open ? 'mdi-lock' : 'mdi-lock-open'"
            @click="askToggle(s, 'reclamation')"
          >{{ s.is_open ? 'Fermer' : 'Ouvrir' }}</v-btn>
          <v-btn
            size="small" variant="outlined" color="#0F2D5E"
            prepend-icon="mdi-pencil"
            @click="openEditDialog(s)"
          >Modifier</v-btn>
        </div>

      </div>
    </div>

    <!-- ─── Dialog confirmation ──────────────────────────────────── -->
    <v-dialog v-model="confirmDialog" max-width="420">
      <v-card rounded="xl" elevation="4" class="pa-6">
        <div class="confirm-icon-wrap">
          <v-icon :color="confirmMeta.iconColor" size="44">{{ confirmMeta.icon }}</v-icon>
        </div>
        <div class="confirm-title">{{ confirmMeta.title }}</div>
        <div class="confirm-body">{{ confirmMeta.body }}</div>
        <div class="confirm-btns">
          <v-btn variant="outlined" color="grey-darken-1" rounded="lg" @click="confirmDialog = false">
            Annuler
          </v-btn>
          <v-btn :color="confirmMeta.btnColor" rounded="lg" :loading="toggling" @click="confirmToggle">
            Confirmer
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ─── Dialog formulaire ────────────────────────────────────── -->
    <v-dialog v-model="formDialog" max-width="540" scrollable>
      <v-card rounded="xl" elevation="4">
        <div class="form-dialog-head">
          <v-icon size="22" color="#0F2D5E" class="mr-2">
            {{ editing ? 'mdi-pencil-circle' : 'mdi-plus-circle' }}
          </v-icon>
          {{ editing ? 'Modifier le semestre' : 'Nouveau semestre' }}
        </div>
        <v-divider />
        <v-card-text class="pa-6">
          <v-row dense>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.code" label="Code *" variant="outlined" density="comfortable" :error-messages="formErrors.code" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.academic_year" label="Année académique *" placeholder="2025-2026" variant="outlined" density="comfortable" :error-messages="formErrors.academic_year" />
            </v-col>
            <v-col cols="12">
              <v-text-field v-model="form.label" label="Libellé *" variant="outlined" density="comfortable" :error-messages="formErrors.label" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.order_index" label="Ordre" type="number" min="1" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.niveau_id"
                :items="niveaux"
                item-title="label"
                item-value="id"
                label="Niveau"
                variant="outlined"
                density="comfortable"
                clearable
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.open_at" label="Ouverture" type="datetime-local" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.close_at" label="Fermeture" type="datetime-local" variant="outlined" density="comfortable" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="outlined" color="grey-darken-1" @click="formDialog = false">Annuler</v-btn>
          <v-spacer />
          <v-btn color="#0F2D5E" :loading="submitting" @click="submitForm">
            {{ editing ? 'Enregistrer' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ─── Snackbar ─────────────────────────────────────────────── -->
    <v-snackbar v-model="snack.show" :color="snack.color" location="bottom right" timeout="3500" rounded="lg">
      <v-icon size="18" class="mr-2">{{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
      {{ snack.text }}
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/api/axios'

/* ─── État ───────────────────────────────────────────────────────── */
const loading   = ref(true)
const toggling  = ref(false)
const submitting = ref(false)
const semestres = ref([])
const niveaux   = ref([])
const editing   = ref(false)

/* Snackbar */
const snack = reactive({ show: false, text: '', color: 'success' })
function notify(text, color = 'success') {
  snack.text  = text
  snack.color = color
  snack.show  = true
}

/* Dialogs */
const confirmDialog = ref(false)
const formDialog    = ref(false)

/* Données de confirmation */
const confirmMeta = reactive({ title:'', body:'', icon:'mdi-help-circle', iconColor:'warning', btnColor:'primary' })
let toggleTarget = null
let toggleKind   = null

/* Formulaire */
const formErrors = reactive({})
const form = reactive({ code:'', label:'', academic_year:'', order_index:1, niveau_id:null, open_at:'', close_at:'' })

/* ─── Helpers ────────────────────────────────────────────────────── */
function formatDate(raw) {
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' })
  } catch { return raw }
}

function calcProgress(s) {
  if (!s.open_at || !s.close_at) return 0
  const start = new Date(s.open_at).getTime()
  const end   = new Date(s.close_at).getTime()
  const now   = Date.now()
  if (now <= start) return 0
  if (now >= end)   return 100
  return Math.round(((now - start) / (end - start)) * 100)
}

function daysRemaining(closeAt) {
  if (!closeAt) return -1
  const diff = new Date(closeAt).getTime() - Date.now()
  return diff < 0 ? -1 : Math.ceil(diff / 86400000)
}

/* ─── Chargement ─────────────────────────────────────────────────── */
async function loadData() {
  loading.value = true
  try {
    const [semRes, nivRes] = await Promise.all([
      api.get('/admin/semestres'),
      api.get('/admin/niveaux'),
    ])
    semestres.value = semRes.data?.data ?? semRes.data ?? []
    niveaux.value   = nivRes.data?.data ?? nivRes.data ?? []
  } catch (err) {
    notify('Impossible de charger les données.', 'error')
    console.error('[Semestres] loadData:', err)
  } finally {
    loading.value = false
  }
}

/* ─── Toggle ─────────────────────────────────────────────────────── */
function askToggle(sem, kind) {
  toggleTarget = sem
  toggleKind   = kind
  const isOpen = kind === 'exam'
    ? sem.is_exam_open
    : kind === 'rattrapage'
      ? sem.is_rattrapage_open
      : sem.is_open
  const labels = {
    reclamation: ['réclamation', 'mdi-file-document', 'primary'],
    exam:        ['examen',      'mdi-school',        'orange-darken-1'],
    rattrapage:  ['rattrapage',  'mdi-refresh',       'deep-purple'],
  }
  const [lbl, icon, btnColor] = labels[kind] ?? ['période', 'mdi-calendar', 'primary']
  confirmMeta.title     = `${isOpen ? 'Fermer' : 'Ouvrir'} la période de ${lbl}`
  confirmMeta.body      = `Voulez-vous ${isOpen ? 'fermer' : 'ouvrir'} la période de ${lbl} pour le semestre « ${sem.label} » ?`
  confirmMeta.icon      = isOpen ? 'mdi-lock' : 'mdi-lock-open'
  confirmMeta.iconColor = isOpen ? 'error' : 'success'
  confirmMeta.btnColor  = isOpen ? 'error' : 'success'
  confirmDialog.value   = true
}

async function confirmToggle() {
  if (!toggleTarget || !toggleKind) return
  toggling.value = true
  try {
    const urlMap = {
      reclamation: `/admin/semestres/${toggleTarget.id}/toggle`,
      exam:        `/admin/semestres/${toggleTarget.id}/toggle-exam`,
      rattrapage:  `/admin/semestres/${toggleTarget.id}/toggle-rattrapage`,
    }
    const res = await api.put(urlMap[toggleKind])
    /* mise à jour locale */
    const idx = semestres.value.findIndex(s => s.id === toggleTarget.id)
    if (idx !== -1) {
      if (toggleKind === 'reclamation') semestres.value[idx].is_open            = res.data?.data?.is_open            ?? !toggleTarget.is_open
      if (toggleKind === 'exam')        semestres.value[idx].is_exam_open        = res.data?.data?.is_exam_open        ?? !toggleTarget.is_exam_open
      if (toggleKind === 'rattrapage')  semestres.value[idx].is_rattrapage_open  = res.data?.data?.is_rattrapage_open  ?? !toggleTarget.is_rattrapage_open
    }
    notify(res.data?.message ?? 'Période mise à jour.', 'success')
  } catch (err) {
    notify(err.response?.data?.message ?? 'Erreur lors du toggle.', 'error')
  } finally {
    toggling.value    = false
    confirmDialog.value = false
    toggleTarget = null
    toggleKind   = null
  }
}

/* ─── Formulaire ─────────────────────────────────────────────────── */
function resetForm() {
  form.code          = ''
  form.label         = ''
  form.academic_year = ''
  form.order_index   = 1
  form.niveau_id     = null
  form.open_at       = ''
  form.close_at      = ''
  Object.keys(formErrors).forEach(k => delete formErrors[k])
}

let editingId = null

function openCreateDialog() {
  editing.value = false
  editingId     = null
  resetForm()
  formDialog.value = true
}

function openEditDialog(sem) {
  editing.value = true
  editingId     = sem.id
  form.code          = sem.code ?? ''
  form.label         = sem.label ?? ''
  form.academic_year = sem.academic_year ?? ''
  form.order_index   = sem.order_index ?? 1
  form.niveau_id     = sem.niveau_id ?? null
  /* convertir en format datetime-local (YYYY-MM-DDTHH:mm) */
  form.open_at  = sem.open_at  ? sem.open_at.slice(0, 16)  : ''
  form.close_at = sem.close_at ? sem.close_at.slice(0, 16) : ''
  Object.keys(formErrors).forEach(k => delete formErrors[k])
  formDialog.value = true
}

async function submitForm() {
  Object.keys(formErrors).forEach(k => delete formErrors[k])
  /* Validation basique */
  let valid = true
  if (!form.code.trim())          { formErrors.code          = 'Champ obligatoire.'; valid = false }
  if (!form.label.trim())         { formErrors.label         = 'Champ obligatoire.'; valid = false }
  if (!form.academic_year.trim()) { formErrors.academic_year = 'Champ obligatoire.'; valid = false }
  if (!valid) return

  submitting.value = true
  try {
    const payload = {
      code:          form.code.trim(),
      label:         form.label.trim(),
      academic_year: form.academic_year.trim(),
      order_index:   Number(form.order_index) || 1,
      niveau_id:     form.niveau_id || null,
      open_at:       form.open_at  || null,
      close_at:      form.close_at || null,
    }
    if (editing.value) {
      await api.put(`/admin/semestres/${editingId}`, payload)
      notify('Semestre mis à jour avec succès.', 'success')
    } else {
      await api.post('/admin/semestres', payload)
      notify('Semestre créé avec succès.', 'success')
    }
    formDialog.value = false
    await loadData()
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) Object.assign(formErrors, errors)
    notify(err.response?.data?.message ?? 'Erreur lors de l\'enregistrement.', 'error')
  } finally {
    submitting.value = false
  }
}

/* ─── Lifecycle ──────────────────────────────────────────────────── */
onMounted(loadData)
</script>

<style scoped>
/* ── Layout ── */
.semestres-page { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }

/* ── Header ── */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 28px; }
.page-title  { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin: 0; }
.page-sub    { font-size: .875rem; color: #64748B; margin: 4px 0 0; }

/* ── Loaders & vide ── */
.loading-center { display: flex; justify-content: center; align-items: center; min-height: 240px; }
.empty-state    { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 320px; gap: 12px; text-align: center; }
.empty-title    { font-size: 1.1rem; font-weight: 600; color: #475569; margin: 0; }
.empty-sub      { font-size: .875rem; color: #94A3B8; margin: 0; }

/* ── Grille ── */
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

/* ── Carte ── */
.sem-card {
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 1px 6px rgba(0,0,0,.06);
  transition: box-shadow .2s, transform .2s;
}
.sem-card:hover { box-shadow: 0 6px 24px rgba(15,45,94,.10); transform: translateY(-2px); }

/* Header de carte */
.card-top   { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.card-meta  { display: flex; flex-direction: column; gap: 2px; }
.card-code  { font-size: .8rem; font-weight: 700; color: #0F2D5E; letter-spacing: .05em; text-transform: uppercase; background: #EFF6FF; border-radius: 6px; padding: 2px 8px; width: fit-content; }
.card-label { font-size: .95rem; font-weight: 600; color: #1E293B; margin-top: 4px; }
.card-year  { font-size: .78rem; color: #94A3B8; display: flex; align-items: center; gap: 3px; }
.card-chips { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }

/* Dates */
.card-dates   { display: flex; align-items: center; gap: 5px; font-size: .78rem; color: #6B7280; flex-wrap: wrap; margin-bottom: 4px; }
.days-badge   { background: #FEF9C3; color: #854D0E; border-radius: 20px; padding: 1px 8px; font-size: .72rem; font-weight: 600; margin-left: 4px; }

/* Périodes */
.periods-grid { display: flex; flex-direction: column; gap: 8px; }
.period-row   { display: flex; align-items: center; gap: 6px; }
.period-lbl   { font-size: .82rem; color: #475569; flex: 1; }

/* Actions */
.card-actions { display: flex; justify-content: space-between; gap: 8px; }

/* ── Dialog confirmation ── */
.confirm-icon-wrap { text-align: center; margin-bottom: 12px; }
.confirm-title     { font-size: 1.05rem; font-weight: 700; color: #1E293B; text-align: center; margin-bottom: 8px; }
.confirm-body      { font-size: .875rem; color: #64748B; text-align: center; margin-bottom: 20px; line-height: 1.5; }
.confirm-btns      { display: flex; justify-content: center; gap: 12px; }

/* ── Dialog formulaire ── */
.form-dialog-head  { display: flex; align-items: center; font-size: 1rem; font-weight: 700; color: #0F2D5E; padding: 20px 24px 16px; }
</style>
