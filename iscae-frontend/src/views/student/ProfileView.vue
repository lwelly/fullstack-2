<template>
  <div>
    <!-- ═══════════════════════ HEADER ═══════════════════════ -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h2 class="text-h5 font-weight-bold text-primary">Mon Profil</h2>
        <p class="text-body-2 text-grey mt-1">Vos informations personnelles</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-refresh" variant="tonal"
        @click="loadData" :loading="loading">Actualiser</v-btn>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center pa-12">
      <v-progress-circular indeterminate color="primary" size="56" />
    </div>

    <v-row v-else>
      <!-- ══ LEFT COLUMN ══ -->
      <v-col cols="12" md="4">

        <!-- Avatar Card -->
        <v-card rounded="xl" elevation="2" class="mb-4 text-center pa-6">
        <v-avatar color="primary" size="120" class="mb-4">
        <span class="text-white font-weight-bold text-h4">
                {{ userInitials }}
        </span>
        </v-avatar>
          <h3 class="text-h6 font-weight-bold mb-1">{{ profile?.name || '—' }}</h3>
          <p class="text-body-2 text-grey mb-3">{{ profile?.email || '—' }}</p>
          <div class="d-flex justify-center ga-2 flex-wrap">
            <v-chip color="primary" size="small" variant="tonal">
              <v-icon start size="14">mdi-school</v-icon>
              {{ studentData?.filiere || '—' }}
            </v-chip>
            <v-chip color="secondary" size="small" variant="tonal">
              <v-icon start size="14">mdi-layers</v-icon>
              {{ studentData?.niveau || '—' }}
            </v-chip>
          </div>
        </v-card>

        <!-- Reclamation Stats -->
        <v-card rounded="xl" elevation="2">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-chart-bar</v-icon>Mes Statistiques
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <div v-for="stat in recStats" :key="stat.label"
              class="d-flex align-center justify-space-between mb-3">
              <div class="d-flex align-center ga-2">
                <v-icon :color="stat.color" size="18">{{ stat.icon }}</v-icon>
                <span class="text-body-2 text-grey">{{ stat.label }}</span>
              </div>
              <v-chip :color="stat.color" size="small" variant="tonal"
                class="font-weight-bold">
                {{ stat.value }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>

      </v-col>

      <!-- ══ RIGHT COLUMN ══ -->
      <v-col cols="12" md="8">

        <!-- Personal Info -->
        <v-card rounded="xl" elevation="2" class="mb-4">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-account-details</v-icon>Informations Personnelles
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <v-row dense>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="profile?.name"
                  label="Nom complet" readonly
                  prepend-inner-icon="mdi-account"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="profile?.email"
                  label="Email" readonly
                  prepend-inner-icon="mdi-email"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="studentData?.matricule"
                  label="Matricule" readonly
                  prepend-inner-icon="mdi-card-account-details"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="studentData?.filiere_nom || studentData?.filiere"
                  label="Filière" readonly
                  prepend-inner-icon="mdi-school"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="studentData?.niveau"
                  label="Niveau" readonly
                  prepend-inner-icon="mdi-layers"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="studentData?.academic_year"
                  label="Année académique" readonly
                  prepend-inner-icon="mdi-calendar"
                  density="comfortable" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Security Card -->
        <v-card rounded="xl" elevation="2">
          <v-card-title class="pa-5 pb-3 text-primary">
            <v-icon class="mr-2">mdi-shield-lock</v-icon>Sécurité
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-5">
            <v-alert type="info" variant="tonal" rounded="lg" class="mb-4"
              icon="mdi-information">
              Pour changer votre mot de passe, veuillez contacter l'administration.
            </v-alert>
            <v-row dense>
              <v-col cols="12" md="6">
                <v-text-field
                  model-value="••••••••••••"
                  label="Mot de passe" readonly
                  prepend-inner-icon="mdi-lock"
                  type="password"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="formatDate(profile?.last_login_at)"
                  label="Dernière connexion" readonly
                  prepend-inner-icon="mdi-clock-check"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="profile?.is_active ? 'Actif' : 'Inactif'"
                  label="Statut du compte" readonly
                  prepend-inner-icon="mdi-account-check"
                  density="comfortable" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="formatDate(profile?.email_verified_at)"
                  label="Email vérifié le" readonly
                  prepend-inner-icon="mdi-email-check"
                  density="comfortable" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

      </v-col>
    </v-row>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const toast     = useToast()
const authStore = useAuthStore()

const loading          = ref(false)
const profileData      = ref(null)
const reclamationsData = ref([])

// ── Computed ───────────────────────────────────────────────────────────
const profile = computed(() =>
  profileData.value?.user || profileData.value || authStore.user
)
const studentData = computed(() =>
  profileData.value?.student || profileData.value
)
const initials = computed(() => {
  const name = profile.value?.name || ''
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() || '?'
})

const recStats = computed(() => {
  const recs = reclamationsData.value
  return [
    {
      label: 'Total réclamations',
      value: recs.length,
      color: 'primary',
      icon:  'mdi-file-document-multiple',
    },
    {
      label: 'En cours',
      value: recs.filter(r => ['submitted','received','in_review'].includes(r.status)).length,
      color: 'warning',
      icon:  'mdi-clock-alert',
    },
    {
      label: 'Résolues',
      value: recs.filter(r => r.status === 'resolved').length,
      color: 'success',
      icon:  'mdi-check-circle',
    },
    {
      label: 'Rejetées',
      value: recs.filter(r => r.status === 'rejected').length,
      color: 'error',
      icon:  'mdi-close-circle',
    },
  ]
})

// ── Helpers ────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

// ── Load data ──────────────────────────────────────────────────────────
async function loadData() {
  loading.value = true
  try {
    const [profileRes, recsRes] = await Promise.all([
      api.get('/student/profile'),        // → StudentProfile@show
      api.get('/student/reclamations'),   // → StudentReclamation@index
    ])
    profileData.value      = profileRes.data.data || profileRes.data
    reclamationsData.value = recsRes.data.data    || recsRes.data || []
  } catch (e) {
    toast.error('Erreur de chargement du profil.')
    console.error(e)
  } finally {
    loading.value = false
  }
}


onMounted(loadData)
</script>
