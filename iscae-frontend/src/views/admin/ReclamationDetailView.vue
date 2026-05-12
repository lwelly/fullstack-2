<template>
  <div class="detail-page">

    <!-- ── En-tête ── -->
    <div class="d-flex align-center gap-3 mb-5">
      <v-btn
        icon="mdi-arrow-left"
        variant="text"
        size="small"
        @click="$router.push({ name: 'admin.reclamations' })"
      />
      <div>
        <h1 class="detail-page__title">Détail Réclamation</h1>
        <p class="detail-page__sub">
          {{ rec?.reference_number ?? rec?.reference ?? `#${route.params.id}` }}
        </p>
      </div>
    </div>

    <!-- ── Loading ── -->
    <div v-if="loading" class="py-10 text-center">
      <v-progress-circular indeterminate color="#0F2D5E" size="32" />
      <p style="font-size:13px;color:#6B7280;margin-top:12px">Chargement…</p>
    </div>

    <!-- ── Erreur ── -->
    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      rounded="lg"
      class="mb-4"
    >
      {{ error }}
      <v-btn variant="text" size="small" class="ml-2" @click="load">
        Réessayer
      </v-btn>
    </v-alert>

    <!-- ── Contenu ── -->
    <template v-else-if="rec">
      <v-row>

        <!-- ════ Colonne principale ════ -->
        <v-col cols="12" md="8">

          <!-- Informations générales -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Informations générales</span>
              <span class="status-chip" :style="chipStyle(rec.status)">
                {{ sLabel(rec.status) }}
              </span>
            </div>
            <div class="card-body">
              <div class="info-grid">

                <div class="info-item">
                  <span class="info-key">Référence</span>
                  <span class="ref-code">
                    {{ rec.reference_number ?? rec.reference ?? `#${rec.id}` }}
                  </span>
                </div>

                <div class="info-item">
                  <span class="info-key">Type</span>
                  <span class="info-val">{{ typeLabel(rec.type) }}</span>
                </div>

                <div class="info-item">
                  <span class="info-key">Module</span>
                  <span class="info-val">{{ rec.module?.name ?? '—' }}</span>
                </div>

                <div class="info-item">
                  <span class="info-key">Code module</span>
                  <span class="info-val">{{ rec.module?.code ?? '—' }}</span>
                </div>

                <div class="info-item">
                  <span class="info-key">Semestre</span>
                  <span class="info-val">
                    {{ rec.semestre?.label ?? rec.semestre?.code ?? '—' }}
                  </span>
                </div>

                <div class="info-item">
                  <span class="info-key">Année universitaire</span>
                  <span class="info-val">{{ rec.academic_year ?? '—' }}</span>
                </div>

                <div class="info-item">
                  <span class="info-key">Note actuelle</span>
                  <span class="info-val font-weight-bold" style="color:#0F2D5E">
                    {{ rec.note_actuelle != null ? rec.note_actuelle + ' / 20' : '—' }}
                  </span>
                </div>

                <div class="info-item">
                  <span class="info-key">Note réclamée</span>
                  <span class="info-val font-weight-bold" style="color:#EA580C">
                    {{ rec.note_reclamee != null ? rec.note_reclamee + ' / 20' : '—' }}
                  </span>
                </div>

                <div class="info-item">
                  <span class="info-key">Date soumission</span>
                  <span class="info-val">{{ fDateFull(rec.created_at) }}</span>
                </div>

                <div class="info-item">
                  <span class="info-key">Dernière mise à jour</span>
                  <span class="info-val">{{ fDateFull(rec.updated_at) }}</span>
                </div>

                <div v-if="rec.resolved_at" class="info-item">
                  <span class="info-key">Résolue le</span>
                  <span class="info-val">{{ fDateFull(rec.resolved_at) }}</span>
                </div>

                <div v-if="rec.is_escalated" class="info-item">
                  <span class="info-key">Escaladée</span>
                  <v-chip size="x-small" color="orange" variant="tonal">Oui</v-chip>
                </div>

              </div>
            </div>
          </div>

          <!-- Justification -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Justification de la réclamation</span>
            </div>
            <div class="card-body">
              <p class="justif-text">
                {{ rec.justification?.trim() || 'Aucune justification fournie.' }}
              </p>
            </div>
          </div>

          <!-- Réponse admin -->
          <div v-if="rec.admin_response" class="card mb-4">
            <div class="card-head">
              <span class="card-title">Réponse de l'administration</span>
            </div>
            <div class="card-body">
              <p class="justif-text admin-response">{{ rec.admin_response }}</p>
            </div>
          </div>

          <!-- Pièces jointes -->
          <div v-if="rec.attachments?.length" class="card mb-4">
            <div class="card-head">
              <span class="card-title">Pièces jointes</span>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
              <v-chip
                v-for="att in rec.attachments"
                :key="att.id"
                size="small"
                prepend-icon="mdi-paperclip"
                color="blue"
                variant="tonal"
                :href="att.url"
                target="_blank"
              >
                {{ att.original_name ?? att.filename ?? 'Fichier' }}
              </v-chip>
            </div>
          </div>

          <!-- Historique -->
          <div v-if="rec.history?.length" class="card mb-4">
            <div class="card-head">
              <span class="card-title">Historique des statuts</span>
            </div>
            <div class="card-body pa-0">
              <div
                v-for="(h, i) in rec.history"
                :key="i"
                class="history-row"
              >
                <div
                  class="history-dot"
                  :style="{ background: sColor(h.new_status) }"
                />
                <div class="history-content">
                  <div class="d-flex align-center gap-2 flex-wrap">
                    <span
                      v-if="h.old_status"
                      class="status-chip"
                      :style="chipStyle(h.old_status)"
                    >
                      {{ sLabel(h.old_status) }}
                    </span>
                    <v-icon v-if="h.old_status" size="12" color="#9CA3AF">
                      mdi-arrow-right
                    </v-icon>
                    <span class="status-chip" :style="chipStyle(h.new_status)">
                      {{ sLabel(h.new_status) }}
                    </span>
                    <span style="font-size:11px;color:#9CA3AF">
                      {{ fDateFull(h.created_at) }}
                    </span>
                  </div>
                  <p v-if="h.comment" class="history-comment">{{ h.comment }}</p>
                  <p v-if="h.changed_by" class="history-by">Par : {{ h.changed_by }}</p>
                </div>
              </div>
            </div>
          </div>

        </v-col>

        <!-- ════ Colonne droite ════ -->
        <v-col cols="12" md="4">

          <!-- ── Étudiant ── -->
          <div class="card mb-4">
            <div class="card-head">
              <v-icon size="16" color="#0F2D5E" class="mr-1">
                mdi-account-circle-outline
              </v-icon>
              <span class="card-title">Étudiant</span>
            </div>
            <div class="card-body">

              <!-- Avatar + nom -->
              <div class="d-flex align-center gap-3 mb-4">
                <v-avatar
                  size="48"
                  :color="avatarColor(studentName)"
                  style="flex-shrink:0"
                >
                  <img
                    v-if="rec.student?.photo_url"
                    :src="rec.student.photo_url"
                    :alt="studentName"
                  />
                  <span
                    v-else
                    style="font-size:14px;font-weight:700;color:white"
                  >
                    {{ initials(studentName) }}
                  </span>
                </v-avatar>
                <div>
                  <div style="font-size:14px;font-weight:600;color:#111827">
                    {{ studentName }}
                  </div>
                  <div style="font-size:12px;color:#6B7280">
                    {{ rec.student?.matricule ?? '—' }}
                  </div>
                </div>
              </div>

              <!-- Détails -->
              <div class="detail-row">
                <span class="detail-key">Email</span>
                <span class="detail-val">
                  {{ rec.student?.email ?? '—' }}
                </span>
              </div>

              <!-- ✅ Filière corrigée -->
              <div class="detail-row">
                <span class="detail-key">Filière</span>
                <span class="detail-val">
                  {{ rec.student?.filiere?.nom
                    ?? rec.student?.filiere?.name
                    ?? rec.student?.filiere?.code
                    ?? '—' }}
                </span>
              </div>

              <!-- ✅ Niveau corrigé -->
              <div class="detail-row">
                <span class="detail-key">Niveau</span>
                <span class="detail-val">
                  {{ rec.student?.niveau?.label
                    ?? rec.student?.niveau?.name
                    ?? rec.student?.niveau?.code
                    ?? '—' }}
                </span>
              </div>

            </div>
          </div>

          <!-- ── Traitement ── -->
          <div class="card mb-4">
            <div class="card-head">
              <v-icon size="16" color="#0F2D5E" class="mr-1">
                mdi-cog-outline
              </v-icon>
              <span class="card-title">Traitement</span>
            </div>
            <div class="card-body">

              <div class="field-label mb-1">Nouveau statut</div>
              <v-select
                v-model="newStatus"
                :items="allowedTransitions"
                item-title="label"
                item-value="value"
                variant="outlined"
                density="compact"
                rounded="lg"
                hide-details
                class="mb-3"
                no-data-text="Aucune transition disponible"
              />

              <div class="field-label mb-1">Réponse / Commentaire</div>
              <v-textarea
                v-model="adminComment"
                placeholder="Expliquez votre décision..."
                variant="outlined"
                rounded="lg"
                density="compact"
                hide-details
                rows="3"
                class="mb-3"
              />

              <v-btn
                color="#0F2D5E"
                variant="flat"
                size="small"
                rounded="lg"
                block
                :loading="updating"
                :disabled="!newStatus || newStatus === rec.status || allowedTransitions.length === 0"
                @click="updateStatus"
              >
                <v-icon start size="16">mdi-content-save-outline</v-icon>
                Enregistrer
              </v-btn>

              <div
                v-if="allowedTransitions.length === 0"
                class="mt-2 text-center"
                style="font-size:12px;color:#9CA3AF"
              >
                Réclamation terminée — aucune action possible
              </div>

            </div>
          </div>

        </v-col>
      </v-row>
    </template>

    <!-- ── Snackbar ── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      timeout="3500"
      location="top right"
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
import { useRoute } from 'vue-router'
import api from '@/api/axios'

const route = useRoute()

const loading      = ref(true)
const error        = ref('')
const rec          = ref(null)
const updating     = ref(false)
const newStatus    = ref('')
const adminComment = ref('')
const snack        = ref({ show: false, text: '', color: 'success' })

const notify = (text, color = 'success') => {
  snack.value = { show: true, text, color }
}

// ── Transitions autorisées ────────────────────────────────────────────────────
const TRANSITIONS = {
  submitted : ['in_review', 'resolved', 'rejected'],
  received  : ['in_review', 'resolved', 'rejected'],
  in_review : ['resolved', 'rejected', 'escalated'],
  escalated : ['resolved', 'rejected', 'in_review'],
  resolved  : [],
  rejected  : [],
}

const ALL_STATUSES = [
  { value: 'submitted', label: 'Soumise'   },
  { value: 'received',  label: 'Reçue'     },
  { value: 'in_review', label: 'En cours'  },
  { value: 'escalated', label: 'Escaladée' },
  { value: 'resolved',  label: 'Résolue'   },
  { value: 'rejected',  label: 'Rejetée'   },
]

const allowedTransitions = computed(() => {
  const current = rec.value?.status ?? ''
  const allowed = TRANSITIONS[current] ?? []
  return ALL_STATUSES.filter(s => allowed.includes(s.value))
})

// ── Nom étudiant ──────────────────────────────────────────────────────────────
const studentName = computed(() => {
  const s = rec.value?.student
  if (!s) return '—'
  if (s.full_name && s.full_name.trim()) return s.full_name.trim()
  const parts = [
    s.prenom ?? s.first_name ?? '',
    s.nom    ?? s.last_name  ?? '',
  ].filter(p => p.trim() !== '')
  if (parts.length) return parts.join(' ')
  if (s.name) return s.name
  return '—'
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const COLORS_AVATAR = [
  '#0F2D5E', '#2563EB', '#16A34A',
  '#D97706', '#DC2626', '#7C3AED',
]
const avatarColor = (name) =>
  COLORS_AVATAR[(name?.charCodeAt(0) ?? 0) % COLORS_AVATAR.length]

const initials = (name) => {
  if (!name || name === '—') return '?'
  return name
    .split(' ')
    .map(n => n[0]?.toUpperCase() ?? '')
    .slice(0, 2)
    .join('')
}

const TYPE_LABELS = {
  cc         : 'Devoir / CC',
  controle   : 'Contrôle Continu',
  examen     : 'Examen',
  exam       : 'Examen',
  rattrapage : 'Rattrapage',
}
const typeLabel = (t) => {
  if (!t) return '—'
  return TYPE_LABELS[t.toLowerCase()] ?? t
}

const STATUS_MAP = {
  submitted : { label: 'Soumise',   color: '#2563EB' },
  received  : { label: 'Reçue',     color: '#0369A1' },
  in_review : { label: 'En cours',  color: '#7C3AED' },
  escalated : { label: 'Escaladée', color: '#EA580C' },
  resolved  : { label: 'Résolue',   color: '#16A34A' },
  rejected  : { label: 'Rejetée',   color: '#DC2626' },
}

const sColor = (s) => STATUS_MAP[s]?.color ?? '#9CA3AF'
const sLabel = (s) => STATUS_MAP[s]?.label ?? s ?? '—'

const chipStyle = (s) => {
  const c = sColor(s)
  return {
    background   : c + '18',
    color        : c,
    border       : '1px solid ' + c + '30',
    borderRadius : '20px',
    fontSize     : '11px',
    padding      : '3px 10px',
    fontWeight   : '600',
    display      : 'inline-block',
    whiteSpace   : 'nowrap',
  }
}

const fDateFull = (d) => {
  if (!d) return '—'
  return new Date(d).toLocaleString('fr-FR', {
    day    : '2-digit',
    month  : 'long',
    year   : 'numeric',
    hour   : '2-digit',
    minute : '2-digit',
  })
}

// ── Chargement ────────────────────────────────────────────────────────────────
async function load() {
  loading.value = true
  error.value   = ''
  try {
    const res          = await api.get(`/admin/reclamations/${route.params.id}`)
    rec.value          = res.data?.data ?? res.data
    newStatus.value    = rec.value?.status ?? ''
    adminComment.value = rec.value?.admin_response ?? ''
  } catch (err) {
    console.error('[ReclamationDetail]', err.response?.data ?? err)
    error.value = err.response?.data?.message ?? 'Impossible de charger la réclamation.'
  } finally {
    loading.value = false
  }
}

// ── Mise à jour statut ────────────────────────────────────────────────────────
async function updateStatus() {
  if (!newStatus.value || newStatus.value === rec.value.status) return
  updating.value = true
  try {
    await api.put(`/admin/reclamations/${route.params.id}/status`, {
      status         : newStatus.value,
      admin_response : adminComment.value || undefined,
      comment        : adminComment.value || undefined,
    })
    notify('Statut mis à jour avec succès ✅')
    adminComment.value = ''
    await load()                          // recharge toutes les données
  } catch (err) {
    notify(
      err.response?.data?.message ?? 'Erreur lors de la mise à jour.',
      'error'
    )
  } finally {
    updating.value = false
  }
}

onMounted(load)
</script>

<style scoped>
/* ── Page ─────────────────────────────────────────────────────────────────── */
.detail-page        { max-width: 1200px; }
.detail-page__title { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
.detail-page__sub   { font-size: 13px; color: #6B7280; margin: 3px 0 0; }

/* ── Cards ────────────────────────────────────────────────────────────────── */
.card {
  background    : #fff;
  border        : 1px solid #E5E7EB;
  border-radius : 12px;
  overflow      : hidden;
}
.card-head {
  display         : flex;
  align-items     : center;
  justify-content : space-between;
  padding         : 14px 18px;
  border-bottom   : 1px solid #F3F4F6;
}
.card-title { font-size: 14px; font-weight: 600; color: #111827; }
.card-body  { padding: 16px 18px; }

/* ── Grille infos ─────────────────────────────────────────────────────────── */
.info-grid {
  display               : grid;
  grid-template-columns : 1fr 1fr;
  gap                   : 10px;
}
.info-item {
  display        : flex;
  flex-direction : column;
  gap            : 3px;
  padding        : 10px 12px;
  background     : #F9FAFB;
  border-radius  : 8px;
  border         : 1px solid #F3F4F6;
}
.info-key {
  font-size      : 11px;
  font-weight    : 600;
  color          : #9CA3AF;
  text-transform : uppercase;
  letter-spacing : .4px;
}
.info-val { font-size: 13px; font-weight: 500; color: #111827; }
.ref-code { font-family: monospace; font-size: 13px; font-weight: 700; color: #0F2D5E; }

/* ── Justification ────────────────────────────────────────────────────────── */
.justif-text {
  font-size    : 14px;
  color        : #374151;
  line-height  : 1.75;
  white-space  : pre-wrap;
  margin       : 0;
  background   : #F9FAFB;
  border       : 1px solid #E5E7EB;
  border-radius: 8px;
  padding      : 14px;
}
.admin-response {
  background  : #F0FDF4;
  border      : none;
  border-left : 3px solid #16A34A;
  color       : #15803D;
}

/* ── Étudiant détails ─────────────────────────────────────────────────────── */
.detail-row {
  display         : flex;
  justify-content : space-between;
  align-items     : flex-start;
  padding         : 9px 0;
  border-bottom   : 1px solid #F9FAFB;
  font-size       : 13px;
}
.detail-row:last-child { border-bottom: none; }
.detail-key { color: #6B7280; font-weight: 500; flex-shrink: 0; }
.detail-val {
  color       : #111827;
  font-weight : 500;
  text-align  : right;
  max-width   : 62%;
  word-break  : break-word;
}

/* ── Champs traitement ────────────────────────────────────────────────────── */
.field-label { font-size: 12px; font-weight: 600; color: #374151; }

/* ── Status chip ──────────────────────────────────────────────────────────── */
.status-chip { display: inline-block; white-space: nowrap; }

/* ── Historique ───────────────────────────────────────────────────────────── */
.history-row {
  display       : flex;
  gap           : 14px;
  padding       : 14px 18px;
  border-bottom : 1px solid #F3F4F6;
}
.history-row:last-child { border-bottom: none; }
.history-dot {
  width         : 12px;
  height        : 12px;
  border-radius : 50%;
  margin-top    : 4px;
  flex-shrink   : 0;
}
.history-content { flex: 1; }
.history-comment { font-size: 13px; color: #6B7280; margin: 4px 0 0; }
.history-by      { font-size: 11px; color: #9CA3AF; margin: 2px 0 0; }

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 600px) {
  .info-grid { grid-template-columns: 1fr; }
}
</style>
