<template>
  <v-container fluid class="pa-6">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold text-grey-darken-3">
          <v-icon class="mr-2" color="primary">mdi-file-document-multiple</v-icon>
          Mes Réclamations
        </h1>
        <p class="text-body-2 text-grey mt-1">
          Suivez l'état de vos réclamations académiques
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        :to="{ name: 'student.reclamations.new' }"
        rounded="lg"
      >
        Nouvelle réclamation
      </v-btn>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center align-center py-12">
      <v-progress-circular indeterminate color="primary" size="48" />
    </div>

    <!-- Error -->
    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      class="mb-4"
      rounded="lg"
    >
      {{ error }}
    </v-alert>

    <!-- Empty state -->
    <v-card
      v-else-if="reclamations.length === 0"
      rounded="xl"
      class="text-center pa-12"
      elevation="0"
      border
    >
      <v-icon size="80" color="grey-lighten-2">mdi-file-document-outline</v-icon>
      <h3 class="text-h6 text-grey mt-4">Aucune réclamation</h3>
      <p class="text-body-2 text-grey-lighten-1 mt-2">
        Vous n'avez pas encore soumis de réclamation.
      </p>
      <v-btn
        color="primary"
        class="mt-6"
        prepend-icon="mdi-plus"
        :to="{ name: 'student.reclamations.new' }"
        rounded="lg"
      >
        Créer ma première réclamation
      </v-btn>
    </v-card>

    <!-- Liste des réclamations -->
    <template v-else>
      <!-- Filtres -->
      <v-card rounded="xl" elevation="0" border class="mb-4 pa-4">
        <div class="d-flex gap-3 flex-wrap">
          <v-select
            v-model="filterStatus"
            :items="statusOptions"
            item-title="label"
            item-value="value"
            label="Statut"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            style="max-width: 200px"
          />
          <v-select
            v-model="filterType"
            :items="typeOptions"
            item-title="label"
            item-value="value"
            label="Type"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            style="max-width: 200px"
          />
          <v-spacer />
          <v-chip variant="tonal" color="primary">
            {{ filteredReclamations.length }} réclamation(s)
          </v-chip>
        </div>
      </v-card>

      <!-- Cards réclamations -->
      <v-row>
        <v-col
          v-for="reclamation in filteredReclamations"
          :key="reclamation.id"
          cols="12"
          md="6"
          lg="4"
        >
          <v-card
            rounded="xl"
            elevation="0"
            border
            class="reclamation-card h-100"
            :to="{ name: 'student.reclamation.detail', params: { id: reclamation.id } }"
          >
            <v-card-item>
              <!-- Header card -->
              <div class="d-flex align-center justify-space-between mb-3">
                <v-chip
                  :color="statusColor(reclamation.status)"
                  variant="tonal"
                  size="small"
                  :prepend-icon="statusIcon(reclamation.status)"
                >
                  {{ statusLabel(reclamation.status) }}
                </v-chip>
                <v-chip
                  :color="typeColor(reclamation.type)"
                  variant="outlined"
                  size="small"
                >
                  {{ typeLabel(reclamation.type) }}
                </v-chip>
              </div>

              <!-- Référence -->
              <div class="text-caption text-grey mb-1">
                {{ reclamation.reference }}
              </div>

              <!-- Module -->
              <div class="text-subtitle-1 font-weight-semibold text-grey-darken-2 mb-1">
                {{ reclamation.module?.name || reclamation.module?.nom || '—' }}
              </div>

              <!-- Semestre -->
              <div class="text-body-2 text-grey mb-3">
                <v-icon size="14" class="mr-1">mdi-calendar-range</v-icon>
                {{ reclamation.semestre?.label || reclamation.semestre?.nom || '—' }}
                · {{ reclamation.academic_year }}
              </div>

              <!-- Notes -->
              <div class="d-flex gap-3 mb-3">
                <div class="note-chip">
                  <span class="text-caption text-grey">Actuelle</span>
                  <span class="text-subtitle-2 font-weight-bold text-orange">
                    {{ reclamation.note_actuelle ?? '—' }}/20
                  </span>
                </div>
                <div v-if="reclamation.note_reclamee" class="note-chip">
                  <span class="text-caption text-grey">Réclamée</span>
                  <span class="text-subtitle-2 font-weight-bold text-green">
                    {{ reclamation.note_reclamee }}/20
                  </span>
                </div>
              </div>

              <!-- Date -->
              <div class="text-caption text-grey">
                <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                Soumise le {{ formatDate(reclamation.created_at) }}
              </div>
            </v-card-item>

            <v-card-actions class="pt-0 px-4 pb-3">
              <v-spacer />
              <v-btn
                variant="text"
                color="primary"
                size="small"
                append-icon="mdi-arrow-right"
                :to="{ name: 'student.reclamation.detail', params: { id: reclamation.id } }"
              >
                Voir détail
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

const reclamations = ref([])
const loading      = ref(true)
const error        = ref('')
const filterStatus = ref(null)
const filterType   = ref(null)

const statusOptions = [
  { value: 'submitted',  label: 'Soumise'   },
  { value: 'in_review',  label: 'En cours'  },
  { value: 'resolved',   label: 'Résolue'   },
  { value: 'rejected',   label: 'Rejetée'   },
  { value: 'escalated',  label: 'Escaladée' },
]

const typeOptions = [
  { value: 'cc',         label: 'Devoir (CC)' },
  { value: 'examen',     label: 'Examen'       },
  { value: 'rattrapage', label: 'Rattrapage'   },
]

const filteredReclamations = computed(() => {
  return reclamations.value.filter(r => {
    if (filterStatus.value && r.status !== filterStatus.value) return false
    if (filterType.value   && r.type   !== filterType.value)   return false
    return true
  })
})

async function loadReclamations() {
  loading.value = true
  error.value   = ''
  try {
    const res = await api.get('/student/reclamations')
    const raw = res.data?.data ?? res.data
    reclamations.value = Array.isArray(raw) ? raw : []
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du chargement des réclamations.'
  } finally {
    loading.value = false
  }
}

onMounted(loadReclamations)

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

function statusLabel(s) {
  return { submitted:'Soumise', in_review:'En cours', resolved:'Résolue', rejected:'Rejetée', escalated:'Escaladée', closed:'Fermée' }[s] ?? s
}
function statusColor(s) {
  return { submitted:'blue', in_review:'orange', resolved:'green', rejected:'red', escalated:'purple', closed:'grey' }[s] ?? 'grey'
}
function statusIcon(s) {
  return { submitted:'mdi-send', in_review:'mdi-progress-clock', resolved:'mdi-check-circle', rejected:'mdi-close-circle', escalated:'mdi-alert-circle', closed:'mdi-lock' }[s] ?? 'mdi-help-circle'
}
function typeLabel(t) {
  return { cc:'Devoir (CC)', examen:'Examen', rattrapage:'Rattrapage' }[t] ?? t
}
function typeColor(t) {
  return { cc:'indigo', examen:'teal', rattrapage:'deep-orange' }[t] ?? 'grey'
}
</script>

<style scoped>
.reclamation-card {
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}
.reclamation-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
.note-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #f5f5f5;
  border-radius: 8px;
  padding: 4px 12px;
  min-width: 70px;
}
</style>
