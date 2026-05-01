<template>
  <div>

    <!-- En-tête -->
    <div class="d-flex align-center justify-space-between mb-5">
      <div>
        <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0">Étudiants</h1>
        <p style="font-size:13px;color:#6B7280;margin:4px 0 0">
          {{ filtered.length }} étudiant{{ filtered.length > 1 ? 's' : '' }} trouvé{{ filtered.length > 1 ? 's' : '' }}
        </p>
      </div>
    </div>

    <!-- Filtres + Recherche -->
    <div class="d-flex align-center gap-3 mb-4 flex-wrap">
      <v-text-field
        v-model="search"
        placeholder="Nom, matricule, email..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined" density="compact" rounded="lg"
        hide-details style="max-width:300px"
        @update:model-value="page = 1"
      />
      <v-select
        v-model="filterFiliere"
        :items="filiereOptions"
        placeholder="Toutes les filières"
        variant="outlined" density="compact" rounded="lg"
        hide-details style="max-width:200px"
        clearable
        @update:model-value="page = 1"
      />
      <v-select
        v-model="filterNiveau"
        :items="niveauOptions"
        placeholder="Tous les niveaux"
        variant="outlined" density="compact" rounded="lg"
        hide-details style="max-width:180px"
        clearable
        @update:model-value="page = 1"
      />
    </div>

    <!-- Table -->
    <div class="card">
      <div v-if="loading" class="py-10 text-center">
        <v-progress-circular indeterminate color="#0F2D5E" size="28" />
      </div>

      <div v-else-if="filtered.length === 0" class="empty">
        <v-icon size="44" color="#D1D5DB">mdi-account-off-outline</v-icon>
        <p>Aucun étudiant trouvé</p>
      </div>

      <div v-else>
        <!-- Header -->
        <div class="tbl-head">
          <span>Étudiant</span>
          <span>Matricule</span>
          <span>Contact</span>
          <span>Filière</span>
          <span>Niveau</span>
          <span>Année</span>
          <span>Statut</span>
          <span></span>
        </div>

        <!-- Rows -->
        <div
          v-for="s in paginated" :key="s.id"
          class="tbl-row"
          @click="openDetail(s)"
        >
          <!-- Étudiant -->
          <div class="d-flex align-center gap-2">
            <v-avatar size="32" :color="avatarColor(s.full_name ?? s.name)">
              <v-img v-if="s.photo_url" :src="s.photo_url" cover />
              <span v-else style="font-size:11px;font-weight:700;color:white">
                {{ initials(s.full_name ?? s.name) }}
              </span>
            </v-avatar>
            <div>
              <div style="font-size:13px;font-weight:600;color:#111827">
                {{ s.full_name ?? s.name ?? '—' }}
              </div>
              <div style="font-size:11px;color:#9CA3AF">
                {{ s.user?.email ?? s.email ?? '—' }}
              </div>
            </div>
          </div>

          <!-- Matricule -->
          <span class="ref-code">{{ s.matricule ?? '—' }}</span>

          <!-- Contact -->
          <div>
            <div style="font-size:12px;color:#374151">{{ s.phone ?? '—' }}</div>
            <div style="font-size:11px;color:#9CA3AF">{{ s.cin ?? '' }}</div>
          </div>

          <!-- Filière -->
          <div>
            <span class="chip-filiere">{{ s.filiere?.code ?? s.filiere ?? '—' }}</span>
            <div style="font-size:11px;color:#9CA3AF;margin-top:2px">
              {{ s.filiere?.name ?? '' }}
            </div>
          </div>

          <!-- Niveau -->
          <span style="font-size:13px;color:#374151;font-weight:500">
            {{ s.niveau?.code ?? s.niveau ?? '—' }}
          </span>

          <!-- Année académique -->
          <span style="font-size:12px;color:#6B7280">
            {{ s.academic_year ?? '—' }}
          </span>

          <!-- Statut compte -->
          <span>
            <span
              class="chip"
              :style="s.user?.status === 'active' || s.status === 'active'
                ? { background:'#DCFCE7', color:'#16A34A', border:'1px solid #16A34A30' }
                : { background:'#FEE2E2', color:'#DC2626', border:'1px solid #DC262630' }"
            >
              {{ s.user?.status === 'active' || s.status === 'active' ? 'Actif' : 'Inactif' }}
            </span>
          </span>

          <!-- Action -->
          <v-icon size="16" color="#9CA3AF">mdi-chevron-right</v-icon>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="d-flex justify-center mt-4">
      <v-pagination
        v-model="page"
        :length="totalPages"
        size="small" rounded="lg"
        active-color="#0F2D5E"
      />
    </div>

    <!-- ── Dialog détail étudiant ── -->
    <v-dialog v-model="dialog" max-width="640" scrollable>
      <v-card v-if="selected" rounded="xl">

        <v-card-title class="d-flex align-center justify-space-between pa-5 pb-3">
          <span style="font-size:16px;font-weight:700;color:#111827">Fiche Étudiant</span>
          <v-btn icon size="small" variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-5">

          <!-- Avatar + nom -->
          <div class="d-flex align-center gap-4 mb-5">
            <v-avatar size="64" :color="avatarColor(selected.full_name ?? selected.name)">
              <v-img v-if="selected.photo_url" :src="selected.photo_url" cover />
              <span v-else style="font-size:20px;font-weight:700;color:white">
                {{ initials(selected.full_name ?? selected.name) }}
              </span>
            </v-avatar>
            <div>
              <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0">
                {{ selected.full_name ?? selected.name ?? '—' }}
              </h2>
              <p style="font-size:13px;color:#6B7280;margin:3px 0 0">
                {{ selected.user?.email ?? selected.email ?? '—' }}
              </p>
              <span
                class="chip mt-2"
                :style="selected.user?.status === 'active' || selected.status === 'active'
                  ? { background:'#DCFCE7', color:'#16A34A', border:'1px solid #16A34A30' }
                  : { background:'#FEE2E2', color:'#DC2626', border:'1px solid #DC262630' }"
              >
                {{ selected.user?.status === 'active' || selected.status === 'active' ? 'Compte actif' : 'Compte inactif' }}
              </span>
            </div>
          </div>

          <!-- Infos académiques -->
          <div class="section-title">Informations académiques</div>
          <div class="info-grid mb-4">
            <div class="info-item">
              <span class="info-key">Matricule</span>
              <span class="ref-code">{{ selected.matricule ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Filière</span>
              <span class="info-val">{{ selected.filiere?.name ?? selected.filiere ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Code filière</span>
              <span class="info-val">{{ selected.filiere?.code ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Niveau</span>
              <span class="info-val">{{ selected.niveau?.code ?? selected.niveau ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Année académique</span>
              <span class="info-val">{{ selected.academic_year ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Groupe</span>
              <span class="info-val">{{ selected.group ?? selected.groupe ?? '—' }}</span>
            </div>
          </div>

          <!-- Infos personnelles -->
          <div class="section-title">Informations personnelles</div>
          <div class="info-grid mb-4">
            <div class="info-item">
              <span class="info-key">CIN</span>
              <span class="info-val">{{ selected.cin ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Téléphone</span>
              <span class="info-val">{{ selected.phone ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Date naissance</span>
              <span class="info-val">{{ fDate(selected.birth_date) }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Ville</span>
              <span class="info-val">{{ selected.city ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Nationalité</span>
              <span class="info-val">{{ selected.nationality ?? '—' }}</span>
            </div>
            <div class="info-item">
              <span class="info-key">Genre</span>
              <span class="info-val">{{ selected.gender === 'M' ? 'Masculin' : selected.gender === 'F' ? 'Féminin' : '—' }}</span>
            </div>
          </div>

          <!-- Réclamations -->
          <div class="section-title">
            Réclamations
            <span class="section-count">{{ selected.reclamations_count ?? selected.reclamations?.length ?? 0 }}</span>
          </div>
          <div v-if="selected.reclamations?.length > 0" class="recl-mini-list">
            <div
              v-for="r in selected.reclamations.slice(0, 5)"
              :key="r.id"
              class="recl-mini-row"
            >
              <div class="dot" :style="{ background: sColor(r.status) }" />
              <span style="font-size:12px;color:#374151;flex:1">
                {{ r.reference ?? `#${r.id}` }} — {{ r.subject ?? r.type ?? '—' }}
              </span>
              <span class="chip" :style="chipStyle(r.status)">{{ sLabel(r.status) }}</span>
            </div>
          </div>
          <p v-else style="font-size:13px;color:#9CA3AF;margin:0">Aucune réclamation.</p>

        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="outlined" size="small" rounded="lg" @click="dialog = false">
            Fermer
          </v-btn>
        </v-card-actions>

      </v-card>
    </v-dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── État ──────────────────────────────────────────────────────────────
const loading       = ref(true)
const list          = ref([])
const search        = ref('')
const filterFiliere = ref(null)
const filterNiveau  = ref(null)
const page          = ref(1)
const PER           = 12
const dialog        = ref(false)
const selected      = ref(null)

// ── Options de filtre ─────────────────────────────────────────────────
const filiereOptions = computed(() => {
  const codes = [...new Set(
    list.value
      .map(s => s.filiere?.code ?? s.filiere)
      .filter(Boolean)
  )]
  return codes
})

const niveauOptions = computed(() => {
  const codes = [...new Set(
    list.value
      .map(s => s.niveau?.code ?? s.niveau)
      .filter(Boolean)
  )]
  return codes
})

// ── Filtrage ──────────────────────────────────────────────────────────
const filtered = computed(() => {
  let res = list.value

  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    res = res.filter(s =>
      (s.full_name ?? s.name ?? '').toLowerCase().includes(q) ||
      (s.matricule ?? '').toLowerCase().includes(q) ||
      (s.user?.email ?? s.email ?? '').toLowerCase().includes(q) ||
      (s.cin ?? '').toLowerCase().includes(q) ||
      (s.phone ?? '').includes(q)
    )
  }

  if (filterFiliere.value) {
    res = res.filter(s =>
      (s.filiere?.code ?? s.filiere) === filterFiliere.value
    )
  }

  if (filterNiveau.value) {
    res = res.filter(s =>
      (s.niveau?.code ?? s.niveau) === filterNiveau.value
    )
  }

  return res
})

const totalPages = computed(() => Math.ceil(filtered.value.length / PER))
const paginated  = computed(() =>
  filtered.value.slice((page.value - 1) * PER, page.value * PER)
)

// ── Helpers visuels ───────────────────────────────────────────────────
const COLORS_AVATAR = [
  '#0F2D5E', '#2563EB', '#16A34A', '#D97706',
  '#DC2626', '#7C3AED', '#0891B2', '#EA580C',
]

function avatarColor(name) {
  if (!name) return '#0F2D5E'
  const i = name.charCodeAt(0) % COLORS_AVATAR.length
  return COLORS_AVATAR[i]
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

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
  ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
  : '—'

// ── Ouverture dialog ──────────────────────────────────────────────────
async function openDetail(student) {
  selected.value = student
  dialog.value   = true

  // Charge les détails complets si pas encore chargés
  if (!student._loaded) {
    try {
      const res      = await api.get(`/admin/students/${student.id}`)
      const full     = res.data?.data ?? res.data
      // Fusionne les données
      Object.assign(student, full, { _loaded: true })
      selected.value = { ...student }
    } catch { /* garde les données existantes */ }
  }
}

// ── Chargement ────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const res  = await api.get('/admin/students')
    list.value = res.data?.data ?? []
  } catch {
    list.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; }
.empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:40px; color:#9CA3AF; font-size:13px; }

.tbl-head {
  display: grid;
  grid-template-columns: 220px 110px 130px 140px 80px 100px 80px 40px;
  padding: 10px 18px;
  background: #F9FAFB;
  border-bottom: 1px solid #E5E7EB;
  font-size: 11px; font-weight: 600;
  color: #9CA3AF; text-transform: uppercase; letter-spacing: .5px;
}

.tbl-row {
  display: grid;
  grid-template-columns: 220px 110px 130px 140px 80px 100px 80px 40px;
  padding: 13px 18px;
  border-bottom: 1px solid #F3F4F6;
  align-items: center;
  cursor: pointer;
  transition: background .12s;
}
.tbl-row:last-child { border-bottom: none; }
.tbl-row:hover      { background: #F8FAFC; }

.ref-code {
  font-family: monospace;
  font-size: 12px; font-weight: 600; color: #0F2D5E;
}

.chip {
  font-size: 11px; font-weight: 600;
  padding: 2px 9px; border-radius: 20px;
  display: inline-block; white-space: nowrap;
}

.chip-filiere {
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 6px;
  background: #DBEAFE; color: #1D4ED8;
  display: inline-block;
}

/* Dialog */
.section-title {
  font-size: 12px; font-weight: 700;
  color: #6B7280; text-transform: uppercase;
  letter-spacing: .6px; margin-bottom: 10px;
  display: flex; align-items: center; gap: 6px;
}
.section-count {
  background: #F3F4F6; color: #374151;
  font-size: 11px; padding: 1px 7px;
  border-radius: 10px; font-weight: 600;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.info-item {
  display: flex; flex-direction: column;
  gap: 2px; padding: 10px 12px;
  background: #F9FAFB; border-radius: 8px;
  border: 1px solid #F3F4F6;
}

.info-key {
  font-size: 11px; font-weight: 600;
  color: #9CA3AF; text-transform: uppercase; letter-spacing: .4px;
}

.info-val {
  font-size: 13px; font-weight: 500; color: #111827;
}

.recl-mini-list { display: flex; flex-direction: column; gap: 6px; }
.recl-mini-row  {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 10px; background: #F9FAFB;
  border-radius: 8px; border: 1px solid #F3F4F6;
}
.dot {
  width: 7px; height: 7px;
  border-radius: 50%; flex-shrink: 0;
}
</style>
