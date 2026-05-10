<template>
  <div class="reclamations-page">

    <!-- ───── En-tête ───── -->
    <div class="page-header mb-6">
      <div>
        <p class="page-sub">
          <v-icon size="14" color="#6B7280" class="mr-1">mdi-file-document-multiple</v-icon>
          {{ meta.total ?? list.length }} réclamation(s) au total
        </p>
      </div>
     
    </div>

    <!-- ───── Filtres statut ───── -->
    <div class="filter-tabs mb-5">
      <button
        v-for="f in statusFilters"
        :key="f.value"
        class="filter-tab"
        :class="{ 'tab-active': activeStatus === f.value }"
        @click="setStatus(f.value)"
      >
        <v-icon size="13" class="tab-icon">{{ f.icon }}</v-icon>
        {{ f.label }}
        <span class="tab-count">{{ f.count }}</span>
      </button>
    </div>

    <!-- ───── Barre recherche + filtres ───── -->
    <v-card elevation="0" rounded="xl" border class="mb-5 pa-4">
      <div class="search-bar">
        <v-text-field
          v-model="search"
          placeholder="Référence, étudiant, matricule..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          clearable
          style="max-width:340px"
          @update:model-value="onSearch"
        />
        <v-select
          v-model="activeType"
          :items="typeFilters"
          item-title="label"
          item-value="value"
          label="Type"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          style="max-width:180px"
          @update:model-value="load(1)"
        />
        <v-select
          v-model="activeSemestre"
          :items="semestres"
          item-title="label"
          item-value="id"
          label="Semestre"
          variant="outlined"
          density="compact"
          rounded="lg"
          hide-details
          clearable
          style="max-width:200px"
          @update:model-value="load(1)"
        />
        <v-spacer />
        
      </div>
    </v-card>

    <!-- ───── Tableau ───── -->
    <v-card elevation="2" rounded="xl" class="overflow-hidden">

      <!-- Chargement -->
      <div v-if="loading" class="py-16 text-center">
        <v-progress-circular indeterminate color="#0F2D5E" size="40" width="3" />
        <p class="mt-4 text-body-2 text-medium-emphasis">Chargement des réclamations…</p>
      </div>

      <!-- Vide -->
      <div v-else-if="list.length === 0" class="empty-state">
        <div class="empty-icon-wrapper mb-4">
          <v-icon size="40" color="#93C5FD">mdi-inbox-outline</v-icon>
        </div>
        <p class="empty-title">Aucune réclamation trouvée</p>
        <p class="empty-sub">Essayez de modifier vos filtres de recherche</p>
      </div>

      <template v-else>
        <!-- En-tête colonnes -->
        <div class="rec-head">
          <span>Référence</span>
          <span>Étudiant</span>
          <span>Module / Type</span>
          <span>Semestre</span>
          <span>Date</span>
          <span>Statut</span>
          <span class="text-center">Action</span>
        </div>

        <!-- Lignes -->
        <div
          v-for="r in list"
          :key="r.id"
          class="rec-row"
          @click="openDetail(r)"
        >
          <!-- Référence -->
          <div class="rec-ref-wrapper">
            <span class="rec-ref">
              {{ r.reference ?? r.reference_number ?? `#${r.id}` }}
            </span>
          </div>

          <!-- Étudiant -->
          <div class="rec-student">
            <div
              class="student-avatar"
              :style="{ background: avatarColor(studentName(r)) }"
            >
              {{ initials(studentName(r)) }}
            </div>
            <div class="student-info">
              <div class="student-name">{{ studentName(r) }}</div>
              <div class="student-mat">
                {{ r.student?.student_number ?? r.student?.matricule ?? '' }}
              </div>
            </div>
          </div>

          <!-- Module / Type -->
          <div class="rec-module">
            <div class="module-name">{{ r.module?.name ?? '—' }}</div>
            <span class="type-badge" :class="`type-${r.type}`">
              {{ typeLabel(r.type) }}
            </span>
          </div>

          <!-- Semestre -->
          <div class="rec-semestre">
            {{ r.semestre?.label ?? r.semestre?.code ?? '—' }}
          </div>

          <!-- Date -->
          <div class="rec-date">
            <v-icon size="12" color="#9CA3AF" class="mr-1">mdi-calendar</v-icon>
            {{ fDate(r.created_at) }}
          </div>

          <!-- Statut -->
          <div>
            <span class="status-chip" :style="statusStyle(r.status)">
              {{ statusLabel(r.status) }}
            </span>
          </div>

          <!-- Action -->
          <div class="text-center">
            <v-btn
              icon
              size="small"
              variant="tonal"
              color="primary"
              @click.stop="openDetail(r)"
            >
              <v-icon size="16">mdi-eye-outline</v-icon>
            </v-btn>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-bar">
          <span class="page-info-text">
            Page <strong>{{ page }}</strong> sur <strong>{{ meta.last_page }}</strong>
            <span class="ml-2 text-medium-emphasis">({{ meta.total }} résultat(s))</span>
          </span>
          <div class="d-flex align-center gap-1">
            <v-btn
              icon="mdi-chevron-left"
              size="small"
              variant="text"
              :disabled="page === 1"
              @click="load(page - 1)"
            />
            <template v-for="p in paginationPages" :key="p">
              <v-btn
                v-if="p !== '…'"
                size="small"
                :variant="p === page ? 'elevated' : 'text'"
                :color="p === page ? '#0F2D5E' : 'default'"
                min-width="36"
                @click="load(p)"
              >
                {{ p }}
              </v-btn>
              <span v-else class="px-1 text-medium-emphasis">…</span>
            </template>
            <v-btn
              icon="mdi-chevron-right"
              size="small"
              variant="text"
              :disabled="page === meta.last_page"
              @click="load(page + 1)"
            />
          </div>
        </div>
      </template>
    </v-card>

    <!-- ══════════════════════════════════════
         DIALOG DÉTAIL
    ══════════════════════════════════════ -->
    <v-dialog v-model="dialog" max-width="780" scrollable>
      <v-card v-if="selected" rounded="xl" class="detail-card">

        <!-- Header -->
        <div class="dialog-head">
          <div class="d-flex align-center gap-3">
            <div class="dialog-icon-wrapper">
              <v-icon color="#0F2D5E" size="22">mdi-file-document-outline</v-icon>
            </div>
            <div>
              <div class="dialog-label">RÉCLAMATION</div>
              <div class="dialog-ref">
                {{ selected.reference ?? selected.reference_number ?? `#${selected.id}` }}
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <span class="status-chip" :style="statusStyle(selected.status)">
              {{ statusLabel(selected.status) }}
            </span>
            <v-btn
              icon="mdi-close"
              size="small"
              variant="text"
              color="grey"
              @click="dialog = false"
            />
          </div>
        </div>

        <v-divider />

        <v-card-text class="dialog-body pa-0">

          <!-- Loader -->
          <div v-if="loadingDetail" class="text-center py-12">
            <v-progress-circular indeterminate color="#0F2D5E" size="36" />
            <p class="mt-3 text-body-2 text-medium-emphasis">Chargement des détails…</p>
          </div>

          <template v-else>
            <div class="dialog-inner pa-6">

              <!-- ── Infos étudiant ── -->
              <div class="detail-section mb-4">
                <div class="detail-section-header">
                  <v-icon size="16" color="#0F2D5E">mdi-account-circle</v-icon>
                  <span>Étudiant</span>
                </div>
                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-key">Nom complet</span>
                    <span class="info-val">{{ studentName(selected) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Matricule</span>
                    <span class="info-val font-mono">
                      {{ selected.student?.student_number ?? selected.student?.matricule ?? '—' }}
                    </span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Email</span>
                    <span class="info-val">
                      {{ selected.student?.email ?? selected.student?.user?.email ?? '—' }}
                    </span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Filière / Niveau</span>
                    <span class="info-val">
                      {{ selected.student?.filiere?.name ?? '—' }}
                      /
                      {{ selected.student?.niveau?.name ?? '—' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- ── Détails réclamation ── -->
              <div class="detail-section mb-4">
                <div class="detail-section-header">
                  <v-icon size="16" color="#0F2D5E">mdi-clipboard-text</v-icon>
                  <span>Détails de la réclamation</span>
                </div>
                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-key">Type</span>
                    <span class="info-val">
                      <span class="type-badge" :class="`type-${selected.type}`">
                        {{ typeLabel(selected.type) }}
                      </span>
                    </span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Module</span>
                    <span class="info-val font-weight-medium">
                      {{ selected.module?.name ?? '—' }}
                    </span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Semestre</span>
                    <span class="info-val">
                      {{ selected.semestre?.label ?? selected.semestre?.code ?? '—' }}
                    </span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Date soumission</span>
                    <span class="info-val">{{ fDateTime(selected.created_at) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-key">Note actuelle</span>
                    <span class="info-val">
                      <span v-if="selected.note_actuelle != null" class="note-badge note-current">
                        {{ selected.note_actuelle }} / 20
                      </span>
                      <span v-else>—</span>
                    </span>
                  </div>
                  <div v-if="selected.note_reclamee != null" class="info-item">
                    <span class="info-key">Note réclamée</span>
                    <span class="info-val">
                      <span class="note-badge note-claimed">
                        {{ selected.note_reclamee }} / 20
                      </span>
                    </span>
                  </div>
                  <div v-if="selected.academic_year" class="info-item">
                    <span class="info-key">Année universitaire</span>
                    <span class="info-val">{{ selected.academic_year }}</span>
                  </div>
                  <div v-if="selected.resolved_at" class="info-item">
                    <span class="info-key">Résolue le</span>
                    <span class="info-val">{{ fDateTime(selected.resolved_at) }}</span>
                  </div>
                </div>

                <!-- Justification -->
                <div class="mt-4">
                  <span class="info-key d-block mb-2">Justification</span>
                  <div class="desc-box">{{ selected.justification ?? '—' }}</div>
                </div>
              </div>

              <!-- ── Pièces jointes ── -->
              <div v-if="selected.attachments?.length" class="detail-section mb-4">
                <div class="detail-section-header">
                  <v-icon size="16" color="#0F2D5E">mdi-paperclip</v-icon>
                  <span>Pièces jointes ({{ selected.attachments.length }})</span>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                  <a
                    v-for="att in selected.attachments"
                    :key="att.id"
                    :href="att.url"
                    target="_blank"
                    class="attachment-chip"
                  >
                    <v-icon size="13">mdi-file-outline</v-icon>
                    {{ att.name ?? att.original_name }}
                  </a>
                </div>
              </div>

              <!-- ── Réponse & traitement ── -->
              <div class="detail-section mb-4">
                <div class="detail-section-header">
                  <v-icon size="16" color="#0F2D5E">mdi-pencil-circle</v-icon>
                  <span>Réponse & traitement</span>
                </div>
                <div class="treatment-form mt-3">
                  <v-select
                    v-model="newStatus"
                    :items="allowedTransitions"
                    item-title="label"
                    item-value="value"
                    label="Nouveau statut"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    hide-details
                    class="mb-3"
                  >
                    <template #prepend-inner>
                      <v-icon size="16" color="#6B7280">mdi-swap-horizontal</v-icon>
                    </template>
                  </v-select>

                  <v-textarea
                    v-model="adminComment"
                    label="Commentaire / Réponse à l'étudiant"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    rows="3"
                    hide-details
                    class="mb-4"
                    placeholder="Rédigez votre réponse ici…"
                  />

                  <div class="d-flex gap-2 flex-wrap">
                    <v-btn
                      color="#0F2D5E"
                      rounded="lg"
                      size="small"
                      :loading="updating"
                      :disabled="!newStatus"
                      elevation="0"
                      @click="updateStatus"
                    >
                      <v-icon start size="15">mdi-content-save-outline</v-icon>
                      Enregistrer
                    </v-btn>
                  </div>
                </div>
              </div>

              <!-- ── Historique ── -->
              <div v-if="selected.history?.length" class="detail-section">
                <div class="detail-section-header">
                  <v-icon size="16" color="#0F2D5E">mdi-history</v-icon>
                  <span>Historique ({{ selected.history.length }})</span>
                </div>
                <div class="history-list mt-3">
                  <div
                    v-for="(h, i) in selected.history"
                    :key="i"
                    class="history-item"
                  >
                    <div class="history-dot" />
                    <div class="history-content">
                      <div class="history-statuses">
                        <span
                          v-if="h.old_status"
                          class="status-chip"
                          :style="statusStyle(h.old_status)"
                        >
                          {{ statusLabel(h.old_status) }}
                        </span>
                        <v-icon v-if="h.old_status" size="12" color="#9CA3AF">
                          mdi-arrow-right
                        </v-icon>
                        <span class="status-chip" :style="statusStyle(h.new_status)">
                          {{ statusLabel(h.new_status) }}
                        </span>
                      </div>
                      <div class="history-meta">
                        <v-icon size="11" color="#9CA3AF">mdi-clock-outline</v-icon>
                        {{ fDateTime(h.created_at) }}
                        <span class="mx-1">·</span>
                        <v-icon size="11" color="#9CA3AF">mdi-account</v-icon>
                        {{ h.user?.name ?? 'Système' }}
                      </div>
                      <div v-if="h.comment" class="history-comment">
                        {{ h.comment }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </template>
        </v-card-text>

        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" color="#6B7280" @click="dialog = false">Fermer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ══════════════════════════════════════
         DIALOG ESCALADE
    ══════════════════════════════════════ -->
    <v-dialog v-model="escalateDialog" max-width="440">
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex align-center gap-3 mb-5">
          <v-avatar color="orange-lighten-4" size="42">
            <v-icon color="orange" size="22">mdi-arrow-up-circle</v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">Escalader la réclamation</div>
            <div class="text-caption text-medium-emphasis">
              Transférer vers un autre responsable
            </div>
          </div>
        </div>

        <v-select
          v-model="escalateTo"
          :items="admins"
          item-title="name"
          item-value="id"
          label="Escalader vers"
          variant="outlined"
          density="compact"
          rounded="lg"
          class="mb-3"
        />

        <v-textarea
          v-model="escalateReason"
          label="Raison de l'escalade"
          variant="outlined"
          density="compact"
          rounded="lg"
          rows="3"
          class="mb-4"
        />

        <div class="d-flex gap-2 justify-end">
          <v-btn variant="text" color="grey" @click="escalateDialog = false">Annuler</v-btn>
          <v-btn
            color="orange"
            rounded="lg"
            :loading="escalating"
            :disabled="!escalateTo"
            @click="doEscalate"
          >
            Escalader
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- ───── Snackbar ───── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      timeout="3500"
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

// ─── État principal ──────────────────────────────────────────────────────────
const loading        = ref(true)
const loadingDetail  = ref(false)
const list           = ref([])
const meta           = ref({ total: 0, last_page: 1 })
const page           = ref(1)
const search         = ref('')
const activeStatus   = ref('')
const activeType     = ref('')
const activeSemestre = ref(null)
const semestres      = ref([])

// ─── Dialog détail ───────────────────────────────────────────────────────────
const dialog       = ref(false)
const selected     = ref(null)
const newStatus    = ref('')
const adminComment = ref('')
const updating     = ref(false)

// ─── Dialog escalade ─────────────────────────────────────────────────────────
const escalateDialog = ref(false)
const escalateTo     = ref(null)
const escalateReason = ref('')
const escalating     = ref(false)
const admins         = ref([])

// ─── Snackbar ────────────────────────────────────────────────────────────────
const snack  = ref({ show: false, text: '', color: 'success' })
const notify = (text, color = 'success') => { snack.value = { show: true, text, color } }

// ─── Compteurs statut ────────────────────────────────────────────────────────
const counts = ref({
  '': 0, submitted: 0, received: 0,
  in_review: 0, escalated: 0, resolved: 0, rejected: 0,
})

const statusFilters = computed(() => [
  { label: 'Toutes',     value: '',          count: counts.value[''],       icon: 'mdi-format-list-bulleted'   },
  { label: 'En attente', value: 'submitted', count: counts.value.submitted, icon: 'mdi-clock-outline'          },
  { label: 'En cours',   value: 'in_review', count: counts.value.in_review, icon: 'mdi-progress-clock'         },
  { label: 'Escaladées', value: 'escalated', count: counts.value.escalated, icon: 'mdi-arrow-up-circle-outline'},
  { label: 'Résolues',   value: 'resolved',  count: counts.value.resolved,  icon: 'mdi-check-circle-outline'   },
  { label: 'Rejetées',   value: 'rejected',  count: counts.value.rejected,  icon: 'mdi-close-circle-outline'   },
])

const typeFilters = [
  { label: 'Tous les types',   value: '' },
  { label: 'Contrôle Continu', value: 'cc' },
  { label: 'Examen',           value: 'examen' },
  { label: 'Rattrapage',       value: 'rattrapage' },
]

const editableStatuses = [
  { label: 'En attente', value: 'submitted' },
  { label: 'En cours',   value: 'in_review' },
  { label: 'Escaladée',  value: 'escalated' },
  { label: 'Résolue',    value: 'resolved'  },
  { label: 'Rejetée',    value: 'rejected'  },
]

const TRANSITIONS = {
  submitted: ['in_review', 'resolved', 'rejected'],
  in_review: ['resolved', 'rejected', 'escalated'],
  escalated: ['resolved', 'rejected', 'in_review'],
  resolved:  [],
  rejected:  [],
}

const allowedTransitions = computed(() => {
  const current = selected.value?.status ?? ''
  const allowed = TRANSITIONS[current] ?? Object.keys(TRANSITIONS)
  return editableStatuses.filter(s => allowed.includes(s.value))
})

// ─── Pagination intelligente ─────────────────────────────────────────────────
const paginationPages = computed(() => {
  const total = meta.value.last_page
  const cur   = page.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = new Set([1, total, cur])
  if (cur > 1) pages.add(cur - 1)
  if (cur < total) pages.add(cur + 1)
  const sorted = [...pages].sort((a, b) => a - b)
  const result = []
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…')
    result.push(sorted[i])
  }
  return result
})

// ─── Chargement liste ────────────────────────────────────────────────────────
async function load(p = 1) {
  loading.value = true
  page.value    = p
  try {
    const params = { page: p, per_page: 15, sort: 'created_at', direction: 'desc' }
    if (activeStatus.value)   params.status      = activeStatus.value
    if (activeType.value)     params.type        = activeType.value
    if (activeSemestre.value) params.semestre_id = activeSemestre.value
    if (search.value)         params.search      = search.value

    const res  = await api.get('/admin/reclamations', { params })
    const body = res.data

    if (body?.data?.data) {
      list.value = body.data.data
      meta.value = { total: body.data.total, last_page: body.data.last_page }
    } else if (Array.isArray(body?.data)) {
      list.value = body.data
      meta.value = { total: body.meta?.total ?? body.data.length, last_page: body.meta?.last_page ?? 1 }
    } else if (Array.isArray(body)) {
      list.value = body
      meta.value = { total: body.length, last_page: 1 }
    } else {
      list.value = []
      meta.value = { total: 0, last_page: 1 }
    }
    updateCounts()
  } catch (err) {
    console.error('[ReclamationsView] load error:', err)
    notify('Impossible de charger les réclamations.', 'error')
    list.value = []
  } finally {
    loading.value = false
  }
}

function updateCounts() {
  const c = { '': meta.value.total, submitted: 0, received: 0, in_review: 0, escalated: 0, resolved: 0, rejected: 0 }
  list.value.forEach(r => { if (c[r.status] !== undefined) c[r.status]++ })
  counts.value = c
}

async function loadSemestres() {
  try {
    const res  = await api.get('/admin/semestres')
    const data = res.data?.data ?? res.data ?? []
    semestres.value = Array.isArray(data) ? data : []
  } catch { semestres.value = [] }
}

async function loadAdmins() {
  try {
    const res  = await api.get('/admin/admins')
    const data = res.data?.data ?? res.data ?? []
    admins.value = (Array.isArray(data) ? data : []).map(a => ({
      id:   a.id,
      name: a.name ?? a.full_name ?? `Admin #${a.id}`,
    }))
  } catch { admins.value = [] }
}

let searchTimer = null
function onSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), 400)
}

function setStatus(val) {
  activeStatus.value = val
  load(1)
}

// ─── Ouvrir dialog détail ─────────────────────────────────────────────────────
async function openDetail(r) {
  selected.value      = { ...r }
  newStatus.value     = r.status
  adminComment.value  = r.admin_response ?? ''
  dialog.value        = true
  loadingDetail.value = true
  try {
    const res  = await api.get(`/admin/reclamations/${r.id}`)
    const full = res.data?.data ?? res.data
    selected.value     = { ...r, ...full }
    newStatus.value    = full.status ?? r.status
    adminComment.value = full.admin_response ?? r.admin_response ?? ''
  } catch (err) {
    console.error('[ReclamationsView] openDetail error:', err)
  } finally {
    loadingDetail.value = false
  }
}

// ─── Mettre à jour statut ─────────────────────────────────────────────────────
async function updateStatus() {
  if (!newStatus.value) return
  updating.value = true
  try {
    const res     = await api.put(`/admin/reclamations/${selected.value.id}/status`, {
      status:         newStatus.value,
      admin_response: adminComment.value,
      comment:        adminComment.value,
    })
    const updated = res.data?.data ?? res.data
    const idx     = list.value.findIndex(r => r.id === selected.value.id)
    if (idx !== -1) {
      list.value[idx] = {
        ...list.value[idx],
        status:         newStatus.value,
        admin_response: adminComment.value,
      }
    }
    selected.value = { ...selected.value, ...updated, status: newStatus.value }
    notify('Statut mis à jour avec succès ✅')
    updateCounts()
    dialog.value = false
  } catch (err) {
    notify(err.response?.data?.message ?? 'Erreur lors de la mise à jour.', 'error')
  } finally {
    updating.value = false
  }
}

// ─── Escalader ────────────────────────────────────────────────────────────────
async function doEscalate() {
  if (!escalateTo.value) return
  escalating.value = true
  try {
    await api.post(`/admin/reclamations/${selected.value.id}/escalate`, {
      escalated_to: escalateTo.value,
      reason:       escalateReason.value,
    })
    notify('Réclamation escaladée avec succès ✅')
    escalateDialog.value = false
    dialog.value         = false
    load(page.value)
  } catch (err) {
    notify(err.response?.data?.message ?? "Erreur lors de l'escalade.", 'error')
  } finally {
    escalating.value = false
  }
}

// ─── Export CSV ───────────────────────────────────────────────────────────────
function exportCsv() {
  const headers = ['Référence','Étudiant','Matricule','Type','Module','Semestre','Statut','Date']
  const rows    = list.value.map(r => [
    r.reference ?? r.reference_number ?? r.id,
    studentName(r),
    r.student?.student_number ?? r.student?.matricule ?? '',
    typeLabel(r.type),
    r.module?.name ?? '',
    r.semestre?.label ?? r.semestre?.code ?? '',
    statusLabel(r.status),
    fDate(r.created_at),
  ])
  const csv  = [headers, ...rows].map(row => row.map(c => `"${c}"`).join(',')).join('\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `reclamations_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => { load(1); loadSemestres(); loadAdmins() })

// ─── Helpers ──────────────────────────────────────────────────────────────────
function studentName(r) {
  if (!r?.student) return '—'
  const s = r.student
  if (s.full_name) return s.full_name
  const parts = [
    s.first_name ?? s.prenom ?? '',
    s.last_name  ?? s.nom    ?? '',
  ].filter(Boolean)
  if (parts.length) return parts.join(' ')
  if (s.name) return s.name
  return '—'
}

function initials(name) {
  if (!name || name === '—') return '?'
  return name.split(' ').map(p => p[0]?.toUpperCase() ?? '').slice(0, 2).join('')
}

const AVATAR_COLORS = ['#2563EB','#7C3AED','#DB2777','#0891B2','#D97706','#16A34A']
function avatarColor(name) {
  let h = 0
  for (const c of (name ?? '')) h = (h * 31 + c.charCodeAt(0)) & 0xFFFF
  return AVATAR_COLORS[h % AVATAR_COLORS.length]
}

const TYPE_LABELS = {
  cc:         'Devoir',
  controle:   'Devoir',
  examen:     'Examen',
  exam:       'Examen',
  rattrapage: 'Rattrapage',
}
function typeLabel(v) { return TYPE_LABELS[v?.toLowerCase()] ?? v ?? '—' }

const STATUS_MAP = {
  submitted: { label: 'Soumise',   bg: '#DBEAFE', text: '#1D4ED8' },
  received:  { label: 'Reçue',     bg: '#E0F2FE', text: '#0369A1' },
  in_review: { label: 'En cours',  bg: '#EDE9FE', text: '#6D28D9' },
  escalated: { label: 'Escaladée', bg: '#FEF3C7', text: '#B45309' },
  resolved:  { label: 'Résolue',   bg: '#DCFCE7', text: '#15803D' },
  approved:  { label: 'Approuvée', bg: '#DCFCE7', text: '#15803D' },
  rejected:  { label: 'Rejetée',   bg: '#FEE2E2', text: '#B91C1C' },
}
function statusLabel(v) { return STATUS_MAP[v]?.label ?? v ?? '—' }
function statusStyle(v) {
  const c = STATUS_MAP[v] ?? { bg: '#F3F4F6', text: '#6B7280' }
  return `background:${c.bg};color:${c.text};`
}

function fDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}
function fDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<style scoped>
/* ── Page ───────────────────────────────────────────────── */
.reclamations-page { max-width: 1400px; margin: 0 auto; padding: 24px 16px; }

/* ── En-tête ────────────────────────────────────────────── */
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.page-title  { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
.page-sub    { font-size: 13px; color: #6B7280; margin: 4px 0 0; display: flex; align-items: center; }

/* ── Filtres onglets ────────────────────────────────────── */
.filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.filter-tab {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 14px; border-radius: 20px;
  border: 1px solid #E5E7EB; background: #fff;
  font-size: 12px; font-weight: 500; color: #6B7280;
  cursor: pointer; transition: all .15s ease; white-space: nowrap;
}
.filter-tab:hover { border-color: #93C5FD; color: #2563EB; background: #EFF6FF; }
.tab-active {
  background: #0F2D5E !important; border-color: #0F2D5E !important;
  color: #fff !important; box-shadow: 0 2px 8px rgba(15,45,94,.25);
}
.tab-count {
  background: rgba(0,0,0,.1); padding: 1px 7px;
  border-radius: 10px; font-size: 11px;
}
.tab-active .tab-count { background: rgba(255,255,255,.2); }

/* ── Barre recherche ────────────────────────────────────── */
.search-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }

/* ── Tableau ────────────────────────────────────────────── */
.rec-head, .rec-row {
  display: grid;
  grid-template-columns: 130px 1fr 160px 110px 100px 110px 60px;
  align-items: center; padding: 10px 20px; font-size: 12px;
}
.rec-head {
  background: #F8FAFC; font-weight: 700; color: #64748B;
  text-transform: uppercase; font-size: 11px; letter-spacing: .5px;
  border-bottom: 2px solid #E2E8F0;
}
.rec-row {
  border-bottom: 1px solid #F1F5F9;
  cursor: pointer; transition: background .12s;
}
.rec-row:last-child { border-bottom: none; }
.rec-row:hover { background: #F0F7FF; }

/* Référence */
.rec-ref {
  font-family: 'Courier New', monospace; font-size: 11px; font-weight: 600;
  color: #0F2D5E; background: #EFF6FF; padding: 3px 8px;
  border-radius: 6px; border: 1px solid #BFDBFE;
}

/* Étudiant */
.rec-student { display: flex; align-items: center; gap: 10px; }
.student-avatar {
  width: 34px; height: 34px; border-radius: 50%; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
  box-shadow: 0 1px 4px rgba(0,0,0,.15);
}
.student-info { min-width: 0; }
.student-name {
  font-size: 13px; color: #111827; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.student-mat { font-size: 11px; color: #9CA3AF; }

/* Module */
.module-name { font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 3px; }

/* Type badge */
.type-badge {
  display: inline-block; font-size: 10px; font-weight: 600;
  padding: 2px 7px; border-radius: 4px;
  text-transform: uppercase; letter-spacing: .3px;
}
.type-cc, .type-controle { background: #DBEAFE; color: #1D4ED8; }
.type-examen              { background: #EDE9FE; color: #6D28D9; }
.type-rattrapage          { background: #FEF3C7; color: #B45309; }

/* Semestre & date */
.rec-semestre { font-size: 12px; color: #6B7280; }
.rec-date     { font-size: 12px; color: #6B7280; display: flex; align-items: center; }

/* Status chip */
.status-chip {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 600;
  padding: 3px 10px; border-radius: 20px; white-space: nowrap;
}

/* ── Pagination ─────────────────────────────────────────── */
.pagination-bar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px; padding: 14px 20px;
  border-top: 2px solid #F1F5F9; background: #FAFAFA;
}
.page-info-text { font-size: 12px; color: #6B7280; }

/* ── Empty state ────────────────────────────────────────── */
.empty-state { padding: 64px 24px; text-align: center; }
.empty-icon-wrapper {
  width: 72px; height: 72px; border-radius: 50%; background: #EFF6FF;
  display: flex; align-items: center; justify-content: center; margin: 0 auto;
}
.empty-title { font-size: 15px; font-weight: 600; color: #374151; margin: 0 0 6px; }
.empty-sub   { font-size: 13px; color: #9CA3AF; margin: 0; }

/* ── Dialog ─────────────────────────────────────────────── */
.dialog-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; background: #FAFBFF;
}
.dialog-icon-wrapper {
  width: 42px; height: 42px; border-radius: 10px; background: #EFF6FF;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid #BFDBFE;
}
.dialog-label {
  font-size: 10px; font-weight: 700; color: #94A3B8;
  letter-spacing: .8px; text-transform: uppercase;
}
.dialog-ref { font-size: 17px; font-weight: 700; color: #0F2D5E; }
.dialog-body { max-height: 70vh; overflow-y: auto; }

/* ── Sections détail ────────────────────────────────────── */
.detail-section {
  background: #FAFBFF; border: 1px solid #E2E8F0;
  border-radius: 12px; padding: 16px;
}
.detail-section-header {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 700; color: #0F2D5E;
  text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;
}

/* Grille infos */
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.info-item { display: flex; flex-direction: column; gap: 3px; }
.info-key  { font-size: 11px; color: #94A3B8; font-weight: 500; }
.info-val  { font-size: 13px; color: #111827; font-weight: 500; }
.font-mono { font-family: monospace; }

/* Notes */
.note-badge {
  display: inline-block; font-size: 12px; font-weight: 700;
  padding: 3px 10px; border-radius: 6px;
}
.note-current { background: #FEF3C7; color: #B45309; }
.note-claimed { background: #DCFCE7; color: #15803D; }

/* Justification */
.desc-box {
  background: #fff; border: 1px solid #E2E8F0; border-radius: 8px;
  padding: 12px; font-size: 13px; color: #374151; line-height: 1.6;
}

/* Pièces jointes */
.attachment-chip {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 500; color: #2563EB; background: #EFF6FF;
  border: 1px solid #BFDBFE; padding: 5px 12px; border-radius: 20px;
  text-decoration: none; transition: all .15s;
}
.attachment-chip:hover { background: #DBEAFE; }

/* Traitement */
.treatment-form {
  background: #fff; border: 1px solid #E2E8F0;
  border-radius: 10px; padding: 16px;
}

/* ── Historique ─────────────────────────────────────────── */
.history-list  { display: flex; flex-direction: column; }
.history-item  { display: flex; gap: 12px; padding: 10px 0; position: relative; }
.history-item:not(:last-child)::after {
  content: ''; position: absolute; left: 6px; top: 26px;
  bottom: -10px; width: 2px; background: #E2E8F0;
}
.history-dot {
  width: 14px; height: 14px; border-radius: 50%;
  background: #DBEAFE; border: 2px solid #2563EB;
  flex-shrink: 0; margin-top: 4px;
}
.history-content  { flex: 1; }
.history-statuses { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.history-meta {
  font-size: 11px; color: #9CA3AF; margin-top: 4px;
  display: flex; align-items: center; gap: 3px;
}
.history-comment {
  font-size: 12px; color: #6B7280; margin-top: 6px;
  background: #F9FAFB; border-left: 3px solid #E2E8F0;
  padding: 6px 10px; border-radius: 0 6px 6px 0;
}
</style>
