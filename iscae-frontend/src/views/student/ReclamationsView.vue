<template>
  <div>

    <!-- En-tête -->
    <div class="d-flex align-center justify-space-between mb-5">
      <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0">
        Mes Réclamations
      </h1>
      <v-btn
        color="#0F2D5E" variant="flat" size="small"
        prepend-icon="mdi-plus" rounded="lg"
        :to="{ name: 'student.reclamations.new' }"
      >
        Nouvelle réclamation
      </v-btn>
    </div>

    <!-- Filtres -->
    <div class="filters mb-4">
      <button
        v-for="f in filters" :key="f.key"
        class="filter-btn"
        :class="{ active: activeFilter === f.key }"
        @click="activeFilter = f.key"
      >
        {{ f.label }}
        <span class="filter-count">{{ counts[f.key] }}</span>
      </button>
    </div>

    <!-- Recherche -->
    <v-text-field
      v-model="search"
      placeholder="Rechercher une réclamation..."
      prepend-inner-icon="mdi-magnify"
      variant="outlined"
      density="compact"
      rounded="lg"
      hide-details
      class="mb-4"
      style="max-width:340px"
    />

    <!-- Liste -->
    <div class="card">
      <div v-if="loading" class="py-10 text-center">
        <v-progress-circular indeterminate color="#0F2D5E" size="28" />
      </div>

      <div v-else-if="filtered.length === 0" class="empty">
        <v-icon size="44" color="#D1D5DB">mdi-inbox-outline</v-icon>
        <p>Aucune réclamation trouvée</p>
      </div>

      <div v-else>
        <!-- Header table -->
        <div class="tbl-head">
          <span class="col-ref">Référence</span>
          <span class="col-type">Type</span>
          <span class="col-date">Date</span>
          <span class="col-status">Statut</span>
          <span class="col-action"></span>
        </div>

        <div
          v-for="r in paginated"
          :key="r.id"
          class="tbl-row"
          @click="$router.push({ name: 'student.reclamation.detail', params: { id: r.id } })"
        >
          <span class="col-ref">
            <span class="ref-code">{{ r.reference ?? `#${r.id}` }}</span>
          </span>
          <span class="col-type text-truncate">{{ r.module?.name ?? r.type ?? '—' }}</span>
          <span class="col-date">{{ fDate(r.created_at) }}</span>
          <span class="col-status">
            <span class="chip" :style="chipStyle(r.status)">{{ sLabel(r.status) }}</span>
          </span>
          <span class="col-action">
            <v-icon size="16" color="#9CA3AF">mdi-chevron-right</v-icon>
          </span>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="d-flex justify-center mt-4">
      <v-pagination
        v-model="page"
        :length="totalPages"
        size="small"
        rounded="lg"
        active-color="#0F2D5E"
      />
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

const loading      = ref(true)
const list         = ref([])
const search       = ref('')
const activeFilter = ref('all')
const page         = ref(1)
const PER_PAGE     = 10

const filters = [
  { key: 'all',        label: 'Toutes' },
  { key: 'pending',    label: 'En attente' },
  { key: 'resolved',   label: 'Résolues' },
  { key: 'rejected',   label: 'Rejetées' },
]

const PENDING  = ['submitted', 'in_review', 'escalated']
const RESOLVED = ['resolved', 'closed']

const counts = computed(() => ({
  all:      list.value.length,
  pending:  list.value.filter(r => PENDING.includes(r.status)).length,
  resolved: list.value.filter(r => RESOLVED.includes(r.status)).length,
  rejected: list.value.filter(r => r.status === 'rejected').length,
}))

const filtered = computed(() => {
  let res = list.value
  if (activeFilter.value === 'pending')  res = res.filter(r => PENDING.includes(r.status))
  if (activeFilter.value === 'resolved') res = res.filter(r => RESOLVED.includes(r.status))
  if (activeFilter.value === 'rejected') res = res.filter(r => r.status === 'rejected')
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    res = res.filter(r =>
      r.reference?.toLowerCase().includes(q) ||
      r.module?.name?.toLowerCase().includes(q) ||
      r.type?.toLowerCase().includes(q)
    )
  }
  return res.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const totalPages = computed(() => Math.ceil(filtered.value.length / PER_PAGE))
const paginated  = computed(() =>
  filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE)
)

const COLORS = {
  submitted: '#2563EB', in_review: '#D97706',
  escalated: '#EA580C', resolved:  '#16A34A',
  rejected:  '#DC2626', closed:    '#9CA3AF',
}
const LABELS = {
  submitted: 'Soumise',   in_review: 'En cours',
  escalated: 'Escaladée', resolved:  'Résolue',
  rejected:  'Rejetée',   closed:    'Fermée',
}
const sColor    = s => COLORS[s] ?? '#9CA3AF'
const sLabel    = s => LABELS[s] ?? s ?? '—'
const chipStyle = s => ({
  background: sColor(s) + '18', color: sColor(s),
  border: `1px solid ${sColor(s)}30`,
  borderRadius: '20px', fontSize: '11px',
  padding: '2px 8px', fontWeight: '600',
})
const fDate = d => d
  ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
  : '—'

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
.filters { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 20px; border: 1px solid #E5E7EB;
  background: #fff; font-size: 13px; font-weight: 500; color: #6B7280;
  cursor: pointer; transition: all .15s;
}
.filter-btn:hover   { border-color: #0F2D5E; color: #0F2D5E; }
.filter-btn.active  { background: #0F2D5E; color: #fff; border-color: #0F2D5E; }
.filter-count {
  background: rgba(0,0,0,0.08); border-radius: 10px;
  font-size: 11px; padding: 0 6px; line-height: 1.6;
}
.filter-btn.active .filter-count { background: rgba(255,255,255,0.2); }

.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; }

.empty {
  display: flex; flex-direction: column;
  align-items: center; gap: 8px;
  padding: 40px; color: #9CA3AF; font-size: 13px;
}

.tbl-head {
  display: grid;
  grid-template-columns: 140px 1fr 100px 110px 40px;
  padding: 10px 18px;
  background: #F9FAFB;
  border-bottom: 1px solid #E5E7EB;
  font-size: 11px; font-weight: 600;
  color: #9CA3AF; text-transform: uppercase; letter-spacing: .5px;
}

.tbl-row {
  display: grid;
  grid-template-columns: 140px 1fr 100px 110px 40px;
  padding: 13px 18px;
  border-bottom: 1px solid #F3F4F6;
  align-items: center;
  cursor: pointer;
  transition: background .12s;
  font-size: 13px; color: #374151;
}
.tbl-row:last-child { border-bottom: none; }
.tbl-row:hover      { background: #F8FAFC; }

.ref-code {
  font-family: monospace; font-size: 12px;
  font-weight: 600; color: #0F2D5E;
}
</style>
