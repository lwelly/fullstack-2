<template>
  <div class="dash">

    <!-- En-tête -->
    <div class="d-flex align-center justify-space-between mb-5">
      <div>
        <h1 class="dash-title">Bonjour, {{ firstName }} 👋</h1>
        <p class="dash-sub text-capitalize">{{ today }}</p>
      </div>
      <v-btn
        color="#3B82F6"
        variant="flat"
        size="small"
        prepend-icon="mdi-plus"
        rounded="lg"
        :to="{ name: 'student.reclamations.new' }"
      >
        Nouvelle réclamation
      </v-btn>
    </div>

    <!-- KPI -->
    <v-row class="mb-5">
      <v-col v-for="k in kpis" :key="k.label" cols="6" md="3">
        <div class="kpi-card">
          <div class="kpi-icon" :style="{ background: k.light }">
            <v-icon :color="k.color" size="20">{{ k.icon }}</v-icon>
          </div>
          <div class="kpi-val">{{ loading ? '—' : k.value }}</div>
          <div class="kpi-lbl">{{ k.label }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Graphiques principaux -->
    <v-row class="mb-5">

      <!-- Graphique barre / courbe -->
      <v-col cols="12" md="8">
        <div class="dark-card">
          <div class="dark-card-head">
            <div>
              <p class="dark-card-title">Évolution des réclamations</p>
              <p class="dark-card-sub">Activité sur les 6 derniers mois</p>
            </div>
            <div class="toggle-group">
              <button
                class="toggle-btn"
                :class="{ active: chartType === 'bar' }"
                @click="switchChart('bar')"
              >Barres</button>
              <button
                class="toggle-btn"
                :class="{ active: chartType === 'line' }"
                @click="switchChart('line')"
              >Courbe</button>
            </div>
          </div>
          <div class="chart-area">
            <canvas ref="mainRef" aria-label="Évolution mensuelle des réclamations"></canvas>
          </div>
        </div>
      </v-col>

      <!-- Doughnut répartition -->
      <v-col cols="12" md="4">
        <div class="dark-card">
          <div class="dark-card-head">
            <div>
              <p class="dark-card-title">Répartition</p>
              <p class="dark-card-sub">Par statut</p>
            </div>
          </div>
          <div style="padding:20px 16px 16px">
            <!-- Doughnut avec total centré -->
            <div class="doughnut-wrap">
              <canvas ref="doughnutRef" aria-label="Répartition par statut"></canvas>
              <div class="doughnut-center">
                <span class="doughnut-num">{{ list.length }}</span>
                <span class="doughnut-lbl">Total</span>
              </div>
            </div>
            <!-- Légende -->
            <div class="dleg-list">
              <div v-for="s in statusLegend" :key="s.key" class="dleg-row">
                <span class="dleg-dot" :style="{ background: s.color }"></span>
                <span class="dleg-label">{{ s.label }}</span>
                <span class="dleg-count">{{ s.count }}</span>
                <span class="dleg-pct">{{ s.pct }}%</span>
              </div>
            </div>
          </div>
        </div>
      </v-col>

    </v-row>

    <!-- Réclamations récentes -->
    <div class="dark-card">
      <div class="dark-card-head">
        <p class="dark-card-title">Réclamations récentes</p>
        <router-link :to="{ name: 'student.reclamations' }" class="see-all">
          Voir tout <v-icon size="13">mdi-arrow-right</v-icon>
        </router-link>
      </div>

      <div v-if="loading" class="py-8 text-center">
        <v-progress-circular indeterminate color="#3B82F6" size="28" />
      </div>

      <div v-else-if="recent.length === 0" class="empty">
        <v-icon size="40" color="#4B5563">mdi-inbox-outline</v-icon>
        <p style="color:#6B7280;font-size:13px">Aucune réclamation pour le moment</p>
        <v-btn size="small" variant="tonal" color="#3B82F6" :to="{ name: 'student.reclamations.new' }">
          Créer une réclamation
        </v-btn>
      </div>

      <div v-else>
        <div
          v-for="r in recent"
          :key="r.id"
          class="recl-row"
          @click="$router.push({ name: 'student.reclamation.detail', params: { id: r.id } })"
        >
          <div class="recl-dot" :style="{ background: sColor(r.status) }" />
          <div class="recl-info">
            <span class="recl-ref">{{ r.reference ?? `#${r.id}` }}</span>
            <span class="recl-sub">{{ r.module?.name ?? r.type ?? '—' }}</span>
          </div>
          <div class="recl-right">
            <span class="chip" :style="chipStyle(r.status)">{{ sLabel(r.status) }}</span>
            <span class="recl-date">{{ fDate(r.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

Chart.register(...registerables)

const authStore   = useAuthStore()
const loading     = ref(true)
const list        = ref([])
const mainRef     = ref(null)
const doughnutRef = ref(null)
const chartType   = ref('bar')   // 'bar' | 'line'
let mainChart, doughnutChart

// ─── Identité ─────────────────────────────────────────────
const firstName = computed(() =>
  (authStore.user?.name ?? 'Étudiant').split(' ')[0]
)
const today = computed(() =>
  new Date().toLocaleDateString('fr-FR', {
    weekday:'long', day:'numeric', month:'long', year:'numeric',
  })
)

// ─── Stats ────────────────────────────────────────────────
const stats = computed(() => ({
  total:    list.value.length,
  pending:  list.value.filter(r => ['submitted','in_review','escalated'].includes(r.status)).length,
  resolved: list.value.filter(r => r.status === 'resolved').length,
  rejected: list.value.filter(r => r.status === 'rejected').length,
}))

const kpis = computed(() => [
  { label:'Total',      value: stats.value.total,    icon:'mdi-clipboard-list-outline', color:'#60A5FA', light:'rgba(59,130,246,0.15)' },
  { label:'En attente', value: stats.value.pending,  icon:'mdi-clock-outline',           color:'#FBBF24', light:'rgba(251,191,36,0.15)'  },
  { label:'Résolues',   value: stats.value.resolved, icon:'mdi-check-circle-outline',    color:'#34D399', light:'rgba(52,211,153,0.15)'  },
  { label:'Rejetées',   value: stats.value.rejected, icon:'mdi-close-circle-outline',    color:'#F87171', light:'rgba(248,113,113,0.15)' },
])

// ─── Couleurs & libellés ──────────────────────────────────
const COLORS = {
  submitted: '#60A5FA', in_review: '#FBBF24',
  escalated: '#FB923C', resolved:  '#34D399',
  rejected:  '#F87171', closed:    '#6B7280',
}
const LABELS = {
  submitted: 'Soumise',   in_review: 'En cours',
  escalated: 'Escaladée', resolved:  'Résolue',
  rejected:  'Rejetée',   closed:    'Fermée',
}
const sColor    = s => COLORS[s] ?? '#6B7280'
const sLabel    = s => LABELS[s] ?? s ?? '—'
const chipStyle = s => ({
  background: sColor(s) + '22', color: sColor(s),
  border: `1px solid ${sColor(s)}44`,
  borderRadius: '20px', fontSize: '11px',
  padding: '2px 8px', fontWeight: '600', whiteSpace: 'nowrap',
})
const fDate = d => d
  ? new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short' })
  : '—'

// ─── Légende doughnut ─────────────────────────────────────
const statusLegend = computed(() => {
  const total = list.value.length || 1
  return [
    { key: 'in_review', label: 'En attente', color: '#FBBF24' },
    { key: 'resolved',  label: 'Résolues',   color: '#34D399' },
    { key: 'rejected',  label: 'Rejetées',   color: '#F87171' },
    { key: 'submitted', label: 'Soumises',   color: '#60A5FA' },
    { key: 'escalated', label: 'Escaladées', color: '#FB923C' },
  ]
    .map(s => ({
      ...s,
      count: list.value.filter(r => r.status === s.key).length,
      pct: Math.round((list.value.filter(r => r.status === s.key).length / total) * 100),
    }))
    .filter(s => s.count > 0)
})

// ─── Récents ──────────────────────────────────────────────
const recent = computed(() =>
  [...list.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 6)
)

// ─── Agrégation mensuelle (6 mois) ────────────────────────
function buildMonthlyData(items) {
  const map = {}
  // Initialiser les 6 derniers mois
  for (let i = 5; i >= 0; i--) {
    const d   = new Date()
    d.setDate(1)
    d.setMonth(d.getMonth() - i)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    map[key] = 0
  }
  items.forEach(r => {
    if (!r.created_at) return
    const d   = new Date(r.created_at)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    if (map[key] !== undefined) map[key]++
  })
  const sorted = Object.keys(map).sort()
  const labels = sorted.map(k => {
    const [y, m] = k.split('-')
    return new Date(Number(y), Number(m) - 1)
      .toLocaleDateString('fr-FR', { month: 'short' })
      .replace('.', '')
      .replace(/^\w/, c => c.toUpperCase())
  })
  return { labels, data: sorted.map(k => map[k]) }
}

// ─── Rendu graphique principal ────────────────────────────
function renderMain() {
  const { labels, data } = buildMonthlyData(list.value)
  if (mainChart) mainChart.destroy()

  const isBar = chartType.value === 'bar'

  mainChart = new Chart(mainRef.value, {
    type: isBar ? 'bar' : 'line',
    data: {
      labels,
      datasets: [{
        label: 'réclamation(s)',
        data,
        backgroundColor: isBar ? '#3B82F6' : 'rgba(59,130,246,0.12)',
        borderColor: '#3B82F6',
        borderRadius: isBar ? 6 : 0,
        fill: !isBar,
        tension: 0.4,
        pointRadius: isBar ? 0 : 5,
        pointBackgroundColor: '#3B82F6',
        borderWidth: isBar ? 0 : 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#fff',
          titleColor: '#6B7280',
          bodyColor: '#111827',
          borderColor: '#E5E7EB',
          borderWidth: 1,
          padding: 10,
          callbacks: { label: ctx => ` ${ctx.parsed.y} réclamation(s)` },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { color: '#9CA3AF', font: { size: 12 } },
        },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { color: '#9CA3AF', font: { size: 12 }, stepSize: 1 },
          beginAtZero: true,
        },
      },
    },
  })
}

// ─── Rendu doughnut ───────────────────────────────────────
function renderDoughnut() {
  const segments = statusLegend.value
  if (!segments.length) return
  if (doughnutChart) doughnutChart.destroy()
  doughnutChart = new Chart(doughnutRef.value, {
    type: 'doughnut',
    data: {
      labels: segments.map(s => s.label),
      datasets: [{
        data: segments.map(s => s.count),
        backgroundColor: segments.map(s => s.color),
        borderWidth: 3,
        borderColor: '#ffffff',
        hoverOffset: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#fff',
          titleColor: '#6B7280',
          bodyColor: '#111827',
          borderColor: '#E5E7EB',
          borderWidth: 1,
          callbacks: { label: ctx => ` ${ctx.label} : ${ctx.parsed}` },
        },
      },
    },
  })
}

function switchChart(type) {
  chartType.value = type
  renderMain()
}

// ─── Chargement ───────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const res = await api.get('/student/reclamations')
    list.value = res.data?.data ?? []
  } catch {
    list.value = []
  } finally {
    loading.value = false
    await nextTick()
    renderMain()
    renderDoughnut()
  }
}

onMounted(load)
</script>

<style scoped>
.dash       { max-width: 1200px; }
.dash-title { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
.dash-sub   { font-size: 13px; color: #6B7280; margin: 4px 0 0; }

/* KPI */
.kpi-card {
  background: #fff;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 16px;
  transition: box-shadow .2s;
}
.kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.07); }
.kpi-icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 10px;
}
.kpi-val { font-size: 26px; font-weight: 700; color: #111827; line-height: 1; }
.kpi-lbl { font-size: 12px; color: #6B7280; margin-top: 3px; }

/* Card (anciennement dark-card) */
.dark-card {
  background: #fff;
  border: 1px solid #E5E7EB;
  border-radius: 16px;
  overflow: hidden;
  height: 100%;
}
.dark-card-head {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 16px 20px 14px;
  border-bottom: 1px solid #F3F4F6;
}
.dark-card-title { font-size: 15px; font-weight: 600; color: #111827; margin: 0; }
.dark-card-sub   { font-size: 12px; color: #9CA3AF; margin: 3px 0 0; }

/* Toggle barres/courbe */
.toggle-group {
  display: flex; gap: 3px;
  background: #F3F4F6;
  border-radius: 8px;
  padding: 3px;
}
.toggle-btn {
  font-size: 12px; font-weight: 500;
  padding: 5px 14px; border-radius: 6px;
  border: none; cursor: pointer;
  color: #6B7280; background: transparent;
  transition: all .15s;
}
.toggle-btn.active {
  background: #0F2D5E;
  color: #fff;
  box-shadow: 0 1px 4px rgba(15,45,94,0.25);
}

/* Chart area */
.chart-area {
  position: relative; width: 100%;
  height: 320px;
  padding: 16px 20px;
  box-sizing: border-box;
}

/* Doughnut */
.doughnut-wrap {
  position: relative;
  height: 180px;
  max-width: 220px;
  margin: 0 auto 20px;
}
.doughnut-center {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}
.doughnut-num { display: block; font-size: 28px; font-weight: 700; color: #111827; line-height: 1; }
.doughnut-lbl { display: block; font-size: 12px; color: #9CA3AF; margin-top: 3px; }

/* Légende doughnut */
.dleg-list { display: flex; flex-direction: column; gap: 10px; }
.dleg-row {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px;
}
.dleg-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.dleg-label { flex: 1; color: #374151; }
.dleg-count { font-weight: 700; color: #111827; min-width: 16px; text-align: right; }
.dleg-pct   { font-size: 12px; color: #9CA3AF; min-width: 38px; text-align: right; }

/* Voir tout */
.see-all {
  font-size: 12px; color: #2563EB;
  text-decoration: none;
  display: flex; align-items: center; gap: 2px;
}

/* Liste récente */
.empty {
  display: flex; flex-direction: column;
  align-items: center; gap: 10px;
  padding: 36px; color: #9CA3AF; font-size: 13px;
}
.recl-row {
  display: flex; align-items: center; gap: 12px;
  padding: 13px 20px;
  border-bottom: 1px solid #F9FAFB;
  cursor: pointer;
  transition: background .12s;
}
.recl-row:last-child { border-bottom: none; }
.recl-row:hover { background: #F8FAFC; }
.recl-dot   { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.recl-info  { flex: 1; min-width: 0; }
.recl-ref   { display: block; font-size: 13px; font-weight: 600; color: #111827; }
.recl-sub   { display: block; font-size: 11px; color: #6B7280; margin-top: 2px; }
.recl-right { display: flex; flex-direction: column; align-items: flex-end; gap: 3px; }
.recl-date  { font-size: 11px; color: #9CA3AF; }
.chip       { display: inline-block; }

.mb-5 { margin-bottom: 20px; }
</style>