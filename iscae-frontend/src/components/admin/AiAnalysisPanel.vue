<template>
  <v-card class="mt-4" variant="outlined">
    <!-- En-tête du panneau -->
    <v-card-title class="d-flex align-center gap-2 pa-4">
      <v-icon color="purple" size="22">mdi-robot-outline</v-icon>
      <span class="text-subtitle-1 font-weight-semibold">Analyse Intelligente (IA)</span>
      <v-spacer />
      <v-chip
        v-if="analysis"
        :color="analysis.decision_color"
        size="small"
        variant="flat"
        class="font-weight-bold"
      >
        {{ analysis.decision_label }}
      </v-chip>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-4">

      <!-- État : pas encore d'analyse -->
      <div v-if="!analysis && !loading" class="text-center py-4">
        <v-icon color="purple-lighten-2" size="48">mdi-brain</v-icon>
        <p class="text-body-2 text-medium-emphasis mt-2">
          Cliquez pour analyser cette réclamation.<br>
          <span class="text-caption">L'IA utilisera automatiquement le PDF officiel du module si disponible.</span>
        </p>
        <v-btn
          color="purple"
          variant="flat"
          class="mt-3"
          prepend-icon="mdi-play-circle-outline"
          @click="triggerAnalysis()"
        >
          Analyser avec l'IA
        </v-btn>
      </div>

      <!-- État : chargement -->
      <div v-if="loading" class="text-center py-6">
        <v-progress-circular indeterminate color="purple" size="40" />
        <p class="text-body-2 text-medium-emphasis mt-3">
          Analyse en cours…
        </p>
      </div>

      <!-- État : erreur -->
      <v-alert
        v-if="error && !loading"
        type="error"
        variant="tonal"
        class="mt-2"
        closable
        @click:close="error = null"
      >
        {{ error }}
      </v-alert>

      <!-- Résultat de l'analyse -->
      <template v-if="analysis && !loading">

        <!-- Score de confiance -->
        <div class="mb-4">
          <div class="d-flex justify-space-between align-center mb-1">
            <span class="text-body-2 text-medium-emphasis">Score de confiance</span>
            <span class="text-body-2 font-weight-bold" :class="`text-${analysis.decision_color}`">
              {{ analysis.confidence_percent }}%
            </span>
          </div>
          <v-progress-linear
            :model-value="analysis.confidence_percent"
            :color="analysis.decision_color"
            bg-color="grey-lighten-3"
            rounded
            height="8"
          />
        </div>

        <!-- Explication -->
        <v-card variant="tonal" :color="analysis.decision_color" class="mb-4 pa-3">
          <p class="text-body-2">{{ analysis.explanation }}</p>
        </v-card>

        <!-- Tableau comparatif des notes -->
        <v-table density="compact" class="mb-4">
          <thead>
            <tr>
              <th class="text-left">Source</th>
              <th class="text-center">Note</th>
              <th class="text-center">Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Note officielle (BD)</td>
              <td class="text-center font-weight-bold">
                {{ analysis.notes.from_db != null ? Number(analysis.notes.from_db).toFixed(2) + '/20' : '—' }}
              </td>
              <td class="text-center">
                <v-chip color="blue" size="x-small" variant="tonal">Référence</v-chip>
              </td>
            </tr>
            <tr>
              <td>Note réclamée par l'étudiant</td>
              <td class="text-center font-weight-bold">
                {{ analysis.notes.claimed != null ? Number(analysis.notes.claimed).toFixed(2) + '/20' : '—' }}
              </td>
              <td class="text-center">
                <v-chip color="orange" size="x-small" variant="tonal">Revendiquée</v-chip>
              </td>
            </tr>
            <tr v-if="analysis.notes.from_pdf != null">
              <td>Note extraite du PDF joint</td>
              <td class="text-center font-weight-bold">
                {{ Number(analysis.notes.from_pdf).toFixed(2) + '/20' }}
              </td>
              <td class="text-center">
                <v-chip
                  :color="analysis.notes.discrepancy_found ? 'error' : 'success'"
                  size="x-small"
                  variant="tonal"
                >
                  {{ analysis.notes.discrepancy_found ? 'Discordance' : 'Concordant' }}
                </v-chip>
              </td>
            </tr>
          </tbody>
        </v-table>

        <!-- Discordance détectée -->
        <v-alert
          v-if="analysis.notes.discrepancy_found && analysis.notes.discrepancy_details"
          type="warning"
          variant="tonal"
          density="compact"
          class="mb-4"
        >
          <strong>Discordance détectée :</strong> {{ analysis.notes.discrepancy_details }}
        </v-alert>

        <!-- Recommandation -->
        <div v-if="analysis.recommendation" class="mb-4">
          <p class="text-body-2 text-medium-emphasis mb-1">
            <v-icon size="14" class="mr-1">mdi-lightbulb-outline</v-icon>
            Recommandation IA
          </p>
          <p class="text-body-2">{{ analysis.recommendation }}</p>
        </div>

        <!-- Info PDF -->
        <v-alert
          v-if="!analysis.pdf.parsed_successfully && analysis.pdf.parse_error"
          type="info"
          variant="tonal"
          density="compact"
          class="mb-4"
        >
          PDF : {{ analysis.pdf.parse_error }}
        </v-alert>

        <!-- Métadonnées -->
        <div class="d-flex flex-wrap gap-2 text-caption text-medium-emphasis">
          <span>Modèle : {{ analysis.metadata.model }}</span>
          <span v-if="analysis.metadata.total_tokens">· {{ analysis.metadata.total_tokens }} tokens</span>
          <span v-if="analysis.metadata.processing_time">· {{ analysis.metadata.processing_time }}</span>
          <span v-if="analysis.metadata.analyzed_at">· {{ formatDate(analysis.metadata.analyzed_at) }}</span>
        </div>

        <!-- Bouton relancer -->
        <v-btn
          variant="text"
          color="purple"
          size="small"
          class="mt-3"
          prepend-icon="mdi-refresh"
          :loading="loading"
          @click="triggerAnalysis(true)"
        >
          Relancer l'analyse
        </v-btn>

      </template>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { analyzeReclamation, getAnalysis } from '@/api/aiAnalysis'

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
  reclamationId: {
    type: Number,
    required: true,
  },
  attachments: {
    type: Array,
    default: () => [],
  },
})

// ─── État ─────────────────────────────────────────────────────────────────────
const analysis = ref(null)
const loading  = ref(false)
const error    = ref(null)

// ─── Computed ─────────────────────────────────────────────────────────────────
const hasPdf = computed(() =>
  props.attachments.some(a => a.mime_type === 'application/pdf')
)

const pdfAttachmentId = computed(() =>
  props.attachments.find(a => a.mime_type === 'application/pdf')?.id ?? null
)

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  await fetchExistingAnalysis()
})

// ─── Méthodes ─────────────────────────────────────────────────────────────────
async function fetchExistingAnalysis() {
  try {
    const response = await getAnalysis(props.reclamationId)
    if (response.data.success && response.data.data) {
      analysis.value = response.data.data
    }
  } catch (err) {
    if (err.response?.status !== 404) {
      console.warn('AiAnalysisPanel: erreur inattendue', err)
    }
  }
}

async function triggerAnalysis(forceReanalyze = false) {
  loading.value = true
  error.value   = null

  try {
    const payload = { force_reanalyze: Boolean(forceReanalyze) }
    if (pdfAttachmentId.value) {
      payload.attachment_id = pdfAttachmentId.value
    }

    const response = await analyzeReclamation(props.reclamationId, payload)
    if (response.data.success) {
      analysis.value = response.data.data
    } else {
      error.value = response.data.message || 'Erreur inconnue.'
    }
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = err.response?.data?.message || 'Erreur lors de la communication avec le serveur.'
    }
    console.error('AiAnalysisPanel error:', err.response?.data)
  } finally {
    loading.value = false
  }
}

function formatDate(isoString) {
  if (!isoString) return ''
  return new Date(isoString).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>