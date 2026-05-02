<template>
  <div class="dashboard-page">

    <!-- Header -->
    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">Tableau de bord</h1>
        <p class="page-sub">Vue d'ensemble des réclamations — {{ todayFr }}</p>
      </div>
      <v-btn color="#0F2D5E" size="small" rounded="lg" variant="flat"
        prepend-icon="mdi-refresh" :loading="loading" @click="load">
        Actualiser
      </v-btn>
    </div>

    <!-- ── KPI cards ── -->
    <v-row class="mb-2">
      <v-col v-for="kpi in kpiCards" :key="kpi.key" cols="12" sm="6" md="3">
        <div class="kpi-card" :style="{ borderTop: '3px solid ' + kpi.color }">
          <div class="kpi-icon-wrap" :style="{ background: kpi.color + '18' }">
            <v-icon :color="kpi.color" size="22">{{ kpi.icon }}</v-icon>
          </div>
          <div class="kpi-body">
            <div class="kpi-value">
              <span v-if="loading">—</span>
              <span v-else>{{ stats[kpi.key] ?? 0 }}</span>
            </div>
            <div class="kpi-label">{{ kpi.label }}</div>
          </div>
          <v-sparkline
            v-if="kpi.trend?.length"
            :model-value="kpi.trend"
            :color="kpi.color"
            smooth line-width="2"
            padding="4"
            height="36"
            class="kpi-sparkline"
          />
        </div>
      </v-col>
    </v-row>

    <!-- ── Status breakdown ── -->
    <v-row class="mb-2">
      <v-col cols="12" md="8">
        <div class="card h-100">
          <div class="card-head"><span class="card-title">Répartition par statut</span></div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-6">
              <v-progress-circular indeterminate color="#0F2D5E" size="24" />
            </div>
            <div v-else>
              <div v-for="s in statusBreakdown" :key="s.key" class="breakdown-row">
                <div class="d-flex align-center gap-2" style="min-width:130px">
                  <v-icon size="16" :color="s.color">{{ s.icon }}</v-icon>
                  <span class="breakdown-label">{{ s.label }}</span>
                </div>
                <div class="breakdown-bar-wrap">
                  <div class="breakdown-bar"
                    :style="{ width: barWidth(s.count) + '%', background: s.color }" />
                </div>
                <span class="breakdown-count">{{ s.count }}</span>
              </div>
              <div v-if="!statusBreakdown.some(s => s.count > 0)" class="text-center py-4" style="font-size:13px;color:#9CA3AF">
                Aucune réclamation pour le moment.
              </div>
            </div>
          </div>
        </div>
      </v-col>

      <v-col cols="12" md="4">
        <div class="card h-100">
          <div class="card-head"><span class="card-title">Répartition par type</span></div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-6">
              <v-progress-circular indeterminate color="#0F2D5E" size="24" />
            </div>
            <div v-else class="d-flex flex-column gap-3">
              <div v-for="t in typeBreakdown" :key="t.key" class="type-item">
                <div class="d-flex justify-space-between mb-1">
                  <span class="type-label">{{ t.label }}</span>
                  <span class="type-count">{{ t.count }}</span>
                </div>
                <v-progress-linear
                  :model-value="typePercent(t.count)"
                  :color="t.color"
                  height="6"
                  rounded
                  bg-color="#F3F4F6"
                />
              </div>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- ── Recent reclamations ── -->
    <v-row>
      <v-col cols="12">
        <div class="card">
          <div class="card-head">
            <span class="card-title">Réclamations récentes</span>
            <v-btn variant="text" size="x-small" color="#0F2D5E"
              :to="{ name: 'admin.reclamations' }">
              Voir toutes
              <v-icon end size="14">mdi-arrow-right</v-icon>
            </v-btn>
          </div>
          <div class="card-body pa-0">
            <div v-if="loading" class="text-center py-8">
              <v-progress-circular indeterminate color="#0F2D5E" size="24" />
            </div>
            <template v-else>
              <div v-if="!recent.length" class="empty-state">
                <v-icon size="32" color="#D1D5DB">mdi-inbox-outline</v-icon>
                <p>Aucune réclamation récente</p>
              </div>
              <div v-else>
                <div class="recent-header">
                  <span>Référence</span>
                  <span>Étudiant</span>
                  <span>Module</span>
                  <span>Type</span>
                  <span>Date</span>
                  <span>Statut</span>
                  <span></span>
                </div>
                <div v-for="r in recent" :key="r.id" class="recent-row">
                  <span class="ref-code">{{ r.reference ?? r.reference_number ?? `#${r.id}` }}</span>
                  <span>
                    <div class="d-flex align-center gap-2">
                      <v-avatar size="28" :color="avatarColor(sName(r))" style="flex-shrink:0">
                        <span style="font-size:10px;font-weight:700;color:white">{{ initials(sName(r)) }}</span>
                      </v-avatar>
                      <span style="font-size:13px;font-weight:500;color:#111827">{{ sName(r) }}</span>
                    </div>
                  </span>
                  <span style="font-size:13px;color:#374151">{{ r.module?.name ?? '—' }}</span>
                  <span>
                    <v-chip size="x-small" :color="typeColor(r.type)" variant="tonal">
                      {{ typeLabel(r.type) }}
                    </v-chip>
                  </span>
                  <span style="font-size:12px;color:#6B7280">{{ fDate(r.created_at) }}</span>
                  <span>
                    <span class="status-chip" :style="statusStyle(r.status)">{{ statusLabel(r.status) }}</span>
                  </span>
                  <span>
                    <v-btn size="x-small" icon="mdi-eye-outline" variant="text" color="#6B7280"
                      :to="{ name: 'admin.reclamation.detail', params: { id: r.id } }" />
                  </span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </v-col>
    </v-row>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ─── State ────────────────────────────────────────────────────
const loading = ref(false)
const stats   = ref({})
const recent  = ref([])

// ─── Maps ─────────────────────────────────────────────────────
const STATUS_MAP = {
  submitted: { label: 'Soumises',   color: '#2563EB', icon: 'mdi-send-outline'              },
  received:  { label: 'Reçues',     color: '#0369A1', icon: 'mdi-inbox-arrow-down-outline'  },
  in_review: { label: 'En cours',   color: '#7C3AED', icon: 'mdi-magnify'                   },
  escalated: { label: 'Escaladées', color: '#EA580C', icon: 'mdi-arrow-up-circle-outline'   },
  resolved:  { label: 'Résolues',   color: '#16A34A', icon: 'mdi-check-circle-outline'      },
  rejected:  { label: 'Rejetées',   color: '#DC2626', icon: 'mdi-close-circle-outline'      },
}

const TYPE_MAP = {
  cc:         { label: 'Contrôle Continu', color: '#2563EB' },
  controle:   { label: 'Contrôle Continu', color: '#2563EB' },
  examen:     { label: 'Examen',           color: '#7C3AED' },
  rattrapage: { label: 'Rattrapage',       color: '#EA580C' },
}

const AVATAR_COLORS = ['#0F2D5E','#2563EB','#16A34A','#D97706','#DC2626','#7C3AED']

// ─── KPI cards config ─────────────────────────────────────────
const kpiCards = [
  { key: 'total',       label: 'Total réclamations', icon: 'mdi-file-document-multiple-outline', color: '#0F2D5E', trend: [5,8,12,9,15,11,18] },
  { key: 'pending',     label: 'En attente',         icon: 'mdi-clock-outline',                  color: '#D97706', trend: [3,5,4,7,6,8,5]  },
  { key: 'in_progress', label: 'En cours',           icon: 'mdi-progress-clock',                 color: '#7C3AED', trend: [1,2,3,4,3,5,6]  },
  { key: 'resolved',    label: 'Résolues',           icon: 'mdi-check-circle-outline',           color: '#16A34A', trend: [0,1,2,1,3,4,5]  },
]

// ─── Computed breakdowns ──────────────────────────────────────
const statusBreakdown = computed(() =>
  Object.entries(STATUS_MAP).map(([key, { label, color, icon }]) => ({
    key, label, color, icon,
    count: stats.value?.by_status?.[key] ?? 0,
  }))
)

const typeBreakdown = computed(() => [
  { key: 'controle',   label: 'Contrôle Continu', color: '#2563EB', count: stats.value?.by_type?.controle   ?? stats.value?.by_type?.cc  ?? 0 },
  { key: 'examen',     label: 'Examen',            color: '#7C3AED', count: stats.value?.by_type?.examen     ?? 0 },
  { key: 'rattrapage', label: 'Rattrapage',        color: '#EA580C', count: stats.value?.by_type?.rattrapage ?? 0 },
])

const maxCount = computed(() => Math.max(1, ...statusBreakdown.value.map(s => s.count)))
const totalTypes = computed(() => typeBreakdown.value.reduce((a, t) => a + t.count, 0))

const barWidth     = (count) => Math.round((count / maxCount.value) * 100)
const typePercent  = (count) => totalTypes.value ? Math.round((count / totalTypes.value) * 100) : 0

// ─── Helpers ──────────────────────────────────────────────────
const todayFr = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

const statusLabel = (s) => STATUS_MAP[s]?.label ?? s ?? '—'
const statusColor = (s) => STATUS_MAP[s]?.color ?? '#9CA3AF'
const statusStyle = (s) => {
  const c = statusColor(s)
  return {
    background: c + '18', color: c,
    border: '1px solid ' + c + '30',
    borderRadius: '20px', fontSize: '11px',
    padding: '3px 10px', fontWeight: '600',
    display: 'inline-block', whiteSpace: 'nowrap',
  }
}

const typeLabel = (t) => !t ? '—' : (TYPE_MAP[t.toLowerCase()]?.label ?? t)
const typeColor = (t) => !t ? 'grey' : (TYPE_MAP[t.toLowerCase()]?.color ?? '#9CA3AF')

const avatarColor = (name) => AVATAR_COLORS[(name?.charCodeAt(0) ?? 0) % AVATAR_COLORS.length]
const initials = (name) => {
  if (!name || name === '—') return '?'
  return name.split(' ').filter(Boolean).map(n => n[0]?.toUpperCase() ?? '').slice(0, 2).join('')
}

const sName = (item) => {
  const s = item?.student
  if (!s) return '—'
  if (s.full_name) return s.full_name
  const parts = [s.prenom ?? s.first_name ?? '', s.nom ?? s.last_name ?? ''].filter(p => p.trim())
  if (parts.length) return parts.join(' ')
  return s.name || '—'
}

const fDate = (d) => {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

// ─── Data loading ─────────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    // Load stats and recent reclamations in parallel
    const [statsRes, recentRes] = await Promise.all([
      api.get('/admin/dashboard/stats').catch(() => ({ data: null })),
      api.get('/admin/reclamations', { params: { page: 1, per_page: 8, sort: 'created_at', order: 'desc' } }),
    ])

    // Parse stats — handle various response shapes
    const raw = statsRes.data?.data ?? statsRes.data ?? {}
    stats.value = {
      total:       raw.total       ?? raw.total_reclamations ?? 0,
      pending:     raw.pending     ?? (raw.by_status?.submitted ?? 0) + (raw.by_status?.received ?? 0),
      in_progress: raw.in_progress ?? raw.by_status?.in_review ?? 0,
      resolved:    raw.resolved    ?? raw.by_status?.resolved   ?? 0,
      by_status:   raw.by_status   ?? {},
      by_type:     raw.by_type     ?? {},
    }

    // If no stats endpoint exists, compute from recent list
    if (!statsRes.data) {
      const items = recentRes.data?.data?.data ?? recentRes.data?.data ?? []
      const bySt  = {}
      const byTy  = {}
      items.forEach(r => {
        bySt[r.status] = (bySt[r.status] ?? 0) + 1
        byTy[r.type]   = (byTy[r.type]   ?? 0) + 1
      })
      stats.value = {
        total:       items.length,
        pending:     (bySt.submitted ?? 0) + (bySt.received ?? 0),
        in_progress: bySt.in_review ?? 0,
        resolved:    bySt.resolved  ?? 0,
        by_status:   bySt,
        by_type:     byTy,
      }
    }

    recent.value = recentRes.data?.data?.data ?? recentRes.data?.data ?? []
  } catch (err) {
    console.error('[Dashboard] load error:', err)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.dashboard-page { max-width: 1300px; }
.page-header { display: flex; align-items: center; justify-content: space-between; }
.page-title { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
.page-sub { font-size: 13px; color: #6B7280; margin: 2px 0 0; text-transform: capitalize; }

/* KPI Cards */
.kpi-card {
  background: #fff;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  position: relative;
  overflow: hidden;
  min-height: 90px;
}
.kpi-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.kpi-body { flex: 1; }
.kpi-value { font-size: 28px; font-weight: 700; color: #111827; line-height: 1; }
.kpi-label { font-size: 12px; color: #6B7280; margin-top: 4px; font-weight: 500; }
.kpi-sparkline {
  position: absolute;
  bottom: 0; right: 0;
  width: 100px;
  opacity: .45;
}

/* General card */
.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; }
.card-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid #F3F4F6; }
.card-title { font-size: 14px; font-weight: 600; color: #111827; }
.card-body { padding: 16px 18px; }
.h-100 { height: 100%; }

/* Status breakdown */
.breakdown-row {
  display: flex; align-items: center; gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #F9FAFB;
}
.breakdown-row:last-child { border-bottom: none; }
.breakdown-label { font-size: 13px; font-weight: 500; color: #374151; }
.breakdown-bar-wrap { flex: 1; height: 8px; background: #F3F4F6; border-radius: 4px; overflow: hidden; }
.breakdown-bar { height: 100%; border-radius: 4px; transition: width .5s ease; min-width: 4px; }
.breakdown-count { font-size: 13px; font-weight: 600; color: #111827; min-width: 28px; text-align: right; }

/* Type breakdown */
.type-item { }
.type-label { font-size: 13px; font-weight: 500; color: #374151; }
.type-count { font-size: 13px; font-weight: 600; color: #111827; }

/* Recent table */
.recent-header,
.recent-row {
  display: grid;
  grid-template-columns: 150px 1fr 1fr 120px 110px 120px 44px;
  align-items: center;
  gap: 12px;
  padding: 10px 18px;
}
.recent-header {
  background: #F9FAFB;
  font-size: 11px; font-weight: 600;
  color: #9CA3AF;
  text-transform: uppercase;
  letter-spacing: .4px;
  border-bottom: 1px solid #F3F4F6;
}
.recent-row {
  border-bottom: 1px solid #F9FAFB;
  font-size: 13px;
  transition: background .12s;
}
.recent-row:hover { background: #F9FAFB; }
.recent-row:last-child { border-bottom: none; }
.ref-code { font-family: monospace; font-size: 12px; font-weight: 700; color: #0F2D5E; }
.status-chip { display: inline-block; white-space: nowrap; }

/* Empty state */
.empty-state { text-align: center; padding: 40px; color: #9CA3AF; }
.empty-state p { font-size: 14px; margin-top: 8px; }
</style>
