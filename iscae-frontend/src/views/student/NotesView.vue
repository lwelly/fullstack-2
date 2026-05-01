<template>
  <div>
    <!-- ═══════════════════════ HEADER ═══════════════════════ -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h2 class="text-h5 font-weight-bold text-primary">Mes Notes</h2>
        <p class="text-body-2 text-grey mt-1">
          Année académique : {{ currentYear }}
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-refresh" variant="tonal"
        @click="loadData" :loading="loading">Actualiser</v-btn>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center pa-12">
      <v-progress-circular indeterminate color="primary" size="56" />
    </div>

    <div v-else>
      <!-- ═══════════════════════ GLOBAL AVERAGE ═══════════════════════ -->
      <v-row class="mb-4">
        <v-col cols="12" sm="4">
          <v-card rounded="xl" elevation="2" class="text-center pa-5"
            :style="`border-top: 3px solid rgb(var(--v-theme-${globalAvg >= 10 ? 'success' : 'error'}))`">
            <v-avatar :color="globalAvg >= 10 ? 'success' : 'error'" size="56" class="mb-2">
              <span class="text-white text-h6 font-weight-black">{{ globalAvg }}</span>
            </v-avatar>
            <p class="text-body-1 font-weight-bold">Moyenne Générale</p>
            <v-chip :color="globalAvg >= 10 ? 'success' : 'error'"
              size="small" variant="tonal" class="mt-1">
              {{ globalAvg >= 10 ? 'Admis(e)' : 'Ajourné(e)' }}
            </v-chip>
          </v-card>
        </v-col>
        <v-col cols="6" sm="4">
          <v-card rounded="xl" elevation="1" class="text-center pa-5">
            <v-avatar color="success" size="44" class="mb-2">
              <v-icon color="white">mdi-check-circle</v-icon>
            </v-avatar>
            <p class="text-h4 font-weight-bold text-success">{{ totalPassed }}</p>
            <p class="text-caption text-grey">Modules admis</p>
          </v-card>
        </v-col>
        <v-col cols="6" sm="4">
          <v-card rounded="xl" elevation="1" class="text-center pa-5">
            <v-avatar color="error" size="44" class="mb-2">
              <v-icon color="white">mdi-close-circle</v-icon>
            </v-avatar>
            <p class="text-h4 font-weight-bold text-error">{{ totalFailed }}</p>
            <p class="text-caption text-grey">Modules ajournés</p>
          </v-card>
        </v-col>
      </v-row>

      <!-- No data -->
      <div v-if="semestres.length === 0" class="text-center pa-12 text-grey">
        <v-icon size="80" color="grey-lighten-2" class="mb-3">mdi-school-outline</v-icon>
        <p class="text-h6">Aucune note disponible</p>
        <p class="text-body-2 mt-2">Vos notes apparaîtront ici une fois publiées.</p>
      </div>

      <!-- ═══════════════════════ SEMESTRE TABS ═══════════════════════ -->
      <v-card v-else rounded="xl" elevation="2">
        <v-tabs v-model="activeTab" color="primary" align-tabs="start" class="px-4 pt-2">
          <v-tab v-for="(sem, i) in semestres" :key="i" :value="i">
            <v-icon start size="16">mdi-calendar-range</v-icon>
            {{ sem.code || sem.semestre || `Semestre ${i+1}` }}
            <v-chip
              :color="semAvg(sem) >= 10 ? 'success' : 'error'"
              size="x-small" class="ml-2" variant="tonal">
              {{ semAvg(sem) }}
            </v-chip>
          </v-tab>
        </v-tabs>

        <v-divider />

        <v-window v-model="activeTab">
          <v-window-item v-for="(sem, i) in semestres" :key="i" :value="i">
            <v-card-text class="pa-5">

              <!-- Semestre Stats -->
              <v-row class="mb-4">
                <v-col cols="6" sm="3">
                  <div class="text-center">
                    <p class="text-h5 font-weight-bold"
                      :class="semAvg(sem) >= 10 ? 'text-success' : 'text-error'">
                      {{ semAvg(sem) }}
                    </p>
                    <p class="text-caption text-grey">Moyenne</p>
                  </div>
                </v-col>
                <v-col cols="6" sm="3">
                  <div class="text-center">
                    <p class="text-h5 font-weight-bold text-primary">
                      {{ (sem.notes || sem.modules || []).length }}
                    </p>
                    <p class="text-caption text-grey">Modules</p>
                  </div>
                </v-col>
                <v-col cols="6" sm="3">
                  <div class="text-center">
                    <p class="text-h5 font-weight-bold text-success">
                      {{ passedInSem(sem) }}
                    </p>
                    <p class="text-caption text-grey">Admis</p>
                  </div>
                </v-col>
                <v-col cols="6" sm="3">
                  <div class="text-center">
                    <v-chip
                      :color="semAvg(sem) >= 10 ? 'success' : 'error'"
                      size="small" variant="tonal">
                      {{ semAvg(sem) >= 10 ? '✓ Validé' : '✗ Non validé' }}
                    </v-chip>
                  </div>
                </v-col>
              </v-row>

              <v-divider class="mb-4" />

              <!-- Notes Table -->
              <v-data-table
                :headers="noteHeaders"
                :items="sem.notes || sem.modules || []"
                :items-per-page="-1"
                density="comfortable"
                hide-default-footer
              >
                <!-- Module -->
                <template #item.module="{ item }">
                  <div>
                    <p class="text-body-2 font-weight-medium">
                      {{ item.module?.name || item.module_name || item.name || '—' }}
                    </p>
                    <p class="text-caption text-grey">
                      {{ item.module?.code || item.module_code || item.code || '' }}
                    </p>
                  </div>
                </template>

                <!-- Coefficient -->
                <template #item.coefficient="{ item }">
                  <v-chip size="x-small" color="grey" variant="tonal">
                    {{ item.coefficient || item.module?.coefficient || '—' }}
                  </v-chip>
                </template>

                <!-- Note CC -->
                <template #item.note_cc="{ item }">
                  <span :class="noteClass(item.note_cc)">
                    {{ item.note_cc ?? '—' }}
                  </span>
                </template>

                <!-- Note Examen -->
                <template #item.note_exam="{ item }">
                  <span :class="noteClass(item.note_exam)">
                    {{ item.note_exam ?? '—' }}
                  </span>
                </template>

                <!-- Note Finale -->
                <template #item.note_finale="{ item }">
                  <v-chip
                    :color="noteFinale(item) >= 10 ? 'success' : noteFinale(item) > 0 ? 'error' : 'grey'"
                    size="small" variant="tonal" class="font-weight-bold">
                    {{ noteFinale(item) > 0 ? noteFinale(item) : '—' }}
                  </v-chip>
                </template>

                <!-- Statut -->
                <template #item.statut="{ item }">
                  <v-icon
                    :color="noteFinale(item) >= 10 ? 'success' : noteFinale(item) > 0 ? 'error' : 'grey'"
                    size="20">
                    {{ noteFinale(item) >= 10 ? 'mdi-check-circle' :
                       noteFinale(item) > 0  ? 'mdi-close-circle' : 'mdi-minus' }}
                  </v-icon>
                </template>

                <!-- Action -->
                <template #item.action="{ item }">
                  <v-btn
                    v-if="canReclaim(item, sem)"
                    size="x-small"
                    color="warning"
                    variant="tonal"
                    prepend-icon="mdi-alert"
                    :to="`/student/reclamations/new?module_id=${item.module_id || item.module?.id}&semestre_id=${sem.semestre_id || sem.id}`">
                    Réclamer
                  </v-btn>
                  <span v-else class="text-caption text-grey">—</span>
                </template>

                <!-- No data -->
                <template #no-data>
                  <div class="text-center pa-4 text-grey">
                    <v-icon size="40" color="grey-lighten-2">mdi-file-document-outline</v-icon>
                    <p class="text-body-2 mt-2">Aucune note disponible</p>
                  </div>
                </template>
              </v-data-table>

            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const toast = useToast()

const loading   = ref(false)
const notesData = ref(null)
const activeTab = ref(0)

// ── Table headers ──────────────────────────────────────────────────────
const noteHeaders = [
  { title: 'Module',       key: 'module',      sortable: false },
  { title: 'Coeff.',       key: 'coefficient', sortable: false },
  { title: 'CC /20',       key: 'note_cc',     sortable: true  },
  { title: 'Examen /20',   key: 'note_exam',   sortable: true  },
  { title: 'Finale /20',   key: 'note_finale', sortable: true  },
  { title: 'Statut',       key: 'statut',      sortable: false },
  { title: 'Action',       key: 'action',      sortable: false },
]

// ── Computed ───────────────────────────────────────────────────────────
const semestres = computed(() =>
  notesData.value?.semestres || notesData.value?.data || []
)

const currentYear = computed(() =>
  notesData.value?.academic_year || new Date().getFullYear() + '-' + (new Date().getFullYear() + 1)
)

const globalAvg = computed(() => {
  if (!semestres.value.length) return 0
  const avgs = semestres.value.map(s => parseFloat(semAvg(s))).filter(n => n > 0)
  if (!avgs.length) return 0
  return parseFloat((avgs.reduce((a, b) => a + b, 0) / avgs.length).toFixed(2))
})

const totalPassed = computed(() => {
  let count = 0
  semestres.value.forEach(sem => {
    (sem.notes || sem.modules || []).forEach(n => {
      if (noteFinale(n) >= 10) count++
    })
  })
  return count
})

const totalFailed = computed(() => {
  let count = 0
  semestres.value.forEach(sem => {
    (sem.notes || sem.modules || []).forEach(n => {
      const nf = noteFinale(n)
      if (nf > 0 && nf < 10) count++
    })
  })
  return count
})

// ── Helpers ────────────────────────────────────────────────────────────
function semAvg(sem) {
  const notes = sem.notes || sem.modules || []
  if (!notes.length) return 0
  const valid = notes.map(n => noteFinale(n)).filter(n => n > 0)
  if (!valid.length) return 0
  return parseFloat((valid.reduce((a, b) => a + b, 0) / valid.length).toFixed(2))
}

function passedInSem(sem) {
  return (sem.notes || sem.modules || []).filter(n => noteFinale(n) >= 10).length
}

function noteFinale(item) {
  // Use backend-computed value if available
  if (item.note_finale !== null && item.note_finale !== undefined) {
    return parseFloat(item.note_finale)
  }
  const cc   = parseFloat(item.note_cc)   || 0
  const exam = parseFloat(item.note_exam) || 0
  if (!item.note_cc && !item.note_exam) return 0
  return parseFloat(((cc * 0.4) + (exam * 0.6)).toFixed(2))
}

function noteClass(val) {
  if (val === null || val === undefined) return 'text-grey'
  return parseFloat(val) >= 10 ? 'text-success font-weight-bold' : 'text-error font-weight-bold'
}

function canReclaim(item, sem) {
  const nf = noteFinale(item)
  return nf > 0 && nf < 10 && (sem.is_open ?? true)
}

// ── Load data ──────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true
  try {
    const res = await api.get('/student/notes')
    notesData.value = res.data.data || res.data
    if (semestres.value.length) activeTab.value = 0
  } catch (e) {
    toast.error('Erreur de chargement des notes.')
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>
