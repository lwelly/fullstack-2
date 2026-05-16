<template>
  <div class="reclamations-page">

    <!-- ══════════════════════════════════════════════════════════════
         EN-TÊTE
    ══════════════════════════════════════════════════════════════════ -->
    <div class="page-header mb-6">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3">
        <div>
          <p class="page-subtitle">
            {{ list.length }} réclamation{{ list.length > 1 ? 's' : '' }} au total
          </p>
        </div>
        <v-btn
          color="primary"
          variant="flat"
          size="small"
          prepend-icon="mdi-plus"
          rounded="lg"
          :to="{ name: 'student.reclamations.new' }"
          class="new-btn"
        >
          Nouvelle réclamation
        </v-btn>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         FILTRES + RECHERCHE
    ══════════════════════════════════════════════════════════════════ -->
    <div class="toolbar mb-5">
      <div class="filter-group">
        <button
          v-for="f in filters"
          :key="f.key"
          class="filter-btn"
          :class="{ active: activeFilter === f.key }"
          @click="setFilter(f.key)"
        >
          <v-icon size="14" class="mr-1">{{ f.icon }}</v-icon>
          {{ f.label }}
          <span class="filter-count">{{ counts[f.key] }}</span>
        </button>
      </div>

      <v-text-field
        v-model="search"
        placeholder="Rechercher..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        rounded="lg"
        hide-details
        class="search-field"
        clearable
      />
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         ÉTAT : CHARGEMENT
    ══════════════════════════════════════════════════════════════════ -->
    <div v-if="loading" class="state-box">
      <v-progress-circular indeterminate color="primary" size="36" width="3" />
      <p class="state-text mt-3">Chargement de vos réclamations...</p>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         ÉTAT : VIDE
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else-if="filtered.length === 0" class="state-box">
      <div class="empty-icon-wrapper">
        <v-icon size="40" color="secondary">mdi-inbox-outline</v-icon>
      </div>
      <p class="state-title mt-3">Aucune réclamation trouvée</p>
      <p class="state-text">
        {{
          search
            ? 'Aucun résultat pour votre recherche.'
            : activeFilter !== 'all'
              ? 'Aucune réclamation dans cette catégorie.'
              : 'Vous n\'avez pas encore soumis de réclamation.'
        }}
      </p>
      <v-btn
        v-if="activeFilter === 'all' && !search"
        color="primary"
        variant="tonal"
        size="small"
        prepend-icon="mdi-plus"
        class="mt-3"
        :to="{ name: 'student.reclamations.new' }"
      >
        Soumettre une réclamation
      </v-btn>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TABLEAU
    ══════════════════════════════════════════════════════════════════ -->
    <div v-else class="table-card">

      <div class="tbl-head">
        <span class="col-ref">Référence</span>
        <span class="col-module">Module</span>
        <span class="col-type">Type</span>
        <span class="col-date">Date</span>
        <span class="col-status">Statut</span>
        <span class="col-action"></span>
      </div>

      <transition-group name="row-fade" tag="div">
        <div
          v-for="r in paginated"
          :key="r.id"
          class="tbl-row"
          @click="goDetail(r.id)"
        >
          <span class="col-ref">
            <span class="ref-code">{{ r.reference_number ?? r.reference ?? `#${r.id}` }}</span>
          </span>
          <span class="col-module">
            <span class="module-name text-truncate">{{ r.module?.name ?? '—' }}</span>
          </span>
          <span class="col-type">
            <span class="type-chip" :class="`type-${r.type}`">{{ typeLabel(r.type) }}</span>
          </span>
          <span class="col-date">
            <span class="date-main">{{ fDateShort(r.created_at) }}</span>
            <span class="date-time">{{ fTime(r.created_at) }}</span>
          </span>
          <span class="col-status">
            <span class="status-chip" :class="`status-${r.status}`">
              <span class="status-dot"></span>
              {{ sLabel(r.status) }}
            </span>
          </span>
          <span class="col-action">
            <v-icon size="18" class="arrow-icon">mdi-chevron-right</v-icon>
          </span>
        </div>
      </transition-group>

      <div class="tbl-footer">
        <span class="footer-info">
          {{ paginated.length }} sur {{ filtered.length }} réclamation{{ filtered.length > 1 ? 's' : '' }}
        </span>
        <v-pagination
          v-if="totalPages > 1"
          v-model="page"
          :length="totalPages"
          size="small"
          rounded="circle"
          active-color="primary"
          density="compact"
        />
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router       = useRouter()
const loading      = ref(true)
const list         = ref([])
const search       = ref('')
const activeFilter = ref('all')
const page         = ref(1)
const PER_PAGE     = 10

const filters = [
  { key: 'all',      label: 'Toutes',     icon: 'mdi-format-list-bulleted' },
  { key: 'pending',  label: 'En attente', icon: 'mdi-clock-outline'        },
  { key: 'resolved', label: 'Résolues',   icon: 'mdi-check-circle-outline' },
  { key: 'rejected', label: 'Rejetées',   icon: 'mdi-close-circle-outline' },
]

const PENDING  = ['submitted', 'received', 'in_review', 'escalated']
const RESOLVED = ['resolved', 'closed']

const counts = computed(() => ({
  all:      list.value.length,
  pending:  list.value.filter(r => PENDING.includes(r.status)).length,
  resolved: list.value.filter(r => RESOLVED.includes(r.status)).length,
  rejected: list.value.filter(r => r.status === 'rejected').length,
}))

function setFilter(key) { activeFilter.value = key; page.value = 1 }

const filtered = computed(() => {
  let res = list.value
  if (activeFilter.value === 'pending')  res = res.filter(r => PENDING.includes(r.status))
  if (activeFilter.value === 'resolved') res = res.filter(r => RESOLVED.includes(r.status))
  if (activeFilter.value === 'rejected') res = res.filter(r => r.status === 'rejected')
  if (search.value?.trim()) {
    const q = search.value.toLowerCase()
    res = res.filter(r =>
      r.reference_number?.toLowerCase().includes(q) ||
      r.reference?.toLowerCase().includes(q)        ||
      r.module?.name?.toLowerCase().includes(q)     ||
      r.type?.toLowerCase().includes(q)
    )
  }
  return [...res].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)))
const paginated  = computed(() => filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE))

const STATUS_LABELS = {
  submitted:'Soumise', received:'Reçue', in_review:'En cours',
  escalated:'Escaladée', resolved:'Résolue', rejected:'Rejetée',
  closed:'Fermée', cancelled:'Annulée',
}
const sLabel = s => STATUS_LABELS[s] ?? s ?? '—'

const TYPE_LABELS = { cc:'Devoir', controle:'Devoir', examen:'Examen', rattrapage:'Rattrapage' }
const typeLabel = t => TYPE_LABELS[t] ?? t ?? '—'

const fDateShort = d => d
  ? new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' })
  : '—'
const fTime = d => d
  ? new Date(d).toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' })
  : ''

function goDetail(id) {
  router.push({ name: 'student.reclamation.detail', params: { id } })
}

onMounted(async () => {
  try {
    const res  = await api.get('/student/reclamations')
    list.value = res.data?.data ?? []
  } catch {
    list.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.reclamations-page { width: 100%; }

/* ── Subtitle ── */
.page-subtitle {
  font-size: 0.85rem;
  color: rgb(var(--v-theme-text-secondary));
  margin: 4px 0 0;
}

/* ── Toolbar ── */
.toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.filter-group {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  flex: 1;
}
.search-field { max-width: 260px; flex-shrink: 0; }

/* ── Boutons filtre ── */
.filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid rgb(var(--v-theme-border-default));
  background: rgb(var(--v-theme-surface));
  font-size: 12.5px;
  font-weight: 500;
  color: rgb(var(--v-theme-text-secondary));
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}
.filter-btn:hover {
  border-color: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.06);
}
.filter-btn.active {
  background: rgb(var(--v-theme-primary));
  color: #fff;
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 2px 8px rgba(15,45,94,0.25);
}
.filter-count {
  background: rgba(0,0,0,0.10);
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 7px;
  min-width: 20px;
  text-align: center;
}
.filter-btn.active .filter-count { background: rgba(255,255,255,0.25); }

/* ── États ── */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgb(var(--v-theme-border-default));
  border-radius: 16px;
  text-align: center;
}
.empty-icon-wrapper {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: rgb(var(--v-theme-surface-variant));
  display: flex; align-items: center; justify-content: center;
}
.state-title {
  font-size: 1rem; font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  margin: 0;
}
.state-text {
  font-size: 0.85rem;
  color: rgb(var(--v-theme-text-secondary));
  margin: 4px 0 0;
}

/* ── Table card ── */
.table-card {
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgb(var(--v-theme-border-default));
  border-radius: 16px;
  overflow: hidden;
  width: 100%;
}

/* ── En-tête tableau ── */
.tbl-head {
  display: grid;
  grid-template-columns: 160px 1fr 110px 140px 130px 44px;
  padding: 12px 20px;
  background: rgb(var(--v-theme-surface-variant));
  border-bottom: 1px solid rgb(var(--v-theme-border-default));
  font-size: 11px;
  font-weight: 700;
  color: rgb(var(--v-theme-text-secondary));
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

/* ── Lignes ── */
.tbl-row {
  display: grid;
  grid-template-columns: 160px 1fr 110px 140px 130px 44px;
  padding: 14px 20px;
  border-bottom: 1px solid rgb(var(--v-theme-border-light));
  align-items: center;
  cursor: pointer;
  transition: background 0.12s ease, transform 0.1s ease;
  font-size: 13px;
  color: rgb(var(--v-theme-on-surface));
}
.tbl-row:last-child { border-bottom: none; }
.tbl-row:hover {
  background: rgba(var(--v-theme-primary), 0.05);
  transform: translateX(2px);
}

/* ── Colonnes ── */
.col-ref    { display: flex; align-items: center; }
.col-module { display: flex; align-items: center; min-width: 0; padding-right: 12px; }
.col-type   { display: flex; align-items: center; }
.col-date   { display: flex; flex-direction: column; gap: 2px; }
.col-status { display: flex; align-items: center; }
.col-action { display: flex; align-items: center; justify-content: flex-end; }

.ref-code {
  font-family: 'Courier New', monospace;
  font-size: 12px; font-weight: 700;
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
  padding: 3px 8px;
  border-radius: 6px;
}
.module-name {
  font-size: 13px; font-weight: 500;
  color: rgb(var(--v-theme-on-surface));
  display: block; max-width: 100%;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.date-main { font-size: 12.5px; color: rgb(var(--v-theme-on-surface)); font-weight: 500; }
.date-time { font-size: 11px; color: rgb(var(--v-theme-text-secondary)); }

/* ── Chip type ── */
.type-chip {
  font-size: 11px; font-weight: 600;
  padding: 3px 10px; border-radius: 20px;
  display: inline-block; white-space: nowrap;
}
.type-cc, .type-controle { background: rgba(37,99,235,0.12); color: #2563EB; }
.type-examen              { background: rgba(217,119,6,0.12); color: #D97706; }
.type-rattrapage          { background: rgba(124,58,237,0.12); color: #7C3AED; }

/* ── Chip statut ── */
.status-chip {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 600;
  padding: 4px 10px; border-radius: 20px; white-space: nowrap;
}
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

.status-submitted { background: rgba(37,99,235,0.12);  color: #2563EB; }
.status-submitted .status-dot { background: #2563EB; }
.status-received  { background: rgba(5,150,105,0.12);  color: #059669; }
.status-received  .status-dot { background: #059669; }
.status-in_review { background: rgba(217,119,6,0.12);  color: #D97706; }
.status-in_review .status-dot { background: #D97706; }
.status-escalated { background: rgba(234,88,12,0.12);  color: #EA580C; }
.status-escalated .status-dot { background: #EA580C; }
.status-resolved  { background: rgba(22,163,74,0.12);  color: #16A34A; }
.status-resolved  .status-dot { background: #16A34A; }
.status-rejected  { background: rgba(220,38,38,0.12);  color: #DC2626; }
.status-rejected  .status-dot { background: #DC2626; }
.status-closed    { background: rgba(107,114,128,0.12);color: #6B7280; }
.status-closed    .status-dot { background: #9CA3AF; }
.status-cancelled { background: rgba(107,114,128,0.12);color: #6B7280; }
.status-cancelled .status-dot { background: #9CA3AF; }

/* ── Flèche ── */
.arrow-icon { color: rgb(var(--v-theme-text-secondary)); transition: color 0.15s, transform 0.15s; }
.tbl-row:hover .arrow-icon { color: rgb(var(--v-theme-primary)); transform: translateX(3px); }

/* ── Pied de tableau ── */
.tbl-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 20px;
  background: rgb(var(--v-theme-surface-variant));
  border-top: 1px solid rgb(var(--v-theme-border-default));
}
.footer-info { font-size: 12px; color: rgb(var(--v-theme-text-secondary)); }

/* ── Animations ── */
.row-fade-enter-active { transition: all 0.2s ease; }
.row-fade-leave-active { transition: all 0.15s ease; }
.row-fade-enter-from   { opacity: 0; transform: translateY(-6px); }
.row-fade-leave-to     { opacity: 0; transform: translateX(10px); }

/* ── Responsive ── */
@media (max-width: 768px) {
  .tbl-head, .tbl-row { grid-template-columns: 130px 1fr 110px 36px; }
  .col-module, .col-type { display: none; }
  .search-field { max-width: 100%; }
  .toolbar { flex-direction: column; align-items: stretch; }
}
@media (max-width: 480px) {
  .tbl-head, .tbl-row { grid-template-columns: 1fr 100px 32px; }
  .col-date { display: none; }
}
</style>