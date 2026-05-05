<template>
  <v-app>
    <v-main class="login-bg d-flex align-center justify-center">
      <v-container class="d-flex justify-center" style="min-height:100vh; align-items:center;">
        <v-col cols="12" sm="8" md="5" lg="4">

          <!-- Logo -->
          <div class="text-center mb-6">
            <div class="logo-wrapper mx-auto mb-3">
              <img src="https://www.genspark.ai/api/files/s/UImvfoE3" alt="Logo ISCAE" class="logo-img" />
            </div>
            <h1 class="text-h5 font-weight-bold text-white">ISCAE Réclamations</h1>
            <p class="text-body-2 text-white-darken-1">Système de gestion des réclamations</p>
          </div>

          <v-card class="pa-6 rounded-xl" elevation="12">

            <!-- ═══ ÉTAPE 1 : Formulaire de connexion ══════════════════ -->
            <template v-if="step === 'login'">
              <v-card-title class="text-h6 font-weight-bold text-center mb-4">
                Connexion
              </v-card-title>

              <v-form @submit.prevent="handleLogin">
                <v-text-field
                  v-model="form.login"
                  label="Matricule ou Email"
                  prepend-inner-icon="mdi-account"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                  :disabled="loading"
                  autofocus
                />

                <v-text-field
                  v-model="form.password"
                  label="Mot de passe"
                  prepend-inner-icon="mdi-lock"
                  :append-inner-icon="showPwd ? 'mdi-eye-off' : 'mdi-eye'"
                  :type="showPwd ? 'text' : 'password'"
                  variant="outlined"
                  density="comfortable"
                  class="mb-1"
                  :disabled="loading"
                  @click:append-inner="showPwd = !showPwd"
                />

                <!-- Mot de passe oublié -->
                <div class="text-right mb-4">
                  <router-link
                    :to="{ name: 'forgot-password' }"
                    class="text-primary text-decoration-none text-body-2"
                  >
                    <v-icon size="14" class="mr-1">mdi-lock-question</v-icon>
                    Mot de passe oublié ?
                  </router-link>
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
                  class="mb-4"
                >
                  <v-icon start>mdi-login</v-icon>
                  Se connecter
                </v-btn>

                <div class="text-center text-body-2">
                  Pas encore de compte ?
                  <router-link :to="{ name: 'register' }" class="text-primary font-weight-medium text-decoration-none">
                    Créer mon compte
                  </router-link>
                </div>
              </v-form>
            </template>

            <!-- ═══ ÉTAPE 2 : Vérification OTP nouvel appareil ════════ -->
            <template v-else-if="step === 'device-otp'">
              <div class="text-center mb-4">
                <v-icon size="56" color="warning" class="mb-2">mdi-devices</v-icon>
                <h2 class="text-h6 font-weight-bold">Nouvel appareil détecté</h2>
                <p class="text-body-2 text-medium-emphasis mt-2">
                  Un code de vérification a été envoyé à<br>
                  <strong>{{ maskedEmail }}</strong>
                </p>
              </div>

              <v-form @submit.prevent="handleVerifyDeviceOtp">
                <v-otp-input
                  v-model="form.otp"
                  length="6"
                  variant="outlined"
                  class="mb-3"
                  :disabled="loading"
                />

                <!-- Compte à rebours -->
                <div class="text-center mb-4">
                  <span v-if="resendCooldown > 0" class="text-body-2 text-medium-emphasis">
                    <v-icon size="14">mdi-timer-outline</v-icon>
                    Renvoyer dans <strong>{{ resendCooldown }}s</strong>
                  </span>
                  <v-btn
                    v-else
                    variant="text"
                    size="small"
                    color="primary"
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
                  <v-icon start>mdi-shield-check</v-icon>
                  Vérifier et continuer
                </v-btn>

                <div class="text-center">
                  <v-btn
                    variant="text"
                    size="small"
                    color="grey"
                    @click="step = 'login'; errorMsg = ''"
                  >
                    <v-icon start size="14">mdi-arrow-left</v-icon>
                    Retour
                  </v-btn>
                </div>
              </v-form>
            </template>

          </v-card>
        </v-col>
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
const step           = ref('login')   // 'login' | 'device-otp'
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

    // ── Nouvel appareil → OTP requis ──────────────────────────────
    if (result?.requiresDeviceOtp) {
      pendingUserId.value = result.userId
      maskedEmail.value   = result.maskedEmail
      step.value          = 'device-otp'
      startCooldown()
      return
    }

    // ── 2FA classique ─────────────────────────────────────────────
    if (result?.requires2FA) {
      router.push({ name: 'verify-2fa', query: { userId: result.userId } })
      return
    }

    // ── Connexion directe ─────────────────────────────────────────
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

// ── Redirection selon le rôle ─────────────────────────────────────────
function redirectToDashboard() {
  const role = authStore.user?.role
  if (role === 'admin')        router.push({ name: 'admin.dashboard' })
  else if (role === 'student') router.push({ name: 'student.dashboard' })
  else                         router.push({ name: 'login' })
}

// ── Compte à rebours ──────────────────────────────────────────────────
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
.logo-wrapper {
  width: 72px; height: 72px;
  border-radius: 50%; background: #fff;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.3), 0 6px 20px rgba(0,0,0,0.4);
}
.logo-img { width: 64px; height: 64px; object-fit: contain; border-radius: 50%; }
</style>
