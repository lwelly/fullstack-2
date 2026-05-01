<template>
  <div>

    <!-- En-tête -->
    <div class="d-flex align-center justify-space-between mb-5">
      <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0">Tableau de bord</h1>
        <p style="font-size:13px;color:#6B7280;margin:4px 0 0" class="text-capitalize">{{ today }}</p>
      </div>
    </div>

    <!-- KPI -->
    <v-row class="mb-5">
      <v-col v-for="k in kpis" :key="k.label" cols="6" md="3">
        <div class="kpi">
          <div class="kpi-top" :style="{ background: k.light }">
            <v-icon :color="k.color" size="22">{{ k.icon }}</v-icon>
          </div>
          <div class="kpi-val">{{ loading ? '—' : k.value }}</div>
          <div class="kpi-lbl">{{ k.label }}</div>
        </div>
      </v-col>
    </v-row>

    <!-- Réclamations récentes -->
    <div class="card">
      <div class="card-head">
        <span style="font-size:14px;font-weight:600;color:#111827">Réclamations récentes</span>
        <router-link :to="{ name: 'admin.reclamations' }" class="see-all">
          Voir tout <v-icon size="13">mdi-arrow-right</v-icon>
        </router-link>
      </div>

      <div v-if="loading" class="py-8 text-center">
        <v-progress-circular indeterminate color="#0F2D5E" size="28" />
      </div>

      <div v-else-if="recent.length === 0" class="empty">
        <v-icon size="40" color="#D1D5DB">mdi-inbox-outline</v-icon>
        <p>Aucune réclamation</p>
      </div>

      <div v-else>
        <div class="tbl-head">
          <span>Référence</span>
          <span>Étudiant</span>
          <span>Type</span>
          <span>Date</span>
          <span>Statut</span>
          <span></span>
        </div>
        <div
          v-for="r in recent" :key="r.id"
          class="tbl-row"
          @click="$router.push({ name: 'admin.reclamation.detail', params: { id: r.id } })"
        >
          <span class="ref-code">{{ r.reference ?? `#${r.id}` }}</span>
          <span class="text-truncate" style="font-size:13px;color:#374151">
            {{ r.student?.full_name ?? r.user?.name ?? '—' }}
          </span>
          <span style="font-size:13px;color:#374151">{{ typeLabel(r.type) }}</span>
          <span style="font-size:12px;color:#9CA3AF">{{ fDate(r.created_at) }}</span>
          <span>
            <span class="chip" :style="chipStyle(r.status)">{{ sLabel(r.status) }}</span>
          </span>
          <span>
            <v-icon size="16" color="#9CA3AF">mdi-chevron-right</v-icon>
          </span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

const loading = ref(true)
const list    = ref([])

const today = computed(() =>
  new Date().toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' })
)

const stats = computed(() => ({
  total:      list.value.length,
  pending:    list.value.filter(r => ['submitted','in_review'].includes(r.status)).length,
  resolved:   list.value.filter(r => r.status === 'resolved').length,
  escalated:  list.value.filter(r => r.status === 'escalated').length,
}))

const kpis = computed(() => [
  { label:'Total',      value: stats.value.total,     icon:'mdi-clipboard-list-outline', color:'#2563EB', light:'#DBEAFE' },
  { label:'En attente', value: stats.value.pending,   icon:'mdi-clock-outline',           color:'#D97706', light:'#FEF3C7' },
  { label:'Résolues',   value: stats.value.resolved,  icon:'mdi-check-circle-outline',    color:'#16A34A', light:'#DCFCE7' },
  { label:'Escaladées', value: stats.value.escalated, icon:'mdi-alert-circle-outline',    color:'#EA580C', light:'#FFEDD5' },
])

const recent = computed(() =>
  [...list.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 8)
)

const COLORS = { submitted:'#2563EB', in_review:'#D97706', escalated:'#EA580C', resolved:'#16A34A', rejected:'#DC2626', closed:'#9CA3AF' }
const LABELS = { submitted:'Soumise', in_review:'En cours', escalated:'Escaladée', resolved:'Résolue', rejected:'Rejetée', closed:'Fermée' }
const sColor    = s => COLORS[s] ?? '#9CA3AF'
const sLabel    = s => LABELS[s] ?? s ?? '—'
const typeLabel = t => ({ cc:'CC', examen:'Examen', rattrapage:'Rattrapage' }[t] ?? t ?? '—')
const chipStyle = s => ({
  background: sColor(s) + '18', color: sColor(s),
  border: `1px solid ${sColor(s)}30`,
  borderRadius:'20px', fontSize:'11px', padding:'2px 8px', fontWeight:'600',
})
const fDate = d => d
  ? new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' })
  : '—'

onMounted(async () => {
  try {
    const res = await api.get('/admin/reclamations')
    list.value = res.data?.data ?? []
  } catch { list.value = [] }
  finally { loading.value = false }
})
</script>

<style scoped>
.kpi { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px; transition:box-shadow .2s; }
.kpi:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.kpi-top { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
.kpi-val { font-size:26px; font-weight:700; color:#111827; line-height:1; }
.kpi-lbl { font-size:12px; color:#6B7280; margin-top:3px; }

.card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; }
.card-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #F3F4F6; }
.see-all { font-size:12px; color:#2563EB; text-decoration:none; display:flex; align-items:center; gap:2px; }
.empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:32px; color:#9CA3AF; font-size:13px; }

.tbl-head {
  display:grid; grid-template-columns:130px 1fr 100px 110px 110px 40px;
  padding:10px 18px; background:#F9FAFB; border-bottom:1px solid #E5E7EB;
  font-size:11px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:.5px;
}
.tbl-row {
  display:grid; grid-template-columns:130px 1fr 100px 110px 110px 40px;
  padding:13px 18px; border-bottom:1px solid #F3F4F6;
  align-items:center; cursor:pointer; transition:background .12s;
}
.tbl-row:last-child { border-bottom:none; }
.tbl-row:hover { background:#F8FAFC; }
.ref-code { font-family:monospace; font-size:12px; font-weight:600; color:#0F2D5E; }
</style>
