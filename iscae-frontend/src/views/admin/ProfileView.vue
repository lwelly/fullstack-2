<template>
  <div class="profile-page">

    <!-- Loading -->
    <div v-if="loading" class="loading-center">
      <v-progress-circular indeterminate color="accent" size="52" width="3" />
    </div>

    <template v-else>

      <!-- ════ PAGE HEADER ════ -->
      <div class="page-header">
        <div>
          <h1 class="page-h1">Mon Profil Administrateur</h1>
          <p class="page-sub">Gérez vos informations personnelles et la sécurité de votre compte</p>
        </div>
      </div>

      <div class="profile-grid">

        <!-- ════ COLONNE GAUCHE — Carte profil ════ -->
        <div class="left-col">

          <!-- Photo + Infos -->
          <div class="card profile-card">
            <div class="profile-avatar-section">
              <div class="avatar-wrapper">
                <div class="avatar-circle">
                  <img
                    v-if="photoPreview || profile.photo_url"
                    :src="photoPreview || profile.photo_url"
                    class="avatar-img"
                    alt="Photo profil"
                  />
                  <span v-else class="avatar-initials">{{ userInitials }}</span>
                </div>
                <button class="avatar-edit-btn" :disabled="loadingPhoto" @click="$refs.photoInput.click()">
                  <v-icon size="14" color="white">{{ loadingPhoto ? 'mdi-loading mdi-spin' : 'mdi-camera' }}</v-icon>
                </button>
                <input
                  ref="photoInput"
                  type="file"
                  accept="image/jpeg,image/png,image/jpg"
                  style="display:none"
                  @change="handlePhotoUpload"
                />
              </div>
              <h2 class="profile-name">{{ profile.name }}</h2>
              <p class="profile-email">{{ profile.email }}</p>
              <span class="admin-badge">
                <v-icon size="12">mdi-shield-crown</v-icon>
                Administrateur
              </span>
            </div>

            <div class="profile-divider" />

            <!-- Stats -->
            <div class="stats-grid">
              <div class="stat-item stat-item--blue">
                <span class="stat-value">{{ profile.stats?.total_reclamations ?? 0 }}</span>
                <span class="stat-label">Total Récl.</span>
              </div>
              <div class="stat-item stat-item--orange">
                <span class="stat-value">{{ profile.stats?.pending_reclamations ?? 0 }}</span>
                <span class="stat-label">En attente</span>
              </div>
              <div class="stat-item stat-item--green">
                <span class="stat-value">{{ profile.stats?.resolved_reclamations ?? 0 }}</span>
                <span class="stat-label">Résolues</span>
              </div>
              <div class="stat-item stat-item--teal">
                <span class="stat-value">{{ profile.stats?.total_students ?? 0 }}</span>
                <span class="stat-label">Étudiants</span>
              </div>
            </div>

            <div class="profile-divider" />

            <!-- Infos membres -->
            <div class="meta-info">
              <div class="meta-row">
                <v-icon size="14" color="#9CA3AF">mdi-calendar-outline</v-icon>
                <span class="meta-label">Membre depuis</span>
                <span class="meta-value">{{ formatDate(profile.created_at) }}</span>
              </div>
              <div class="meta-row">
                <v-icon size="14" color="#9CA3AF">mdi-shield-check-outline</v-icon>
                <span class="meta-label">Rôle</span>
                <span class="meta-value">Administrateur</span>
              </div>
            </div>
          </div>

        </div>

        <!-- ════ COLONNE DROITE — Formulaires ════ -->
        <div class="right-col">

          <!-- Tabs -->
          <div class="form-tabs">
            <button
              class="form-tab"
              :class="{ 'form-tab--active': activeTab === 'info' }"
              @click="activeTab = 'info'"
            >
              <v-icon size="16">mdi-account-edit-outline</v-icon>
              Informations
            </button>
            <button
              class="form-tab"
              :class="{ 'form-tab--active': activeTab === 'security' }"
              @click="activeTab = 'security'"
            >
              <v-icon size="16">mdi-shield-lock-outline</v-icon>
              Sécurité
            </button>
          </div>

          <!-- Tab : Informations personnelles -->
          <div v-show="activeTab === 'info'" class="card form-card">
            <div class="form-card-header">
              <div class="form-card-title-group">
                <div class="form-title-bar" style="background:#2563EB" />
                <span class="form-card-title">Informations personnelles</span>
              </div>
            </div>

            <div class="form-body">
              <!-- Nom -->
              <div class="field-group">
                <label class="field-label">Nom complet <span class="required">*</span></label>
                <div class="field-input-wrap" :class="{ 'field-input-wrap--error': errors.name }">
                  <v-icon size="16" color="#9CA3AF" class="field-icon">mdi-account-outline</v-icon>
                  <input
                    v-model="form.name"
                    type="text"
                    class="field-input"
                    placeholder="Votre nom complet"
                    @input="errors.name = ''"
                  />
                </div>
                <span v-if="errors.name" class="field-error">{{ errors.name }}</span>
              </div>

              <!-- Email (désactivé) -->
              <div class="field-group">
                <label class="field-label">Adresse e-mail</label>
                <div class="field-input-wrap field-input-wrap--disabled">
                  <v-icon size="16" color="#9CA3AF" class="field-icon">mdi-email-outline</v-icon>
                  <input
                    :value="profile.email"
                    type="email"
                    class="field-input"
                    disabled
                  />
                  <v-icon size="16" color="#9CA3AF" class="field-icon-right">mdi-lock-outline</v-icon>
                </div>
                <span class="field-hint">L'adresse e-mail ne peut pas être modifiée</span>
              </div>

              <div class="form-actions">
                <button
                  class="action-btn action-btn--primary"
                  :disabled="savingProfile"
                  @click="saveProfile"
                >
                  <v-icon size="16">{{ savingProfile ? 'mdi-loading mdi-spin' : 'mdi-content-save-outline' }}</v-icon>
                  {{ savingProfile ? 'Sauvegarde...' : 'Sauvegarder les modifications' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Tab : Sécurité -->
          <div v-show="activeTab === 'security'" class="card form-card">
            <div class="form-card-header">
              <div class="form-card-title-group">
                <div class="form-title-bar" style="background:#DC2626" />
                <span class="form-card-title">Changer le mot de passe</span>
              </div>
            </div>

            <div class="form-body">
              <!-- Mot de passe actuel -->
              <div class="field-group">
                <label class="field-label">Mot de passe actuel <span class="required">*</span></label>
                <div class="field-input-wrap" :class="{ 'field-input-wrap--error': errors.current_password }">
                  <v-icon size="16" color="#9CA3AF" class="field-icon">mdi-lock-outline</v-icon>
                  <input
                    v-model="pwd.current"
                    :type="showPwd.current ? 'text' : 'password'"
                    class="field-input"
                    placeholder="••••••••"
                    @input="errors.current_password = ''"
                  />
                  <button class="pwd-toggle" @click="showPwd.current = !showPwd.current">
                    <v-icon size="16" color="#9CA3AF">{{ showPwd.current ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}</v-icon>
                  </button>
                </div>
                <span v-if="errors.current_password" class="field-error">{{ errors.current_password }}</span>
              </div>

              <!-- Nouveau mot de passe -->
              <div class="field-group">
                <label class="field-label">Nouveau mot de passe <span class="required">*</span></label>
                <div class="field-input-wrap" :class="{ 'field-input-wrap--error': errors.new_password }">
                  <v-icon size="16" color="#9CA3AF" class="field-icon">mdi-lock-plus-outline</v-icon>
                  <input
                    v-model="pwd.new"
                    :type="showPwd.new ? 'text' : 'password'"
                    class="field-input"
                    placeholder="Minimum 8 caractères"
                    @input="errors.new_password = ''"
                  />
                  <button class="pwd-toggle" @click="showPwd.new = !showPwd.new">
                    <v-icon size="16" color="#9CA3AF">{{ showPwd.new ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}</v-icon>
                  </button>
                </div>
                <span v-if="errors.new_password" class="field-error">{{ errors.new_password }}</span>

                <!-- Indicateur de force -->
                <div v-if="pwd.new" class="pwd-strength">
                  <div class="pwd-strength-bars">
                    <div
                      v-for="i in 4"
                      :key="i"
                      class="pwd-bar"
                      :class="{ 'pwd-bar--active': i <= pwdStrength.level }"
                      :style="i <= pwdStrength.level ? { background: pwdStrength.color } : {}"
                    />
                  </div>
                  <span class="pwd-strength-label" :style="{ color: pwdStrength.color }">
                    {{ pwdStrength.label }}
                  </span>
                </div>
              </div>

              <!-- Confirmer -->
              <div class="field-group">
                <label class="field-label">Confirmer le nouveau mot de passe <span class="required">*</span></label>
                <div class="field-input-wrap" :class="{ 'field-input-wrap--error': errors.confirm }">
                  <v-icon size="16" color="#9CA3AF" class="field-icon">mdi-lock-check-outline</v-icon>
                  <input
                    v-model="pwd.confirm"
                    :type="showPwd.confirm ? 'text' : 'password'"
                    class="field-input"
                    placeholder="Répétez le nouveau mot de passe"
                    @input="errors.confirm = ''"
                  />
                  <button class="pwd-toggle" @click="showPwd.confirm = !showPwd.confirm">
                    <v-icon size="16" color="#9CA3AF">{{ showPwd.confirm ? 'mdi-eye-off-outline' : 'mdi-eye-outline' }}</v-icon>
                  </button>
                </div>
                <span v-if="errors.confirm" class="field-error">{{ errors.confirm }}</span>
                <span v-else-if="pwd.confirm && pwd.confirm === pwd.new" class="field-success">
                  <v-icon size="13">mdi-check-circle</v-icon> Les mots de passe correspondent
                </span>
              </div>

              <div class="security-tip">
                <v-icon size="14" color="#D97706">mdi-lightbulb-outline</v-icon>
                <span>Utilisez au moins 8 caractères avec des majuscules, chiffres et symboles.</span>
              </div>

              <div class="form-actions">
                <button
                  class="action-btn action-btn--danger"
                  :disabled="savingPwd"
                  @click="changePassword"
                >
                  <v-icon size="16">{{ savingPwd ? 'mdi-loading mdi-spin' : 'mdi-shield-key-outline' }}</v-icon>
                  {{ savingPwd ? 'Modification...' : 'Modifier le mot de passe' }}
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const toast     = useToast()
const authStore = useAuthStore()

// ── State ──────────────────────────────────────────────────────────────
const loading      = ref(true)
const savingProfile= ref(false)
const savingPwd    = ref(false)
const loadingPhoto = ref(false)
const activeTab    = ref('info')
const photoInput   = ref(null)
const photoPreview = ref(null)

const profile = ref({
  id: null, name: '', email: '', role: 'admin', photo_url: null, created_at: null,
  stats: { total_reclamations: 0, pending_reclamations: 0, resolved_reclamations: 0, total_students: 0 },
})

const form = ref({ name: '' })

const pwd = ref({ current: '', new: '', confirm: '' })
const showPwd = ref({ current: false, new: false, confirm: false })

const errors = ref({
  name: '', current_password: '', new_password: '', confirm: '',
})

// ── Computed ───────────────────────────────────────────────────────────
const userInitials = computed(() => {
  return (profile.value.name ?? '').split(' ')
    .map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'AD'
})

const pwdStrength = computed(() => {
  const p = pwd.value.new
  if (!p) return { level: 0, color: '#E5E7EB', label: '' }
  let score = 0
  if (p.length >= 8)          score++
  if (p.length >= 12)         score++
  if (/[A-Z]/.test(p) && /[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  const map = [
    { level: 0, color: '#E5E7EB', label: '' },
    { level: 1, color: '#DC2626', label: 'Faible' },
    { level: 2, color: '#D97706', label: 'Moyen' },
    { level: 3, color: '#16A34A', label: 'Fort' },
    { level: 4, color: '#0D9488', label: 'Très fort' },
  ]
  return map[Math.min(score, 4)]
})

// ── Methods ────────────────────────────────────────────────────────────
async function loadProfile() {
  loading.value = true
  try {
    const res = await api.get('/admin/profile')
    profile.value      = res.data.data
    form.value.name    = res.data.data.name
    photoPreview.value = res.data.data.photo_url
  } catch {
    toast.error('Erreur lors du chargement du profil.')
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  errors.value.name = ''
  if (!form.value.name || form.value.name.length < 2) {
    errors.value.name = 'Le nom doit contenir au moins 2 caractères.'
    return
  }
  savingProfile.value = true
  try {
    await api.put('/admin/profile', { name: form.value.name })
    profile.value.name = form.value.name
    if (authStore.user) authStore.user.name = form.value.name
    toast.success('Profil mis à jour avec succès !')
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Erreur lors de la sauvegarde.'
    toast.error(msg)
  } finally {
    savingProfile.value = false
  }
}

async function changePassword() {
  errors.value.current_password = ''
  errors.value.new_password     = ''
  errors.value.confirm          = ''

  let valid = true
  if (!pwd.value.current) { errors.value.current_password = 'Mot de passe actuel requis.' ; valid = false }
  if (!pwd.value.new || pwd.value.new.length < 8) { errors.value.new_password = 'Minimum 8 caractères.' ; valid = false }
  if (pwd.value.new !== pwd.value.confirm) { errors.value.confirm = 'Les mots de passe ne correspondent pas.' ; valid = false }
  if (!valid) return

  savingPwd.value = true
  try {
    await api.put('/admin/profile/password', {
      current_password:          pwd.value.current,
      new_password:              pwd.value.new,
      new_password_confirmation: pwd.value.confirm,
    })
    toast.success('Mot de passe modifié avec succès !')
    pwd.value = { current: '', new: '', confirm: '' }
  } catch (err) {
    const apiErrors = err.response?.data?.errors ?? {}
    if (apiErrors.current_password) errors.value.current_password = apiErrors.current_password[0]
    if (apiErrors.new_password)     errors.value.new_password     = apiErrors.new_password[0]
    const msg = err.response?.data?.message ?? 'Erreur lors du changement.'
    toast.error(msg)
  } finally {
    savingPwd.value = false
  }
}

async function handlePhotoUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const allowed = ['image/jpeg','image/png','image/jpg']
  if (!allowed.includes(file.type)) { toast.error('Format non supporté (JPG, PNG uniquement).') ; return }
  if (file.size > 5 * 1024 * 1024)  { toast.error('Fichier trop volumineux (max 5 Mo).') ; return }

  photoPreview.value = URL.createObjectURL(file)
  loadingPhoto.value = true
  try {
    const fd = new FormData()
    fd.append('photo', file)
    const res = await api.post('/admin/profile/photo', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data.success) {
      profile.value.photo_url = res.data.data?.photo_url ?? profile.value.photo_url
      photoPreview.value = res.data.data?.photo_url ?? photoPreview.value
      await authStore.fetchMe()
      toast.success('Photo de profil mise à jour !')
    }
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Erreur lors de l\'upload.')
    photoPreview.value = profile.value.photo_url
  } finally {
    loadingPhoto.value = false
    if (photoInput.value) photoInput.value.value = ''
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
}

onMounted(loadProfile)
</script>

<style scoped>
.profile-page    { padding: 28px 32px; min-height: 100%; }
.loading-center  { display: flex; justify-content: center; align-items: center; height: 60vh; }

/* ── HEADER ────────────────────────────────────────────────────────── */
.page-header   { margin-bottom: 24px; }
.page-h1       { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
.page-sub      { font-size: 13px; color: #6B7280; margin: 3px 0 0; }

/* ── GRID ──────────────────────────────────────────────────────────── */
.profile-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 20px;
  align-items: start;
}

@media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }

/* ── CARD BASE ─────────────────────────────────────────────────────── */
.card {
  background: #FFFFFF;
  border-radius: 12px;
  border: 0.5px solid #E5E7EB;
  overflow: hidden;
}

/* ── PROFILE CARD ──────────────────────────────────────────────────── */
.profile-card { padding: 0; }

.profile-avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 20px 20px;
  text-align: center;
}

.avatar-wrapper { position: relative; margin-bottom: 14px; }

.avatar-circle {
  width: 96px; height: 96px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0F2D5E, #2563EB);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 3px solid #FFFFFF;
  box-shadow: 0 4px 16px rgba(15,45,94,0.2);
}

.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
}

.avatar-initials {
  font-size: 28px;
  font-weight: 700;
  color: white;
  letter-spacing: 1px;
}

.avatar-edit-btn {
  position: absolute;
  bottom: 2px; right: 2px;
  width: 28px; height: 28px;
  border-radius: 50%;
  background: #2563EB;
  border: 2px solid white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}

.avatar-edit-btn:hover:not(:disabled) { background: #1d4ed8; }
.avatar-edit-btn:disabled              { opacity: 0.6; cursor: not-allowed; }

.profile-name  { font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 4px; }
.profile-email { font-size: 13px; color: #6B7280; margin: 0 0 10px; }

.admin-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 600;
  color: #DC2626;
  background: #FEE2E2;
  padding: 4px 10px;
  border-radius: 20px;
}

.profile-divider { height: 1px; background: #F3F4F6; margin: 0 20px; }

/* ── STATS GRID ────────────────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: #F3F4F6;
  margin: 0;
}

.stat-item {
  background: white;
  padding: 14px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  position: relative;
}

.stat-item::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
}

.stat-item--blue::before   { background: #2563EB; }
.stat-item--orange::before { background: #D97706; }
.stat-item--green::before  { background: #16A34A; }
.stat-item--teal::before   { background: #0D9488; }

.stat-value { font-size: 22px; font-weight: 700; color: #111827; }
.stat-label { font-size: 10px; color: #9CA3AF; font-weight: 500; text-align: center; }

/* ── META INFO ─────────────────────────────────────────────────────── */
.meta-info { padding: 14px 20px; }

.meta-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 12px;
}

.meta-row:last-child { margin-bottom: 0; }

.meta-label { color: #9CA3AF; flex: 1; }
.meta-value { color: #374151; font-weight: 500; }

/* ── FORM TABS ─────────────────────────────────────────────────────── */
.form-tabs {
  display: flex;
  gap: 0;
  background: #FFFFFF;
  border: 0.5px solid #E5E7EB;
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 12px;
}

.form-tab {
  flex: 1;
  padding: 8px 12px;
  border: none;
  background: none;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 500;
  color: #6B7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.15s;
  font-family: inherit;
}

.form-tab:hover { color: #111827; }

.form-tab--active {
  background: #F4F6FA;
  color: #0F2D5E;
  font-weight: 600;
}

/* ── FORM CARD ─────────────────────────────────────────────────────── */
.form-card { }

.form-card-header {
  padding: 16px 20px 14px;
  border-bottom: 1px solid #F3F4F6;
}

.form-card-title-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-title-bar {
  width: 4px; height: 16px;
  border-radius: 2px;
}

.form-card-title { font-size: 14px; font-weight: 600; color: #111827; }

.form-body { padding: 20px; display: flex; flex-direction: column; gap: 18px; }

/* ── FIELD ─────────────────────────────────────────────────────────── */
.field-group { display: flex; flex-direction: column; gap: 5px; }

.field-label {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 3px;
}

.required { color: #DC2626; }

.field-input-wrap {
  display: flex;
  align-items: center;
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  padding: 0 12px;
  background: #FAFAFA;
  transition: border-color 0.15s, box-shadow 0.15s;
  gap: 8px;
}

.field-input-wrap:focus-within {
  border-color: #2563EB;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
  background: #FFFFFF;
}

.field-input-wrap--error {
  border-color: #DC2626;
  box-shadow: 0 0 0 3px rgba(220,38,38,0.08);
}

.field-input-wrap--disabled {
  background: #F3F4F6;
  opacity: 0.7;
}

.field-icon       { flex-shrink: 0; }
.field-icon-right { flex-shrink: 0; margin-left: auto; }

.field-input {
  flex: 1;
  border: none;
  background: none;
  outline: none;
  font-size: 13px;
  color: #111827;
  padding: 10px 0;
  font-family: inherit;
}

.field-input::placeholder { color: #9CA3AF; }
.field-input:disabled      { cursor: not-allowed; color: #6B7280; }

.field-error {
  font-size: 11px;
  color: #DC2626;
  display: flex;
  align-items: center;
  gap: 3px;
}

.field-hint    { font-size: 11px; color: #9CA3AF; }

.field-success {
  font-size: 11px;
  color: #16A34A;
  display: flex;
  align-items: center;
  gap: 3px;
}

/* ── PASSWORD STRENGTH ─────────────────────────────────────────────── */
.pwd-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.pwd-strength {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.pwd-strength-bars {
  display: flex;
  gap: 3px;
}

.pwd-bar {
  width: 32px; height: 4px;
  border-radius: 2px;
  background: #E5E7EB;
  transition: background 0.2s;
}

.pwd-bar--active { background: currentColor; }

.pwd-strength-label {
  font-size: 11px;
  font-weight: 600;
}

/* ── SECURITY TIP ──────────────────────────────────────────────────── */
.security-tip {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 14px;
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  border-radius: 8px;
  font-size: 12px;
  color: #92400E;
  line-height: 1.5;
}

/* ── FORM ACTIONS ──────────────────────────────────────────────────── */
.form-actions { padding-top: 4px; }

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.1s;
  font-family: inherit;
}

.action-btn:hover:not(:disabled)  { opacity: 0.88; transform: translateY(-1px); }
.action-btn:active:not(:disabled) { transform: translateY(0); }
.action-btn:disabled               { opacity: 0.5; cursor: not-allowed; }

.action-btn--primary { background: #2563EB; color: white; }
.action-btn--danger  { background: #DC2626; color: white; }

/* ── DARK MODE ─────────────────────────────────────────────────────── */
:global(.v-theme--iscaeDark) .profile-page .page-h1    { color: #F9FAFB; }
:global(.v-theme--iscaeDark) .profile-page .page-sub   { color: #9CA3AF; }
:global(.v-theme--iscaeDark) .card                      { background: #1F2937; border-color: rgba(255,255,255,0.08); }
:global(.v-theme--iscaeDark) .profile-name              { color: #F9FAFB; }
:global(.v-theme--iscaeDark) .profile-email             { color: #9CA3AF; }
:global(.v-theme--iscaeDark) .profile-divider           { background: rgba(255,255,255,0.06); }
:global(.v-theme--iscaeDark) .stat-item                 { background: #1F2937; }
:global(.v-theme--iscaeDark) .stats-grid                { background: rgba(255,255,255,0.04); }
:global(.v-theme--iscaeDark) .stat-value                { color: #F9FAFB; }
:global(.v-theme--iscaeDark) .meta-value                { color: #D1D5DB; }
:global(.v-theme--iscaeDark) .form-tabs                 { background: #1F2937; border-color: rgba(255,255,255,0.08); }
:global(.v-theme--iscaeDark) .form-tab                  { color: #9CA3AF; }
:global(.v-theme--iscaeDark) .form-tab--active          { background: rgba(255,255,255,0.08); color: #F9FAFB; }
:global(.v-theme--iscaeDark) .form-card-header          { border-bottom-color: rgba(255,255,255,0.06); }
:global(.v-theme--iscaeDark) .form-card-title           { color: #F9FAFB; }
:global(.v-theme--iscaeDark) .field-label               { color: #D1D5DB; }
:global(.v-theme--iscaeDark) .field-input-wrap          { background: #111827; border-color: rgba(255,255,255,0.1); }
:global(.v-theme--iscaeDark) .field-input-wrap:focus-within { background: #1F2937; border-color: #3B82F6; }
:global(.v-theme--iscaeDark) .field-input-wrap--disabled    { background: rgba(255,255,255,0.04); }
:global(.v-theme--iscaeDark) .field-input               { color: #F9FAFB; }
:global(.v-theme--iscaeDark) .security-tip              { background: rgba(217,119,6,0.1); border-color: rgba(217,119,6,0.3); color: #FCD34D; }
:global(.v-theme--iscaeDark) .pwd-bar                   { background: rgba(255,255,255,0.1); }
</style>
