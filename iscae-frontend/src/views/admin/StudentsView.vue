<template>
  <div class="students-page">

    <!-- ── En-tête ──────────────────────────────────────────── -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Gestion des étudiants</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ meta.total }} étudiant(s) enregistré(s)
        </p>
      </div>
      
    </div>

    <!-- ── Filtres ───────────────────────────────────────────── -->
    <v-card elevation="1" rounded="xl" class="mb-4 pa-4">
      <v-row dense align="center">
        <!-- Recherche -->
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.search"
            placeholder="Rechercher par nom, matricule, NNI, email…"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @update:model-value="debouncedLoad"
          />
        </v-col>

        <!-- Filière -->
        <v-col cols="12" sm="6" md="2">
          <v-select
            v-model="filters.filiere_id"
            :items="filterData.filieres"
            item-title="name"
            item-value="id"
            label="Filière"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @update:model-value="load"
          />
        </v-col>

        <!-- Niveau -->
        <v-col cols="12" sm="6" md="2">
          <v-select
            v-model="filters.niveau_id"
            :items="filterData.niveaux"
            item-title="label"
            item-value="id"
            label="Niveau"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @update:model-value="load"
          />
        </v-col>

        <!-- Statut -->
        <v-col cols="12" sm="6" md="2">
          <v-select
            v-model="filters.status"
            :items="statusItems"
            label="Statut"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @update:model-value="load"
          />
        </v-col>

        <!-- Année académique -->
        <v-col cols="12" sm="6" md="2">
          <v-select
            v-model="filters.academic_year"
            :items="filterData.years"
            label="Année académique"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @update:model-value="load"
          />
        </v-col>
      </v-row>
    </v-card>

    <!-- ── KPI rapide ─────────────────────────────────────────── -->
    <v-row dense class="mb-4">
      <v-col cols="6" sm="3" v-for="k in kpis" :key="k.label">
        <v-card elevation="1" rounded="xl" class="pa-4 text-center">
          <v-icon :color="k.color" size="28" class="mb-1">{{ k.icon }}</v-icon>
          <div class="text-h6 font-weight-bold" :style="`color:${k.color}`">
            {{ loading ? '—' : k.value }}
          </div>
          <div class="text-caption text-medium-emphasis">{{ k.label }}</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ── Tableau ───────────────────────────────────────────── -->
    <v-card elevation="2" rounded="xl">
      <!-- Skeleton -->
      <div v-if="loading" class="pa-4">
        <v-skeleton-loader
          v-for="i in 8" :key="i"
          type="table-row"
          class="mb-1"
        />
      </div>

      <template v-else>
        <!-- En-tête tableau -->
        <v-table hover>
          <thead>
            <tr>
              <th class="text-left" style="width:50px">#</th>
              <th class="text-left">Étudiant</th>
              <th class="text-left sortable" @click="setSort('matricule')">
                Matricule
                <v-icon size="14" class="ml-1">
                  {{ sortIcon('matricule') }}
                </v-icon>
              </th>
              <th class="text-left">Filière / Niveau</th>
              <th class="text-left sortable" @click="setSort('academic_year')">
                Année
                <v-icon size="14" class="ml-1">{{ sortIcon('academic_year') }}</v-icon>
              </th>
              <th class="text-center">Réclamations</th>
              <th class="text-center">Statut</th>
              <th class="text-left sortable" @click="setSort('last_login_at')">
                Dernière connexion
                <v-icon size="14" class="ml-1">{{ sortIcon('last_login_at') }}</v-icon>
              </th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in students" :key="s.id">
              <!-- # -->
              <td class="text-medium-emphasis text-caption">{{ s.id }}</td>

              <!-- Étudiant -->
              <td>
                <div class="d-flex align-center gap-3 py-2">
                  <v-avatar :color="avatarColor(s.full_name)" size="38">
                    <v-img v-if="s.photo_url" :src="s.photo_url" cover />
                    <span v-else class="text-caption font-weight-bold text-white">
                      {{ initials(s.full_name) }}
                    </span>
                  </v-avatar>
                  <div>
                    <div class="text-body-2 font-weight-medium">{{ s.full_name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ s.email }}</div>
                  </div>
                </div>
              </td>

              <!-- Matricule -->
              <td>
                <code class="text-caption bg-surface-variant px-2 py-1 rounded">
                  {{ s.matricule }}
                </code>
              </td>

              <!-- Filière / Niveau -->
              <td>
                <div class="text-body-2">{{ s.filiere_name }}</div>
                <v-chip size="x-small" color="indigo" variant="tonal" class="mt-1">
                  {{ s.niveau_label }}
                </v-chip>
              </td>

              <!-- Année -->
              <td class="text-body-2">{{ s.academic_year ?? '—' }}</td>

              <!-- Réclamations -->
              <td class="text-center">
                <div class="d-flex align-center justify-center gap-1">
                  <v-chip size="x-small" color="primary" variant="tonal">
                    {{ s.reclamations_count }} total
                  </v-chip>
                  <v-chip
                    v-if="s.reclamations_open > 0"
                    size="x-small"
                    color="warning"
                    variant="tonal"
                  >
                    {{ s.reclamations_open }} ouvert(s)
                  </v-chip>
                </div>
              </td>

              <!-- Statut -->
              <td class="text-center">
                <v-chip
                  size="small"
                  :color="s.is_active ? 'success' : 'error'"
                  variant="tonal"
                  label
                >
                  <v-icon start size="12">
                    {{ s.is_active ? 'mdi-check-circle' : 'mdi-cancel' }}
                  </v-icon>
                  {{ s.is_active ? 'Actif' : 'Inactif' }}
                </v-chip>
              </td>

              <!-- Dernière connexion -->
              <td class="text-caption text-medium-emphasis">
                {{ fDate(s.last_login_at) }}
              </td>

              <!-- Actions -->
              <td class="text-center">
                <v-btn
                  icon
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openDetail(s)"
                >
                  <v-icon size="18">mdi-eye</v-icon>
                </v-btn>
                <v-btn
                  icon
                  size="small"
                  variant="text"
                  :color="s.is_active ? 'error' : 'success'"
                  @click="confirmToggle(s)"
                >
                  <v-icon size="18">
                    {{ s.is_active ? 'mdi-account-cancel' : 'mdi-account-check' }}
                  </v-icon>
                </v-btn>
              </td>
            </tr>

            <!-- Vide -->
            <tr v-if="!students.length">
              <td colspan="9" class="text-center py-12 text-medium-emphasis">
                <v-icon size="48" class="mb-3 d-block opacity-30">mdi-account-search</v-icon>
                Aucun étudiant trouvé
              </td>
            </tr>
          </tbody>
        </v-table>

        <!-- Pagination -->
        <div class="d-flex align-center justify-space-between px-4 py-3 border-t">
          <span class="text-caption text-medium-emphasis">
            {{ paginationInfo }}
          </span>
          <v-pagination
            v-model="filters.page"
            :length="meta.last_page"
            :total-visible="6"
            density="compact"
            @update:model-value="load"
          />
        </div>
      </template>
    </v-card>

    <!-- ══════════════════════════════════════════════════════
         DIALOG : Détail étudiant
    ══════════════════════════════════════════════════════ -->
    <v-dialog v-model="detailDialog" max-width="760" scrollable>
      <v-card rounded="xl" v-if="selectedStudent">
        <!-- Header -->
        <v-card-title class="pa-6 pb-4 d-flex align-center gap-4">
          <v-avatar :color="avatarColor(selectedStudent.full_name)" size="56">
            <v-img v-if="selectedStudent.photo_url" :src="selectedStudent.photo_url" cover />
            <span v-else class="text-h6 font-weight-bold text-white">
              {{ initials(selectedStudent.full_name) }}
            </span>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">{{ selectedStudent.full_name }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ selectedStudent.email }}</div>
          </div>
          <v-spacer />
          <v-chip
            :color="selectedStudent.is_active ? 'success' : 'error'"
            variant="tonal"
            size="small"
          >
            {{ selectedStudent.is_active ? 'Actif' : 'Inactif' }}
          </v-chip>
        </v-card-title>
        <v-divider />

        <v-card-text class="pa-6">
          <v-row dense>
            <!-- Infos personnelles -->
            <v-col cols="12">
              <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center gap-2">
                <v-icon color="primary" size="18">mdi-account</v-icon>
                Informations personnelles
              </div>
            </v-col>

            <v-col cols="6" sm="4" v-for="info in studentInfos" :key="info.label">
              <div class="info-block pa-3 rounded-lg mb-2">
                <div class="text-caption text-medium-emphasis mb-1">{{ info.label }}</div>
                <div class="text-body-2 font-weight-medium">{{ info.value || '—' }}</div>
              </div>
            </v-col>

            <!-- Réclamations -->
            <v-col cols="12" class="mt-4">
              <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center gap-2">
                <v-icon color="primary" size="18">mdi-file-document-multiple</v-icon>
                Réclamations ({{ detailReclamations.length }})
              </div>
            </v-col>

            <v-col cols="12" v-if="loadingDetail">
              <v-skeleton-loader type="list-item-two-line" v-for="i in 3" :key="i" />
            </v-col>

            <v-col cols="12" v-else-if="detailReclamations.length">
              <v-table density="compact">
                <thead>
                  <tr>
                    <th>Référence</th>
                    <th>Module</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in detailReclamations" :key="r.id">
                    <td>
                      <code class="text-caption">{{ r.reference_number }}</code>
                    </td>
                    <td class="text-caption">{{ r.module_name ?? '—' }}</td>
                    <td>
                      <v-chip size="x-small" :color="typeColor(r.type)" variant="tonal">
                        {{ typeLabel(r.type) }}
                      </v-chip>
                    </td>
                    <td>
                      <v-chip size="x-small" :color="statusColor(r.status)" variant="tonal">
                        {{ statusLabel(r.status) }}
                      </v-chip>
                    </td>
                    <td class="text-caption">{{ fDate(r.created_at) }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-col>

            <v-col cols="12" v-else>
              <div class="text-center text-medium-emphasis py-6">
                <v-icon size="32" class="mb-2 opacity-30">mdi-inbox-outline</v-icon>
                <p class="text-body-2">Aucune réclamation</p>
              </div>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn
            :color="selectedStudent.is_active ? 'error' : 'success'"
            variant="tonal"
            :loading="togglingStatus"
            @click="toggleStatus(selectedStudent)"
          >
            <v-icon start>
              {{ selectedStudent.is_active ? 'mdi-account-cancel' : 'mdi-account-check' }}
            </v-icon>
            {{ selectedStudent.is_active ? 'Désactiver le compte' : 'Activer le compte' }}
          </v-btn>
          <v-spacer />
          <v-btn variant="text" @click="detailDialog = false">Fermer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ══════════════════════════════════════════════════════
         DIALOG : Confirmation toggle statut
    ══════════════════════════════════════════════════════ -->
    <v-dialog v-model="toggleDialog" max-width="420">
      <v-card rounded="xl" v-if="toggleTarget">
        <v-card-title class="pa-5">
          <v-icon :color="toggleTarget.is_active ? 'error' : 'success'" class="mr-2">
            {{ toggleTarget.is_active ? 'mdi-account-cancel' : 'mdi-account-check' }}
          </v-icon>
          {{ toggleTarget.is_active ? 'Désactiver' : 'Activer' }} le compte
        </v-card-title>
        <v-card-text class="pa-5 pt-0">
          <p class="text-body-2 mb-4">
            Voulez-vous {{ toggleTarget.is_active ? 'désactiver' : 'activer' }} le compte de
            <strong>{{ toggleTarget.full_name }}</strong> ?
          </p>
          
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="toggleDialog = false">Annuler</v-btn>
          <v-btn
            :color="toggleTarget.is_active ? 'error' : 'success'"
            variant="elevated"
            :loading="togglingStatus"
            @click="doToggle"
          >
            Confirmer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ── Snackbar ───────────────────────────────────────── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      :timeout="4000"
      location="bottom right"
      rounded="lg"
    >
      <v-icon class="mr-2">
        {{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
      </v-icon>
      {{ snack.text }}
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── State ──────────────────────────────────────────────────
const loading       = ref(true)
const loadingDetail = ref(false)
const togglingStatus = ref(false)
const students      = ref([])
const detailReclamations = ref([])
const filterData    = ref({ filieres: [], niveaux: [], years: [] })
const meta          = ref({ total: 0, per_page: 15, current_page: 1, last_page: 1 })
const detailDialog  = ref(false)
const toggleDialog  = ref(false)
const selectedStudent = ref(null)
const toggleTarget  = ref(null)
const toggleReason  = ref('')
const snack         = ref({ show: false, text: '', color: 'success' })

// ── Filtres ────────────────────────────────────────────────
const filters = ref({
  search:        '',
  filiere_id:    null,
  niveau_id:     null,
  status:        null,
  academic_year: null,
  page:          1,
  per_page:      15,
  sort:          'created_at',
  dir:           'desc',
})

const statusItems = [
  { title: 'Actif',   value: 'active'   },
  { title: 'Inactif', value: 'inactive' },
]

// ── Computed ───────────────────────────────────────────────
const paginationInfo = computed(() => {
  const { current_page, per_page, total } = meta.value
  const from = ((current_page - 1) * per_page) + 1
  const to   = Math.min(current_page * per_page, total)
  return total > 0 ? `${from}–${to} sur ${total} étudiants` : '0 étudiant'
})

const kpis = computed(() => [
  {
    label: 'Total',
    value: meta.value.total,
    icon:  'mdi-account-group',
    color: '#1976D2',
  },
  {
    label: 'Actifs',
    value: students.value.filter(s => s.is_active).length,
    icon:  'mdi-account-check',
    color: '#4CAF50',
  },
  {
    label: 'Inactifs',
    value: students.value.filter(s => !s.is_active).length,
    icon:  'mdi-account-cancel',
    color: '#F44336',
  },
  {
    label: 'Avec réclamations',
    value: students.value.filter(s => s.reclamations_count > 0).length,
    icon:  'mdi-file-document-alert',
    color: '#FF9800',
  },
])

const studentInfos = computed(() => {
  const s = selectedStudent.value
  if (!s) return []
  return [
    { label: 'Matricule',      value: s.matricule },
    { label: 'NNI',            value: s.nni },
    { label: 'Filière',        value: s.filiere_name },
    { label: 'Niveau',         value: s.niveau_label },
    { label: 'Année acad.',    value: s.academic_year },
    { label: 'Téléphone',      value: s.phone },
    { label: 'Date naissance', value: s.date_naissance },
    { label: 'Nationalité',    value: s.nationalite },
    { label: 'Adresse',        value: s.adresse },
    { label: 'Dernière co.',   value: fDate(s.last_login_at) },
    { label: 'Inscrit le',     value: fDate(s.created_at) },
    { label: 'Réclamations',   value: `${s.reclamations_count} (${s.reclamations_open} ouverte(s))` },
  ]
})

// ── Helpers ────────────────────────────────────────────────
function notify(text, color = 'success') {
  snack.value = { show: true, text, color }
}

function fDate(raw) {
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleDateString('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric',
    })
  } catch { return raw }
}

function initials(name) {
  if (!name) return '?'
  const parts = name.trim().split(' ').filter(Boolean)
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase()
}

function avatarColor(name) {
  const colors = ['indigo','teal','purple','blue','cyan','green','orange','red','pink']
  if (!name) return 'indigo'
  return colors[name.charCodeAt(0) % colors.length]
}

function statusLabel(s) {
  const m = { submitted:'Soumise', received:'Reçue', in_review:'En cours',
              escalated:'Escaladée', resolved:'Résolue', rejected:'Rejetée' }
  return m[s] ?? s
}
function statusColor(s) {
  const m = { submitted:'orange', received:'blue', in_review:'purple',
              escalated:'deep-orange', resolved:'success', rejected:'error' }
  return m[s] ?? 'grey'
}
function typeLabel(t) {
  return { controle:'Contrôle', examen:'Examen', rattrapage:'Rattrapage' }[t] ?? t
}
function typeColor(t) {
  return { controle:'blue', examen:'purple', rattrapage:'orange' }[t] ?? 'grey'
}

// ── Tri ────────────────────────────────────────────────────
function setSort(col) {
  if (filters.value.sort === col) {
    filters.value.dir = filters.value.dir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sort = col
    filters.value.dir  = 'asc'
  }
  filters.value.page = 1
  load()
}

function sortIcon(col) {
  if (filters.value.sort !== col) return 'mdi-unfold-more-horizontal'
  return filters.value.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
}

// ── Debounce recherche ─────────────────────────────────────
let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    filters.value.page = 1
    load()
  }, 400)
}

// ── Chargement liste ───────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const params = {
      page:          filters.value.page,
      per_page:      filters.value.per_page,
      sort:          filters.value.sort,
      dir:           filters.value.dir,
    }
    if (filters.value.search)        params.search        = filters.value.search
    if (filters.value.filiere_id)    params.filiere_id    = filters.value.filiere_id
    if (filters.value.niveau_id)     params.niveau_id     = filters.value.niveau_id
    if (filters.value.status)        params.status        = filters.value.status
    if (filters.value.academic_year) params.academic_year = filters.value.academic_year

    const res = await api.get('/admin/students', { params })
    const d   = res.data

    students.value  = d.data    ?? []
    meta.value      = d.meta    ?? meta.value

    // Listes filtres (chargées une seule fois)
    if (d.filters) {
      filterData.value = {
        filieres: d.filters.filieres ?? [],
        niveaux:  d.filters.niveaux  ?? [],
        years:    d.filters.years    ?? [],
      }
    }
  } catch (err) {
    console.error('[StudentsView] load error', err)
    notify('Impossible de charger les étudiants.', 'error')
  } finally {
    loading.value = false
  }
}

// ── Ouvrir détail ──────────────────────────────────────────
async function openDetail(student) {
  selectedStudent.value    = student
  detailReclamations.value = []
  detailDialog.value       = true
  loadingDetail.value      = true
  try {
    const res = await api.get(`/admin/students/${student.id}`)
    const d   = res.data?.data ?? {}
    // Fusionner les données enrichies
    selectedStudent.value    = { ...student, ...d }
    detailReclamations.value = d.reclamations ?? []
  } catch (err) {
    console.error('[StudentsView] detail error', err)
  } finally {
    loadingDetail.value = false
  }
}

// ── Toggle statut ──────────────────────────────────────────
function confirmToggle(student) {
  toggleTarget.value = student
  toggleReason.value = ''
  toggleDialog.value = true
}

async function doToggle() {
  if (!toggleTarget.value) return
  await toggleStatus(toggleTarget.value)
  toggleDialog.value = false
}

async function toggleStatus(student) {
  togglingStatus.value = true
  try {
    await api.put(`/admin/students/${student.id}/status`, {
      is_active: !student.is_active,
      reason:    toggleReason.value || null,
    })
    const label = !student.is_active ? 'activé' : 'désactivé'
    notify(`Compte ${label} avec succès.`)
    // Mettre à jour localement
    const idx = students.value.findIndex(s => s.id === student.id)
    if (idx !== -1) students.value[idx].is_active = !student.is_active
    if (selectedStudent.value?.id === student.id) {
      selectedStudent.value.is_active = !student.is_active
    }
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Erreur lors de la mise à jour.'
    notify(msg, 'error')
  } finally {
    togglingStatus.value = false
  }
}

// ── Lifecycle ──────────────────────────────────────────────
onMounted(load)
</script>

<style scoped>
.students-page {
  max-width: 1300px;
  margin: 0 auto;
  padding: 24px 16px;
}

.sortable {
  cursor: pointer;
  user-select: none;
}
.sortable:hover {
  color: rgb(var(--v-theme-primary));
}

.info-block {
  background: rgba(var(--v-theme-surface-variant), .5);
  border: 1px solid rgba(var(--v-theme-on-surface), .06);
}

.border-t {
  border-top: 1px solid rgba(var(--v-theme-on-surface), .08);
}
</style>
