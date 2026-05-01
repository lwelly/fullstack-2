<template>
  <v-app>
    <v-main style="background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%); min-height: 100vh;">
      <v-container fluid class="fill-height">
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="5" lg="4">

            <!-- Header -->
            <div class="text-center mb-8">
              <v-avatar size="80" color="white" class="mb-4 elevation-4">
                <v-icon size="48" color="primary">mdi-shield-key</v-icon>
              </v-avatar>
              <h1 class="text-h4 font-weight-bold text-white">Vérification</h1>
              <p class="text-subtitle-1 text-white mt-1" style="opacity:0.9">
                Authentification à deux facteurs
              </p>
            </div>

            <!-- Card OTP -->
            <v-card rounded="xl" elevation="8" class="pa-6">
              <v-card-title class="text-h5 font-weight-bold text-primary text-center mb-2">
                Code OTP
              </v-card-title>

              <v-card-text>

                <!-- Info email -->
                <v-alert
                  v-if="email"
                  type="info"
                  variant="tonal"
                  density="compact"
                  rounded="lg"
                  class="mb-5"
                  icon="mdi-email-outline"
                >
                  Code envoyé à <strong>{{ maskedEmail }}</strong>
                </v-alert>

                <!-- Alerte dev OTP -->
                <v-alert
                  v-if="devOtp"
                  type="warning"
                  variant="tonal"
                  density="compact"
                  rounded="lg"
                  class="mb-5"
                  icon="mdi-bug"
                >
                  <strong>[DEV]</strong> Code OTP : <strong>{{ devOtp }}</strong>
                </v-alert>

                <v-form ref="formRef" @submit.prevent="handleVerify">

                  <!-- Champ OTP — 6 cases séparées -->
                  <div class="otp-wrapper mb-5">
                    <v-text-field
                      v-for="(_, i) in otpDigits"
                      :key="i"
                      :ref="el => inputRefs[i] = el"
                      v-model="otpDigits[i]"
                      variant="outlined"
                      density="compact"
                      maxlength="1"
                      class="otp-input"
                      hide-details
                      inputmode="numeric"
                      @input="onDigitInput(i)"
                      @keydown.backspace="onBackspace(i)"
                      @paste.prevent="onPaste($event)"
                    />
                  </div>

                  <!-- Erreur -->
                  <v-alert
                    v-if="error"
                    type="error"
                    variant="tonal"
                    rounded="lg"
                    class="mb-4"
                    density="compact"
                  >
                    {{ error }}
                  </v-alert>

                  <!-- Bouton vérifier -->
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                    :disabled="otpValue.length < 6"
                    class="mb-3"
                  >
                    <v-icon start>mdi-shield-check</v-icon>
                    Vérifier le code
                  </v-btn>

                  <!-- Renvoi OTP -->
                  <v-btn
                    variant="text"
                    color="primary"
                    block
                    :disabled="resendCooldown > 0 || loading"
                    @click="resendOtp"
                  >
                    <v-icon start>mdi-refresh</v-icon>
                    {{ resendCooldown > 0 ? `Renvoyer dans ${resendCooldown}s` : 'Renvoyer le code' }}
                  </v-btn>

                </v-form>
              </v-card-text>

              <v-divider class="mx-6" />

              <v-card-actions class="justify-center pa-4">
                <v-btn
                  variant="text"
                  color="grey"
                  size="small"
                  :to="{ name: 'login' }"
                >
                  <v-icon start>mdi-arrow-left</v-icon>
                  Retour à la connexion
                </v-btn>
              </v-card-actions>
            </v-card>

            <div class="text-center mt-6">
              <p class="text-caption text-white" style="opacity:0.7">
                © 2026 ISCAE — Tous droits réservés
              </p>
            </div>

          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

// ── Données récupérées depuis sessionStorage ──────────────────────────────
const userId = ref(sessionStorage.getItem('2fa_user_id')    ?? null)
const email  = ref(sessionStorage.getItem('2fa_email')      ?? '')
const devOtp = ref(sessionStorage.getItem('2fa_otp_dev')    ?? '')

// ── État ──────────────────────────────────────────────────────────────────
const formRef     = ref(null)
const loading     = ref(false)
const error       = ref('')
const otpDigits   = ref(['', '', '', '', '', ''])
const inputRefs   = ref([])
const resendCooldown = ref(0)
let   cooldownTimer  = null

// ── Computed ──────────────────────────────────────────────────────────────
const otpValue = computed(() => otpDigits.value.join(''))

const maskedEmail = computed(() => {
  if (!email.value) return ''
  const [local, domain] = email.value.split('@')
  if (!domain) return email.value
  const masked = local.slice(0, 2) + '***'
  return `${masked}@${domain}`
})

// ── Gestion des champs OTP ────────────────────────────────────────────────
function onDigitInput(index) {
  // Ne garde que les chiffres
  otpDigits.value[index] = otpDigits.value[index].replace(/\D/g, '').slice(-1)

  // Auto-focus champ suivant
  if (otpDigits.value[index] && index < 5) {
    inputRefs.value[index + 1]?.focus()
  }

  // Auto-submit si complet
  if (otpValue.value.length === 6) {
    handleVerify()
  }
}

function onBackspace(index) {
  if (!otpDigits.value[index] && index > 0) {
    otpDigits.value[index - 1] = ''
    inputRefs.value[index - 1]?.focus()
  }
}

function onPaste(event) {
  const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  if (!pasted) return
  pasted.split('').forEach((char, i) => {
    if (i < 6) otpDigits.value[i] = char
  })
  // Focus sur le dernier champ rempli
  const lastIndex = Math.min(pasted.length - 1, 5)
  inputRefs.value[lastIndex]?.focus()

  if (pasted.length === 6) handleVerify()
}

// ── Vérification OTP ──────────────────────────────────────────────────────
async function handleVerify() {
  error.value = ''

  if (otpValue.value.length < 6) {
    error.value = 'Entrez les 6 chiffres du code OTP.'
    return
  }

  if (!userId.value) {
    error.value = 'Session expirée. Veuillez vous reconnecter.'
    setTimeout(() => router.push({ name: 'login' }), 2000)
    return
  }

  loading.value = true
  try {
    await authStore.verify2FA({
      user_id: parseInt(userId.value),   // ← cast en int (cohérent avec backend)
      otp:     otpValue.value,
    })

    // Nettoyage sessionStorage
    sessionStorage.removeItem('2fa_user_id')
    sessionStorage.removeItem('2fa_login_type')
    sessionStorage.removeItem('2fa_email')
    sessionStorage.removeItem('2fa_otp_dev')

    toast.success('Connexion réussie !')

    // Redirection selon le rôle
    const role = authStore.user?.role
    router.push(role === 'admin'
      ? { name: 'admin.dashboard' }
      : { name: 'student.dashboard' })

  } catch (err) {
    error.value = err?.response?.data?.message ?? 'Code OTP invalide.'

    // Vide les cases en cas d'erreur
    otpDigits.value = ['', '', '', '', '', '']
    inputRefs.value[0]?.focus()
  } finally {
    loading.value = false
  }
}

// ── Renvoi OTP ────────────────────────────────────────────────────────────
async function resendOtp() {
  if (!userId.value) return
  try {
    const res = await api.post('/auth/resend-otp', { user_id: parseInt(userId.value) })
    toast.success('Nouveau code envoyé !')

    // Met à jour le code dev si retourné
    if (res.data?.otp_dev) {
      devOtp.value = res.data.otp_dev
      sessionStorage.setItem('2fa_otp_dev', res.data.otp_dev)
    }

    // Cooldown 60 secondes
    resendCooldown.value = 60
    cooldownTimer = setInterval(() => {
      resendCooldown.value--
      if (resendCooldown.value <= 0) {
        clearInterval(cooldownTimer)
      }
    }, 1000)
  } catch {
    toast.error('Impossible de renvoyer le code.')
  }
}

// ── Sécurité — redirige si pas de user_id ────────────────────────────────
onMounted(() => {
  if (!userId.value) {
    toast.warning('Session invalide. Veuillez vous reconnecter.')
    router.push({ name: 'login' })
    return
  }
  // Focus automatique sur le premier champ
  setTimeout(() => inputRefs.value[0]?.focus(), 100)
})

onUnmounted(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})
</script>

<style scoped>
.otp-wrapper {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.otp-input {
  width: 52px;
  flex: none;
}

/* Centre le texte dans chaque case OTP */
.otp-input :deep(input) {
  text-align: center;
  font-size: 1.4rem;
  font-weight: 700;
  letter-spacing: 0;
  padding: 8px 4px;
}
</style>
