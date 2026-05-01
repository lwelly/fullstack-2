<template>
  <div class="reclamations-page">

    <!-- En-tête -->
    <div class="page-header mb-5">
      <div>
        <h1 class="page-title">Réclamations</h1>
        <p class="page-sub">{{ meta.total ?? list.length }} réclamation(s) au total</p>
      </div>
      <v-btn
        variant="outlined"
        color="#0F2D5E"
        size="small"
        prepend-icon="mdi-download"
        @click="exportCsv"
      >
        Exporter CSV
      </v-btn>
    </div>

    <!-- Filtres par statut -->
    <div class="filter-tabs mb-4">
      <button
        v-for="f in statusFilters"
        :key="f.value"
        class="filter-tab"
        :class="{ 'tab-active': activeStatus === f.value }"
        @click="setStatus(f.value)"
      >
        {{ f.label }}
        <span class="tab-count">{{ f.count }}</span>
      </button>
    </div>

    <!-- Barre de recherche + type -->
    <div class="search-bar mb-4">
      <v-text-field
        v-model="search"
        placeholder="Référence, étudiant, matricule..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        rounded="lg"
        hide-details
        clearable
        style="max-width: 340px;"
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
        style="max-width: 180px;"
        @update:model-value="load(1)"
      />
    </div>

    <!-- Table -->
    <div class="section-card">
      <div v-if="loading" class="py-12 text-center">
        <v-progress-circular indeterminate color="#0F2D5E" size="32" />
        <p style="font-size:13px;color:#6B7280;margin-top:12px">Chargement…</p>
      </div>

      <div v-else-if="list.length === 0" class="empty-state">
        <v-icon size="44" color="#D1D5DB">mdi-inbox-outline</v-icon>
        <p>Aucune réclamation trouvée</p>
      </div>

      <template v-else>
        <!-- En-tête -->
        <div class="rec-head">
          <span>Référence</span>
          <span>Étudiant</span>
          <span>Module / Type</span>
          <span>Date</span>
          <span>Statut</span>
          <span>Action</span>
        </div>
        <!-- Lignes -->
        <div
          v-for="r in list"
          :key="r.id"
          class="rec-row"
          @click="openDetail(r)"
        >
          <span class="rec-ref">{{ r.reference ?? `#${r.id}` }}</span>
          <div class="rec-student">
            <div class="student-avatar">{{ initials(studentName(r)) }}</div>
            <div>
              <div class="student-name">{{ studentName(r) }}</div>
              <div class="student-mat">{{ r.student?.student_number ?? r.student?.matricule ?? '' }}</div>
            </div>
          </div>
          <div>
            <div style="font-size:12px;color:#374151;font-weight:500">{{ r.module?.name ?? '—' }}</div>
            <div style="font-size:11px;color:#9CA3AF">{{ typeLabel(r.type) }}</div>
          </div>
          <span class="rec-date">{{ fDate(r.created_at) }}</span>
          <span>
            <span class="status-chip" :style="statusStyle(r.status)">{{ statusLabel(r.status) }}</span>
          </span>
          <span>
            <v-btn icon size="x-small" variant="text" color="#2563EB" @click.stop="openDetail(r)">
              <v-icon size="16">mdi-eye-outline</v-icon>
            </v-btn>
          </span>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="pagination-bar">
          <v-btn
            icon="mdi-chevron-left"
            size="small"
            variant="text"
            :disabled="page === 1"
            @click="load(page - 1)"
          />
          <span class="page-info">Page {{ page }} / {{ meta.last_page }}</span>
          <v-btn
            icon="mdi-chevron-right"
            size="small"
            variant="text"
            :disabled="page === meta.last_page"
            @click="load(page + 1)"
          />
        </div>
      </template>
    </div>

    <!-- ══════ DIALOG DÉTAIL ══════ -->
    <v-dialog v-model="dialog" max-width="740" scrollable>
      <v-card v-if="selected" rounded="xl">
        <!-- Header dialog -->
        <div class="dialog-head">
          <div>
            <div style="font-size:11px;color:#6B7280;font-weight:600;letter-spacing:.5px">RÉCLAMATION</div>
            <div style="font-size:16px;font-weight:700;color:#111827">{{ selected.reference ?? `#${selected.id}` }}</div>
          </div>
          <div class="d-flex align-center gap-2">
            <span class="status-chip" :style="statusStyle(selected.status)">{{ statusLabel(selected.status) }}</span>
            <v-btn icon="mdi-close" size="small" variant="text" @click="dialog = false" />
          </div>
        </div>

        <v-divider />

        <v-card-text style="padding: 20px 24px; max-height: 72vh; overflow-y: auto;">

          <!-- Infos étudiant -->
          <div class="info-section mb-4">
            <h3 class="info-title">Étudiant</h3>
            <div class="info-grid">
              <div class="info-item"><span class="info-key">Nom complet</span><span class="info-val">{{ studentName(selected) }}</span></div>
              <div class="info-item"><span class="info-key">Matricule</span><span class="info-val">{{ selected.student?.student_number ?? '—' }}</span></div>
              <div class="info-item"><span class="info-key">Email</span><span class="info-val">{{ selected.student?.user?.email ?? selected.student?.email ?? '—' }}</span></div>
              <div class="info-item"><span class="info-key">Filière / Niveau</span><span class="info-val">{{ selected.student?.filiere?.name ?? '—' }} / {{ selected.student?.niveau?.name ?? '—' }}</span></div>
            </div>
          </div>

          <!-- Infos réclamation -->
          <div class="info-section mb-4">
            <h3 class="info-title">Détails de la réclamation</h3>
            <div class="info-grid">
              <div class="info-item"><span class="info-key">Type</span><span class="info-val">{{ typeLabel(selected.type) }}</span></div>
              <div class="info-item"><span class="info-key">Module</span><span class="info-val">{{ selected.module?.name ?? '—' }}</span></div>
              <div class="info-item"><span class="info-key">Semestre</span><span class="info-val">{{ selected.semester?.label ?? selected.semestre?.label ?? '—' }}</span></div>
              <div class="info-item"><span class="info-key">Date soumission</span><span class="info-val">{{ fDateTime(selected.created_at) }}</span></div>
              <div v-if="selected.obtained_grade != null" class="info-item">
                <span class="info-key">Note obtenue</span>
                <span class="info-val">{{ selected.obtained_grade }} / 20</span>
              </div>
              <div v-if="selected.expected_grade != null" class="info-item">
                <span class="info-key">Note attendue</span>
                <span class="info-val">{{ selected.expected_grade }} / 20</span>
              </div>
            </div>
            <div class="info-item mt-3" style="flex-direction:column;gap:4px">
              <span class="info-key">Objet</span>
              <span class="info-val" style="font-size:14px;font-weight:600">{{ selected.subject ?? '—' }}</span>
            </div>
            <div class="desc-box mt-3">{{ selected.description ?? '—' }}</div>
          </div>

          <!-- Réponse admin -->
          <div class="info-section mb-4">
            <h3 class="info-title">Réponse & traitement</h3>
            <v-select
              v-model="newStatus"
              :items="editableStatuses"
              item-title="label"
              item-value="value"
              label="Changer le statut"
              variant="outlined"
              density="compact"
              rounded="lg"
              hide-details
              class="mb-3"
            />
            <v-textarea
              v-model="adminComment"
              label="Commentaire / Réponse"
              variant="outlined"
              density="compact"
              rounded="lg"
              rows="3"
              hide-details
              class="mb-3"
            />
            <v-btn
              color="#0F2D5E"
              rounded="lg"
              size="small"
              :loading="updating"
              @click="updateStatus"
            >
              <v-icon start size="15">mdi-content-save-outline</v-icon>
              Enregistrer
            </v-btn>
          </div>

          <!-- Historique -->
          <div v-if="selected.history?.length" class="info-section">
            <h3 class="info-title">Historique</h3>
            <div class="history-list">
              <div v-for="(h, i) in selected.history" :key="i" class="history-item">
                <div class="history-dot" />
                <div class="history-content">
                  <div style="font-size:12px;font-weight:600;color:#374151">{{ h.action ?? h.status }}</div>
                  <div style="font-size:11px;color:#9CA3AF">{{ fDateTime(h.created_at) }} — {{ h.user?.name ?? 'Système' }}</div>
                  <div v-if="h.comment" style="font-size:12px;color:#6B7280;margin-top:2px">{{ h.comment }}</div>
                </div>
              </div>
            </div>
          </div>

        </v-card-text>

        <v-divider />
        <v-card-actions class="pa-4">
          <v-btn variant="text" color="#6B7280" @click="dialog = false">Fermer</v-btn>
          <v-spacer />
          <v-btn
            color="#0F2D5E"
            size="small"
            rounded="lg"
            :to="{ name: 'admin.reclamation.detail', params: { id: selected.id } }"
          >
            Voir page complète <v-icon end size="14">mdi-arrow-right</v-icon>
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const toast = useToast()

/* ─── État ────────────────────────────────────────────────── */
const loading  = ref(true)
const list     = ref([])
const meta     = ref({ total: 0, last_page: 1 })
const page     = ref(1)
const search   = ref('')
const activeStatus = ref('')
const activeType   = ref('')

/* ─── Dialog ──────────────────────────────────────────────── */
const dialog       = ref(false)
const selected     = ref(null)
const newStatus    = ref('')
const adminComment = ref('')
const updating     = ref(false)

/* ─── Filtres statut ──────────────────────────────────────── */
const statusCounts = computed(() => {
  // Sera mis à jour via meta après un appel par statut
  return {}
})

const statusFilters = [
  { label: 'Toutes',       value: '' },
  { label: 'En attente',   value: 'submitted' },
  { label: 'En cours',     value: 'in_review' },
  { label: 'Escaladées',   value: 'escalated' },
  { label: 'Résolues',     value: 'resolved' },
  { label: 'Rejetées',     value: 'rejected' },
].map(f => ({ ...f, count: computed(() => {
  if (!f.value) return list.value.length
  return list.value.filter(r => r.status === f.value).length
}) }))

const typeFilters = [
  { label: 'Tous les types', value: '' },
  { label: 'Contrôle Continu', value: 'cc' },
  { label: 'Examen Final',     value: 'exam' },
  { label: 'Rattrapage',       value: 'rattrapage' },
  { label: 'TP / Projet',      value: 'tp' },
  { label: 'Autre',            value: 'autre' },
]

const editableStatuses = [
  { label: 'En attente',  value: 'submitted' },
  { label: 'En cours',    value: 'in_review' },
  { label: 'Escaladée',   value: 'escalated' },
  { label: 'Résolue',     value: 'resolved' },
  { label: 'Rejetée',     value: 'rejected' },
]

/* ─── Chargement ──────────────────────────────────────────── */
async function load (p = 1) {
  loading.value = true
  page.value    = p
  try {
    const params = {
      page:      p,
      per_page:  15,
      sort:      'created_at',
      direction: 'desc',
    }
    if (activeStatus.value) params.status = activeStatus.value
    if (activeType.value)   params.type   = activeType.value
    if (search.value)       params.search = search.value

    const res = await api.get('/admin/reclamations', { params })
    const data = res.data

    // Gérer les différents formats de réponse Laravel
    if (data?.data?.data) {
      list.value = data.data.data
      meta.value = { total: data.data.total, last_page: data.data.last_page }
    } else if (Array.isArray(data?.data)) {
      list.value = data.data
      meta.value = { total: data.meta?.total ?? data.data.length, last_page: data.meta?.last_page ?? 1 }
    } else if (Array.isArray(data)) {
      list.value = data
      meta.value = { total: data.length, last_page: 1 }
    } else {
      list.value = []
    }
  } catch (err) {
    console.error('Reclamations load error:', err)
    list.value = []
  } finally {
    loading.value = false
  }
}

let searchTimer = null
function onSearch () {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), 400)
}

function setStatus (val) {
  activeStatus.value = val
  load(1)
}

/* ─── Ouvrir dialog ───────────────────────────────────────── */
async function openDetail (r) {
  selected.value     = r
  newStatus.value    = r.status
  adminComment.value = r.admin_response ?? ''
  dialog.value       = true

  // Charger le détail complet si pas déjà chargé
  if (!r.history) {
    try {
      const res = await api.get(`/admin/reclamations/${r.id}`)
      const full = res.data?.data ?? res.data
      selected.value = { ...r, ...full }
    } catch { /* Utiliser les données existantes */ }
  }
}

/* ─── Mettre à jour statut ────────────────────────────────── */
async function updateStatus () {
  updating.value = true
  try {
    await api.put(`/admin/reclamations/${selected.value.id}/status`, {
      status:         newStatus.value,
      admin_response: adminComment.value,
      comment:        adminComment.value,
    })
    toast.success('Statut mis à jour avec succès.')
    // Mettre à jour localement
    const idx = list.value.findIndex(r => r.id === selected.value.id)
    if (idx !== -1) {
      list.value[idx] = { ...list.value[idx], status: newStatus.value }
    }
    selected.value = { ...selected.value, status: newStatus.value }
    dialog.value   = false
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Erreur lors de la mise à jour.')
  } finally {
    updating.value = false
  }
}

/* ─── Export CSV ──────────────────────────────────────────── */
function exportCsv () {
  const headers = ['Référence', 'Étudiant', 'Matricule', 'Type', 'Module', 'Statut', 'Date']
  const rows    = list.value.map(r => [
    r.reference ?? r.id,
    studentName(r),
    r.student?.student_number ?? '',
    typeLabel(r.type),
    r.module?.name ?? '',
    statusLabel(r.status),
    fDate(r.created_at),
  ])
  const csv   = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n')
  const blob  = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url   = URL.createObjectURL(blob)
  const a     = document.createElement('a')
  a.href      = url
  a.download  = `reclamations_${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(() => load(1))

/* ─── Helpers ─────────────────────────────────────────────── */
function studentName (r) {
  return r.student?.full_name ??
    [r.student?.first_name, r.student?.last_name].filter(Boolean).join(' ') ??
    r.student?.name ?? r.user?.name ?? '—'
}

function initials (name) {
  return name.split(' ').map(p => p[0]?.toUpperCase() ?? '').slice(0, 2).join('')
}

const AVATAR_COLORS = ['#2563EB','#7C3AED','#DB2777','#0891B2','#D97706','#16A34A']
function avatarColor (name) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xFFFF
  return AVATAR_COLORS[h % AVATAR_COLORS.length]
}

const TYPE_LABELS = { cc:'Contrôle Continu', exam:'Examen Final', examen:'Examen Final', rattrapage:'Rattrapage', tp:'TP/Projet', projet:'Projet', stage:'Stage', autre:'Autre' }
function typeLabel (v) { return TYPE_LABELS[v?.toLowerCase()] ?? v ?? '—' }

const STATUS_LABELS = { submitted:'Soumise', soumise:'Soumise', pending:'En attente', en_attente:'En attente', in_review:'En cours', in_progress:'En cours', en_cours:'En cours', escalated:'Escaladée', resolved:'Résolue', resolu:'Résolue', approved:'Approuvée', rejected:'Rejetée', rejete:'Rejetée' }
const STATUS_COLORS = { submitted:{bg:'#DBEAFE',text:'#1D4ED8'}, soumise:{bg:'#DBEAFE',text:'#1D4ED8'}, pending:{bg:'#FEF3C7',text:'#B45309'}, en_attente:{bg:'#FEF3C7',text:'#B45309'}, in_review:{bg:'#EDE9FE',text:'#6D28D9'}, in_progress:{bg:'#EDE9FE',text:'#6D28D9'}, en_cours:{bg:'#EDE9FE',text:'#6D28D9'}, escalated:{bg:'#FEF3C7',text:'#B45309'}, resolved:{bg:'#DCFCE7',text:'#15803D'}, resolu:{bg:'#DCFCE7',text:'#15803D'}, approved:{bg:'#DCFCE7',text:'#15803D'}, rejected:{bg:'#FEE2E2',text:'#B91C1C'}, rejete:{bg:'#FEE2E2',text:'#B91C1C'} }
function statusLabel (v) { return STATUS_LABELS[v] ?? v ?? '—' }
function statusStyle (v) { const c = STATUS_COLORS[v] ?? {bg:'#F3F4F6',text:'#6B7280'}; return `background:${c.bg};color:${c.text};` }

function fDate (d) { if (!d) return '—'; return new Date(d).toLocaleDateString('fr-FR',{day:'2-digit',month:'short',year:'numeric'}) }
function fDateTime (d) { if (!d) return '—'; return new Date(d).toLocaleString('fr-FR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) }
</script>

<style scoped>
.reclamations-page { max-width: 1400px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.page-title  { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
.page-sub    { font-size: 13px; color: #6B7280; margin: 3px 0 0; }

/* Filtres */
.filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-tab  {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 20px;
  border: 1px solid #E5E7EB; background: #fff;
  font-size: 12px; font-weight: 500; color: #6B7280; cursor: pointer;
  transition: all .15s;
}
.filter-tab:hover { border-color: #93C5FD; color: #2563EB; }
.tab-active  { background: #0F2D5E !important; border-color: #0F2D5E !important; color: #fff !important; }
.tab-count   {
  background: rgba(0,0,0,.08); color: inherit;
  padding: 1px 6px; border-radius: 10px; font-size: 11px;
}
.tab-active .tab-count { background: rgba(255,255,255,.2); }

/* Recherche */
.search-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }

/* Section card */
.section-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; overflow: hidden; }

/* Table */
.rec-head, .rec-row {
  display: grid;
  grid-template-columns: 110px 1fr 140px 100px 110px 56px;
  align-items: center; padding: 10px 18px; font-size: 12px;
}
.rec-head {
  background: #F9FAFB; font-weight: 600;
  color: #6B7280; text-transform: uppercase; font-size: 11px;
  letter-spacing: .4px;
}
.rec-row {
  border-top: 1px solid #F3F4F6; cursor: pointer; transition: background .12s;
}
.rec-row:hover { background: #F0F7FF; }
.rec-ref { font-family: monospace; color: #374151; }
.rec-student { display: flex; align-items: center; gap: 8px; }
.student-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: #0F2D5E; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.student-name { font-size: 13px; color: #111827; font-weight: 500; }
.student-mat  { font-size: 11px; color: #9CA3AF; }
.rec-date { font-size: 12px; color: #6B7280; }
.status-chip {
  display: inline-block; font-size: 11px; font-weight: 600;
  padding: 3px 9px; border-radius: 20px;
}

/* Pagination */
.pagination-bar {
  display: flex; align-items: center; justify-content: center;
  gap: 12px; padding: 12px;
  border-top: 1px solid #F3F4F6;
}
.page-info { font-size: 12px; color: #6B7280; }

/* Dialog */
.dialog-head {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 16px 20px;
}

/* Infos */
.info-section  { }
.info-title    { font-size: 13px; font-weight: 700; color: #374151; margin: 0 0 10px; text-transform: uppercase; letter-spacing: .4px; }
.info-grid     { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.info-item     { display: flex; flex-direction: column; gap: 2px; }
.info-key      { font-size: 11px; color: #9CA3AF; }
.info-val      { font-size: 13px; color: #111827; font-weight: 500; }
.desc-box {
  background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px;
  padding: 12px; font-size: 13px; color: #374151; line-height: 1.6;
}

/* Historique */
.history-list { display: flex; flex-direction: column; gap: 0; }
.history-item { display: flex; gap: 12px; padding: 8px 0; position: relative; }
.history-item:not(:last-child)::after {
  content: ''; position: absolute; left: 6px; top: 24px; bottom: -8px;
  width: 1px; background: #E5E7EB;
}
.history-dot {
  width: 14px; height: 14px; border-radius: 50%;
  background: #DBEAFE; border: 2px solid #2563EB;
  flex-shrink: 0; margin-top: 2px;
}
.history-content { flex: 1; }

/* Empty */
.empty-state { padding: 48px; text-align: center; color: #9CA3AF; }
.empty-state p { margin: 8px 0 0; font-size: 13px; }
</style>
