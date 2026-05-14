<template>
  <div class="semestres-page">

    <!-- ─── En-tête ─────────────────────────────────────────────── -->
    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">Gérez les périodes de Devoir, d'Examen et de Rattrapage.</h1>
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
             <!-- Statut global basé sur l'ouverture de n'importe quelle période -->
            <v-chip size="x-small" :color="(s.is_open || s.is_exam_open || s.is_rattrapage_open) ? 'success' : 'error'" variant="flat">
              {{ (s.is_open || s.is_exam_open || s.is_rattrapage_open) ? 'Actif' : 'Inactif' }}
            </v-chip>
            <v-chip v-if="s.niveau" size="x-small" color="indigo" variant="tonal">
              {{ s.niveau.label ?? s.niveau.code }}
            </v-chip>
          </div>
        </div>

        <!-- Dates globales du semestre -->
        <div class="card-dates">
          <v-icon size="13" color="#6B7280">mdi-clock-outline</v-icon>
          <span>{{ formatDate(s.open_at) }}</span>
          <v-icon size="11" color="#D1D5DB">mdi-arrow-right-thin</v-icon>
          <span>{{ formatDate(s.close_at) }}</span>
        </div>

        <v-divider class="my-3" />

        <!-- ── Grille des Périodes (CC, Examen, Rattrapage) ── -->
        <div class="periods-grid">
          <!-- Contrôle Continu (CC) -->
          <div class="period-row">
            <v-icon size="16" :color="s.is_open ? '#22C55E' : '#CBD5E1'">
              {{ s.is_open ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
            <span class="period-lbl">Devoir</span>
            <v-btn
              density="compact" size="x-small"
              :color="s.is_open ? 'error' : 'success'"
              variant="tonal"
              @click="askToggle(s, 'cc')"
            >
              {{ s.is_open ? 'Fermer' : 'Ouvrir' }}
            </v-btn>
          </div>

          <!-- Examen -->
          <div class="period-row">
            <v-icon size="16" :color="s.is_exam_open ? '#22C55E' : '#CBD5E1'">
              {{ s.is_exam_open ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
            <span class="period-lbl">Examen</span>
            <v-btn
              density="compact" size="x-small"
              :color="s.is_exam_open ? 'error' : 'success'"
              variant="tonal"
              @click="askToggle(s, 'exam')"
            >
              {{ s.is_exam_open ? 'Fermer' : 'Ouvrir' }}
            </v-btn>
          </div>

          <!-- Rattrapage -->
          <div class="period-row">
            <v-icon size="16" :color="s.is_rattrapage_open ? '#22C55E' : '#CBD5E1'">
              {{ s.is_rattrapage_open ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
            <span class="period-lbl">Rattrapage</span>
            <v-btn
              density="compact" size="x-small"
              :color="s.is_rattrapage_open ? 'error' : 'success'"
              variant="tonal"
              @click="askToggle(s, 'rattrapage')"
            >
              {{ s.is_rattrapage_open ? 'Fermer' : 'Ouvrir' }}
            </v-btn>
          </div>
        </div>

        <v-divider class="my-3" />

        <!-- Actions de gestion -->
        <div class="card-actions">
          <v-spacer />
          <v-btn
            size="small" variant="outlined" color="#0F2D5E"
            prepend-icon="mdi-pencil"
            @click="openEditDialog(s)"
          >
            Modifier
          </v-btn>
        </div>

      </div>
    </div>

    <!-- ─── Dialog confirmation ──────────────────────────────────── -->
    <v-dialog v-model="confirmDialog" max-width="420">
      <v-card rounded="xl" class="pa-6">
        <div class="confirm-icon-wrap">
          <v-icon :color="confirmMeta.iconColor" size="48">{{ confirmMeta.icon }}</v-icon>
        </div>
        <div class="confirm-title">{{ confirmMeta.title }}</div>
        <div class="confirm-body">{{ confirmMeta.body }}</div>
        <div class="confirm-btns">
          <v-btn variant="text" color="grey-darken-1" @click="confirmDialog = false">Annuler</v-btn>
          <v-btn :color="confirmMeta.btnColor" rounded="lg" :loading="toggling" @click="confirmToggle">
            Confirmer
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ─── Dialog formulaire (Create/Edit) ───────────────────────── -->
    <v-dialog v-model="formDialog" max-width="540" scrollable>
      <v-card rounded="xl">
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
              <v-select
                v-model="form.niveau_id"
                :items="niveaux"
                item-title="label"
                item-value="id"
                label="Niveau"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.order_index" label="Ordre" type="number" variant="outlined" density="comfortable" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" @click="formDialog = false">Annuler</v-btn>
          <v-spacer />
          <v-btn color="#0F2D5E" :loading="submitting" @click="submitForm">
            {{ editing ? 'Enregistrer' : 'Créer' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000">
      {{ snack.text }}
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/api/axios'

/* État */
const loading = ref(true)
const toggling = ref(false)
const submitting = ref(false)
const semestres = ref([])
const niveaux = ref([])
const editing = ref(false)
const snack = reactive({ show: false, text: '', color: 'success' })

/* Dialogs */
const confirmDialog = ref(false)
const formDialog = ref(false)
const confirmMeta = reactive({ title:'', body:'', icon:'', iconColor:'', btnColor:'' })
let toggleTarget = null
let toggleKind = null

/* Formulaire */
const formErrors = reactive({})
const form = reactive({ code:'', label:'', academic_year:'', order_index:1, niveau_id:null })
let editingId = null

function notify(text, color = 'success') {
  snack.text = text; snack.color = color; snack.show = true
}

function formatDate(raw) {
  if (!raw) return 'Non défini'
  return new Date(raw).toLocaleDateString('fr-FR')
}

async function loadData() {
  loading.value = true
  try {
    const [semRes, nivRes] = await Promise.all([
      api.get('/admin/semestres'),
      api.get('/admin/niveaux'),
    ])
    semestres.value = semRes.data?.data || []
    niveaux.value = nivRes.data?.data || []
  } catch (err) {
    notify('Erreur de chargement', 'error')
  } finally {
    loading.value = false
  }
}

/* Gestion des Toggles (CC, Exam, Rattrapage) */
function askToggle(sem, kind) {
  toggleTarget = sem
  toggleKind = kind
  
  // Mapping pour identifier l'état actuel
  const states = {
    cc: sem.is_open,
    exam: sem.is_exam_open,
    rattrapage: sem.is_rattrapage_open
  }
  const isOpen = states[kind]
  
  const labels = {
    cc: ['Contrôle Continu', 'mdi-file-edit'],
    exam: ['Examens', 'mdi-school'],
    rattrapage: ['Rattrapage', 'mdi-refresh']
  }

  confirmMeta.title = `${isOpen ? 'Fermer' : 'Ouvrir'} - ${labels[kind][0]}`
  confirmMeta.body = `Confirmez-vous la modification pour le semestre ${sem.code} ?`
  confirmMeta.icon = labels[kind][1]
  confirmMeta.iconColor = isOpen ? 'error' : 'success'
  confirmMeta.btnColor = isOpen ? 'error' : 'success'
  confirmDialog.value = true
}

async function confirmToggle() {
  toggling.value = true
  try {
    const urls = {
      cc: `/admin/semestres/${toggleTarget.id}/toggle`,
      exam: `/admin/semestres/${toggleTarget.id}/toggle-exam`,
      rattrapage: `/admin/semestres/${toggleTarget.id}/toggle-rattrapage`
    }
    
    const res = await api.put(urls[toggleKind])
    
    // Mise à jour de la ligne dans le tableau local avec les données renvoyées par le serveur
    const idx = semestres.value.findIndex(s => s.id === toggleTarget.id)
    if (idx !== -1 && res.data?.data) {
      semestres.value[idx] = res.data.data
    }
    
    notify(res.data?.message || 'Mise à jour réussie')
  } catch (err) {
    notify('Erreur lors de la mise à jour', 'error')
  } finally {
    toggling.value = false
    confirmDialog.value = false
  }
}

/* Formulaire Logic */
function openCreateDialog() {
  editing.value = false
  editingId = null
  Object.assign(form, { code:'', label:'', academic_year:'', order_index:1, niveau_id:null })
  formDialog.value = true
}

function openEditDialog(sem) {
  editing.value = true
  editingId = sem.id
  Object.assign(form, { ...sem })
  formDialog.value = true
}

async function submitForm() {
  submitting.value = true
  try {
    if (editing.value) {
      await api.put(`/admin/semestres/${editingId}`, form)
    } else {
      await api.post('/admin/semestres', form)
    }
    formDialog.value = false
    loadData()
    notify('Enregistré avec succès')
  } catch (err) {
    notify('Erreur lors de l\'enregistrement', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.semestres-page { max-width: 1200px; margin: 0 auto; padding: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; }
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.sem-card { background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; transition: transform 0.2s; }
.sem-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.card-top { display: flex; justify-content: space-between; }
.card-code { background: #EFF6FF; color: #0F2D5E; padding: 2px 8px; border-radius: 6px; font-weight: bold; font-size: 0.8rem; }
.card-label { display: block; font-weight: 600; margin-top: 8px; }
.card-year { font-size: 0.8rem; color: #94A3B8; }
.periods-grid { display: flex; flex-direction: column; gap: 12px; }
.period-row { display: flex; align-items: center; gap: 12px; }
.period-lbl { flex: 1; font-size: 0.9rem; color: #475569; }
.confirm-icon-wrap { text-align: center; margin-bottom: 16px; }
.confirm-title { text-align: center; font-weight: bold; font-size: 1.2rem; }
.confirm-body { text-align: center; color: #64748B; margin: 8px 0 24px; }
.confirm-btns { display: flex; justify-content: flex-end; gap: 8px; }
.form-dialog-head { padding: 20px; font-weight: bold; font-size: 1.1rem; }
</style>