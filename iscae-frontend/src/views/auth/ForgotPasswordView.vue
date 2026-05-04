<template>
  <v-app>
    <v-main class="login-bg d-flex align-center justify-center">
      <v-container class="d-flex justify-center" style="min-height:100vh; align-items:center;">
        <v-col cols="12" sm="8" md="5" lg="4">

          <!-- Logo -->
          <div class="text-center mb-6">
            <div class="logo-wrapper mx-auto mb-3">
              <img
                src="https://www.genspark.ai/api/files/s/UImvfoE3"
                alt="Logo ISCAE"
                class="logo-img"
              />
            </div>
            <h1 class="text-h5 font-weight-bold text-white">Mot de passe oublié</h1>
            <p class="text-body-2 text-white-darken-1">Réinitialisation sécurisée</p>
          </div>

          <v-card class="pa-6 rounded-xl" elevation="12">

            <!-- Indicateur d'étapes -->
            <div class="d-flex align-center justify-center mb-5 gap-2">
              <template v-for="n in 3" :key="n">
                <div
                  :class="[
                    'step-dot',
                    step === n ? 'step-dot--active' : '',
                    step > n  ? 'step-dot--done'   : '',
                  ]"
                >
                  <v-icon v-if="step > n" size="12">mdi-check</v-icon>
                  <span v-else>{{ n }}</span>
                </div>
                <div v-if="n < 3" class="step-line" :class="step > n ? 'step-line--done' : ''" />
              </template>
            </div>

            <!-- ═══ ÉTAPE 1 : Email ═══════════════════════════════════════ -->
            <template v-if="step === 1">
              <div class="text-center mb-4">
                <v-icon size="48" color="primary" class="mb-2">mdi-email-lock</v-icon>
                <h2 class="text-h6 font-weight-bold">Entrez votre email</h2>
                <p class="text-body-2 text-medium-emphasis mt-1">
                  Un code à 6 chiffres vous sera envoyé par email.
                </p>
              </div>

              <v-form @submit.prevent="handleSendOtp">
                <v-text-field
                  v-model="form.email"
                  label="Adresse email"
                  type="email"
                  prepend-inner-icon="mdi-email-outline"
                  variant="outlined"
                  density="comfortable"
                  placeholder="votre.email@exemple.com"
                  class="mb-4"
                  :disabled="loading"
                  autofocus
                />

                <v-alert
                  v-if="errorMsg"
                  type="error"
                  variant="tonal"
                  density="compact"
                  class="mb-4"
                  closable
                  @click:close="errorMsg = ''"
                >
                  {{ errorMsg }}
                </v-alert>

                <v-btn
                  type="submit"
                  color="primary"
                  size="large"
                  block
                  :loading="loading"
                  class="mb-3"
                >
                  <v-icon start>mdi-send</v-icon>
                  Envoyer le code
                </v-btn>
              </v-form>
            </template>

            <!-- ═══ ÉTAPE 2 : OTP ════════════════════════════════════════ -->
            <template v-else-if="step === 2">
              <div class="text-center mb-4">
                <v-icon size="48" color="warning" class="mb-2">mdi-shield-key</v-icon>
                <h2 class="text-h6 font-weight-bold">Code de vérification</h2>
                <p class="text-body-2 text-medium-emphasis mt-1">
                  Code envoyé à <strong>{{ maskedEmail }}</strong>
                </p>
              </div>

              <v-form @submit.prevent="handleVerifyOtp">
                <v-otp-input
                  v-model="form.otp"
                  length="6"
                  variant="outlined"
                  class="mb-2"
                  :disabled="loading"
                />

                <!-- Compte à rebours -->
                <div class="text-center mb-4">
                  <span v-if="resendCooldown > 0" class="text-body-2 text-medium-emphasis">
                    <v-icon size="14" class="mr-1">mdi-timer-outline</v-icon>
                    Renvoyer dans <strong>{{ resendCooldown }}s</strong>
                  </span>
                  <v-btn
                    v-else
                    variant="text"
                    size="small"
                    color="primary"
                    :disabled="loading"
                    @click="handleSendOtp"
                  >
                    <v-icon start size="14">mdi-refresh</v-icon>
                    Renvoyer le code
                  </v-btn>
                </div>

                <v-alert
                  v-if="errorMsg"
                  type="error"
                  variant="tonal"
                  density="compact"
                  class="mb-4"
                  closable
                  @click:close="errorMsg = ''"
                >
                  {{ errorMsg }}
                </v-alert>

                <v-btn
                  type="submit"
                  color="primary"
                  size="large"
                  block
                  :loading="loading"
                  class="mb-3"
                >
                  <v-icon start>mdi-check-circle</v-icon>
                  Vérifier le code
                </v-btn>
              </v-form>
            </template>

            <!-- ═══ ÉTAPE 3 : Nouveau mot de passe ══════════════════════ -->
            <template v-else-if="step === 3">
              <div class="text-center mb-4">
                <v-icon size="48" color="success" class="mb-2">mdi-lock-reset</v-icon>
                <h2 class="text-h6 font-weight-bold">Nouveau mot de passe</h2>
                <p class="text-body-2 text-medium-emphasis mt-1">
                  Choisissez un mot de passe sécurisé (min. 8 caractères).
                </p>
              </div>

              <v-form @submit.prevent="handleResetPassword">
                <v-text-field
                  v-model="form.password"
                  label="Nouveau mot de passe"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="showPwd ? 'mdi-eye-off' : 'mdi-eye'"
                  :type="showPwd ? 'text' : 'password'"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                  :disabled="loading"
                  @click:append-inner="showPwd = !showPwd"
                />

                <!-- Jauge de force -->
                <div class="mb-3">
                  <div class="d-flex justify-space-between mb-1">
                    <span class="text-caption text-medium-emphasis">Force du mot de passe</span>
                    <span class="text-caption font-weight-medium" :class="strengthColor">
                      {{ strengthLabel }}
                    </span>
                  </div>
                  <v-progress-linear
                    :model-value="strengthScore * 25"
                    :color="strengthColor"
                    height="4"
                    rounded
                  />
                </div>

                <v-text-field
                  v-model="form.passwordConfirm"
                  label="Confirmer le mot de passe"
                  prepend-inner-icon="mdi-lock-check-outline"
                  :type="showPwd ? 'text' : 'password'"
                  variant="outlined"
                  density="comfortable"
                  class="mb-4"
                  :disabled="loading"
                  :error="!!form.passwordConfirm && form.password !== form.passwordConfirm"
                  :error-messages="
                    form.passwordConfirm && form.password !== form.passwordConfirm
                      ? 'Les mots de passe ne correspondent pas.'
                      : ''
                  "
                />

                <v-alert
                  v-if="errorMsg"
                  type="error"
                  variant="tonal"
                  density="compact"
                  class="mb-4"
                  closable
                  @click:close="errorMsg = ''"
                >
                  {{ errorMsg }}
                </v-alert>

                <v-btn
                  type="submit"
                  color="success"
                  size="large"
                  block
                  :loading="loading"
                  :disabled="form.password !== form.passwordConfirm || form.password.length < 8"
                  class="mb-3"
                >
                  <v-icon start>mdi-lock-reset</v-icon>
                  Réinitialiser
                </v-btn>
              </v-form>
            </template>

            <!-- ═══ ÉTAPE 4 : Succès ══════════════════════════════════ -->
            <template v-else-if="step === 4">
              <div class="text-center py-6">
                <v-icon size="88" color="success" class="mb-4">mdi-check-circle</v-icon>
                <h3 class="text-h6 font-weight-bold mb-2">Mot de passe réinitialisé !</h3>
                <p class="text-body-2 text-medium-emphasis mb-6">
                  Votre mot de passe a été mis à jour. Vous pouvez maintenant vous connecter.
                </p>
                <v-btn
                  color="primary"
                  size="large"
                  block
                  :to="{ name: 'login' }"
                >
                  <v-icon start>mdi-login</v-icon>
                  Aller à la connexion
                </v-btn>
              </div>
            </template>

            <!-- Lien retour connexion -->
            <v-divider v-if="step < 4" class="my-3" />
            <div v-if="step < 4" class="text-center">
              <router-link
                :to="{ name: 'login' }"
                class="text-body-2 text-primary text-decoration-none"
              >
                <v-icon size="14" class="mr-1">mdi-arrow-left</v-icon>
                Retour à la connexion
              </router-link>
            </div>

          </v-card>
        </v-col>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '@/api/axios'

// ── State ───────────────────────────────────────────────────────────────
const step           = ref(1)
const loading        = ref(false)
const errorMsg       = ref('')
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

// ── Force du mot de passe ────────────────────────────────────────────────
const strengthScore = computed(() => {
  const p = form.value.password
  if (!p) return 0
  let score = 0
  if (p.length >= 8)               score++
  if (p.length >= 12)              score++
  if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++
  if (/[0-9]/.test(p))             score++
  if (/[^A-Za-z0-9]/.test(p))     score++
  return Math.min(score, 4)
})

const strengthLabel = computed(() => {
  return ['', 'Faible', 'Moyen', 'Bon', 'Fort'][strengthScore.value] ?? ''
})

const strengthColor = computed(() => {
  return ['', 'error', 'warning', 'info', 'success'][strengthScore.value] ?? 'grey'
})

// ── Étape 1 : Envoyer OTP par email ────────────────────────────────────
async function handleSendOtp() {
  errorMsg.value = ''
  if (!form.value.email) {
    errorMsg.value = 'Veuillez entrer votre adresse email.'
    return
  }

  loading.value = true
  try {
    const res  = await api.post('/auth/forgot-password', { email: form.value.email })
    const data = res.data?.data ?? {}

    userId.value      = data.user_id
    maskedEmail.value = data.masked_email ?? form.value.email

    if (step.value === 1) step.value = 2
    startCooldown()
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ?? 'Erreur lors de l\'envoi du code. Vérifiez votre email.'
  } finally {
    loading.value = false
  }
}

// ── Étape 2 : Vérifier OTP ──────────────────────────────────────────────
async function handleVerifyOtp() {
  errorMsg.value = ''
  if (!form.value.otp || form.value.otp.length < 6) {
    errorMsg.value = 'Veuillez entrer le code à 6 chiffres.'
    return
  }

  loading.value = true
  try {
    const res    = await api.post('/auth/forgot-password/verify-otp', {
      user_id:  userId.value,
      otp_code: form.value.otp,
    })
    resetToken.value = res.data?.data?.reset_token
    step.value = 3
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ?? 'Code invalide ou expiré. Réessayez.'
    form.value.otp = ''
  } finally {
    loading.value = false
  }
}

// ── Étape 3 : Réinitialiser le mot de passe ─────────────────────────────
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
    errorMsg.value =
      err.response?.data?.message ?? 'Erreur lors de la réinitialisation.'
  } finally {
    loading.value = false
  }
}

// ── Cooldown renvoi ─────────────────────────────────────────────────────
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
.login-bg {
  background: linear-gradient(135deg, #1a237e 0%, #283593 40%, #3949ab 100%);
  min-height: 100vh;
}

/* Logo */
.logo-wrapper {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: #fff;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.3), 0 6px 20px rgba(0,0,0,0.4);
}
.logo-img { width: 64px; height: 64px; object-fit: contain; border-radius: 50%; }

/* Step dots */
.step-dot {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: #e0e0e0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 600;
  color: #9e9e9e;
  transition: all .3s ease;
}
.step-dot--active {
  background: #1565c0;
  color: #fff;
  box-shadow: 0 0 0 3px rgba(21,101,192,0.25);
}
.step-dot--done {
  background: #2e7d32;
  color: #fff;
}
.step-line {
  flex: 1; height: 2px;
  background: #e0e0e0;
  transition: background .3s ease;
  max-width: 40px;
}
.step-line--done { background: #2e7d32; }
</style>
