<template>
  <div class="rec-detail-page">

    <!-- ══════════════════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════════════════════ -->
    <div class="page-header">
      <div class="header-left">
        <v-btn icon="mdi-arrow-left" variant="text" size="small" class="back-btn"
               @click="router.push('/student/reclamations')" />
        <div>
          <h1 class="page-title">Suivez l'état de votre réclamation</h1>
        </div>
      </div>
      <div v-if="rec" class="header-right">
        <v-chip :color="statusColor(rec.status)" variant="flat" size="large" class="status-chip-header">
          <v-icon start :icon="statusIcon(rec.status)" />
          {{ statusLabel(rec.status) }}
        </v-chip>
        <span class="ref-badge">#{{ rec.reference_number }}</span>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         LOADING
    ══════════════════════════════════════════════════════════════════ -->
    <div v-if="loading" class="loading-state">
      <v-progress-circular indeterminate color="primary" size="56" width="4" />
      <p>Chargement de la réclamation…</p>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         ERREUR
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else-if="error" class="error-state">
      <v-icon size="80" color="secondary">mdi-file-document-alert-outline</v-icon>
      <p class="error-title">{{ error }}</p>
      <v-btn color="primary" variant="flat" @click="router.push('/student/reclamations')">
        <v-icon start>mdi-arrow-left</v-icon> Retour
      </v-btn>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         CONTENU PRINCIPAL
    ══════════════════════════════════════════════════════════════════ -->
    <v-row v-else-if="rec" class="main-content" gutter="16">

      <!-- ── Colonne gauche (8/12) ── -->
      <v-col cols="12" md="8">

        <!-- Bannière de statut -->
        <div class="status-banner mb-4" :style="{ background: statusBg(rec.status), borderColor: statusColor(rec.status) }">
          <div class="status-banner-icon" :style="{ background: statusColor(rec.status) }">
            <v-icon color="white" size="28">{{ statusIcon(rec.status) }}</v-icon>
          </div>
          <div class="status-banner-text">
            <strong>{{ statusLabel(rec.status) }}</strong>
            <span>{{ statusDesc(rec.status) }}</span>
          </div>
          <div v-if="rec.resolved_at || rec.updated_at" class="status-banner-date">
            <v-icon size="14" color="grey">mdi-clock-outline</v-icon>
            {{ fDateTime(rec.resolved_at ?? rec.updated_at) }}
          </div>
        </div>

        <!-- Barre de progression -->
        <v-card class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="primary" class="mr-2">mdi-progress-check</v-icon>
            Progression
          </v-card-title>
          <div class="progress-steps">
            <div
              v-for="(step, i) in progressSteps" :key="step.key"
              class="progress-step"
              :class="{
                'step-done':     step.done,
                'step-active':   step.active,
                'step-pending':  !step.done && !step.active,
                'step-rejected': rec.status === 'rejected' && step.active,
              }"
            >
              <div class="step-circle">
                <v-icon v-if="step.done" size="16" color="white">mdi-check</v-icon>
                <v-icon v-else-if="rec.status === 'rejected' && step.active" size="16" color="white">mdi-close</v-icon>
                <span v-else class="step-num">{{ i + 1 }}</span>
              </div>
              <div v-if="i < progressSteps.length - 1" class="step-connector" />
              <div class="step-label">{{ step.label }}</div>
            </div>
          </div>
        </v-card>

        <!-- Détails de la réclamation -->
        <v-card class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="primary" class="mr-2">mdi-information-outline</v-icon>
            Détails de la réclamation
          </v-card-title>
          <v-divider />
          <v-card-text>
            <v-row dense>
              <v-col cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Référence</span>
                  <span class="info-value ref-tag">{{ rec.reference_number }}</span>
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Type</span>
                  <v-chip :color="typeColor(rec.type)" variant="tonal" size="small">
                    <v-icon start size="14">{{ typeIcon(rec.type) }}</v-icon>
                    {{ typeLabel(rec.type) }}
                  </v-chip>
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Note actuelle</span>
                  <span class="info-value note-badge">{{ rec.note_actuelle ?? '—' }} / 20</span>
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Note réclamée</span>
                  <span class="info-value note-badge note-claim">{{ rec.note_reclamee ?? '—' }} / 20</span>
                </div>
              </v-col>
              <v-col cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Date de soumission</span>
                  <span class="info-value">{{ fDateTime(rec.created_at) }}</span>
                </div>
              </v-col>
              <v-col v-if="rec.resolved_at" cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Date de résolution</span>
                  <span class="info-value">{{ fDateTime(rec.resolved_at) }}</span>
                </div>
              </v-col>
              <v-col v-if="rec.justification" cols="12">
                <div class="info-row flex-col">
                  <span class="info-label">Justification</span>
                  <p class="info-value justification-text">{{ rec.justification }}</p>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Module & Semestre -->
        <v-row dense class="mb-4">
          <v-col cols="12" sm="6">
            <v-card elevation="0" border class="themed-card h-100">
              <v-card-text>
                <div class="module-header">
                  <div class="module-icon-box">
                    <v-icon color="white" size="22">mdi-book-open-variant</v-icon>
                  </div>
                  <div>
                    <p class="module-code">{{ rec.module?.code }}</p>
                    <p class="module-name">{{ rec.module?.name }}</p>
                  </div>
                </div>
                <v-divider class="my-3" />
                <div class="module-stats">
                  <div class="module-stat">
                    <span class="stat-label">Coefficient</span>
                    <span class="stat-value">{{ rec.module?.coefficient ?? '—' }}</span>
                  </div>
                  <div class="module-stat">
                    <span class="stat-label">Crédits</span>
                    <span class="stat-value">{{ rec.module?.credits ?? '—' }}</span>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6">
            <v-card elevation="0" border class="themed-card h-100">
              <v-card-text>
                <div class="module-header">
                  <div class="module-icon-box semestre-icon">
                    <v-icon color="white" size="22">mdi-calendar-range</v-icon>
                  </div>
                  <div>
                    <p class="module-code semestre-code">{{ rec.semestre?.code }}</p>
                    <p class="module-name">{{ rec.semestre?.label }}</p>
                  </div>
                </div>
                <v-divider class="my-3" />
                <div class="module-stats">
                  <div class="module-stat">
                    <span class="stat-label">Année universitaire</span>
                    <span class="stat-value">{{ rec.semestre?.academic_year ?? '—' }}</span>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Réponse de l'administration -->
        <v-card
          v-if="rec.admin_response"
          class="themed-card mb-4"
          elevation="0"
          border
          :style="{ borderColor: rec.status === 'rejected' ? '#DC2626' : '#059669' }"
        >
          <v-card-title
            class="admin-response-title"
            :style="{
              background: rec.status === 'rejected'
                ? 'rgba(220,38,38,0.08)'
                : 'rgba(5,150,105,0.08)'
            }"
          >
            <v-icon :color="rec.status === 'rejected' ? 'error' : 'success'" class="mr-2">
              {{ rec.status === 'rejected' ? 'mdi-close-circle' : 'mdi-check-circle' }}
            </v-icon>
            Réponse de l'administration
            <v-spacer />
            <span class="response-date">{{ fDateTime(rec.responded_at) }}</span>
          </v-card-title>
          <v-card-text class="admin-response-body">{{ rec.admin_response }}</v-card-text>
        </v-card>

        <!-- Réunion planifiée -->
        <v-card v-if="rec.meeting?.scheduled_at" class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="blue" class="mr-2">mdi-calendar-clock</v-icon>
            Réunion planifiée
          </v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Date & Heure</span>
                  <span class="info-value">{{ fDateTime(rec.meeting.scheduled_at) }}</span>
                </div>
              </v-col>
              <v-col v-if="rec.meeting.location" cols="12" sm="6">
                <div class="info-row">
                  <span class="info-label">Lieu</span>
                  <span class="info-value">{{ rec.meeting.location }}</span>
                </div>
              </v-col>
              <v-col v-if="rec.meeting.notes" cols="12">
                <div class="info-row flex-col">
                  <span class="info-label">Notes</span>
                  <p class="info-value">{{ rec.meeting.notes }}</p>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Pièces jointes -->
        <v-card v-if="attachments.length" class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="primary" class="mr-2">mdi-paperclip</v-icon>
            Pièces jointes ({{ attachments.length }})
          </v-card-title>
          <v-divider />
          <v-card-text>
            <div v-for="att in attachments" :key="att.id" class="attachment-row">
              <v-icon :color="attIconColor(att.mime_type)" size="32">{{ attIcon(att.mime_type) }}</v-icon>
              <div class="att-info">
                <span class="att-name">{{ att.file_name }}</span>
                <span class="att-size">{{ formatSize(att.file_size) }}</span>
              </div>
              <v-btn icon="mdi-download" variant="text" color="primary" size="small"
                     :href="att.url" target="_blank" />
            </div>
          </v-card-text>
        </v-card>

        <!-- Historique -->
        <v-card class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="primary" class="mr-2">mdi-history</v-icon>
            Historique des statuts
          </v-card-title>
          <v-divider />
          <v-card-text>
            <div v-if="!history.length" class="no-history">
              <v-icon color="secondary" size="36">mdi-timeline-outline</v-icon>
              <p>Aucun historique disponible</p>
            </div>
            <v-timeline v-else side="end" density="compact" truncate-line="both" class="history-timeline">
              <v-timeline-item
                v-for="h in history" :key="h.id"
                :dot-color="statusColor(h.new_status)" size="small"
              >
                <div class="timeline-item">
                  <div class="timeline-header">
                    <div class="timeline-statuses">
                      <v-chip v-if="h.old_status" size="x-small" variant="tonal" :color="statusColor(h.old_status)">
                        {{ statusLabel(h.old_status) }}
                      </v-chip>
                      <v-icon v-if="h.old_status" size="12" color="grey">mdi-arrow-right</v-icon>
                      <v-chip size="x-small" variant="flat" :color="statusColor(h.new_status)">
                        {{ statusLabel(h.new_status) }}
                      </v-chip>
                    </div>
                    <span class="timeline-date">{{ fDateTime(h.created_at) }}</span>
                  </div>
                  <p v-if="h.comment" class="timeline-comment">{{ h.comment }}</p>
                  <span class="timeline-actor">
                    <v-icon size="12">mdi-account</v-icon>
                    {{ h.changed_by_label ?? 'Système' }}
                  </span>
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>

      </v-col>

      <!-- ── Colonne droite (4/12) ── -->
      <v-col cols="12" md="4">

        <!-- Infos rapides -->
        <v-card class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="primary" class="mr-2">mdi-lightning-bolt</v-icon>
            Informations rapides
          </v-card-title>
          <v-divider />
          <v-list density="compact" class="quick-list">
            <v-list-item>
              <template #prepend><v-icon size="18" color="secondary">mdi-tag-outline</v-icon></template>
              <v-list-item-title class="quick-label">Référence</v-list-item-title>
              <template #append><span class="quick-value">{{ rec.reference_number }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="18" color="secondary">mdi-format-list-bulleted-type</v-icon></template>
              <v-list-item-title class="quick-label">Type</v-list-item-title>
              <template #append>
                <v-chip size="x-small" :color="typeColor(rec.type)" variant="tonal">{{ typeLabel(rec.type) }}</v-chip>
              </template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="18" color="secondary">mdi-calendar-plus</v-icon></template>
              <v-list-item-title class="quick-label">Soumis le</v-list-item-title>
              <template #append><span class="quick-value">{{ fDate(rec.created_at) }}</span></template>
            </v-list-item>
            <v-list-item v-if="rec.resolved_at">
              <template #prepend><v-icon size="18" color="secondary">mdi-calendar-check</v-icon></template>
              <v-list-item-title class="quick-label">Résolu le</v-list-item-title>
              <template #append><span class="quick-value">{{ fDate(rec.resolved_at) }}</span></template>
            </v-list-item>
            <v-list-item>
              <template #prepend><v-icon size="18" color="secondary">mdi-timer-sand</v-icon></template>
              <v-list-item-title class="quick-label">Délai de traitement</v-list-item-title>
              <template #append><span class="quick-value">{{ delayLabel }}</span></template>
            </v-list-item>
            <v-list-item v-if="rec.academic_year">
              <template #prepend><v-icon size="18" color="secondary">mdi-school</v-icon></template>
              <v-list-item-title class="quick-label">Année univ.</v-list-item-title>
              <template #append><span class="quick-value">{{ rec.academic_year }}</span></template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Escalade -->
        <v-card v-if="rec.is_escalated" class="themed-card mb-4" elevation="0" border>
          <v-card-title class="section-title">
            <v-icon color="orange" class="mr-2">mdi-arrow-up-bold-circle</v-icon>
            Réclamation escaladée
          </v-card-title>
          <v-card-text>
            <p v-if="rec.escalation_reason" class="escalade-reason">{{ rec.escalation_reason }}</p>
            <p v-if="rec.escalated_at" class="escalade-date">
              <v-icon size="14">mdi-clock-outline</v-icon>
              {{ fDateTime(rec.escalated_at) }}
            </p>
          </v-card-text>
        </v-card>

        <!-- Bouton retour -->
        <v-btn variant="outlined" color="primary" block
               @click="router.push('/student/reclamations')" class="mb-4">
          <v-icon start>mdi-format-list-bulleted</v-icon>
          Voir toutes mes réclamations
        </v-btn>

      </v-col>
    </v-row>

    <!-- ══════════════════════════════════════════════════════════════
         DIALOG ANNULATION
    ══════════════════════════════════════════════════════════════════ -->
    <v-dialog v-model="confirmCancelDialog" max-width="460" persistent>
      <v-card class="themed-card" style="border-radius:16px!important">
        <v-card-title class="dialog-title">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          Annuler la réclamation
        </v-card-title>
        <v-divider />
        <v-card-text class="dialog-body">
          <p>Êtes-vous sûr de vouloir annuler la réclamation <strong>{{ rec?.reference_number }}</strong> ?</p>
          <p class="dialog-warning">Cette action est irréversible.</p>
          <v-textarea v-model="cancelReason" label="Motif d'annulation (optionnel)"
                      rows="3" variant="outlined" density="compact" class="mt-3" />
        </v-card-text>
        <v-card-actions class="dialog-actions">
          <v-btn variant="text" @click="confirmCancelDialog = false" :disabled="cancelling">Conserver</v-btn>
          <v-btn color="error" variant="flat" @click="cancelReclamation" :loading="cancelling">
            Confirmer l'annulation
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snack.show" :color="snack.color" location="top right" :timeout="4000" rounded="lg">
      <v-icon start>{{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
      {{ snack.text }}
      <template #actions>
        <v-btn icon="mdi-close" variant="text" @click="snack.show = false" />
      </template>
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'

const route  = useRoute()
const router = useRouter()

const loading             = ref(true)
const cancelling          = ref(false)
const confirmCancelDialog = ref(false)
const cancelReason        = ref('')
const error               = ref(null)
const rec                 = ref(null)
const history             = ref([])
const attachments         = ref([])
const snack = reactive({ show: false, text: '', color: 'success' })

const STATUS_MAP = {
  submitted: { label:'Soumis',    color:'#2563EB', icon:'mdi-send-circle',          bg:'rgba(37,99,235,0.08)',   desc:'Votre réclamation a été soumise et est en attente de réception.' },
  received:  { label:'Reçu',     color:'#0891B2', icon:'mdi-inbox-arrow-down',      bg:'rgba(8,145,178,0.08)',   desc:"Votre réclamation a été reçue par l'administration." },
  in_review: { label:'En cours', color:'#D97706', icon:'mdi-magnify-scan',          bg:'rgba(217,119,6,0.08)',   desc:"Votre réclamation est en cours d'examen." },
  escalated: { label:'Escaladé', color:'#EA580C', icon:'mdi-arrow-up-bold-circle',  bg:'rgba(234,88,12,0.08)',   desc:'Votre réclamation a été transmise à un responsable supérieur.' },
  resolved:  { label:'Résolu',   color:'#059669', icon:'mdi-check-circle',          bg:'rgba(5,150,105,0.08)',   desc:'Votre réclamation a été traitée avec succès.' },
  rejected:  { label:'Rejeté',   color:'#DC2626', icon:'mdi-close-circle',          bg:'rgba(220,38,38,0.08)',   desc:"Votre réclamation a été rejetée par l'administration." },
}
const TYPE_MAP = {
  controle  : { label:'Contrôle',   color:'purple', icon:'mdi-pencil-circle' },
  examen    : { label:'Examen',     color:'blue',   icon:'mdi-file-certificate' },
  rattrapage: { label:'Rattrapage', color:'orange', icon:'mdi-reload' },
}

function statusLabel(s) { return STATUS_MAP[s]?.label ?? s }
function statusColor(s) { return STATUS_MAP[s]?.color ?? '#64748B' }
function statusIcon(s)  { return STATUS_MAP[s]?.icon  ?? 'mdi-help-circle' }
function statusBg(s)    { return STATUS_MAP[s]?.bg    ?? 'rgba(100,116,139,0.08)' }
function statusDesc(s)  { return STATUS_MAP[s]?.desc  ?? '' }
function typeLabel(t)   { return TYPE_MAP[t]?.label ?? t }
function typeColor(t)   { return TYPE_MAP[t]?.color ?? 'grey' }
function typeIcon(t)    { return TYPE_MAP[t]?.icon  ?? 'mdi-help' }

const STEP_ORDER = ['submitted', 'received', 'in_review', 'resolved']
const progressSteps = computed(() => {
  if (!rec.value) return []
  const current = rec.value.status
  const idx = current === 'rejected' ? STEP_ORDER.indexOf('in_review') : STEP_ORDER.indexOf(current)
  return [
    { key:'submitted', label:'Soumis' },
    { key:'received',  label:'Reçu' },
    { key:'in_review', label:'En cours' },
    { key:'resolved',  label: current === 'rejected' ? 'Rejeté' : 'Résolu' },
  ].map((step, i) => ({ ...step, done: i < idx, active: i === idx }))
})

const delayLabel = computed(() => {
  if (!rec.value) return '—'
  const start = new Date(rec.value.created_at)
  const end   = rec.value.resolved_at ? new Date(rec.value.resolved_at) : new Date()
  const days  = Math.floor((end - start) / 86_400_000)
  return days === 0 ? "Moins d'un jour" : days + (days > 1 ? ' jours' : ' jour')
})

const canCancel = computed(() => rec.value && ['submitted','received'].includes(rec.value.status))

function attIcon(mime) {
  if (!mime) return 'mdi-file-outline'
  if (mime.includes('pdf'))   return 'mdi-file-pdf-box'
  if (mime.includes('image')) return 'mdi-file-image'
  return 'mdi-file-outline'
}
function attIconColor(mime) {
  if (!mime) return 'grey'
  if (mime.includes('pdf'))   return 'red'
  if (mime.includes('image')) return 'blue'
  return 'grey'
}
function formatSize(bytes) {
  if (!bytes) return ''
  if (bytes < 1024)      return bytes + ' o'
  if (bytes < 1_048_576) return (bytes / 1024).toFixed(1) + ' Ko'
  return (bytes / 1_048_576).toFixed(1) + ' Mo'
}
const fDate = d => d ? new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'long', year:'numeric' }) : '—'
const fDateTime = d => d ? new Date(d).toLocaleString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'

function notify(text, color = 'success') { snack.text = text; snack.color = color; snack.show = true }

async function load() {
  loading.value = true; error.value = null
  try {
    const id  = route.params.id
    const res = await api.get(`/student/reclamations/${id}`)
    const payload = res.data?.data ?? res.data
    rec.value = payload
    history.value = payload?.history ?? []
    attachments.value = payload?.attachments ?? []
  } catch (err) {
    const status = err.response?.status
    const msg    = err.response?.data?.message ?? err.response?.data?.error
    if (status === 404)      error.value = msg ?? 'Réclamation introuvable.'
    else if (status === 403) error.value = 'Accès non autorisé à cette réclamation.'
    else if (status === 401) router.push('/login')
    else                     error.value = msg ?? 'Impossible de charger la réclamation.'
  } finally {
    loading.value = false
  }
}

async function cancelReclamation() {
  cancelling.value = true
  try {
    await api.delete(`/student/reclamations/${rec.value.id}`, { data: { reason: cancelReason.value || undefined } })
    notify('Réclamation annulée avec succès.', 'success')
    confirmCancelDialog.value = false
    setTimeout(() => router.push('/student/reclamations'), 1500)
  } catch (err) {
    notify(err.response?.data?.message ?? "Erreur lors de l'annulation.", 'error')
  } finally {
    cancelling.value = false
  }
}

onMounted(load)
</script>

<style scoped>
/* ── Layout ── */
.rec-detail-page { padding: 24px; max-width: 1200px; margin: 0 auto; }

/* ── Header ── */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
}
.header-left { display: flex; align-items: center; gap: 12px; }
.back-btn { margin-right: 4px; }
.page-title {
  font-size: 1.5rem; font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin: 0;
}
.header-right { display: flex; align-items: center; gap: 10px; }
.status-chip-header { font-weight: 600; }
.ref-badge {
  font-size: 0.85rem; font-weight: 600;
  color: rgb(var(--v-theme-text-secondary));
  background: rgb(var(--v-theme-surface-variant));
  border-radius: 8px; padding: 4px 10px;
}

/* ── Loading / Error ── */
.loading-state, .error-state {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 16px; min-height: 300px;
  text-align: center; color: rgb(var(--v-theme-text-secondary));
}
.error-title { font-size: 1.1rem; font-weight: 500; color: rgb(var(--v-theme-text-secondary)); }

/* ── Bannière de statut ── */
.status-banner {
  display: flex; align-items: center; gap: 16px;
  border-radius: 12px; border: 2px solid;
  padding: 16px 20px;
}
.status-banner-icon {
  width: 52px; height: 52px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.status-banner-text { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.status-banner-text strong { font-size: 1rem; color: rgb(var(--v-theme-on-surface)); }
.status-banner-text span   { font-size: 0.83rem; color: rgb(var(--v-theme-text-secondary)); }
.status-banner-date { display: flex; align-items: center; gap: 4px; font-size: 0.78rem; color: rgb(var(--v-theme-text-secondary)); white-space: nowrap; }

/* ── Carte générique ── */
.themed-card { border-radius: 12px !important; }

/* ── Progression ── */
.progress-steps {
  display: flex; align-items: flex-start; justify-content: center;
  padding: 8px 16px 20px; gap: 0; position: relative;
}
.progress-step {
  display: flex; flex-direction: column; align-items: center;
  position: relative; flex: 1;
}
.step-circle {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700;
  border: 2px solid rgb(var(--v-theme-border-default));
  background: rgb(var(--v-theme-surface-variant));
  color: rgb(var(--v-theme-text-secondary));
  z-index: 1; transition: all 0.3s;
}
.step-done     .step-circle { background: #059669; border-color: #059669; }
.step-active   .step-circle { background: #2563EB; border-color: #2563EB; box-shadow: 0 0 0 4px rgba(37,99,235,0.2); }
.step-rejected .step-circle { background: #DC2626; border-color: #DC2626; box-shadow: 0 0 0 4px rgba(220,38,38,0.2); }
.step-connector {
  position: absolute; top: 15px; left: 50%; width: 100%;
  height: 2px; background: rgb(var(--v-theme-border-default)); z-index: 0;
}
.step-label { font-size: 0.72rem; font-weight: 500; color: rgb(var(--v-theme-text-secondary)); margin-top: 6px; text-align: center; }
.step-done     .step-label { color: #059669; }
.step-active   .step-label { color: #2563EB; font-weight: 700; }
.step-rejected .step-label { color: #DC2626; font-weight: 700; }

/* ── Section title ── */
.section-title {
  font-size: 0.95rem !important; font-weight: 600 !important;
  padding: 14px 16px 10px !important;
  display: flex !important; align-items: center !important;
  color: rgb(var(--v-theme-on-surface)) !important;
}

/* ── Info rows ── */
.info-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 6px 0; border-bottom: 1px dashed rgb(var(--v-theme-border-light)); gap: 8px;
}
.info-row.flex-col { flex-direction: column; align-items: flex-start; }
.info-label { font-size: 0.8rem; color: rgb(var(--v-theme-text-secondary)); font-weight: 500; white-space: nowrap; }
.info-value { font-size: 0.88rem; color: rgb(var(--v-theme-on-surface)); font-weight: 600; text-align: right; }
.ref-tag {
  font-family: 'Courier New', monospace;
  background: rgb(var(--v-theme-surface-variant));
  border-radius: 6px; padding: 2px 8px; font-size: 0.82rem;
}
.note-badge  { background: rgba(37,99,235,0.1); color: #1D4ED8; border-radius: 6px; padding: 2px 8px; }
.note-claim  { background: rgba(234,88,12,0.1); color: #C2410C; }
.justification-text {
  font-size: 0.88rem; color: rgb(var(--v-theme-on-surface));
  line-height: 1.6; margin-top: 4px; font-weight: 400 !important; text-align: left !important;
}

/* ── Module ── */
.module-header { display: flex; align-items: center; gap: 12px; }
.module-icon-box {
  width: 44px; height: 44px; border-radius: 10px;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.semestre-icon { background: linear-gradient(135deg, #0891B2, #0EA5E9) !important; }
.module-code { font-size: 0.75rem; font-weight: 700; color: #6366F1; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.semestre-code { color: #0891B2; }
.module-name { font-size: 0.88rem; font-weight: 600; color: rgb(var(--v-theme-on-surface)); margin: 0; }
.module-stats { display: flex; gap: 16px; }
.module-stat { display: flex; flex-direction: column; gap: 2px; }
.stat-label { font-size: 0.72rem; color: rgb(var(--v-theme-text-secondary)); font-weight: 500; }
.stat-value { font-size: 0.88rem; font-weight: 700; color: rgb(var(--v-theme-on-surface)); }

/* ── Réponse admin ── */
.admin-response-title {
  font-size: 0.9rem !important; font-weight: 600 !important;
  padding: 12px 16px !important; display: flex !important;
  align-items: center !important; border-radius: 12px 12px 0 0;
}
.response-date { font-size: 0.78rem; color: rgb(var(--v-theme-text-secondary)); font-weight: 400; }
.admin-response-body { font-size: 0.9rem; color: rgb(var(--v-theme-on-surface)); line-height: 1.7; padding: 16px !important; }

/* ── Pièces jointes ── */
.attachment-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px dashed rgb(var(--v-theme-border-light)); }
.att-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.att-name { font-size: 0.88rem; font-weight: 500; color: rgb(var(--v-theme-on-surface)); }
.att-size { font-size: 0.75rem; color: rgb(var(--v-theme-text-secondary)); }

/* ── Timeline ── */
.no-history { display: flex; flex-direction: column; align-items: center; padding: 24px; color: rgb(var(--v-theme-text-secondary)); gap: 8px; font-size: 0.88rem; }
.history-timeline { padding: 8px 0; }
.timeline-item {
  background: rgb(var(--v-theme-surface-variant));
  border-radius: 10px; padding: 10px 14px; margin-bottom: 4px;
  border: 1px solid rgb(var(--v-theme-border-light));
}
.timeline-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.timeline-statuses { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.timeline-date { font-size: 0.75rem; color: rgb(var(--v-theme-text-secondary)); }
.timeline-comment { font-size: 0.83rem; color: rgb(var(--v-theme-on-surface)); margin: 6px 0 4px; line-height: 1.5; }
.timeline-actor { font-size: 0.75rem; color: rgb(var(--v-theme-text-secondary)); display: flex; align-items: center; gap: 4px; }

/* ── Colonne droite ── */
.quick-list { padding: 0 !important; }
.quick-label { font-size: 0.82rem !important; color: rgb(var(--v-theme-text-secondary)); }
.quick-value { font-size: 0.82rem; font-weight: 600; color: rgb(var(--v-theme-on-surface)); }
.escalade-reason { font-size: 0.88rem; color: rgb(var(--v-theme-on-surface)); line-height: 1.6; margin-bottom: 8px; }
.escalade-date { font-size: 0.78rem; color: rgb(var(--v-theme-text-secondary)); display: flex; align-items: center; gap: 4px; }

/* ── Dialog ── */
.dialog-title { font-size: 1rem !important; font-weight: 700 !important; padding: 20px 24px 14px !important; display: flex !important; align-items: center !important; }
.dialog-body  { padding: 16px 24px !important; color: rgb(var(--v-theme-on-surface)); font-size: 0.9rem; }
.dialog-warning { color: #DC2626; font-size: 0.83rem; font-weight: 500; margin-top: 4px; }
.dialog-actions { padding: 12px 24px 20px !important; justify-content: flex-end !important; gap: 8px; }

/* ── Responsive ── */
@media (max-width: 600px) {
  .rec-detail-page { padding: 12px; }
  .page-header     { flex-direction: column; align-items: flex-start; }
  .progress-steps  { overflow-x: auto; }
  .step-label      { font-size: 0.65rem; }
}
</style>