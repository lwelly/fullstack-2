<template>
  <div class="dashboard-page">

    <!-- ── En-tête ──────────────────────────────────────────── -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Tableau de bord</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ todayFr() }} — Vue d'ensemble des réclamations
        </p>
      </div>
      
    </div>

    <!-- ── KPI Cards ─────────────────────────────────────────── -->
    <v-row class="mb-6" dense>
      <v-col
        v-for="kpi in kpiCards"
        :key="kpi.key"
        cols="12" sm="6" md="3"
      >
        <v-card
          elevation="2"
          rounded="xl"
          class="kpi-card pa-5"
          :style="`border-left: 4px solid ${kpi.color}`"
        >
          <div class="d-flex align-center justify-space-between mb-3">
            <span class="text-body-2 text-medium-emphasis font-weight-medium">
              {{ kpi.label }}
            </span>
            <v-avatar :color="kpi.color + '-lighten-4'" size="40">
              <v-icon :color="kpi.color" size="20">{{ kpi.icon }}</v-icon>
            </v-avatar>
          </div>
          <div class="text-h4 font-weight-bold mb-1" :style="`color:${kpi.color}`">
            <template v-if="loading">—</template>
            <template v-else>{{ stats[kpi.key] ?? 0 }}</template>
          </div>
          <div class="text-caption text-medium-emphasis">{{ kpi.sub }}</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ── Graphiques principaux ─────────────────────────────── -->
    <v-row class="mb-6">

      <!-- Évolution barres / courbe -->
      <v-col cols="12" md="8">
        <v-card elevation="2" rounded="xl" class="pa-5">
          <div class="d-flex align-center justify-space-between mb-4 flex-wrap gap-2">
            <div>
              <div class="text-subtitle-1 font-weight-bold">
                Évolution des réclamations
              </div>
              <div class="text-caption text-medium-emphasis">
                Activité sur les 6 derniers mois
              </div>
            </div>
            <v-btn-group density="compact" rounded="lg" variant="outlined" color="primary">
              <v-btn
                size="small"
                :variant="chartType === 'bar' ? 'elevated' : 'outlined'"
                @click="switchChart('bar')"
              >Barres</v-btn>
              <v-btn
                size="small"
                :variant="chartType === 'line' ? 'elevated' : 'outlined'"
                @click="switchChart('line')"
              >Courbe</v-btn>
            </v-btn-group>
          </div>

          <!-- Skeleton pendant le chargement -->
          <div v-if="loading" class="chart-skeleton d-flex align-end gap-2 px-4">
            <div
              v-for="i in 6" :key="i"
              class="skeleton-bar"
              :style="`height:${[60,120,80,160,100,180][i-1]}px`"
            />
          </div>

          <!-- Graphique ApexCharts -->
          <apexchart
            v-else
            :key="'evo-' + chartType"
            :type="chartType"
            height="280"
            :options="evolutionOptions"
            :series="evolutionSeries"
          />
        </v-card>
      </v-col>

      <!-- Donut répartition statut -->
      <v-col cols="12" md="4">
        <v-card elevation="2" rounded="xl" class="pa-5">
          <div class="text-subtitle-1 font-weight-bold mb-1">Répartition</div>
          <div class="text-caption text-medium-emphasis mb-3">Par statut</div>

          <div v-if="loading" class="d-flex justify-center py-8">
            <v-progress-circular indeterminate color="primary" size="80" />
          </div>

          <template v-else>
            <apexchart
              :key="'donut-' + donutSeries.join('-')"
              type="donut"
              height="200"
              :options="donutOptions"
              :series="donutSeries"
            />

            <!-- Légende personnalisée -->
            <v-divider class="my-3" />
            <div
              v-for="item in donutLegend"
              :key="item.label"
              class="d-flex align-center justify-space-between py-1"
            >
              <div class="d-flex align-center gap-2">
                <span
                  class="legend-dot"
                  :style="`background:${item.color}`"
                />
                <span class="text-body-2">{{ item.label }}</span>
              </div>
              <div class="d-flex align-center gap-3">
                <span class="text-body-2 font-weight-bold">{{ item.count }}</span>
                <v-chip size="x-small" :color="item.color" variant="tonal">
                  {{ item.pct }}%
                </v-chip>
              </div>
            </div>
          </template>
        </v-card>
      </v-col>
    </v-row>

    <!-- ── Ligne du bas ───────────────────────────────────────── -->
    <v-row>

      <!-- Répartition par type -->
      <v-col cols="12" md="5">
        <v-card elevation="2" rounded="xl" class="pa-5 h-100">
          <div class="text-subtitle-1 font-weight-bold mb-1">Répartition par type</div>
          <div class="text-caption text-medium-emphasis mb-4">
            Devoir · Examen · Rattrapage
          </div>

          <div v-if="loading">
            <v-skeleton-loader type="list-item-two-line" v-for="i in 3" :key="i" />
          </div>

          <template v-else-if="typeBreakdown.length">
            <div
              v-for="item in typeBreakdown"
              :key="item.key"
              class="mb-5"
            >
              <div class="d-flex justify-space-between align-center mb-1">
                <div class="d-flex align-center gap-2">
                  <v-icon size="16" :color="item.color">{{ item.icon }}</v-icon>
                  <span class="text-body-2 font-weight-medium">{{ item.label }}</span>
                </div>
                <span class="text-body-2">
                  <strong>{{ item.count }}</strong>
                  <span class="text-caption text-medium-emphasis ml-1">({{ item.pct }}%)</span>
                </span>
              </div>
              <v-progress-linear
                :model-value="item.pct"
                :color="item.color"
                height="8"
                rounded
                bg-color="grey-lighten-3"
              />
            </div>
          </template>

          <div v-else class="text-center text-medium-emphasis py-8">
            <v-icon size="40" class="mb-2 opacity-40">mdi-chart-bar</v-icon>
            <p class="text-body-2">Aucune donnée disponible</p>
          </div>
        </v-card>
      </v-col>

      <!-- Réclamations récentes -->
      <v-col cols="12" md="7">
        <v-card elevation="2" rounded="xl" class="h-100">
          <div class="d-flex align-center justify-space-between pa-5 pb-3">
            <div>
              <div class="text-subtitle-1 font-weight-bold">
                Réclamations récentes
              </div>
              <div class="text-caption text-medium-emphasis">
                Les 5 dernières soumissions
              </div>
            </div>
            <v-btn
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="mdi-arrow-right"
              to="/admin/reclamations"
            >
              Voir tout
            </v-btn>
          </div>
          <v-divider />

          <!-- Skeleton -->
          <div v-if="loading" class="pa-2">
            <v-skeleton-loader
              v-for="i in 5" :key="i"
              type="list-item-avatar-two-line"
            />
          </div>

          <!-- Liste -->
          <v-list v-else-if="recent.length" lines="two" class="py-0">
            <template v-for="(r, i) in recent" :key="r.id">
              <v-list-item class="py-3 px-5">
                <template #prepend>
                  <v-avatar
                    :color="avatarColor(r.student_name)"
                    size="40"
                    class="mr-3 text-white"
                  >
                    <span class="text-caption font-weight-bold">
                      {{ initials(r.student_name) }}
                    </span>
                  </v-avatar>
                </template>

                <v-list-item-title class="text-body-2 font-weight-medium">
                  {{ r.reference_number || '—' }}
                  <span class="text-caption text-medium-emphasis ml-1">
                    · {{ r.student_name || '—' }}
                  </span>
                </v-list-item-title>
                <v-list-item-subtitle class="text-caption mt-1">
                  <v-icon size="12" class="mr-1">mdi-book-outline</v-icon>
                  {{ r.module_name || '—' }}
                  <span class="mx-1">·</span>
                  <v-icon size="12" class="mr-1">mdi-calendar</v-icon>
                  {{ fDate(r.created_at) }}
                </v-list-item-subtitle>

                <template #append>
                  <v-chip
                    size="x-small"
                    :color="statusColor(r.status)"
                    variant="tonal"
                    label
                  >
                    {{ statusLabel(r.status) }}
                  </v-chip>
                </template>
              </v-list-item>
              <v-divider v-if="i < recent.length - 1" inset />
            </template>
          </v-list>

          <!-- Vide -->
          <div v-else class="text-center py-12 text-medium-emphasis">
            <v-icon size="48" class="mb-3 opacity-30">mdi-inbox-outline</v-icon>
            <p class="text-body-2">Aucune réclamation récente</p>
          </div>
        </v-card>
      </v-col>
    </v-row>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── State ──────────────────────────────────────────────────
const loading   = ref(true)
const chartType = ref('bar')
const stats     = ref({
  total: 0, pending: 0, in_progress: 0,
  resolved: 0, rejected: 0,
  by_status: {}, by_type: {},
})
const recent    = ref([])
const evolution = ref([])

// ── KPI config ─────────────────────────────────────────────
const kpiCards = [
  {
    key:   'total',
    label: 'Total réclamations',
    icon:  'mdi-file-document-multiple-outline',
    color: '#1976D2',
    sub:   'Toutes périodes confondues',
  },
  {
    key:   'pending',
    label: 'En attente',
    icon:  'mdi-clock-alert-outline',
    color: '#FF9800',
    sub:   'Soumise ',
  },
  {
    key:   'in_progress',
    label: 'En traitement',
    icon:  'mdi-progress-clock',
    color: '#9C27B0',
    sub:   'En cours  + Escalede',
  },
  {
    key:   'resolved',
    label: 'Résolues',
    icon:  'mdi-check-circle-outline',
    color: '#4CAF50',
    sub:   'Clôturées avec succès',
  },
]

// ── Statut config ──────────────────────────────────────────
const STATUS = {
  submitted: { label: 'Soumise',   color: '#FF9800' },
  received:  { label: 'Reçue',     color: '#2196F3' },
  in_review: { label: 'En cours',  color: '#9C27B0' },
  escalated: { label: 'Escaladée', color: '#FF5722' },
  resolved:  { label: 'Résolue',   color: '#4CAF50' },
  rejected:  { label: 'Rejetée',   color: '#F44336' },
}

const TYPE_CONFIG = {
  controle:   { label: 'Devoir',  icon: 'mdi-pencil-outline',  color: '#1976D2' },
  examen:     { label: 'Examen final',      icon: 'mdi-school-outline',  color: '#7B1FA2' },
  rattrapage: { label: 'Rattrapage',        icon: 'mdi-refresh-circle',  color: '#F57C00' },
}

// ── Chart type switch ──────────────────────────────────────
function switchChart(type) {
  chartType.value = type
}

// ── ApexCharts : évolution ─────────────────────────────────
const evolutionSeries = computed(() => [{
  name: 'Réclamations',
  data: evolution.value.map(e => e.count),
}])

const evolutionOptions = computed(() => ({
  chart: {
    type:        chartType.value,
    toolbar:     { show: false },
    zoom:        { enabled: false },
    fontFamily:  'inherit',
    background:  'transparent',
    animations:  { enabled: true, speed: 600 },
  },
  colors:      ['#1976D2'],
  xaxis: {
    categories:  evolution.value.map(e => e.month),
    axisBorder:  { show: false },
    axisTicks:   { show: false },
    labels:      { style: { colors: '#888', fontSize: '12px' } },
  },
  yaxis: {
    min:         0,
    tickAmount:  4,
    labels: {
      formatter: v => Math.round(v),
      style:     { colors: '#888', fontSize: '12px' },
    },
  },
  grid: {
    borderColor:     '#f0f0f0',
    strokeDashArray: 4,
    xaxis:           { lines: { show: false } },
    padding:         { left: 0, right: 0 },
  },
  plotOptions: {
    bar: {
      borderRadius:  6,
      columnWidth:   '45%',
    },
  },
  stroke: {
    curve: 'smooth',
    width: chartType.value === 'line' ? 3 : 0,
  },
  fill: {
    type:     chartType.value === 'line' ? 'gradient' : 'solid',
    gradient: {
      shade:       'light',
      type:        'vertical',
      opacityFrom: 0.5,
      opacityTo:   0.05,
    },
  },
  markers: {
    size:        chartType.value === 'line' ? 5 : 0,
    strokeWidth: 2,
    colors:      ['#fff'],
    strokeColors:['#1976D2'],
  },
  dataLabels: { enabled: false },
  tooltip: {
    theme: 'light',
    y:     { formatter: v => `${v} réclamation(s)` },
  },
}))

// ── ApexCharts : donut ─────────────────────────────────────
const DONUT_KEYS = ['submitted','received','in_review','escalated','resolved','rejected']

const activeDonutKeys = computed(() =>
  DONUT_KEYS.filter(k => (stats.value.by_status?.[k] ?? 0) > 0)
)

const donutSeries = computed(() =>
  activeDonutKeys.value.map(k => Number(stats.value.by_status?.[k] ?? 0))
)

const donutOptions = computed(() => ({
  chart:  { type: 'donut', toolbar: { show: false }, fontFamily: 'inherit' },
  labels: activeDonutKeys.value.map(k => STATUS[k]?.label ?? k),
  colors: activeDonutKeys.value.map(k => STATUS[k]?.color ?? '#999'),
  legend: { show: false },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show:  true,
          total: {
            show:      true,
            label:     'Total',
            fontSize:  '13px',
            color:     '#666',
            formatter: () => String(stats.value.total ?? 0),
          },
        },
      },
    },
  },
  dataLabels: { enabled: false },
  stroke:     { width: 2, colors: ['#fff'] },
  tooltip:    { y: { formatter: v => `${v} réclamation(s)` } },
}))

// Légende groupée (comme le screenshot)
const donutLegend = computed(() => {
  const t  = stats.value.total || 1
  const bs = stats.value.by_status ?? {}
  return [
    {
      label: 'En attente',
      color: '#FF9800',
      count: (bs.submitted ?? 0) + (bs.received ?? 0),
    },
    {
      label: 'En cours',
      color: '#9C27B0',
      count: (bs.in_review ?? 0) + (bs.escalated ?? 0),
    },
    {
      label: 'Résolues',
      color: '#4CAF50',
      count: bs.resolved ?? 0,
    },
    {
      label: 'Rejetées',
      color: '#F44336',
      count: bs.rejected ?? 0,
    },
  ]
    .filter(g => g.count > 0)
    .map(g => ({
      ...g,
      pct: Math.round((g.count / t) * 100),
    }))
})

// ── Computed : types ───────────────────────────────────────
const typeBreakdown = computed(() => {
  const byType = stats.value.by_type ?? {}
  const t      = stats.value.total   || 1
  return Object.entries(TYPE_CONFIG)
    .map(([key, cfg]) => ({
      key,
      label: cfg.label,
      icon:  cfg.icon,
      color: cfg.color,
      count: byType[key] ?? 0,
      pct:   stats.value.total > 0
        ? Math.round(((byType[key] ?? 0) / t) * 100)
        : 0,
    }))
    .filter(i => i.count > 0)
})

// ── Helpers ────────────────────────────────────────────────
function todayFr() {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
}

function fDate(raw) {
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleDateString('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric',
    })
  } catch { return raw }
}

function statusLabel(s) { return STATUS[s]?.label ?? s }
function statusColor(s) { return STATUS[s]?.color  ?? 'grey' }

function initials(name) {
  if (!name) return '?'
  const parts = name.trim().split(' ').filter(Boolean)
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase()
}

function avatarColor(name) {
  const colors = ['indigo','teal','purple','blue','cyan','green','orange','red']
  if (!name) return 'indigo'
  return colors[name.charCodeAt(0) % colors.length]
}

// ── Bâtir les 6 derniers mois ──────────────────────────────
function buildEvolution(monthly = []) {
  const now    = new Date()
  const result = []
  for (let i = 5; i >= 0; i--) {
    const d     = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const key   = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    const label = d.toLocaleDateString('fr-FR', { month: 'short' })
    const found = monthly.find(m => m.month === key)
    result.push({
      month: label.charAt(0).toUpperCase() + label.slice(1, 3),
      count: Number(found?.count ?? 0),
    })
  }
  return result
}

// ── Chargement API ─────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const [statsRes, recentRes] = await Promise.all([
      api.get('/admin/dashboard/stats'),
      api.get('/admin/reclamations', { params: { per_page: 5, page: 1 } }),
    ])

    // Stats
    const d = statsRes.data?.data ?? {}
    stats.value = {
      total:       Number(d.total       ?? 0),
      pending:     Number(d.pending     ?? 0),
      in_progress: Number(d.in_progress ?? 0),
      resolved:    Number(d.resolved    ?? 0),
      rejected:    Number(d.rejected    ?? 0),
      by_status:   d.by_status ?? {},
      by_type:     d.by_type   ?? {},
    }

    // Évolution mensuelle
    evolution.value = buildEvolution(d.monthly ?? [])

    // Réclamations récentes
    const raw  = recentRes.data?.data ?? recentRes.data ?? {}
    const list = Array.isArray(raw)
      ? raw
      : Array.isArray(raw.data)
        ? raw.data
        : []

    recent.value = list.slice(0, 5).map(r => ({
      id:               r.id,
      reference_number: r.reference_number ?? null,
      status:           r.status           ?? 'submitted',
      created_at:       r.created_at       ?? null,
      student_name: r.student
        ? [r.student.first_name, r.student.last_name].filter(Boolean).join(' ')
        : (r.student_name ?? '—'),
      module_name: r.module?.name ?? r.module_name ?? '—',
    }))

  } catch (err) {
    console.error('[Dashboard] load error :', err)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.dashboard-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 16px;
}

/* KPI cards */
.kpi-card {
  transition: transform .2s ease, box-shadow .2s ease;
  cursor: default;
}
.kpi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 28px rgba(0,0,0,.10) !important;
}

/* Skeleton barres */
.chart-skeleton {
  height: 280px;
  align-items: flex-end;
  padding-bottom: 8px;
}
.skeleton-bar {
  flex: 1;
  border-radius: 6px 6px 0 0;
  background: linear-gradient(180deg, #e0e0e0 0%, #f5f5f5 100%);
  animation: pulse 1.4s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: .4; }
}

/* Légende donut */
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

/* Hauteur égale cartes bas de page */
.h-100 { height: 100%; }
</style>
