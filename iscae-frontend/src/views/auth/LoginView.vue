<template>
  <v-app>
    <v-main style="background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%); min-height: 100vh;">
      <v-container fluid class="fill-height">
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="5" lg="4">

            <!-- Header -->
            <div class="text-center mb-8">
              <v-avatar size="80" color="white" class="mb-4 elevation-4">
                <v-icon size="48" color="primary">mdi-school</v-icon>
              </v-avatar>
              <h1 class="text-h4 font-weight-bold text-white">ISCAE</h1>
              <p class="text-subtitle-1 text-white mt-1" style="opacity:0.9">
                Système de Gestion des Réclamations
              </p>
            </div>

            <!-- Card Login -->
            <v-card rounded="xl" elevation="8" class="pa-6">
              <v-card-title class="text-h5 font-weight-bold text-primary text-center mb-4">
                Connexion
              </v-card-title>

              <v-card-text>
                <v-form @submit.prevent="handleLogin" ref="formRef">

                  <v-text-field
                    v-model="form.login"
                    label="Identifiant"
                    placeholder="Email ou matricule"
                    prepend-inner-icon="mdi-account"
                    :disabled="loading"
                    :rules="[v => !!v || 'Identifiant requis']"
                    class="mb-3"
                  />

                  <v-text-field
                    v-model="form.password"
                    label="Mot de passe"
                    :type="showPassword ? 'text' : 'password'"
                    prepend-inner-icon="mdi-lock"
                    :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showPassword = !showPassword"
                    :disabled="loading"
                    :rules="[v => !!v || 'Mot de passe requis']"
                    class="mb-3"
                  />

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

                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                    class="mt-2"
                  >
                    <v-icon start>mdi-login</v-icon>
                    Se connecter
                  </v-btn>

                </v-form>
              </v-card-text>

              <v-divider class="mx-6" />

              <v-card-actions class="justify-center pa-4">
                <span class="text-body-2 text-grey">Première connexion ?</span>
                <v-btn
                  variant="text"
                  color="primary"
                  size="small"
                  to="/register"
                  class="ml-1"
                >
                  Créer votre compte
                </v-btn>
              </v-card-actions>
            </v-card>

            <!-- Footer -->
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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const router    = useRouter()
const authStore = useAuthStore()
const toast     = useToast()

const formRef      = ref(null)
const loading      = ref(false)
const error        = ref('')
const showPassword = ref(false)

// ✅ Le champ s'appelle "login" — cohérent avec LoginRequest backend
const form = ref({ login: '', password: '' })

async function handleLogin() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  error.value   = ''

  try {
    const result = await authStore.login(form.value)

    if (result?.requires_2fa) {
      // ✅ Stocke tout ce que le backend retourne
      sessionStorage.setItem('2fa_user_id',    String(result.user_id    ?? ''))
      sessionStorage.setItem('2fa_login_type', result.login_type ?? 'student')
      sessionStorage.setItem('2fa_email',      result.email      ?? '')

      // En dev : affiche le code OTP dans un toast
      if (result.otp_dev) {
        sessionStorage.setItem('2fa_otp_dev', result.otp_dev)
        toast.info(`[DEV] Code OTP : ${result.otp_dev}`, { timeout: 0 })
      }

      router.push({ name: '2fa' })
      return
    }

    // Connexion directe sans 2FA
    toast.success('Connexion réussie !')
    const role = result?.role ?? authStore.user?.role
    router.push(role === 'admin'
      ? { name: 'admin.dashboard' }
      : { name: 'student.dashboard' })

  } catch (err) {
    const data = err?.response?.data
    // Affiche les erreurs de validation champ par champ si disponibles
    if (data?.errors) {
      const messages = Object.values(data.errors).flat()
      error.value = messages[0] ?? 'Erreur de validation.'
    } else {
      error.value = data?.message ?? 'Identifiants incorrects.'
    }
  } finally {
    loading.value = false
  }
}
</script>
