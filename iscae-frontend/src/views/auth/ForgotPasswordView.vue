<template>
  <v-app>
    <v-main class="forgot-bg">
      <v-container fluid class="fill-height pa-0">
        <v-row class="fill-height" no-gutters>

          <!-- ═══════════════ PANNEAU GAUCHE ═══════════════ -->
          <v-col cols="12" md="5" class="left-panel d-none d-md-flex flex-column align-center justify-center pa-12">
            <div class="left-content text-center">
              <div class="logo-glow mx-auto mb-8">
                <img src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0" alt="ISCAE" class="logo-img" />
              </div>
              <h1 class="text-h4 font-weight-black text-white mb-3">Réinitialisation</h1>
              <p class="text-h6 font-weight-light text-white mb-8" style="opacity:0.85">
                Sécurisez votre accès en quelques étapes simples
              </p>
              <div class="steps-preview">
                <div v-for="(s, i) in previewSteps" :key="i" class="preview-step d-flex align-center mb-5">
                  <div class="preview-icon mr-4">
                    <v-icon :color="s.color" size="22">{{ s.icon }}</v-icon>
                  </div>
                  <div class="text-left">
                    <div class="text-body-2 font-weight-bold text-white">{{ s.title }}</div>
                    <div class="text-caption text-white" style="opacity:0.7">{{ s.desc }}</div>
                  </div>
                </div>
              </div>
            </div>
          </v-col>

          <!-- ═══════════════ PANNEAU DROIT ═══════════════ -->
          <v-col cols="12" md="7" class="right-panel d-flex align-center justify-center pa-4 pa-md-12">
            <v-card class="form-card" elevation="0">

              <!-- Logo mobile -->
              <div class="d-flex d-md-none justify-center mb-6">
                <div class="logo-mobile">
                  <img src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0" alt="ISCAE" class="logo-img-sm" />
                </div>
              </div>

              <!-- ─── Indicateur de progression ─── -->
              <div class="progress-bar-container mb-8">
                <div class="d-flex justify-space-between align-center mb-3">
                  <span class="text-caption font-weight-bold text-medium-emphasis" style="text-transform:uppercase;letter-spacing:1px">
                    Étape {{ step }} sur 3
                  </span>
                  <span class="text-caption font-weight-bold" :style="`color:${stepColor}`">
                    {{ stepPercent }}%
                  </span>
                </div>
                <div class="progress-track">
                  <div class="progress-fill" :style="`width:${stepPercent}%; background:${stepColor}`" />
                </div>
                <div class="d-flex justify-space-between mt-3">
                  <div
                    v-for="(s, i) in stepLabels"
                    :key="i"
                    class="step-indicator"
                    :class="{ 'active': step > i, 'current': step === i + 1 }"
                  >
                    <div class="step-bubble">
                      <v-icon v-if="step > i + 1" size="14" color="white">mdi-check</v-icon>
                      <span v-else class="text-caption font-weight-bold">{{ i + 1 }}</span>
                    </div>
                    <span class="step-label d-none d-sm-block">{{ s }}</span>
                  </div>
                </div>
              </div>

              <transition name="slide-fade" mode="out-in">

                <!-- ═══ ÉTAPE 1 – Email ═══ -->
                <div v-if="step === 1" key="step1">
                  <div class="step-header mb-6">
                    <div class="step-icon-wrapper" style="background:rgba(99,102,241,0.12)">
                      <v-icon color="#6366f1" size="28">mdi-email-search-outline</v-icon>
                    </div>
                    <div>
                      <h2 class="text-h5 font-weight-bold text-grey-darken-3">Votre adresse email</h2>
                      <p class="text-body-2 text-medium-emphasis mt-1">
                        Entrez l'email associé à votre compte ISCAE
                      </p>
                    </div>
                  </div>

                  <v-form @submit.prevent="handleSendOtp">
                    <label class="field-label">Adresse email</label>
                    <v-text-field
                      v-model="form.email"
                      type="email"
                      placeholder="votre.email@exemple.com"
                      prepend-inner-icon="mdi-email-outline"
                      variant="outlined"
                      density="comfortable"
                      class="mb-2"
                      :disabled="loading"
                      bg-color="white"
                      autofocus
                      hide-details="auto"
                    />
                    <p class="text-caption text-medium-emphasis mb-6 mt-1">
                      <v-icon size="12" class="mr-1">mdi-information-outline</v-icon>
                      Un code à 6 chiffres vous sera envoyé par email.
                    </p>

                    <!-- ✅ Alerte avec icône personnalisée selon le type d'erreur -->
                    <v-alert
                      v-if="errorMsg"
                      :type="errorType"
                      variant="tonal"
                      density="compact"
                      class="mb-5"
                      rounded="lg"
                      closable
                      @click:close="errorMsg = ''"
                    >
                      <div class="d-flex align-center gap-2">
                        <v-icon size="18">{{ errorIcon }}</v-icon>
                        <span>{{ errorMsg }}</span>
                      </div>
                    </v-alert>

                    <v-btn
                      type="submit"
                      block
                      size="large"
                      :loading="loading"
                      class="submit-btn mb-4"
                      rounded="lg"
                    >
                      <v-icon start>mdi-send-outline</v-icon>
                      Envoyer le code de vérification
                    </v-btn>
                  </v-form>
                </div>

                <!-- ═══ ÉTAPE 2 – OTP ═══ -->
                <div v-else-if="step === 2" key="step2">
                  <div class="step-header mb-6">
                    <div class="step-icon-wrapper" style="background:rgba(245,158,11,0.12)">
                      <v-icon color="#f59e0b" size="28">mdi-shield-key-outline</v-icon>
                    </div>
                    <div>
                      <h2 class="text-h5 font-weight-bold text-grey-darken-3">Code de vérification</h2>
                      <p class="text-body-2 text-medium-emphasis mt-1">
                        Entrez le code envoyé à <strong>{{ maskedEmail }}</strong>
                      </p>
                    </div>
                  </div>

                  <v-form @submit.prevent="handleVerifyOtp">
                    <label class="field-label">Code OTP (6 chiffres)</label>
                    <v-otp-input
                      v-model="form.otp"
                      length="6"
                      variant="outlined"
                      class="mb-2 otp-input"
                      :disabled="loading"
                      focus-all
                    />

                    <div class="d-flex align-center justify-space-between mb-6 mt-2">
                      <span class="text-caption text-medium-emphasis">
                        <v-icon size="13" class="mr-1">mdi-clock-outline</v-icon>
                        Code valide 10 minutes
                      </span>
                      <div>
                        <span v-if="resendCooldown > 0" class="text-caption text-medium-emphasis">
                          Renvoyer dans <strong class="text-warning">{{ resendCooldown }}s</strong>
                        </span>
                        <v-btn
                          v-else
                          variant="text"
                          size="small"
                          color="primary"
                          :disabled="loading"
                          density="compact"
                          @click="handleSendOtp"
                        >
                          <v-icon start size="13">mdi-refresh</v-icon>Renvoyer
                        </v-btn>
                      </div>
                    </div>

                    <v-alert
                      v-if="errorMsg"
                      type="error"
                      variant="tonal"
                      density="compact"
                      class="mb-5"
                      rounded="lg"
                      closable
                      @click:close="errorMsg = ''"
                    >
                      {{ errorMsg }}
                    </v-alert>

                    <v-btn
                      type="submit"
                      block
                      size="large"
                      :loading="loading"
                      class="submit-btn submit-btn--amber mb-4"
                      rounded="lg"
                    >
                      <v-icon start>mdi-check-circle-outline</v-icon>
                      Vérifier le code
                    </v-btn>

                    <v-btn
                      variant="text"
                      block
                      size="small"
                      color="grey"
                      :disabled="loading"
                      @click="step = 1"
                    >
                      <v-icon start size="15">mdi-arrow-left</v-icon>Changer l'email
                    </v-btn>
                  </v-form>
                </div>

                <!-- ═══ ÉTAPE 3 – Nouveau mot de passe ═══ -->
                <div v-else-if="step === 3" key="step3">
                  <div class="step-header mb-6">
                    <div class="step-icon-wrapper" style="background:rgba(16,185,129,0.12)">
                      <v-icon color="#10b981" size="28">mdi-lock-reset</v-icon>
                    </div>
                    <div>
                      <h2 class="text-h5 font-weight-bold text-grey-darken-3">Nouveau mot de passe</h2>
                      <p class="text-body-2 text-medium-emphasis mt-1">Choisissez un mot de passe sécurisé</p>
                    </div>
                  </div>

                  <v-form @submit.prevent="handleResetPassword">
                    <label class="field-label">Nouveau mot de passe</label>
                    <v-text-field
                      v-model="form.password"
                      :type="showPwd ? 'text' : 'password'"
                      placeholder="Minimum 8 caractères"
                      prepend-inner-icon="mdi-lock-outline"
                      :append-inner-icon="showPwd ? 'mdi-eye-off' : 'mdi-eye'"
                      variant="outlined"
                      density="comfortable"
                      class="mb-3"
                      :disabled="loading"
                      bg-color="white"
                      hide-details
                      @click:append-inner="showPwd = !showPwd"
                    />

                    <!-- Jauge force -->
                    <div class="strength-gauge mb-4">
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-caption text-medium-emphasis">Force du mot de passe</span>
                        <span class="text-caption font-weight-bold" :class="`text-${strengthColor}`">{{ strengthLabel }}</span>
                      </div>
                      <div class="d-flex gap-1">
                        <div
                          v-for="n in 4"
                          :key="n"
                          class="strength-bar"
                          :class="{ 'filled': strengthScore >= n }"
                          :style="strengthScore >= n ? `background:${strengthHexColor}` : ''"
                        />
                      </div>
                      <div class="mt-2">
                        <v-chip
                          v-for="(rule, i) in passwordRules"
                          :key="i"
                          :color="rule.met ? 'success' : 'default'"
                          size="x-small"
                          class="mr-1 mb-1"
                          variant="tonal"
                        >
                          <v-icon start size="10">{{ rule.met ? 'mdi-check' : 'mdi-close' }}</v-icon>
                          {{ rule.text }}
                        </v-chip>
                      </div>
                    </div>

                    <label class="field-label">Confirmer le mot de passe</label>
                    <v-text-field
                      v-model="form.passwordConfirm"
                      :type="showPwd ? 'text' : 'password'"
                      placeholder="Répétez le mot de passe"
                      prepend-inner-icon="mdi-lock-check-outline"
                      variant="outlined"
                      density="comfortable"
                      class="mb-4"
                      :disabled="loading"
                      bg-color="white"
                      :error="!!form.passwordConfirm && form.password !== form.passwordConfirm"
                      :error-messages="form.passwordConfirm && form.password !== form.passwordConfirm ? 'Les mots de passe ne correspondent pas.' : ''"
                    />

                    <v-alert
                      v-if="errorMsg"
                      type="error"
                      variant="tonal"
                      density="compact"
                      class="mb-5"
                      rounded="lg"
                      closable
                      @click:close="errorMsg = ''"
                    >
                      {{ errorMsg }}
                    </v-alert>

                    <v-btn
                      type="submit"
                      block
                      size="large"
                      :loading="loading"
                      :disabled="form.password !== form.passwordConfirm || form.password.length < 8"
                      class="submit-btn submit-btn--green mb-4"
                      rounded="lg"
                    >
                      <v-icon start>mdi-lock-reset</v-icon>
                      Réinitialiser le mot de passe
                    </v-btn>
                  </v-form>
                </div>

                <!-- ═══ SUCCÈS ═══ -->
                <div v-else-if="step === 4" key="step4" class="text-center py-4">
                  <div class="success-animation mx-auto mb-6">
                    <v-icon size="64" color="#10b981">mdi-check-circle</v-icon>
                  </div>
                  <h2 class="text-h5 font-weight-bold text-grey-darken-3 mb-2">
                    Mot de passe réinitialisé !
                  </h2>
                  <p class="text-body-2 text-medium-emphasis mb-8">
                    Votre mot de passe a été mis à jour avec succès.<br />
                    Vous pouvez maintenant vous connecter.
                  </p>
                  <v-btn :to="{ name: 'login' }" block size="large" class="submit-btn" rounded="lg">
                    <v-icon start>mdi-login</v-icon>
                    Se connecter
                  </v-btn>
                </div>

              </transition>

              <!-- Retour connexion -->
              <div v-if="step < 4" class="text-center mt-4">
                <router-link :to="{ name: 'login' }" class="back-link">
                  <v-icon size="14" class="mr-1">mdi-arrow-left</v-icon>
                  Retour à la connexion
                </router-link>
              </div>

            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '@/api/axios'

// ── State ──────────────────────────────────────────────────────────────────
const step           = ref(1)
const loading        = ref(false)
const errorMsg       = ref('')
const errorType      = ref('error')   // ✅ 'error' | 'warning' | 'info'
const showPwd        = ref(false)
const maskedEmail    = ref('')
const userId         = ref(null)
const resetToken     = ref('')
const resendCooldown = ref(0)
let   cooldownTimer  = null

const form = ref({
  email:           '',
  otp:             '',
  password:        '',
  passwordConfirm: '',
})

// ── Données statiques ──────────────────────────────────────────────────────
const previewSteps = [
  { icon: 'mdi-email-outline', color: '#a5b4fc', title: 'Vérification email',  desc: 'Confirmez votre identité par email'  },
  { icon: 'mdi-shield-key',    color: '#fcd34d', title: 'Code de sécurité',     desc: 'Code OTP valable 10 minutes'          },
  { icon: 'mdi-lock-reset',    color: '#6ee7b7', title: 'Nouveau mot de passe', desc: 'Créez un mot de passe sécurisé'       },
]
const stepLabels = ['Email', 'Vérification', 'Nouveau MDP']

// ── Computed ───────────────────────────────────────────────────────────────
const stepPercent = computed(() => Math.round(((step.value - 1) / 3) * 100) || 10)
const stepColor   = computed(() => ['#6366f1', '#f59e0b', '#10b981', '#10b981'][step.value - 1])

// ✅ Icône dynamique selon le type d'erreur
const errorIcon = computed(() => {
  if (errorType.value === 'warning') return 'mdi-account-alert-outline'
  if (errorType.value === 'info')    return 'mdi-information-outline'
  return 'mdi-alert-circle-outline'
})

const passwordRules = computed(() => [
  { text: '8 caractères', met: form.value.password.length >= 8         },
  { text: 'Majuscule',    met: /[A-Z]/.test(form.value.password)        },
  { text: 'Chiffre',      met: /[0-9]/.test(form.value.password)        },
  { text: 'Symbole',      met: /[^A-Za-z0-9]/.test(form.value.password) },
])

const strengthScore = computed(() => passwordRules.value.filter(r => r.met).length)
const strengthLabel = computed(() => ['', 'Faible', 'Moyen', 'Bon', 'Fort'][strengthScore.value] || '')
const strengthColor = computed(() => ['', 'error', 'warning', 'info', 'success'][strengthScore.value] || 'grey')

// ✅ Couleur hex pour les barres de force (évite les CSS vars non résolues)
const strengthHexColor = computed(() => {
  const colors = { error: '#ef4444', warning: '#f59e0b', info: '#3b82f6', success: '#10b981' }
  return colors[strengthColor.value] ?? '#e2e8f0'
})

// ── Actions ────────────────────────────────────────────────────────────────
async function handleSendOtp() {
  errorMsg.value  = ''
  errorType.value = 'error'

  // Validation email vide
  if (!form.value.email) {
    errorMsg.value = 'Veuillez entrer votre adresse email.'
    return
  }

  // Validation format email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.value.email)) {
    errorMsg.value = 'Veuillez entrer une adresse email valide.'
    return
  }

  loading.value = true

  try {
    const res         = await api.post('/auth/forgot-password', { email: form.value.email })
    const data        = res.data?.data ?? {}
    userId.value      = data.user_id
    maskedEmail.value = data.masked_email ?? form.value.email

    if (step.value === 1) step.value = 2
    startCooldown()

  } catch (err) {
    const status  = err.response?.status
    const errCode = err.response?.data?.error_code
    const message = err.response?.data?.message

    // ✅ Message et type personnalisés selon le cas
    if (status === 404 || errCode === 'EMAIL_NOT_FOUND') {
      errorType.value = 'warning'
      errorMsg.value  = 'Aucun étudiant trouvé avec ce email. Contactez l\'administration.'
    } else if (status === 500) {
      errorType.value = 'error'
      errorMsg.value  = 'Erreur serveur. Veuillez réessayer plus tard.'
    } else if (status === 422) {
      errorType.value = 'error'
      errorMsg.value  = message ?? 'Données invalides. Vérifiez votre email.'
    } else {
      errorType.value = 'error'
      errorMsg.value  = message ?? 'Une erreur est survenue. Réessayez.'
    }

  } finally {
    loading.value = false
  }
}

async function handleVerifyOtp() {
  errorMsg.value = ''

  if (!form.value.otp || form.value.otp.length < 6) {
    errorMsg.value = 'Veuillez entrer le code à 6 chiffres.'
    return
  }

  loading.value = true

  try {
    const res        = await api.post('/auth/forgot-password/verify-otp', {
      user_id:  userId.value,
      otp_code: form.value.otp,
    })
    resetToken.value = res.data?.data?.reset_token
    step.value       = 3

  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Code invalide ou expiré.'
    form.value.otp = ''

  } finally {
    loading.value = false
  }
}

async function handleResetPassword() {
  errorMsg.value = ''

  if (form.value.password.length < 8) {
    errorMsg.value = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }
  if (form.value.password !== form.value.passwordConfirm) {
    errorMsg.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  loading.value = true

  try {
    await api.post('/auth/reset-password', {
      reset_token:           resetToken.value,
      password:              form.value.password,
      password_confirmation: form.value.passwordConfirm,
    })
    step.value = 4

  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Erreur lors de la réinitialisation.'

  } finally {
    loading.value = false
  }
}

function startCooldown() {
  clearInterval(cooldownTimer)
  resendCooldown.value = 60
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer)
  }, 1000)
}
</script>

<style scoped>
/* ── Fond ─────────────────────────────────────────────────────────────── */
.forgot-bg {
  background: #f8fafc;
  min-height: 100vh;
}

/* ── Panneau gauche ───────────────────────────────────────────────────── */
.left-panel {
  background: linear-gradient(160deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}
.left-panel::before {
  content: '';
  position: absolute;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.15);
  top: -100px; right: -100px;
}
.left-panel::after {
  content: '';
  position: absolute;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: rgba(167, 139, 250, 0.1);
  bottom: -80px; left: -80px;
}

/* ── Logo ─────────────────────────────────────────────────────────────── */
.logo-glow {
  width: 90px; height: 90px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.95);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 0 8px rgba(255, 255, 255, 0.1), 0 0 40px rgba(99, 102, 241, 0.4);
}
.logo-img {
  width: 72px; height: 72px;
  object-fit: contain;
  border-radius: 50%;
}
.logo-mobile {
  width: 64px; height: 64px;
  border-radius: 50%;
  background: white;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}
.logo-img-sm {
  width: 52px; height: 52px;
  object-fit: contain;
  border-radius: 50%;
}

/* ── Preview steps (gauche) ───────────────────────────────────────────── */
.steps-preview {
  width: 100%; max-width: 280px;
  margin: 0 auto;
}
.preview-step { position: relative; z-index: 1; }
.preview-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.12);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

/* ── Panneau droit ────────────────────────────────────────────────────── */
.right-panel { background: #f8fafc; }
.form-card {
  width: 100%; max-width: 480px;
  background: white;
  border-radius: 24px;
  padding: 40px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 1px 4px rgba(0, 0, 0, 0.04);
}

/* ── Progress bar ─────────────────────────────────────────────────────── */
.progress-track {
  height: 6px;
  border-radius: 99px;
  background: #f1f5f9;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.5s cubic-bezier(.4, 0, .2, 1), background 0.4s ease;
}
.step-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 1;
}
.step-bubble {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #94a3b8;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
  transition: all 0.3s ease;
}
.step-indicator.active .step-bubble  { background: #10b981; color: white; }
.step-indicator.current .step-bubble {
  background: #6366f1;
  color: white;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
}
.step-label {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
  text-align: center;
}
.step-indicator.active .step-label,
.step-indicator.current .step-label { color: #475569; }

/* ── En-tête d'étape ──────────────────────────────────────────────────── */
.step-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.step-icon-wrapper {
  width: 56px; height: 56px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ── Champs ───────────────────────────────────────────────────────────── */
.field-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

/* ── OTP ──────────────────────────────────────────────────────────────── */
.otp-input :deep(.v-otp-input__field) {
  font-size: 22px !important;
  font-weight: 700 !important;
  border-radius: 12px !important;
}

/* ── Jauge de force ───────────────────────────────────────────────────── */
.strength-gauge {
  padding: 12px 14px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}
.strength-bar {
  flex: 1;
  height: 5px;
  border-radius: 99px;
  background: #e2e8f0;
  transition: background 0.3s ease;
}

/* ── Boutons ──────────────────────────────────────────────────────────── */
.submit-btn {
  background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  letter-spacing: 0.3px !important;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4) !important;
  transition: all 0.3s ease !important;
}
.submit-btn:hover {
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5) !important;
  transform: translateY(-1px);
}
.submit-btn--amber {
  background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%) !important;
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4) !important;
}
.submit-btn--amber:hover {
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5) !important;
}
.submit-btn--green {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4) !important;
}
.submit-btn--green:hover {
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5) !important;
}

/* ── Lien retour ──────────────────────────────────────────────────────── */
.back-link {
  color: #6366f1;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  transition: color 0.2s;
}
.back-link:hover { color: #4338ca; }

/* ── Succès ───────────────────────────────────────────────────────────── */
.success-animation {
  width: 100px; height: 100px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.1);
  display: flex; align-items: center; justify-content: center;
  animation: pulse-success 2s ease infinite;
}
@keyframes pulse-success {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.3); }
  50%       { box-shadow: 0 0 0 16px rgba(16, 185, 129, 0); }
}

/* ── Transitions ──────────────────────────────────────────────────────── */
.slide-fade-enter-active,
.slide-fade-leave-active { transition: all 0.3s ease; }
.slide-fade-enter-from   { opacity: 0; transform: translateX(20px); }
.slide-fade-leave-to     { opacity: 0; transform: translateX(-20px); }

/* ── Responsive ───────────────────────────────────────────────────────── */
@media (max-width: 960px) {
  .form-card { padding: 28px 20px; border-radius: 20px; }
}
</style>
