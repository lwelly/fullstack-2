<template>
  <div class="stu-profile-page">

    <!-- ─── En-tête ───────────────────────────────────────────── -->
    <div class="profile-header mb-6">
      <div>
        <h1 class="page-title">Mon Profil</h1>
        <p class="page-sub">Gérez vos informations personnelles et votre sécurité</p>
      </div>
      <v-chip color="#0D4F3C" variant="tonal" size="small" prepend-icon="mdi-check-circle">
        Compte actif
      </v-chip>
    </div>

    <v-row>

      <!-- ═══ Colonne gauche ════════════════════════════════════ -->
      <v-col cols="12" md="4" lg="3">
        <v-card elevation="2" rounded="xl" class="identity-card pa-6 text-center mb-4">

          <!-- Avatar -->
          <div class="avatar-zone mb-4">
            <v-avatar :color="avatarColor" size="100" class="main-avatar">
              <img
                v-if="photoUrl"
                :key="photoUrl"
                :src="photoUrl"
                alt="Photo de profil"
                style="width:100%;height:100%;object-fit:cover"
              />
              <span v-else class="text-h4 font-weight-bold text-white">{{ initials }}</span>
            </v-avatar>

            <button
              class="photo-btn"
              :disabled="uploadingPhoto"
              @click="$refs.photoInput.click()"
              title="Changer la photo"
            >
              <v-progress-circular
                v-if="uploadingPhoto"
                indeterminate size="14" width="2" color="white"
              />
              <v-icon v-else size="15" color="white">mdi-camera</v-icon>
            </button>

            <input
              ref="photoInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              style="display:none"
              @change="uploadPhoto"
            />
          </div>

          <!-- Nom & matricule -->
          <div class="id-name">{{ fullName }}</div>
          <div class="id-matricule">{{ matricule }}</div>

          <!-- Badges -->
          <div class="d-flex justify-center gap-2 flex-wrap mt-3 mb-4">
            <v-chip size="x-small" color="#0D4F3C" variant="flat" class="text-white">
              {{ profile?.student?.niveau?.label ?? profile?.student?.niveau_label ?? 'N/A' }}
            </v-chip>
            <v-chip size="x-small" color="blue" variant="tonal">
              {{ profile?.student?.academic_year ?? '—' }}
            </v-chip>
          </div>

          <v-divider class="mb-4" />

          <!-- Infos fixes -->
          <div class="id-info-list">
            <div class="id-info-row">
              <v-icon size="15" color="#0D4F3C">mdi-email-outline</v-icon>
              <span class="id-info-val">{{ profile?.email ?? '—' }}</span>
            </div>
            <div class="id-info-row">
              <v-icon size="15" color="#0D4F3C">mdi-phone-outline</v-icon>
              <span class="id-info-val">{{ profile?.student?.phone ?? '—' }}</span>
            </div>
            <div class="id-info-row">
              <v-icon size="15" color="#0D4F3C">mdi-domain</v-icon>
              <span class="id-info-val">
                {{ profile?.student?.filiere?.name ?? profile?.student?.filiere_name ?? '—' }}
              </span>
            </div>
            <div class="id-info-row">
              <v-icon size="15" color="#0D4F3C">mdi-map-marker-outline</v-icon>
              <span class="id-info-val">{{ profile?.student?.adresse ?? '—' }}</span>
            </div>
            <div class="id-info-row">
              <v-icon size="15" color="#0D4F3C">mdi-cake-variant-outline</v-icon>
              <span class="id-info-val">{{ fDate(profile?.student?.date_naissance) }}</span>
            </div>
          </div>

          <v-divider class="my-4" />

          <!-- Stats réclamations -->
          <div class="rec-stats">
            <div class="rec-stat-item">
              <span class="stat-num" style="color:#0D4F3C">{{ recStats.total }}</span>
              <span class="stat-lbl">Total</span>
            </div>
            <div class="rec-stat-sep" />
            <div class="rec-stat-item">
              <span class="stat-num" style="color:#D97706">{{ recStats.pending }}</span>
              <span class="stat-lbl">En attente</span>
            </div>
            <div class="rec-stat-sep" />
            <div class="rec-stat-item">
              <span class="stat-num" style="color:#059669">{{ recStats.resolved }}</span>
              <span class="stat-lbl">Résolues</span>
            </div>
          </div>

        </v-card>

        <!-- Carte sécurité -->
        <v-card elevation="1" rounded="xl" class="pa-4 security-mini">
          <div class="d-flex align-center gap-2 mb-3">
            <v-icon size="18" color="#0D4F3C">mdi-shield-check</v-icon>
            <span class="font-weight-bold text-body-2" style="color:#0D4F3C">Sécurité du compte</span>
          </div>
          <div class="sec-item">
            <v-icon size="14" color="success">mdi-check-circle</v-icon>
            <span class="text-caption">Email vérifié</span>
          </div>
          <div class="sec-item mt-2">
            <v-icon size="14" :color="profile?.password_changed_at ? 'success' : 'warning'">
              {{ profile?.password_changed_at ? 'mdi-check-circle' : 'mdi-alert-circle' }}
            </v-icon>
            <span class="text-caption">
              Mot de passe
              <span v-if="profile?.password_changed_at" class="text-medium-emphasis">
                · modifié le {{ fDate(profile.password_changed_at) }}
              </span>
              <span v-else style="color:#D97706"> · jamais modifié</span>
            </span>
          </div>
          <div v-if="profile?.last_login_at" class="sec-item mt-2">
            <v-icon size="14" color="blue">mdi-login</v-icon>
            <span class="text-caption text-medium-emphasis">
              Dernière connexion : {{ fDateTime(profile.last_login_at) }}
            </span>
          </div>
        </v-card>
      </v-col>

      <!-- ═══ Colonne droite ════════════════════════════════════ -->
      <v-col cols="12" md="8" lg="9">
        <v-card elevation="2" rounded="xl">

          <v-tabs v-model="tab" color="#0D4F3C" class="profile-tabs">
            <v-tab value="info">
              <v-icon size="17" class="mr-2">mdi-account-edit</v-icon>
              Informations personnelles
            </v-tab>
            <v-tab value="password">
              <v-icon size="17" class="mr-2">mdi-lock-outline</v-icon>
              Mot de passe
            </v-tab>
          </v-tabs>

          <v-divider />

          <v-window v-model="tab">

            <!-- ── Tab infos ── -->
            <v-window-item value="info">
              <div v-if="loadingProfile" class="py-16 text-center">
                <v-progress-circular indeterminate color="#0D4F3C" size="40" />
              </div>
              <div v-else class="pa-6">

                <div class="section-label mb-4">
                  <v-icon size="15" color="#0D4F3C">mdi-account</v-icon>
                  Informations générales
                </div>

                <v-row dense>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.prenom"
                      label="Prénom"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-account-outline"
                    />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.nom"
                      label="Nom de famille"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-account-outline"
                    />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.email"
                      label="Adresse email"
                      type="email"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-email-outline"
                    />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.phone"
                      label="Téléphone"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-phone-outline"
                    />
                  </v-col>
                </v-row>

                <v-divider class="my-5" />

                <div class="section-label mb-4">
                  <v-icon size="15" color="#0D4F3C">mdi-information-outline</v-icon>
                  Informations complémentaires
                </div>

                <v-row dense>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.date_naissance"
                      label="Date de naissance"
                      type="date"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-cake-variant-outline"
                    />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.lieu_naissance"
                      label="Lieu de naissance"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-map-marker-outline"
                    />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.nationalite"
                      label="Nationalité"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-flag-outline"
                    />
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model="form.nni"
                      label="NNI"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-card-account-details-outline"
                      readonly
                      hint="Non modifiable"
                      persistent-hint
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.adresse"
                      label="Adresse complète"
                      variant="outlined" density="comfortable"
                      prepend-inner-icon="mdi-home-outline"
                    />
                  </v-col>
                </v-row>

                <!-- Infos académiques non modifiables -->
                <div class="readonly-block mt-4">
                  <div class="section-label mb-3">
                    <v-icon size="15" color="#6B7280">mdi-lock-outline</v-icon>
                    Informations académiques (non modifiables)
                  </div>
                  <div class="readonly-grid">
                    <div class="readonly-item">
                      <span class="readonly-key">Matricule</span>
                      <span class="readonly-val font-mono">{{ profile?.student?.matricule ?? '—' }}</span>
                    </div>
                    <div class="readonly-item">
                      <span class="readonly-key">Filière</span>
                      <span class="readonly-val">
                        {{ profile?.student?.filiere?.name ?? profile?.student?.filiere_name ?? '—' }}
                      </span>
                    </div>
                    <div class="readonly-item">
                      <span class="readonly-key">Niveau</span>
                      <span class="readonly-val">
                        {{ profile?.student?.niveau?.label ?? profile?.student?.niveau_label ?? '—' }}
                      </span>
                    </div>
                    <div class="readonly-item">
                      <span class="readonly-key">Année académique</span>
                      <span class="readonly-val">{{ profile?.student?.academic_year ?? '—' }}</span>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-end mt-6">
                  <v-btn
                    color="#0D4F3C"
                    :loading="savingProfile"
                    prepend-icon="mdi-content-save"
                    size="large"
                    @click="saveProfile"
                  >
                    Enregistrer les modifications
                  </v-btn>
                </div>
              </div>
            </v-window-item>

            <!-- ── Tab mot de passe ── -->
            <v-window-item value="password">
              <div class="pa-6">

                <div class="section-label mb-5">
                  <v-icon size="15" color="#0D4F3C">mdi-lock</v-icon>
                  Changer le mot de passe
                </div>

                <v-text-field
                  v-model="pwd.current"
                  label="Mot de passe actuel"
                  :type="show.current ? 'text' : 'password'"
                  variant="outlined" density="comfortable"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="show.current ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="show.current = !show.current"
                  class="mb-4"
                />

                <v-divider class="mb-4">
                  <span class="text-caption text-medium-emphasis">Nouveau mot de passe</span>
                </v-divider>

                <v-text-field
                  v-model="pwd.new"
                  label="Nouveau mot de passe"
                  :type="show.new ? 'text' : 'password'"
                  variant="outlined" density="comfortable"
                  prepend-inner-icon="mdi-lock-plus-outline"
                  :append-inner-icon="show.new ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="show.new = !show.new"
                  class="mb-3"
                />

                <!-- Indicateur de force -->
                <div v-if="pwd.new" class="strength-block mb-4">
                  <div class="strength-bars">
                    <div
                      v-for="i in 4" :key="i"
                      class="strength-seg"
                      :style="`background:${i <= pwdStrength.score ? pwdStrength.color : '#E2E8F0'}`"
                    />
                  </div>
                  <span class="strength-text mt-1 d-block" :style="`color:${pwdStrength.color}`">
                    {{ pwdStrength.label }}
                  </span>
                  <div class="pwd-criteria mt-2">
                    <div
                      v-for="c in pwdCriteria" :key="c.label"
                      class="criteria-item"
                      :class="{ 'criteria-ok': c.ok }"
                    >
                      <v-icon size="12" :color="c.ok ? 'success' : '#CBD5E1'">
                        {{ c.ok ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                      </v-icon>
                      <span>{{ c.label }}</span>
                    </div>
                  </div>
                </div>

                <v-text-field
                  v-model="pwd.confirm"
                  label="Confirmer le nouveau mot de passe"
                  :type="show.confirm ? 'text' : 'password'"
                  variant="outlined" density="comfortable"
                  prepend-inner-icon="mdi-lock-check-outline"
                  :append-inner-icon="show.confirm ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="show.confirm = !show.confirm"
                  :error-messages="pwd.confirm && pwd.new !== pwd.confirm
                    ? ['Les mots de passe ne correspondent pas'] : []"
                  :success-messages="pwd.confirm && pwd.new === pwd.confirm && pwd.confirm
                    ? ['Mots de passe identiques ✓'] : []"
                  class="mb-5"
                />

                <v-alert
                  type="info" variant="tonal" color="#0D4F3C"
                  rounded="lg" density="compact" class="mb-5"
                  icon="mdi-shield-lock-outline"
                >
                  <div class="text-caption">
                    Utilisez au moins 8 caractères avec des majuscules, chiffres et symboles.
                  </div>
                </v-alert>

                <div class="d-flex justify-end">
                  <v-btn
                    color="#0D4F3C"
                    :loading="savingPwd"
                    :disabled="!canSavePwd"
                    prepend-icon="mdi-lock-check"
                    size="large"
                    @click="savePassword"
                  >
                    Changer le mot de passe
                  </v-btn>
                </div>
              </div>
            </v-window-item>

          </v-window>
        </v-card>
      </v-col>
    </v-row>

    <!-- ─── Snackbar ──────────────────────────────────────────── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      location="bottom right"
      timeout="3500"
      rounded="lg"
      elevation="6"
    >
      <div class="d-flex align-center gap-2">
        <v-icon size="18">
          {{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
        </v-icon>
        {{ snack.text }}
      </div>
    </v-snackbar>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import api from '@/api/axios'

/* ─── État ───────────────────────────────────────────────────── */
const tab            = ref('info')
const loadingProfile = ref(true)
const savingProfile  = ref(false)
const savingPwd      = ref(false)
const uploadingPhoto = ref(false)
const localPhotoUrl  = ref(null)
const photoInput     = ref(null)
const profile        = ref(null)

const form = reactive({
  prenom: '', nom: '', email: '', phone: '',
  date_naissance: '', lieu_naissance: '',
  nationalite: '', nni: '', adresse: '',
})

const pwd  = reactive({ current: '', new: '', confirm: '' })
const show = reactive({ current: false, new: false, confirm: false })
const snack = reactive({ show: false, text: '', color: 'success' })

const recStats = reactive({ total: 0, pending: 0, resolved: 0 })

function notify(text, color = 'success') {
  snack.text = text
  snack.color = color
  snack.show = true
}

/* ─── Computed ───────────────────────────────────────────────── */
const fullName = computed(() => {
  const s = profile.value?.student
  if (!s) return profile.value?.email ?? '—'
  const parts = [s.prenom ?? s.first_name, s.nom ?? s.last_name].filter(Boolean)
  return parts.join(' ') || profile.value?.email || '—'
})

const matricule = computed(() => profile.value?.student?.matricule ?? '')

const initials = computed(() => {
  const n = fullName.value
  if (!n || n === '—') return '?'
  const p = n.trim().split(' ').filter(Boolean)
  return p.length >= 2
    ? (p[0][0] + p[p.length - 1][0]).toUpperCase()
    : n.substring(0, 2).toUpperCase()
})

const avatarColor = computed(() => {
  const colors = ['teal-darken-2', 'green-darken-2', 'cyan-darken-2', 'blue-darken-1', 'indigo-darken-1']
  const n = fullName.value
  return n ? colors[n.charCodeAt(0) % colors.length] : 'teal-darken-2'
})

const photoUrl = computed(() => {
  if (localPhotoUrl.value) return localPhotoUrl.value
  const s = profile.value?.student
  const p = s?.photo_url ?? s?.photo_path
  if (!p) return null
  if (p.startsWith('http')) return p
  const base = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')
  return `${base}/storage/${p.replace(/^\//, '')}`
})

const pwdCriteria = computed(() => [
  { label: 'Au moins 8 caractères',        ok: pwd.new.length >= 8 },
  { label: 'Une lettre majuscule',          ok: /[A-Z]/.test(pwd.new) },
  { label: 'Un chiffre',                    ok: /[0-9]/.test(pwd.new) },
  { label: 'Un caractère spécial (!@#$…)', ok: /[^a-zA-Z0-9]/.test(pwd.new) },
])

const pwdStrength = computed(() => {
  const score = pwdCriteria.value.filter(c => c.ok).length
  const levels = [
    { score: 1, color: '#EF4444', label: 'Très faible' },
    { score: 2, color: '#F97316', label: 'Faible' },
    { score: 3, color: '#EAB308', label: 'Moyen' },
    { score: 4, color: '#22C55E', label: 'Fort' },
  ]
  return levels[score - 1] ?? { score: 0, color: '#E2E8F0', label: '' }
})

const canSavePwd = computed(() =>
  pwd.current.length > 0 &&
  pwd.new.length >= 8 &&
  pwd.new === pwd.confirm
)

/* ─── Helpers ────────────────────────────────────────────────── */
function buildUrl(path) {
  if (!path) return null
  if (path.startsWith('http')) return path
  const base = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')
  return `${base}/storage/${path.replace(/^\//, '')}`
}

function fDate(raw) {
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleDateString('fr-FR', {
      day: '2-digit', month: 'long', year: 'numeric',
    })
  } catch { return raw }
}

function fDateTime(raw) {
  if (!raw) return '—'
  try {
    return new Date(raw).toLocaleString('fr-FR', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    })
  } catch { return raw }
}

/* ─── Chargement profil ──────────────────────────────────────── */
async function loadProfile() {
  loadingProfile.value = true
  try {
    const [profileRes, recRes] = await Promise.all([
      api.get('/student/profile'),
      api.get('/student/reclamations', { params: { per_page: 100 } }).catch(() => null),
    ])

    profile.value = profileRes.data?.data ?? profileRes.data ?? {}

    const s = profile.value?.student ?? {}
    form.prenom         = s.prenom         ?? s.first_name    ?? ''
    form.nom            = s.nom            ?? s.last_name     ?? ''
    form.email          = profile.value?.email                ?? ''
    form.phone          = s.phone                             ?? ''
    form.date_naissance = s.date_naissance ? s.date_naissance.slice(0, 10) : ''
    form.lieu_naissance = s.lieu_naissance ?? ''
    form.nationalite    = s.nationalite    ?? ''
    form.nni            = s.nni            ?? ''
    form.adresse        = s.adresse        ?? ''

    /* Photo initiale */
    const p = s.photo_url ?? s.photo_path
    localPhotoUrl.value = buildUrl(p)

    /* Stats réclamations */
    if (recRes) {
      const recData = recRes.data?.data ?? recRes.data ?? {}
      const list    = Array.isArray(recData) ? recData : (recData.data ?? [])
      recStats.total    = Array.isArray(recData) ? recData.length : (recData.total ?? list.length)
      recStats.pending  = list.filter(r => ['submitted', 'received', 'in_review'].includes(r.status)).length
      recStats.resolved = list.filter(r => r.status === 'resolved').length
    }

  } catch (err) {
    console.error('[ProfileView] loadProfile:', err)
    notify('Impossible de charger le profil.', 'error')
  } finally {
    loadingProfile.value = false
  }
}

/* ─── Sauvegarde infos ───────────────────────────────────────── */
async function saveProfile() {
  savingProfile.value = true
  try {
    await api.put('/student/profile', {
      prenom:         form.prenom         || undefined,
      nom:            form.nom            || undefined,
      email:          form.email          || undefined,
      phone:          form.phone          || null,
      date_naissance: form.date_naissance || null,
      lieu_naissance: form.lieu_naissance || null,
      nationalite:    form.nationalite    || null,
      adresse:        form.adresse        || null,
    })
    notify('Profil mis à jour avec succès.', 'success')
    await loadProfile()
  } catch (err) {
    notify(err.response?.data?.message ?? 'Erreur lors de la mise à jour.', 'error')
  } finally {
    savingProfile.value = false
  }
}

/* ─── Upload photo ───────────────────────────────────────────── */
async function uploadPhoto(e) {
  const file = e.target.files?.[0]
  if (!file) return

  if (file.size > 3 * 1024 * 1024) {
    notify('Fichier trop lourd (max 3 Mo).', 'error')
    if (e.target) e.target.value = ''
    return
  }

  /* Aperçu instantané */
  const preview = URL.createObjectURL(file)
  localPhotoUrl.value  = preview
  uploadingPhoto.value = true

  try {
    const fd = new FormData()
    fd.append('photo', file)

    /* ✅ Supprime le Content-Type pour laisser Axios
       générer automatiquement le boundary multipart */
    const res = await api.post('/student/profile/photo', fd, {
      headers: { 'Content-Type': undefined },
    })

    /* Cherche l'URL dans toutes les structures possibles */
    const serverUrl =
      res.data?.data?.photo_url  ??
      res.data?.photo_url        ??
      buildUrl(res.data?.data?.photo_path) ??
      buildUrl(res.data?.photo_path)       ??
      null

    if (serverUrl) {
      const finalUrl = (serverUrl.startsWith('http') ? serverUrl : buildUrl(serverUrl))
                       + '?t=' + Date.now()
      /* Reset + nextTick pour forcer le re-render de <img :key="photoUrl"> */
      localPhotoUrl.value = null
      await nextTick()
      localPhotoUrl.value = finalUrl
    }

    notify('Photo de profil mise à jour.', 'success')

  } catch (err) {
    console.error('[uploadPhoto] erreur:', err?.response ?? err)
    /* Remet l'ancienne photo */
    const s = profile.value?.student
    localPhotoUrl.value = buildUrl(s?.photo_url ?? s?.photo_path)
    notify(err.response?.data?.message ?? 'Erreur lors de l\'upload.', 'error')
  } finally {
    uploadingPhoto.value = false
    URL.revokeObjectURL(preview)
    if (e.target) e.target.value = ''
  }
}

/* ─── Changement de mot de passe ─────────────────────────────── */
async function savePassword() {
  if (!canSavePwd.value) return
  savingPwd.value = true
  try {
    await api.put('/student/profile/password', {
      current_password:      pwd.current,
      password:              pwd.new,
      password_confirmation: pwd.confirm,
    })
    notify('Mot de passe modifié avec succès.', 'success')
    pwd.current = ''; pwd.new = ''; pwd.confirm = ''
  } catch (err) {
    notify(err.response?.data?.message ?? 'Erreur lors du changement de mot de passe.', 'error')
  } finally {
    savingPwd.value = false
  }
}

/* ─── Lifecycle ──────────────────────────────────────────────── */
onMounted(loadProfile)
</script>

<style scoped>
/* ── Layout ── */
.stu-profile-page { }
.profile-header   { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.page-title       { font-size:1.5rem; font-weight:700; color:#0F172A; margin:0; }
.page-sub         { font-size:.875rem; color:#64748B; margin:4px 0 0; }

/* ── Carte identité ── */
.identity-card { border:1px solid #E2E8F0; }

/* Avatar */
.avatar-zone  { position:relative; width:fit-content; margin:0 auto; }
.main-avatar  { border:4px solid #fff; box-shadow:0 4px 20px rgba(0,0,0,.12); }
.photo-btn {
  position:absolute; bottom:4px; right:4px;
  width:30px; height:30px; border-radius:50%;
  background:#0D4F3C; border:3px solid #fff;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:background .15s, transform .15s;
  box-shadow:0 2px 8px rgba(0,0,0,.2);
}
.photo-btn:hover:not(:disabled) { background:#0a3d2e; transform:scale(1.08); }
.photo-btn:disabled              { opacity:.7; cursor:default; }

/* Nom & matricule */
.id-name      { font-size:1.15rem; font-weight:800; color:#1E293B; line-height:1.2; }
.id-matricule {
  font-size:.8rem; font-family:monospace; color:#64748B;
  background:#F1F5F9; border-radius:6px;
  padding:2px 10px; display:inline-block; margin-top:4px;
}

/* Infos liste */
.id-info-list { display:flex; flex-direction:column; gap:8px; text-align:left; }
.id-info-row  { display:flex; align-items:flex-start; gap:8px; }
.id-info-val  { font-size:.8rem; color:#475569; line-height:1.4; word-break:break-all; }

/* Stats */
.rec-stats     { display:flex; align-items:center; justify-content:center; }
.rec-stat-item { display:flex; flex-direction:column; align-items:center; flex:1; }
.stat-num      { font-size:1.4rem; font-weight:800; line-height:1; }
.stat-lbl      { font-size:.68rem; color:#94A3B8; margin-top:3px; text-transform:uppercase; letter-spacing:.04em; }
.rec-stat-sep  { width:1px; height:32px; background:#E2E8F0; }

/* Sécurité */
.security-mini { border:1px solid #E2E8F0; }
.sec-item      { display:flex; align-items:center; gap:6px; }

/* ── Tabs ── */
.profile-tabs :deep(.v-tab) { text-transform:none !important; font-weight:600; font-size:.875rem; }

/* Section label */
.section-label {
  display:flex; align-items:center; gap:6px;
  font-size:.72rem; font-weight:700; color:#64748B;
  text-transform:uppercase; letter-spacing:.07em;
}

/* Readonly */
.readonly-block { background:#F8FAFC; border-radius:12px; padding:16px; border:1px solid #E2E8F0; }
.readonly-grid  { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px,1fr)); gap:12px; }
.readonly-item  { display:flex; flex-direction:column; gap:3px; }
.readonly-key   { font-size:.68rem; color:#9CA3AF; text-transform:uppercase; font-weight:600; letter-spacing:.05em; }
.readonly-val   { font-size:.875rem; color:#374151; font-weight:600; }
.font-mono      { font-family:monospace; }

/* ── Mot de passe ── */
.strength-block { }
.strength-bars  { display:flex; gap:4px; }
.strength-seg   { flex:1; height:5px; border-radius:3px; transition:background .3s; }
.strength-text  { font-size:.75rem; font-weight:700; }
.pwd-criteria   { display:flex; flex-wrap:wrap; gap:8px; }
.criteria-item  { display:flex; align-items:center; gap:4px; font-size:.75rem; color:#94A3B8; transition:color .2s; }
.criteria-ok    { color:#22C55E; }
</style>
