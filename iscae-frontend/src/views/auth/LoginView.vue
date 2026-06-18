<template>
  <v-app>
    <v-main class="login-bg">
      <v-container fluid class="fill-height pa-0">
        <v-row no-gutters class="fill-height">

          <v-col
            cols="12" md="5"
            class="branding-panel d-none d-md-flex flex-column align-center justify-center"
          >
            <div class="brand-logo-wrapper mb-6">
              <img
                src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
                alt="Logo ISCAE"
                class="brand-logo"
              />
            </div>

            <h1 class="brand-title">ISCAE</h1>
            <p class="brand-subtitle">Institut Supérieur de Comptabilité<br>et d'Administration des Entreprises</p>

            <div class="brand-divider my-6" />

            <p class="brand-desc text-center px-8">
              Plateforme de gestion des réclamations étudiantes.
              Soumettez, suivez et résolvez vos réclamations en toute simplicité.
            </p>

            <div class="deco-circle deco-circle-1" />
            <div class="deco-circle deco-circle-2" />
            <div class="deco-circle deco-circle-3" />
          </v-col>

          <v-col
            cols="12" md="7"
            class="form-panel d-flex align-center justify-center"
          >
            <div class="form-wrapper">

              <div class="d-flex d-md-none flex-column align-center mb-8">
                <div class="mobile-logo-wrapper mb-3">
                  <img
                    src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
                    alt="Logo ISCAE"
                    class="mobile-logo"
                  />
                </div>
                <h2 class="text-h5 font-weight-bold text-white">ISCAE Réclamations</h2>
              </div>

              <template v-if="step === 'login'">
                <div class="form-header mb-8">
                  <div class="form-icon-wrapper mb-4">
                    <v-icon size="28" color="white">mdi-shield-lock</v-icon>
                  </div>
                  <h2 class="form-title">Bienvenue</h2>
                  <p class="form-subtitle">Connectez-vous à votre espace</p>
                </div>

                <v-form @submit.prevent="handleLogin">

                  <div class="field-wrapper mb-4">
                    <label class="field-label">Matricule ou Email</label>
                    <v-text-field
                      v-model="form.login"
                      placeholder="Entrez votre matricule ou email"
                      prepend-inner-icon="mdi-account-outline"
                      variant="outlined"
                      density="comfortable"
                      color="#0b8243"
                      :disabled="loading"
                      autofocus
                      class="custom-field"
                      hide-details="auto"
                    />
                  </div>

                  <div class="field-wrapper mb-2">
                    <label class="field-label">Mot de passe</label>
                    <v-text-field
                      v-model="form.password"
                      placeholder="Entrez votre mot de passe"
                      prepend-inner-icon="mdi-lock-outline"
                      :append-inner-icon="showPwd ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                      :type="showPwd ? 'text' : 'password'"
                      variant="outlined"
                      density="comfortable"
                      color="#0b8243"
                      :disabled="loading"
                      class="custom-field"
                      hide-details="auto"
                      @click:append-inner="showPwd = !showPwd"
                    />
                  </div>

                  <div class="d-flex justify-end mb-6">
                    <router-link
                      :to="{ name: 'forgot-password' }"
                      class="forgot-link"
                    >
                      <v-icon size="13" class="mr-1">mdi-lock-question</v-icon>
                      Mot de passe oublié ?
                    </router-link>
                  </div>

                  <v-alert
                    v-if="errorMsg"
                    type="error"
                    variant="tonal"
                    density="compact"
                    rounded="lg"
                    class="mb-5"
                    closable
                    @click:close="errorMsg = ''"
                  >
                    <template #prepend>
                      <v-icon size="18">mdi-alert-circle-outline</v-icon>
                    </template>
                    {{ errorMsg }}
                  </v-alert>

                  <v-btn
                    type="submit"
                    size="large"
                    block
                    :loading="loading"
                    class="login-btn mb-5"
                    elevation="0"
                  >
                    <v-icon start>mdi-login-variant</v-icon>
                    Se connecter
                  </v-btn>

                  <div class="d-flex align-center mb-5">
                    <div class="flex-1 divider-line" />
                    <span class="divider-text mx-3">ou</span>
                    <div class="flex-1 divider-line" />
                  </div>

                  <div class="text-center">
                    <span class="register-text">Pas encore de compte ?</span>
                    <router-link :to="{ name: 'register' }" class="register-link ml-1">
                      Créer mon compte
                    </router-link>
                  </div>
                </v-form>
              </template>

              <template v-else-if="step === 'device-otp'">
                <div class="form-header mb-8">
                  <div class="form-icon-wrapper form-icon-warning mb-4">
                    <v-icon size="28" color="white">mdi-devices</v-icon>
                  </div>
                  <h2 class="form-title">Nouvel appareil</h2>
                  <p class="form-subtitle">
                    Code envoyé à <strong class="text-primary-brand">{{ maskedEmail }}</strong>
                  </p>
                </div>

                <v-alert
                  type="warning"
                  variant="tonal"
                  density="compact"
                  rounded="lg"
                  class="mb-6"
                >
                  <template #prepend>
                    <v-icon size="18">mdi-shield-alert-outline</v-icon>
                  </template>
                  Connexion depuis un nouvel appareil détectée. Vérifiez votre identité.
                </v-alert>

                <v-form @submit.prevent="handleVerifyDeviceOtp">

                  <div class="field-wrapper mb-4">
                    <label class="field-label text-center d-block mb-3">
                      Code à 6 chiffres
                    </label>
                    <v-otp-input
                      v-model="form.otp"
                      length="6"
                      variant="outlined"
                      color="#0b8243"
                      :disabled="loading"
                      class="otp-input"
                    />
                  </div>

                  <div class="text-center mb-6">
                    <template v-if="resendCooldown > 0">
                      <div class="cooldown-badge">
                        <v-icon size="14" class="mr-1">mdi-timer-outline</v-icon>
                        Renvoyer dans <strong>{{ resendCooldown }}s</strong>
                      </div>
                    </template>
                    <v-btn
                      v-else
                      variant="text"
                      size="small"
                      class="resend-btn"
                      :disabled="loading"
                      @click="handleResendOtp"
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
                    rounded="lg"
                    class="mb-5"
                    closable
                    @click:close="errorMsg = ''"
                  >
                    {{ errorMsg }}
                  </v-alert>

                  <v-btn
                    type="submit"
                    size="large"
                    block
                    :loading="loading"
                    class="login-btn mb-4"
                    elevation="0"
                  >
                    <v-icon start>mdi-shield-check</v-icon>
                    Vérifier et continuer
                  </v-btn>

                  <div class="text-center">
                    <v-btn
                      variant="text"
                      size="small"
                      class="back-btn"
                      @click="step = 'login'; errorMsg = ''; form.otp = ''"
                    >
                      <v-icon start size="14">mdi-arrow-left</v-icon>
                      Retour à la connexion
                    </v-btn>
                  </div>
                </v-form>
              </template>

              <div class="form-footer mt-8">
                <span>© {{ new Date().getFullYear() }} ISCAE — Tous droits réservés</span>
              </div>

            </div>
          </v-col>

        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

// ── State ──────────────────────────────────────────────────────────────
const step           = ref('login')
const loading        = ref(false)
const showPwd        = ref(false)
const errorMsg       = ref('')
const maskedEmail    = ref('')
const pendingUserId  = ref(null)
const resendCooldown = ref(0)
let   cooldownTimer  = null

const form = ref({
  login:    '',
  password: '',
  otp:      '',
})

// ── Connexion ──────────────────────────────────────────────────────────
async function handleLogin() {
  errorMsg.value = ''
  if (!form.value.login || !form.value.password) {
    errorMsg.value = 'Veuillez remplir tous les champs.'
    return
  }
  loading.value = true
  try {
    const result = await authStore.login({
      login:    form.value.login,
      password: form.value.password,
    })

    if (result?.requiresDeviceOtp) {
      pendingUserId.value = result.userId
      maskedEmail.value   = result.maskedEmail
      step.value          = 'device-otp'
      startCooldown()
      return
    }

    if (result?.requires2FA) {
      router.push({ name: 'verify-2fa', query: { userId: result.user_id } })
      return
    }

    redirectToDashboard()
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ?? 'Identifiants incorrects. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}

// ── Vérification OTP appareil ─────────────────────────────────────────
async function handleVerifyDeviceOtp() {
  errorMsg.value = ''
  if (!form.value.otp || form.value.otp.length < 6) {
    errorMsg.value = 'Veuillez entrer le code à 6 chiffres.'
    return
  }
  loading.value = true
  try {
    await authStore.verifyDeviceOtp({
      userId:  pendingUserId.value,
      otpCode: form.value.otp,
    })
    redirectToDashboard()
  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Code incorrect ou expiré.'
    form.value.otp = ''
  } finally {
    loading.value = false
  }
}

// ── Renvoyer OTP ──────────────────────────────────────────────────────
async function handleResendOtp() {
  try {
    await authStore.resendDeviceOtp(pendingUserId.value)
    startCooldown()
  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Erreur lors du renvoi.'
  }
}

// ── Redirection ───────────────────────────────────────────────────────
function redirectToDashboard() {
  const role = authStore.user?.role
  if (role === 'admin')        router.push({ name: 'admin.dashboard' })
  else if (role === 'student') router.push({ name: 'student.dashboard' })
  else                         router.push({ name: 'login' })
}

// ── Cooldown ──────────────────────────────────────────────────────────
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
/* ══ FOND GÉNÉRAL ═══════════════════════════════════════════════════ */
.login-bg {
  background: #f4f7f6;
  min-height: 100vh;
}

/* ══ PANNEAU GAUCHE — BRANDING ══════════════════════════════════════ */
.branding-panel {
  background: linear-gradient(160deg, #0b8243 0%, #4a7479 60%, #79c2c4 100%);
  position: relative;
  overflow: hidden;
  min-height: 100vh;
  padding: 48px 40px;
}

.brand-logo-wrapper {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  z-index: 1;
}

.brand-logo {
  width: 88px;
  height: 88px;
  object-fit: contain;
  border-radius: 50%;
}

.brand-title {
  font-size: 2.8rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 4px;
  text-shadow: 0 2px 16px rgba(0, 0, 0, 0.15);
  z-index: 1;
}

.brand-subtitle {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.85);
  text-align: center;
  line-height: 1.6;
  z-index: 1;
}

.brand-divider {
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
  border-radius: 2px;
  z-index: 1;
}

.brand-desc {
  font-size: 0.88rem;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.7;
  max-width: 340px;
  z-index: 1;
}

/* Cercles décoratifs */
.deco-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.07);
  pointer-events: none;
}

.deco-circle-1 { width: 300px; height: 300px; top: -80px; right: -80px; }
.deco-circle-2 { width: 200px; height: 200px; bottom: 60px; left: -60px; }
.deco-circle-3 { width: 120px; height: 120px; bottom: 200px; right: 20px; }

/* ══ PANNEAU DROIT — FORMULAIRE ═════════════════════════════════════ */
.form-panel {
  background: #f4f7f6;
  min-height: 100vh;
  padding: 32px 24px;
}

.form-wrapper {
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 36px;
  box-shadow:
    0 4px 6px rgba(0, 0, 0, 0.02),
    0 12px 40px rgba(11, 130, 67, 0.06),
    0 0 0 1px rgba(0, 0, 0, 0.03);
}

/* En-tête formulaire */
.form-header { text-align: center; }

.form-icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: #0b8243;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: 0 8px 24px rgba(11, 130, 67, 0.25);
}

.form-icon-warning {
  background: #f59e0b !important;
  box-shadow: 0 8px 24px rgba(245, 158, 11, 0.25) !important;
}

.form-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #2c3e50;
  margin-top: 12px;
  margin-bottom: 4px;
}

.form-subtitle {
  font-size: 0.9rem;
  color: #7f8c8d;
}

/* Labels champs */
.field-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #34495e;
  display: block;
  margin-bottom: 6px;
  letter-spacing: 0.3px;
}

/* Modification des champs customs Vuetify pour éviter le double outline */
.custom-field :deep(.v-field) {
  border-radius: 12px !important;
  background: #f8faf9 !important;
}

/* Lien mot de passe oublié */
.forgot-link {
  font-size: 0.82rem;
  font-weight: 600;
  color: #4a7479;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: color 0.2s ease;
}
.forgot-link:hover { color: #0b8243; }

/* Texte de mise en valeur */
.text-primary-brand { color: #0b8243; }

/* Bouton principal */
.login-btn {
  background: #0b8243 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  font-size: 0.95rem !important;
  letter-spacing: 0.5px !important;
  border-radius: 12px !important;
  height: 52px !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease !important;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-1px) !important;
  box-shadow: 0 6px 20px rgba(11, 130, 67, 0.3) !important;
}

/* Séparateur */
.divider-line { height: 1px; background: #e0e6e4; }
.divider-text { font-size: 0.78rem; color: #95a5a6; font-weight: 500; }

/* Inscription */
.register-text { font-size: 0.875rem; color: #7f8c8d; }
.register-link {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0b8243;
  text-decoration: none;
  transition: color 0.2s ease;
}
.register-link:hover { color: #4a7479; }

/* OTP Saisie */
.otp-input :deep(.v-otp-input__field) {
  border-radius: 12px !important;
  background: #f8faf9 !important;
  color: #2c3e50 !important;
}

.otp-input :deep(input) {
  color: #2c3e50 !important;
  caret-color: #0b8243 !important;
}

/* Badge cooldown */
.cooldown-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.82rem;
  color: #7f8c8d;
  background: #ebf3ef;
  border: 1px solid #d0dfd7;
  border-radius: 20px;
  padding: 4px 14px;
}

.resend-btn {
  font-size: 0.82rem !important;
  color: #0b8243 !important;
  font-weight: 700 !important;
}

/* Bouton retour */
.back-btn {
  font-size: 0.82rem !important;
  color: #7f8c8d !important;
  font-weight: 600 !important;
}

/* Logo mobile */
.mobile-logo-wrapper {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  border: 2px solid rgba(255,255,255,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.mobile-logo { width: 64px; height: 64px; object-fit: contain; border-radius: 50%; }
.form-footer { text-align: center; font-size: 0.75rem; color: #95a5a6; }

/* Mobile Version Blueprint */
@media (max-width: 959px) {
  .form-panel {
    background: linear-gradient(160deg, #0b8243 0%, #4a7479 60%, #79c2c4 100%) !important;
    padding: 40px 16px;
  }

  .form-wrapper {
    max-width: 420px;
    padding: 32px 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  }
}
</style>