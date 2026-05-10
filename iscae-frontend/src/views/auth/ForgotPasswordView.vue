<template>
  <v-app>
    <v-main class="auth-bg">
      <v-container fluid class="fill-height pa-0">
        <v-row class="fill-height" no-gutters>

          <!-- ═══════════════════════════════════════════════════════
               PANNEAU GAUCHE — identique au login
          ═══════════════════════════════════════════════════════ -->
          <v-col
            cols="12" md="5"
            class="left-panel d-none d-md-flex flex-column align-center justify-center pa-12"
          >
            <div class="left-content text-center">

              <!-- Logo -->
              <div class="logo-glow mx-auto mb-8">
                <img
                  src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
                  alt="ISCAE"
                  class="logo-img"
                />
              </div>

              <h1 class="text-h4 font-weight-black text-white mb-2">ISCAE</h1>
              <p class="text-h6 font-weight-light text-white mb-2" style="opacity:0.85">
                Institut Supérieur de Comptabilité
              </p>
              <p class="text-body-2 text-white mb-10" style="opacity:0.6">
                et d'Administration des Entreprises
              </p>

              <!-- Étapes preview -->
              <div class="steps-preview">
                <div
                  v-for="(s, i) in previewSteps"
                  :key="i"
                  class="preview-step d-flex align-center mb-5"
                  :class="{ 'preview-step--active': step === i + 1 }"
                >
                  <div
                    class="preview-icon mr-4"
                    :style="step === i + 1
                      ? `background:${s.bgActive}; box-shadow:0 4px 16px ${s.shadow}`
                      : ''"
                  >
                    <v-icon :color="step === i + 1 ? 'white' : s.color" size="20">
                      {{ s.icon }}
                    </v-icon>
                  </div>
                  <div class="text-left">
                    <div
                      class="text-body-2 font-weight-bold"
                      :style="step === i + 1 ? 'color:#fff' : 'color:rgba(255,255,255,0.7)'"
                    >
                      {{ s.title }}
                    </div>
                    <div class="text-caption" style="color:rgba(255,255,255,0.45)">
                      {{ s.desc }}
                    </div>
                  </div>
                  <!-- Check si étape complétée -->
                  <v-icon
                    v-if="step > i + 1"
                    size="16"
                    color="#10b981"
                    class="ml-auto"
                  >
                    mdi-check-circle
                  </v-icon>
                </div>
              </div>

              <!-- Lien connexion -->
              <div class="mt-10">
                <router-link :to="{ name: 'login' }" class="back-link-left">
                  <v-icon size="14" class="mr-1">mdi-arrow-left</v-icon>
                  Retour à la connexion
                </router-link>
              </div>

            </div>
          </v-col>

          <!-- ═══════════════════════════════════════════════════════
               PANNEAU DROIT — formulaire
          ═══════════════════════════════════════════════════════ -->
          <v-col
            cols="12" md="7"
            class="right-panel d-flex align-center justify-center pa-4 pa-md-12"
          >
            <v-card class="form-card" elevation="0">

              <!-- Logo mobile uniquement -->
              <div class="d-flex d-md-none justify-center mb-6">
                <div class="logo-mobile">
                  <img
                    src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
                    alt="ISCAE"
                    class="logo-img-sm"
                  />
                </div>
              </div>

              <!-- ── Barre de progression ─────────────────────────── -->
              <div class="progress-bar-container mb-8">
                <div class="d-flex justify-space-between align-center mb-3">
                  <span
                    class="text-caption font-weight-bold text-medium-emphasis"
                    style="text-transform:uppercase;letter-spacing:1px"
                  >
                    Étape {{ step > 3 ? 3 : step }} sur 3
                  </span>
                  <span
                    class="text-caption font-weight-bold"
                    :style="`color:${stepColor}`"
                  >
                    {{ stepPercent }}%
                  </span>
                </div>

                <div class="progress-track">
                  <div
                    class="progress-fill"
                    :style="`width:${stepPercent}%; background:${stepColor}`"
                  />
                </div>

                <div class="d-flex justify-space-between mt-3">
                  <div
                    v-for="(s, i) in stepLabels"
                    :key="i"
                    class="step-indicator"
                    :class="{
                      'done'   : step > i + 1,
                      'current': step === i + 1,
                    }"
                  >
                    <div class="step-bubble">
                      <v-icon v-if="step > i + 1" size="13" color="white">mdi-check</v-icon>
                      <span v-else class="text-caption font-weight-bold">{{ i + 1 }}</span>
                    </div>
                    <span class="step-label d-none d-sm-block">{{ s }}</span>
                  </div>
                </div>
              </div>

              <!-- ── Transitions entre étapes ──────────────────────── -->
              <transition name="slide-fade" mode="out-in">

                <!-- ════ ÉTAPE 1 — Email ════ -->
                <div v-if="step === 1" key="s1">
                  <div class="step-header mb-6">
                    <div class="step-icon-box" style="background:rgba(99,102,241,0.10)">
                      <v-icon color="#6366f1" size="26">mdi-email-search-outline</v-icon>
                    </div>
                    <div>
                      <h2 class="text-h5 font-weight-bold">Mot de passe oublié ?</h2>
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
                      rounded="lg"
                      class="mb-1"
                      :disabled="loading"
                      bg-color="white"
                      autofocus
                      hide-details="auto"
                    />
                    <p class="text-caption text-medium-emphasis mb-5 mt-2">
                      <v-icon size="12" class="mr-1">mdi-information-outline</v-icon>
                      Un code à 6 chiffres sera envoyé à votre adresse email.
                    </p>

                    <!-- Alerte erreur -->
                    <v-alert
                      v-if="errorMsg"
                      :type="errorType"
                      variant="tonal"
                      density="compact"
                      rounded="lg"
                      closable
                      class="mb-5"
                      @click:close="errorMsg = ''"
                    >
                      <div class="d-flex align-center gap-2">
                        <v-icon size="17">{{ errorIcon }}</v-icon>
                        <span>{{ errorMsg }}</span>
                      </div>
                    </v-alert>

                    <v-btn
                      type="submit"
                      block
                      size="large"
                      rounded="lg"
                      :loading="loading"
                      class="submit-btn"
                    >
                      <v-icon start size="18">mdi-send-outline</v-icon>
                      Envoyer le code de vérification
                    </v-btn>
                  </v-form>
                </div>

                <!-- ════ ÉTAPE 2 — OTP ════ -->
                <div v-else-if="step === 2" key="s2">
                  <div class="step-header mb-6">
                    <div class="step-icon-box" style="background:rgba(245,158,11,0.10)">
                      <v-icon color="#f59e0b" size="26">mdi-shield-key-outline</v-icon>
                    </div>
                    <div>
                      <h2 class="text-h5 font-weight-bold">Code de vérification</h2>
                      <p class="text-body-2 text-medium-emphasis mt-1">
                        Code envoyé à <strong>{{ maskedEmail }}</strong>
                      </p>
                    </div>
                  </div>

                  <v-form @submit.prevent="handleVerifyOtp">
                    <label class="field-label">Code OTP (6 chiffres)</label>
                    <v-otp-input
                      v-model="form.otp"
                      length="6"
                      variant="outlined"
                      class="mb-1 otp-input"
                      :disabled="loading"
                      focus-all
                    />

                    <div class="d-flex align-center justify-space-between mb-5 mt-2">
                      <span class="text-caption text-medium-emphasis">
                        <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                        Valide 10 minutes
                      </span>
                      <span v-if="resendCooldown > 0" class="text-caption text-medium-emphasis">
                        Renvoyer dans
                        <strong style="color:#f59e0b">{{ resendCooldown }}s</strong>
                      </span>
                      <v-btn
                        v-else
                        variant="text"
                        size="small"
                        color="primary"
                        density="compact"
                        :disabled="loading"
                        @click="handleSendOtp"
                      >
                        <v-icon start size="13">mdi-refresh</v-icon>
                        Renvoyer
                      </v-btn>
                    </div>

                    <v-alert
                      v-if="errorMsg"
                      type="error"
                      variant="tonal"
                      density="compact"
                      rounded="lg"
                      closable
                      class="mb-5"
                      @click:close="errorMsg = ''"
                    >
                      {{ errorMsg }}
                    </v-alert>

                    <v-btn
                      type="submit"
                      block
                      size="large"
                      rounded="lg"
                      :loading="loading"
                      class="submit-btn submit-btn--amber mb-3"
                    >
                      <v-icon start size="18">mdi-check-circle-outline</v-icon>
                      Vérifier le code
                    </v-btn>

                    <v-btn
                      variant="text"
                      block
                      size="small"
                      color="grey"
                      :disabled="loading"
                      @click="step = 1; errorMsg = ''"
                    >
                      <v-icon start size="14">mdi-arrow-left</v-icon>
                      Changer l'adresse email
                    </v-btn>
                  </v-form>
                </div>

                <!-- ════ ÉTAPE 3 — Nouveau mot de passe ════ -->
                <div v-else-if="step === 3" key="s3">
                  <div class="step-header mb-6">
                    <div class="step-icon-box" style="background:rgba(16,185,129,0.10)">
                      <v-icon color="#10b981" size="26">mdi-lock-reset</v-icon>
                    </div>
                    <div>
                      <h2 class="text-h5 font-weight-bold">Nouveau mot de passe</h2>
                      <p class="text-body-2 text-medium-emphasis mt-1">
                        Choisissez un mot de passe sécurisé
                      </p>
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
                      rounded="lg"
                      class="mb-3"
                      :disabled="loading"
                      bg-color="white"
                      hide-details
                      @click:append-inner="showPwd = !showPwd"
                    />

                    <!-- Jauge de force -->
                    <div class="strength-gauge mb-4">
                      <div class="d-flex justify-space-between align-center mb-2">
                        <span class="text-caption text-medium-emphasis">Force du mot de passe</span>
                        <span
                          class="text-caption font-weight-bold"
                          :style="`color:${strengthHexColor}`"
                        >
                          {{ strengthLabel }}
                        </span>
                      </div>
                      <div class="d-flex gap-1 mb-2">
                        <div
                          v-for="n in 4"
                          :key="n"
                          class="strength-bar"
                          :style="strengthScore >= n
                            ? `background:${strengthHexColor}`
                            : 'background:#e2e8f0'"
                        />
                      </div>
                      <div>
                        <v-chip
                          v-for="(rule, i) in passwordRules"
                          :key="i"
                          :color="rule.met ? 'success' : 'default'"
                          size="x-small"
                          variant="tonal"
                          class="mr-1 mb-1"
                        >
                          <v-icon start size="10">
                            {{ rule.met ? 'mdi-check' : 'mdi-close' }}
                          </v-icon>
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
                      rounded="lg"
                      class="mb-4"
                      :disabled="loading"
                      bg-color="white"
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
                      rounded="lg"
                      closable
                      class="mb-5"
                      @click:close="errorMsg = ''"
                    >
                      {{ errorMsg }}
                    </v-alert>

                    <v-btn
                      type="submit"
                      block
                      size="large"
                      rounded="lg"
                      :loading="loading"
                      :disabled="
                        form.password !== form.passwordConfirm ||
                        form.password.length < 8
                      "
                      class="submit-btn submit-btn--green"
                    >
                      <v-icon start size="18">mdi-lock-reset</v-icon>
                      Réinitialiser le mot de passe
                    </v-btn>
                  </v-form>
                </div>

                <!-- ════ SUCCÈS ════ -->
                <div v-else-if="step === 4" key="s4" class="text-center py-6">
                  <div class="success-circle mx-auto mb-6">
                    <v-icon size="56" color="#10b981">mdi-check-circle</v-icon>
                  </div>
                  <h2 class="text-h5 font-weight-bold mb-3">
                    Mot de passe réinitialisé !
                  </h2>
                  <p class="text-body-2 text-medium-emphasis mb-8">
                    Votre mot de passe a été mis à jour avec succès.<br />
                    Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
                  </p>
                  <v-btn
                    :to="{ name: 'login' }"
                    block
                    size="large"
                    rounded="lg"
                    class="submit-btn"
                  >
                    <v-icon start size="18">mdi-login</v-icon>
                    Se connecter
                  </v-btn>
                </div>

              </transition>

              <!-- Retour connexion (mobile) -->
              <div v-if="step < 4" class="text-center mt-5">
                <router-link :to="{ name: 'login' }" class="back-link">
                  <v-icon size="13" class="mr-1">mdi-arrow-left</v-icon>
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

// ── État ───────────────────────────────────────────────────────────────────
const step           = ref(1)
const loading        = ref(false)
const errorMsg       = ref('')
const errorType      = ref('error')
const showPwd        = ref(false)
const maskedEmail    = ref('')
const userId         = ref(null)
const resetToken     = ref('')
const resendCooldown = ref(0)
let   cooldownTimer  = null

const form = ref({
  email          : '',
  otp            : '',
  password       : '',
  passwordConfirm: '',
})

// ── Données statiques ──────────────────────────────────────────────────────
const previewSteps = [
  {
    icon    : 'mdi-email-outline',
    color   : '#a5b4fc',
    bgActive: '#6366f1',
    shadow  : 'rgba(99,102,241,0.45)',
    title   : 'Vérification email',
    desc    : 'Confirmez votre identité',
  },
  {
    icon    : 'mdi-shield-key-outline',
    color   : '#fcd34d',
    bgActive: '#f59e0b',
    shadow  : 'rgba(245,158,11,0.45)',
    title   : 'Code de sécurité',
    desc    : 'OTP valable 10 minutes',
  },
  {
    icon    : 'mdi-lock-reset',
    color   : '#6ee7b7',
    bgActive: '#10b981',
    shadow  : 'rgba(16,185,129,0.45)',
    title   : 'Nouveau mot de passe',
    desc    : 'Créez un mot de passe sécurisé',
  },
]

const stepLabels = ['Email', 'Vérification', 'Nouveau MDP']

// ── Computed ───────────────────────────────────────────────────────────────
const stepPercent = computed(() => {
  if (step.value === 1) return 10
  if (step.value === 2) return 45
  if (step.value === 3) return 80
  return 100
})

const stepColor = computed(() => {
  const colors = ['#6366f1', '#f59e0b', '#10b981', '#10b981']
  return colors[(step.value - 1)] ?? '#6366f1'
})

const errorIcon = computed(() => {
  if (errorType.value === 'warning') return 'mdi-account-alert-outline'
  if (errorType.value === 'info')    return 'mdi-information-outline'
  return 'mdi-alert-circle-outline'
})

const passwordRules = computed(() => [
  { text: '8 caractères', met: form.value.password.length >= 8          },
  { text: 'Majuscule',    met: /[A-Z]/.test(form.value.password)         },
  { text: 'Chiffre',      met: /[0-9]/.test(form.value.password)         },
  { text: 'Symbole',      met: /[^A-Za-z0-9]/.test(form.value.password)  },
])

const strengthScore = computed(() =>
  passwordRules.value.filter(r => r.met).length
)

const strengthLabel = computed(() =>
  ['', 'Faible', 'Moyen', 'Bon', 'Fort'][strengthScore.value] ?? ''
)

const strengthHexColor = computed(() => {
  const map = {
    1: '#ef4444',
    2: '#f59e0b',
    3: '#3b82f6',
    4: '#10b981',
  }
  return map[strengthScore.value] ?? '#e2e8f0'
})

// ── Actions ────────────────────────────────────────────────────────────────
async function handleSendOtp() {
  errorMsg.value  = ''
  errorType.value = 'error'

  if (!form.value.email) {
    errorMsg.value = 'Veuillez entrer votre adresse email.'
    return
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
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
    if (status === 404 || errCode === 'EMAIL_NOT_FOUND') {
      errorType.value = 'warning'
      errorMsg.value  = "Aucun étudiant trouvé avec ce email. Contactez l'administration."
    } else if (status === 500) {
      errorMsg.value = 'Erreur serveur. Veuillez réessayer plus tard.'
    } else if (status === 422) {
      errorMsg.value = message ?? 'Données invalides. Vérifiez votre email.'
    } else {
      errorMsg.value = message ?? 'Une erreur est survenue. Réessayez.'
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
      user_id : userId.value,
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
      reset_token          : resetToken.value,
      password             : form.value.password,
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
/* ════════════════════════════════════════════════════════════════════
   FOND GLOBAL
════════════════════════════════════════════════════════════════════ */
.auth-bg {
  background: #f8fafc;
  min-height: 100vh;
}

/* ════════════════════════════════════════════════════════════════════
   PANNEAU GAUCHE
════════════════════════════════════════════════════════════════════ */
.left-panel {
  background: linear-gradient(160deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
  min-height: 100vh;
  position: relative;
  overflow: hidden;
}
.left-panel::before {
  content: '';
  position: absolute;
  width: 420px; height: 420px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.12);
  top: -120px; right: -120px;
  pointer-events: none;
}
.left-panel::after {
  content: '';
  position: absolute;
  width: 320px; height: 320px;
  border-radius: 50%;
  background: rgba(167, 139, 250, 0.08);
  bottom: -90px; left: -90px;
  pointer-events: none;
}

/* ── Logo gauche ── */
.logo-glow {
  width: 92px; height: 92px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    0 0 0 8px rgba(255, 255, 255, 0.10),
    0 0 40px rgba(99, 102, 241, 0.40);
}
.logo-img {
  width: 74px; height: 74px;
  object-fit: contain;
  border-radius: 50%;
}

/* ── Preview steps ── */
.steps-preview { width: 100%; max-width: 290px; margin: 0 auto; }

.preview-step {
  position: relative;
  z-index: 1;
  padding: 10px 12px;
  border-radius: 14px;
  transition: background 0.3s ease;
}
.preview-step--active {
  background: rgba(255, 255, 255, 0.08);
}

.preview-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.10);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  transition: background 0.3s ease, box-shadow 0.3s ease;
}

/* ── Lien retour gauche ── */
.back-link-left {
  color: rgba(255, 255, 255, 0.55);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  transition: color 0.2s;
}
.back-link-left:hover { color: rgba(255, 255, 255, 0.90); }

/* ════════════════════════════════════════════════════════════════════
   PANNEAU DROIT
════════════════════════════════════════════════════════════════════ */
.right-panel { background: #f8fafc; }

.form-card {
  width: 100%;
  max-width: 490px;
  background: #ffffff;
  border-radius: 24px;
  padding: 44px 40px;
  box-shadow:
    0 4px 24px rgba(0, 0, 0, 0.06),
    0 1px 4px rgba(0, 0, 0, 0.04);
}

/* ── Logo mobile ── */
.logo-mobile {
  width: 66px; height: 66px;
  border-radius: 50%;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.10);
}
.logo-img-sm {
  width: 54px; height: 54px;
  object-fit: contain;
  border-radius: 50%;
}

/* ════════════════════════════════════════════════════════════════════
   BARRE DE PROGRESSION
════════════════════════════════════════════════════════════════════ */
.progress-track {
  height: 6px;
  border-radius: 99px;
  background: #f1f5f9;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.55s cubic-bezier(.4, 0, .2, 1), background 0.4s ease;
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
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  transition: all 0.3s ease;
}
.step-indicator.done    .step-bubble {
  background: #4338ca;
  color: white;
}
.step-indicator.current .step-bubble {
  background: #6366f1;
  color: white;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.20);
}
.step-label {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
  text-align: center;
}
.step-indicator.done    .step-label,
.step-indicator.current .step-label { color: #475569; font-weight: 600; }

/* ════════════════════════════════════════════════════════════════════
   EN-TÊTE D'ÉTAPE
════════════════════════════════════════════════════════════════════ */
.step-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.step-icon-box {
  width: 54px; height: 54px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ════════════════════════════════════════════════════════════════════
   CHAMPS
════════════════════════════════════════════════════════════════════ */
.field-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

/* ════════════════════════════════════════════════════════════════════
   OTP
════════════════════════════════════════════════════════════════════ */
.otp-input :deep(.v-otp-input__field) {
  font-size: 22px !important;
  font-weight: 700 !important;
  border-radius: 12px !important;
}

/* ════════════════════════════════════════════════════════════════════
   JAUGE DE FORCE
════════════════════════════════════════════════════════════════════ */
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
  transition: background 0.3s ease;
}

/* ════════════════════════════════════════════════════════════════════
   BOUTONS
════════════════════════════════════════════════════════════════════ */
.submit-btn {
  background: linear-gradient(135deg, #4338ca 0%, #4338ca 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  letter-spacing: 0.3px !important;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35) !important;
  transition: all 0.3s ease !important;
}
.submit-btn:hover {
  box-shadow: 0 6px 22px rgba(99, 102, 241, 0.50) !important;
  transform: translateY(-1px);
}

.submit-btn--amber {
  background: linear-gradient(135deg, #4338ca 0%, #4338ca 100%) !important;
  box-shadow: 0 4px 15px rgba(81, 11, 245, 0.35) !important;
}
.submit-btn--amber:hover {
  box-shadow: 0 6px 22px rgba(101, 11, 245, 0.5) !important;
}

.submit-btn--green {
  background: linear-gradient(135deg, #4338ca 0%, #4338ca 100%) !important;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.35) !important;
}
.submit-btn--green:hover {
  box-shadow: 0 6px 22px rgba(16, 185, 129, 0.50) !important;
}

/* ════════════════════════════════════════════════════════════════════
   LIEN RETOUR (mobile)
════════════════════════════════════════════════════════════════════ */
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

/* ════════════════════════════════════════════════════════════════════
   SUCCÈS
════════════════════════════════════════════════════════════════════ */
.success-circle {
  width: 100px; height: 100px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.10);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse-success 2s ease infinite;
}
@keyframes pulse-success {
  0%,100% { box-shadow: 0 0 0 0   rgba(16, 185, 129, 0.30); }
  50%     { box-shadow: 0 0 0 18px rgba(16, 185, 129, 0.00); }
}

/* ════════════════════════════════════════════════════════════════════
   TRANSITIONS
════════════════════════════════════════════════════════════════════ */
.slide-fade-enter-active,
.slide-fade-leave-active { transition: all 0.28s ease; }
.slide-fade-enter-from   { opacity: 0; transform: translateX(22px); }
.slide-fade-leave-to     { opacity: 0; transform: translateX(-22px); }

/* ════════════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════════════ */
@media (max-width: 960px) {
  .form-card { padding: 28px 20px; border-radius: 20px; }
}
</style>
