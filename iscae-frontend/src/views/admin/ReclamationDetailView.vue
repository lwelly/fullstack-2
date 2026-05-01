<template>
  <div>

    <!-- En-tête -->
    <div class="d-flex align-center gap-3 mb-5">
      <v-btn
        icon="mdi-arrow-left" variant="text" size="small"
        @click="$router.push({ name: 'admin.reclamations' })"
      />
      <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0">
          Détail Réclamation
        </h1>
        <p style="font-size:13px;color:#6B7280;margin:3px 0 0">
          {{ rec?.reference ?? `#${route.params.id}` }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-10 text-center">
      <v-progress-circular indeterminate color="#0F2D5E" size="32" />
    </div>

    <!-- Erreur -->
    <v-alert v-else-if="error" type="error" variant="tonal" rounded="lg">{{ error }}</v-alert>

    <!-- Contenu -->
    <template v-else-if="rec">
      <v-row>

        <!-- ── Colonne principale ── -->
        <v-col cols="12" md="8">

          <!-- Infos générales -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Informations générales</span>
              <span class="chip" :style="chipStyle(rec.status)">{{ sLabel(rec.status) }}</span>
            </div>
            <div class="card-body">
              <div class="info-grid">
                <div class="info-item">
                  <span class="info-key">Référence</span>
                  <span class="ref-code">{{ rec.reference ?? `#${rec.id}` }}</span>
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
                  <span class="info-val">{{ rec.semestre?.label ?? rec.semestre?.code ?? '—' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-key">Note obtenue</span>
                  <span class="info-val">{{ rec.note_obtenue ? `${rec.note_obtenue} / 20` : '—' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-key">Date soumission</span>
                  <span class="info-val">{{ fDateFull(rec.created_at) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-key">Dernière mise à jour</span>
                  <span class="info-val">{{ fDateFull(rec.updated_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Objet -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Objet de la réclamation</span>
            </div>
            <div class="card-body">
              <p style="font-size:14px;font-weight:600;color:#111827;margin:0">
                {{ rec.subject ?? '—' }}
              </p>
            </div>
          </div>

          <!-- Description -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Description détaillée</span>
            </div>
            <div class="card-body">
              <p style="font-size:14px;color:#374151;line-height:1.75;white-space:pre-wrap;margin:0">
                {{ rec.description ?? 'Aucune description fournie.' }}
              </p>
            </div>
          </div>

          <!-- Historique des statuts -->
          <div v-if="rec.history?.length > 0" class="card mb-4">
            <div class="card-head">
              <span class="card-title">Historique</span>
            </div>
            <div class="card-body pa-0">
              <div
                v-for="(h, i) in rec.history"
                :key="i"
                class="history-row"
              >
                <div class="history-dot" :style="{ background: sColor(h.status) }" />
                <div class="history-content">
                  <div class="d-flex align-center gap-2">
                    <span class="chip" :style="chipStyle(h.status)">{{ sLabel(h.status) }}</span>
                    <span style="font-size:12px;color:#9CA3AF">{{ fDateFull(h.created_at) }}</span>
                  </div>
                  <p v-if="h.note" style="font-size:13px;color:#6B7280;margin:4px 0 0">
                    {{ h.note }}
                  </p>
                  <p v-if="h.admin?.name" style="font-size:12px;color:#9CA3AF;margin:2px 0 0">
                    Par : {{ h.admin.name }}
                  </p>
                </div>
              </div>
            </div>
          </div>

        </v-col>

        <!-- ── Colonne droite ── -->
        <v-col cols="12" md="4">

          <!-- Étudiant -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Étudiant</span>
            </div>
            <div class="card-body">
              <div class="d-flex align-center gap-3 mb-4">
                <v-avatar size="48" :color="avatarColor(studentName)">
                  <v-img v-if="rec.student?.photo_url" :src="rec.student.photo_url" cover />
                  <span v-else style="font-size:14px;font-weight:700;color:white">
                    {{ initials(studentName) }}
                  </span>
                </v-avatar>
                <div>
                  <div style="font-size:14px;font-weight:600;color:#111827">{{ studentName }}</div>
                  <div style="font-size:12px;color:#6B7280">{{ rec.student?.matricule ?? '—' }}</div>
                </div>
              </div>
              <div class="detail-row">
                <span class="detail-key">Email</span>
                <span class="detail-val">{{ rec.student?.email ?? rec.user?.email ?? '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-key">Filière</span>
                <span class="detail-val">{{ rec.student?.filiere?.name ?? rec.student?.filiere ?? '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-key">Niveau</span>
                <span class="detail-val">{{ rec.student?.niveau?.code ?? rec.student?.niveau ?? '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-key">Téléphone</span>
                <span class="detail-val">{{ rec.student?.phone ?? '—' }}</span>
              </div>
            </div>
          </div>

          <!-- Changer statut -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Changer le statut</span>
            </div>
            <div class="card-body">
              <div class="field-label mb-1">Nouveau statut</div>
              <v-select
                v-model="newStatus"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                variant="outlined" density="compact" rounded="lg"
                hide-details class="mb-3"
              />
              <div class="field-label mb-1">Note interne (optionnel)</div>
              <v-textarea
                v-model="statusNote"
                placeholder="Commentaire sur la décision..."
                variant="outlined" rounded="lg" density="compact"
                hide-details rows="3" class="mb-3"
              />
              <v-btn
                color="#0F2D5E" variant="flat" size="small"
                rounded="lg" block :loading="updating"
                :disabled="!newStatus || newStatus === rec.status"
                @click="updateStatus"
              >
                <v-icon start size="16">mdi-check</v-icon>
                Mettre à jour
              </v-btn>
            </div>
          </div>

          <!-- Escalader -->
          <div class="card mb-4">
            <div class="card-head">
              <span class="card-title">Actions rapides</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
              <v-btn
                variant="outlined" size="small" rounded="lg" block
                color="#EA580C" :loading="escalating"
                :disabled="rec.status === 'escalated' || rec.status === 'resolved' || rec.status === 'rejected'"
                @click="escalate"
              >
                <v-icon start size="16">mdi-alert-circle-outline</v-icon>
                Escalader
              </v-btn>
              <v-btn
                variant="outlined" size="small" rounded="lg" block
                color="#16A34A" :loading="resolving"
                :disabled="rec.status === 'resolved'"
                @click="resolve"
              >
                <v-icon start size="16">mdi-check-circle-outline</v-icon>
                Marquer résolue
              </v-btn>
              <v-btn
                variant="outlined" size="small" rounded="lg" block
                color="#DC2626" :loading="rejecting"
                :disabled="rec.status === 'rejected'"
                @click="reject"
              >
                <v-icon start size="16">mdi-close-circle-outline</v-icon>
                Rejeter
              </v-btn>
            </div>
          </div>

        </v-col>
      </v-row>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute }   from 'vue-router'
import { useToast }   from 'vue-toastification'
import api from '@/api/axios'

const route = useRoute()
const toast = useToast()

const loading   = ref(true)
const updating  = ref(false)
const escalating = ref(false)
const resolving  = ref(false)
const rejecting  = ref(false)
const error     = ref('')
const rec       = ref(null)
const newStatus = ref('')
const statusNote = ref('')

const statusOptions = [
  { value: 'submitted',  label: 'Soumise' },
  { value: 'in_review',  label: 'En cours de traitement' },
  { value: 'escalated',  label: 'Escaladée' },
  { value: 'resolved',   label: 'Résolue' },
  { value: 'rejected',   label: 'Rejetée' },
  { value: 'closed',     label: 'Fermée' },
]

const studentName = computed(() =>
  rec.value?.student?.full_name ?? rec.value?.user?.name ?? '—'
)

const COLORS_AVATAR = ['#0F2D5E','#2563EB','#16A34A','#D97706','#DC2626','#7C3AED']
const avatarColor = name => COLORS_AVATAR[(name?.charCodeAt(0) ?? 0) % COLORS_AVATAR.length]
const initials    = name => (name ?? '?').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
const typeLabel   = t => ({ cc:'CC', examen:'Examen', rattrapage:'Rattrapage' }[t] ?? t ?? '—')

const COLORS = { submitted:'#2563EB', in_review:'#D97706', escalated:'#EA580C', resolved:'#16A34A', rejected:'#DC2626', closed:'#9CA3AF' }
const LABELS = { submitted:'Soumise', in_review:'En cours', escalated:'Escaladée', resolved:'Résolue', rejected:'Rejetée', closed:'Fermée' }
const sColor    = s => COLORS[s] ?? '#9CA3AF'
const sLabel    = s => LABELS[s] ?? s ?? '—'
const chipStyle = s => ({
  background: sColor(s) + '18', color: sColor(s),
  border: `1px solid ${sColor(s)}30`,
  borderRadius: '20px', fontSize: '12px',
  padding: '3px 10px', fontWeight: '600',
})

const fDateFull = d => d
  ? new Date(d).toLocaleDateString('fr-FR', {
      day:'2-digit', month:'long', year:'numeric',
      hour:'2-digit', minute:'2-digit'
    })
  : '—'

async function load() {
  loading.value = true
  error.value   = ''
  try {
    const res = await api.get(`/admin/reclamations/${route.params.id}`)
    rec.value       = res.data?.data ?? res.data
    newStatus.value = rec.value?.status ?? ''
  } catch {
    error.value = 'Impossible de charger la réclamation.'
  } finally {
    loading.value = false
  }
}

async function updateStatus() {
  updating.value = true
  try {
    await api.put(`/admin/reclamations/${route.params.id}/status`, {
      status: newStatus.value,
      note:   statusNote.value || undefined,
    })
    rec.value.status = newStatus.value
    statusNote.value = ''
    toast.success('Statut mis à jour.')
  } catch {
    toast.error('Erreur lors de la mise à jour.')
  } finally {
    updating.value = false
  }
}

async function escalate() {
  escalating.value = true
  try {
    await api.post(`/admin/reclamations/${route.params.id}/escalate`)
    rec.value.status = 'escalated'
    newStatus.value  = 'escalated'
    toast.success('Réclamation escaladée.')
  } catch { toast.error('Erreur.') }
  finally { escalating.value = false }
}

async function resolve() {
  resolving.value = true
  try {
    await api.put(`/admin/reclamations/${route.params.id}/status`, { status: 'resolved' })
    rec.value.status = 'resolved'
    newStatus.value  = 'resolved'
    toast.success('Réclamation marquée résolue.')
  } catch { toast.error('Erreur.') }
  finally { resolving.value = false }
}

async function reject() {
  rejecting.value = true
  try {
    await api.put(`/admin/reclamations/${route.params.id}/status`, { status: 'rejected' })
    rec.value.status = 'rejected'
    newStatus.value  = 'rejected'
    toast.success('Réclamation rejetée.')
  } catch { toast.error('Erreur.') }
  finally { rejecting.value = false }
}

onMounted(load)
</script>

<style scoped>
.card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; }
.card-head {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 18px; border-bottom:1px solid #F3F4F6;
}
.card-title { font-size:14px; font-weight:600; color:#111827; }
.card-body  { padding:16px 18px; }

.info-grid  { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.info-item  { display:flex; flex-direction:column; gap:3px; padding:10px 12px; background:#F9FAFB; border-radius:8px; border:1px solid #F3F4F6; }
.info-key   { font-size:11px; font-weight:600; color:#9CA3AF; text-transform:uppercase; letter-spacing:.4px; }
.info-val   { font-size:13px; font-weight:500; color:#111827; }
.ref-code   { font-family:monospace; font-size:13px; font-weight:700; color:#0F2D5E; }

.detail-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #F9FAFB; font-size:13px; }
.detail-row:last-child { border-bottom:none; }
.detail-key { color:#6B7280; font-weight:500; }
.detail-val { color:#111827; font-weight:500; text-align:right; }

.field-label { font-size:12px; font-weight:600; color:#374151; }

.chip { display:inline-block; white-space:nowrap; }

.history-row {
  display:flex; gap:14px; padding:14px 18px;
  border-bottom:1px solid #F3F4F6;
  position:relative;
}
.history-row:last-child { border-bottom:none; }
.history-dot {
  width:10px; height:10px; border-radius:50%;
  margin-top:4px; flex-shrink:0;
}
.history-content { flex:1; }
</style>
