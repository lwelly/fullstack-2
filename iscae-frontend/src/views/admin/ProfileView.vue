<template>
  <div class="profile-page">
    <!-- ── Header ─────────────────────────────────── -->
    <div class="page-header mb-6">
      <div class="d-flex align-center gap-3">
        <v-icon size="32" color="primary">mdi-account-circle</v-icon>
        <div>
          <h1 class="text-h5 font-weight-bold">Gérez vos informations personnelles et vos paramètres de sécurité</h1>
          <p class="text-body-2 text-medium-emphasis mb-0">
            
          </p>
        </div>
      </div>
    </div>

    <!-- ── Loading ────────────────────────────────── -->
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="primary" size="56" />
      <p class="mt-4 text-medium-emphasis">Chargement du profil…</p>
    </div>

    <template v-else>
      <v-row>
        <!-- ══════════════════════════════════════════
             LEFT COLUMN – Avatar + quick info
        ══════════════════════════════════════════ -->
        <v-col cols="12" md="4">
          <!-- Avatar card -->
          <v-card class="avatar-card text-center pa-6 mb-4" elevation="2" rounded="xl">
            <!-- Avatar with photo or initials -->
            <div class="avatar-wrapper mx-auto mb-4" style="position:relative;width:120px">
              <v-avatar
                size="120"
                :color="photoUrl ? undefined : avatarColor"
                class="elevation-4"
                style="border:4px solid white"
              >
                <v-img v-if="photoUrl" :src="photoUrl" cover :alt="fullName">
                  <template #error>
                    <span class="avatar-initials text-h5 font-weight-bold white--text">
                      {{ initials }}
                    </span>
                  </template>
                </v-img>
                <span v-else class="avatar-initials text-h5 font-weight-bold text-white">
                  {{ initials }}
                </span>
              </v-avatar>

              <!-- Photo upload overlay -->
              <v-btn
                icon
                size="small"
                color="primary"
                class="photo-edit-btn"
                style="position:absolute;bottom:0;right:0"
                :loading="uploadingPhoto"
                @click="triggerPhotoUpload"
              >
                <v-icon size="16">mdi-camera</v-icon>
              </v-btn>
              <input
                ref="photoInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                style="display:none"
                @change="handlePhotoChange"
              />
            </div>

            <h2 class="text-h6 font-weight-bold mb-1">{{ fullName || 'Administrateur' }}</h2>
            <v-chip size="small" :color="roleColor" class="mb-4">
              <v-icon start size="12">mdi-shield-account</v-icon>
              {{ roleLabel }}
            </v-chip>

            <!-- Quick info list -->
            <v-divider class="mb-4" />
            <div class="quick-info text-left">
              <div class="info-row mb-3" v-if="profile?.email">
                <v-icon size="16" color="primary" class="mr-2">mdi-email-outline</v-icon>
                <span class="text-body-2 text-truncate">{{ profile.email }}</span>
              </div>
              <div class="info-row mb-3" v-if="profile?.admin?.phone">
                <v-icon size="16" color="primary" class="mr-2">mdi-phone-outline</v-icon>
                <span class="text-body-2">{{ profile.admin.phone }}</span>
              </div>
              <div class="info-row mb-3" v-if="profile?.admin?.employee_id">
                <v-icon size="16" color="primary" class="mr-2">mdi-badge-account-outline</v-icon>
                <span class="text-body-2">{{ profile.admin.employee_id }}</span>
              </div>
              <div class="info-row mb-3" v-if="profile?.last_login_at">
                <v-icon size="16" color="success" class="mr-2">mdi-login</v-icon>
                <span class="text-body-2">{{ fDate(profile.last_login_at) }}</span>
              </div>
              <div class="info-row" v-if="profile?.created_at">
                <v-icon size="16" color="secondary" class="mr-2">mdi-calendar-plus</v-icon>
                <span class="text-body-2">Membre depuis {{ fDate(profile.created_at, true) }}</span>
              </div>
            </div>

            <!-- Admin badges -->
            <v-divider class="my-4" v-if="profile?.admin?.is_department_head || profile?.admin?.can_approve_escalations" />
            <div class="d-flex flex-wrap gap-2 justify-center"
                 v-if="profile?.admin?.is_department_head || profile?.admin?.can_approve_escalations">
              <v-chip v-if="profile.admin.is_department_head" size="x-small" color="indigo" variant="tonal">
                <v-icon start size="10">mdi-crown</v-icon>Chef de département
              </v-chip>
              <v-chip v-if="profile.admin.can_approve_escalations" size="x-small" color="orange" variant="tonal">
                <v-icon start size="10">mdi-escalator-up</v-icon>Peut approuver les escalades
              </v-chip>
            </div>
          </v-card>

          <!-- Account status card -->
          <v-card class="pa-4" elevation="1" rounded="xl" color="surface-variant">
            <div class="text-subtitle-2 font-weight-bold mb-3">Informations du compte</div>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-body-2 text-medium-emphasis">Statut</span>
              <v-chip size="x-small" :color="profile?.is_active ? 'success' : 'error'" variant="tonal">
                {{ profile?.is_active ? 'Actif' : 'Inactif' }}
              </v-chip>
            </div>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-body-2 text-medium-emphasis">Rôle système</span>
              <span class="text-body-2 font-weight-medium">{{ profile?.role }}</span>
            </div>
            <div class="d-flex align-center justify-space-between" v-if="profile?.password_changed_at">
              <span class="text-body-2 text-medium-emphasis">Mdp changé</span>
              <span class="text-body-2">{{ fDate(profile.password_changed_at) }}</span>
            </div>
          </v-card>
        </v-col>

        <!-- ══════════════════════════════════════════
             RIGHT COLUMN – Edit forms
        ══════════════════════════════════════════ -->
        <v-col cols="12" md="8">
          <!-- ── Tab navigation ── -->
          <v-tabs v-model="activeTab" color="primary" class="mb-4">
            <v-tab value="info">
              <v-icon start>mdi-account-edit</v-icon>Informations
            </v-tab>
            <v-tab value="security">
              <v-icon start>mdi-lock-reset</v-icon>Sécurité
            </v-tab>
          </v-tabs>

          <!-- ────────────────────────────────────────
               TAB 1 – Personal info form
          ──────────────────────────────────────── -->
          <v-window v-model="activeTab">
            <v-window-item value="info">
              <v-card elevation="2" rounded="xl">
                <v-card-title class="pa-6 pb-2">
                  <v-icon class="mr-2" color="primary">mdi-account-details</v-icon>
                  Informations personnelles
                </v-card-title>
                <v-card-text class="pa-6">
                  <v-row dense>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="form.first_name"
                        label="Prénom"
                        prepend-inner-icon="mdi-account"
                        variant="outlined"
                        density="comfortable"
                        clearable
                        :rules="[v => (v?.length || 0) <= 100 || 'Maximum 100 caractères']"
                      />
                    </v-col>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="form.last_name"
                        label="Nom de famille"
                        prepend-inner-icon="mdi-account"
                        variant="outlined"
                        density="comfortable"
                        clearable
                        :rules="[v => (v?.length || 0) <= 100 || 'Maximum 100 caractères']"
                      />
                    </v-col>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="form.email"
                        label="Adresse e-mail"
                        type="email"
                        prepend-inner-icon="mdi-email"
                        variant="outlined"
                        density="comfortable"
                        :rules="[
                          v => !v || /.+@.+\..+/.test(v) || 'Adresse e-mail invalide'
                        ]"
                      />
                    </v-col>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="form.phone"
                        label="Téléphone"
                        prepend-inner-icon="mdi-phone"
                        variant="outlined"
                        density="comfortable"
                        clearable
                        placeholder="+222 00 00 00 00"
                      />
                    </v-col>
                  </v-row>
                </v-card-text>
                <v-card-actions class="pa-6 pt-0">
                  <v-spacer />
                  <v-btn
                    color="primary"
                    variant="elevated"
                    :loading="savingProfile"
                    :disabled="savingProfile"
                    prepend-icon="mdi-content-save"
                    @click="saveProfile"
                  >
                    Enregistrer les modifications
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-window-item>

            <!-- ────────────────────────────────────────
                 TAB 2 – Password form
            ──────────────────────────────────────── -->
            <v-window-item value="security">
              <v-card elevation="2" rounded="xl">
                <v-card-title class="pa-6 pb-2">
                  <v-icon class="mr-2" color="warning">mdi-shield-lock</v-icon>
                  Changer le mot de passe
                </v-card-title>
                <v-card-text class="pa-6">
                  <!-- Password strength indicator -->
                  <v-alert
                    v-if="pwd.new"
                    :type="pwdStrength.type"
                    variant="tonal"
                    density="compact"
                    class="mb-4"
                    :icon="pwdStrength.icon"
                  >
                    Force : <strong>{{ pwdStrength.label }}</strong>
                  </v-alert>

                  <v-text-field
                    v-model="pwd.current"
                    label="Mot de passe actuel"
                    :type="showPwd.current ? 'text' : 'password'"
                    prepend-inner-icon="mdi-lock"
                    :append-inner-icon="showPwd.current ? 'mdi-eye-off' : 'mdi-eye'"
                    variant="outlined"
                    density="comfortable"
                    class="mb-3"
                    @click:append-inner="showPwd.current = !showPwd.current"
                  />
                  <v-text-field
                    v-model="pwd.new"
                    label="Nouveau mot de passe"
                    :type="showPwd.new ? 'text' : 'password'"
                    prepend-inner-icon="mdi-lock-plus"
                    :append-inner-icon="showPwd.new ? 'mdi-eye-off' : 'mdi-eye'"
                    variant="outlined"
                    density="comfortable"
                    class="mb-3"
                    :rules="[
                      v => !v || v.length >= 8 || 'Minimum 8 caractères',
                    ]"
                    @click:append-inner="showPwd.new = !showPwd.new"
                  />
                  <v-text-field
                    v-model="pwd.confirm"
                    label="Confirmer le nouveau mot de passe"
                    :type="showPwd.confirm ? 'text' : 'password'"
                    prepend-inner-icon="mdi-lock-check"
                    :append-inner-icon="showPwd.confirm ? 'mdi-eye-off' : 'mdi-eye'"
                    variant="outlined"
                    density="comfortable"
                    :rules="[
                      v => !v || v === pwd.new || 'Les mots de passe ne correspondent pas'
                    ]"
                    @click:append-inner="showPwd.confirm = !showPwd.confirm"
                  />
                </v-card-text>
                <v-card-actions class="pa-6 pt-0">
                  <v-spacer />
                  <v-btn
                    color="warning"
                    variant="elevated"
                    :loading="savingPwd"
                    :disabled="!pwd.current || !pwd.new || !pwd.confirm || pwd.new !== pwd.confirm || pwd.new.length < 8"
                    prepend-icon="mdi-lock-reset"
                    @click="savePassword"
                  >
                    Modifier le mot de passe
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-window-item>
          </v-window>
        </v-col>
      </v-row>
    </template>

    <!-- ── Snackbar ───────────────────────────────── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      :timeout="4000"
      location="bottom right"
      rounded="lg"
    >
      <v-icon class="mr-2">{{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
      {{ snack.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── State ────────────────────────────────────────
const loading        = ref(true)
const savingProfile  = ref(false)
const savingPwd      = ref(false)
const uploadingPhoto = ref(false)
const activeTab      = ref('info')
const photoInput     = ref(null)

const profile      = ref(null)
// ✅ Ref dédiée pour la photo — mise à jour instantanée sans problème de réactivité
const localPhotoUrl = ref(null)

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
})

const pwd = ref({
  current: '',
  new: '',
  confirm: '',
})

const showPwd = ref({
  current: false,
  new: false,
  confirm: false,
})

const snack = ref({ show: false, text: '', color: 'success' })

// ── Computed ─────────────────────────────────────
const fullName = computed(() => {
  const a = profile.value?.admin
  if (!a) return profile.value?.email ?? ''
  return [a.first_name, a.last_name].filter(Boolean).join(' ').trim()
})

const initials = computed(() => {
  const fn = profile.value?.admin?.first_name?.trim()
  const ln = profile.value?.admin?.last_name?.trim()
  if (fn && ln) return (fn[0] + ln[0]).toUpperCase()
  if (fn) return fn.substring(0, 2).toUpperCase()
  const email = profile.value?.email ?? ''
  return email.substring(0, 2).toUpperCase()
})

// ✅ photoUrl lit localPhotoUrl en priorité (mise à jour immédiate après upload)
const photoUrl = computed(() => {
  // Priorité 1 : URL locale mise à jour après upload
  if (localPhotoUrl.value) return localPhotoUrl.value

  // Priorité 2 : URL venant du profil chargé depuis l'API
  const p = profile.value?.admin?.photo_url ?? profile.value?.admin?.photo_path
  if (!p) return null
  if (p.startsWith('http')) return p

  const base = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')
  return `${base}/storage/${p.replace(/^\//, '')}`
})

const avatarColor = computed(() => {
  const colors = ['indigo', 'teal', 'purple', 'blue', 'cyan', 'green']
  const name = fullName.value || 'admin'
  return colors[name.charCodeAt(0) % colors.length]
})

const roleLabel = computed(() => {
  const map = {
    admin:         'Administrateur',
    super_admin:   'Super Administrateur',
    administrator: 'Administrateur Principal',
  }
  return profile.value?.admin?.role_label
    ?? map[profile.value?.role]
    ?? profile.value?.role
    ?? 'Administrateur'
})

const roleColor = computed(() => {
  const map = { super_admin: 'red', administrator: 'deep-purple', admin: 'primary' }
  return map[profile.value?.role] ?? 'primary'
})

const pwdStrength = computed(() => {
  const p = pwd.value.new
  if (!p) return { label: '', type: 'info', icon: 'mdi-lock' }
  const score = [
    p.length >= 8,
    p.length >= 12,
    /[A-Z]/.test(p),
    /[0-9]/.test(p),
    /[^A-Za-z0-9]/.test(p),
  ].filter(Boolean).length
  if (score <= 2) return { label: 'Faible',   type: 'error',   icon: 'mdi-lock-alert' }
  if (score === 3) return { label: 'Moyen',    type: 'warning', icon: 'mdi-lock-clock' }
  if (score === 4) return { label: 'Fort',     type: 'success', icon: 'mdi-lock-check' }
  return               { label: 'Très fort', type: 'success', icon: 'mdi-shield-check' }
})

// ── Helpers ──────────────────────────────────────
function fDate(raw, short = false) {
  if (!raw) return '—'
  try {
    const d = new Date(raw)
    if (isNaN(d)) return raw
    return short
      ? d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' })
      : d.toLocaleString('fr-FR', {
          day: '2-digit', month: 'short', year: 'numeric',
          hour: '2-digit', minute: '2-digit',
        })
  } catch { return raw }
}

function notify(text, color = 'success') {
  snack.value = { show: true, text, color }
}

// ── API calls ─────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/profile')
    const d   = res.data?.data ?? res.data
    profile.value = d

    // ✅ Initialiser localPhotoUrl depuis l'API
    const rawPath = d?.admin?.photo_url ?? d?.admin?.photo_path
    if (rawPath) {
      if (rawPath.startsWith('http')) {
        localPhotoUrl.value = rawPath
      } else {
        const base = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')
        localPhotoUrl.value = `${base}/storage/${rawPath.replace(/^\//, '')}`
      }
    } else {
      localPhotoUrl.value = null
    }

    form.value = {
      first_name: d?.admin?.first_name ?? '',
      last_name:  d?.admin?.last_name  ?? '',
      email:      d?.admin?.email      ?? d?.email ?? '',
      phone:      d?.admin?.phone      ?? '',
    }
  } catch (err) {
    console.error('[ProfileView] load error', err)
    notify('Impossible de charger le profil.', 'error')
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  savingProfile.value = true
  try {
    await api.put('/admin/profile', form.value)
    notify('Profil mis à jour avec succès.')
    await load()
  } catch (err) {
    const msg = err.response?.data?.message
      ?? Object.values(err.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erreur lors de la mise à jour.'
    notify(msg, 'error')
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  if (pwd.value.new !== pwd.value.confirm) {
    notify('Les mots de passe ne correspondent pas.', 'error')
    return
  }
  savingPwd.value = true
  try {
    await api.put('/admin/profile/password', {
      current_password:          pwd.value.current,
      new_password:              pwd.value.new,
      new_password_confirmation: pwd.value.confirm,
    })
    notify('Mot de passe modifié avec succès.')
    pwd.value = { current: '', new: '', confirm: '' }
  } catch (err) {
    const msg = err.response?.data?.message
      ?? Object.values(err.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erreur lors de la modification du mot de passe.'
    notify(msg, 'error')
  } finally {
    savingPwd.value = false
  }
}

function triggerPhotoUpload() {
  photoInput.value?.click()
}

async function handlePhotoChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  // ✅ Vérification taille
  if (file.size > 3 * 1024 * 1024) {
    notify('La photo ne doit pas dépasser 3 Mo.', 'error')
    return
  }

  // ✅ Aperçu local IMMÉDIAT avant même l'upload (UX optimiste)
  const localPreview = URL.createObjectURL(file)
  localPhotoUrl.value = localPreview

  uploadingPhoto.value = true
  try {
    const formData = new FormData()
    formData.append('photo', file)

    const res = await api.post('/admin/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    // ✅ Remplacer l'aperçu local par l'URL définitive du serveur
    const serverUrl = res.data?.data?.photo_url
    if (serverUrl) {
      // Ajouter un timestamp pour forcer le rechargement et éviter le cache navigateur
      localPhotoUrl.value = serverUrl + '?t=' + Date.now()
    }
    // Libérer l'URL blob locale
    URL.revokeObjectURL(localPreview)

    notify('Photo de profil mise à jour avec succès.')
  } catch (err) {
    // ✅ En cas d'erreur, revenir à l'ancienne photo
    localPhotoUrl.value = profile.value?.admin?.photo_url ?? null
    URL.revokeObjectURL(localPreview)

    const msg = err.response?.data?.errors?.photo?.[0]
      ?? err.response?.data?.message
      ?? 'Erreur lors du téléchargement de la photo.'
    notify(msg, 'error')
  } finally {
    uploadingPhoto.value = false
    if (photoInput.value) photoInput.value.value = ''
  }
}

// ── Lifecycle ────────────────────────────────────
onMounted(load)
</script>


<style scoped>
.profile-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px 16px;
}

.avatar-card {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), .06) 0%, rgba(var(--v-theme-surface), 1) 60%);
}

.avatar-initials {
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: 2px;
}

.photo-edit-btn {
  box-shadow: 0 2px 8px rgba(0,0,0,.25);
}

.quick-info .info-row {
  display: flex;
  align-items: center;
}

.page-header {
  border-bottom: 2px solid rgba(var(--v-theme-primary), .15);
  padding-bottom: 16px;
}
</style>
