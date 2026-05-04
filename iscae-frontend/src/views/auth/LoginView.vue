<template>
  <div class="login-page">
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="5" lg="4">
          <div class="text-center mb-6">
            <v-icon size="64" color="white">mdi-school</v-icon>
            <h1 class="text-h4 font-weight-bold text-white mt-2">ISCAE</h1>
            <p class="text-white opacity-80">Système de Gestion des Réclamations</p>
          </div>

          <v-card rounded="xl" elevation="8" class="pa-6">
            <h2 class="text-h5 font-weight-bold text-center mb-6">Connexion</h2>

            <v-alert
              v-if="errorMsg"
              type="error"
              variant="tonal"
              rounded="lg"
              class="mb-4"
              closable
              @click:close="errorMsg = ''"
            >
              {{ errorMsg }}
            </v-alert>

            <v-form @submit.prevent="handleLogin">
              <v-text-field
                v-model="form.login"
                label="Identifiant"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                rounded="lg"
                class="mb-3"
                :disabled="loading"
                autocomplete="username"
              />
              <v-text-field
                v-model="form.password"
                label="Mot de passe"
                prepend-inner-icon="mdi-lock"
                :type="showPassword ? 'text' : 'password'"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                variant="outlined"
                rounded="lg"
                class="mb-4"
                :disabled="loading"
                autocomplete="current-password"
              />
              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                rounded="lg"
                :loading="loading"
                prepend-icon="mdi-login"
              >
                Se connecter
              </v-btn>
            </v-form>

            <div class="text-center mt-4">
              <span class="text-body-2 text-medium-emphasis">Première connexion ? </span>
              <router-link :to="{ name: 'register' }" class="text-primary font-weight-medium">
                Créer votre compte
              </router-link>
            </div>
          </v-card>

          <p class="text-center text-white opacity-60 mt-4 text-caption">
            © {{ new Date().getFullYear() }} ISCAE — Tous droits réservés
          </p>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

const form         = ref({ login: '', password: '' })
const loading      = ref(false)
const showPassword = ref(false)
const errorMsg     = ref('')

// ── Génère un fingerprint stable pour cet appareil/navigateur ──────────
async function getDeviceFingerprint() {
  try {
    const raw = [
      navigator.userAgent,
      navigator.language,
      screen.width + 'x' + screen.height,
      screen.colorDepth,
      Intl.DateTimeFormat().resolvedOptions().timeZone,
      navigator.hardwareConcurrency ?? 0,
    ].join('|')
    const buf = await crypto.subtle.digest(
      'SHA-256',
      new TextEncoder().encode(raw)
    )
    return Array.from(new Uint8Array(buf))
      .map(b => b.toString(16).padStart(2, '0'))
      .join('')
  } catch {
    return 'browser-' + Math.random().toString(36).slice(2, 18)
  }
}

async function handleLogin() {
  if (!form.value.login || !form.value.password) {
    errorMsg.value = 'Veuillez remplir tous les champs.'
    return
  }

  loading.value  = true
  errorMsg.value = ''
   
  try {
    const fingerprint = await getDeviceFingerprint()

    const result = await authStore.login({
      login:              form.value.login,
      password:           form.value.password,
      device_fingerprint: fingerprint,
    })

    // ── Cas 1 : OTP requis (nouvel appareil) ──
    if (result.requires_2fa) {
      router.push({
        name:  'verify-2fa',
        query: {
          user_id:    result.user_id,
          login_type: result.login_type,
          fp:         fingerprint,       // fingerprint passé à la page OTP
        },
      })
      return
    }

    // ── Cas 2 : Connexion directe (appareil de confiance) ──
    redirectAfterLogin(result.user ?? authStore.user)

  } catch (err) {
    console.error('[Login] Erreur:', err.response?.data ?? err)
    errorMsg.value = err?.response?.data?.message ?? 'Identifiants incorrects.'
  } finally {
    loading.value = false
  }
}

function redirectAfterLogin(user) {
  if (user?.role === 'admin') {
    router.push({ name: 'admin.dashboard' })
  } else {
    router.push({ name: 'student.dashboard' })
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a237e 0%, #283593 50%, #3949ab 100%);
}
</style>
