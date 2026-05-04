<template>
  <v-app :theme="theme">
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
            <h1 class="text-h5 font-weight-bold text-white">ISCAE Réclamations</h1>
            <p class="text-body-2 text-white-darken-1">Système de gestion des réclamations</p>
          </div>

          <!-- Card de connexion -->
          <v-card class="pa-6 rounded-xl" elevation="12">
            <v-card-title class="text-h6 font-weight-bold text-center mb-4">
              Connexion
            </v-card-title>

            <v-form @submit.prevent="handleLogin">
              <!-- Identifiant -->
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

              <!-- Mot de passe -->
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

              <!-- ✅ LIEN MOT DE PASSE OUBLIÉ -->
              <div class="text-right mb-4">
                <router-link
                  :to="{ name: 'forgot-password' }"
                  class="text-primary text-decoration-none text-body-2 font-weight-medium"
                >
                  <v-icon size="14" class="mr-1">mdi-lock-question</v-icon>
                  Mot de passe oublié ?
                </router-link>
              </div>

              <!-- Message d'erreur -->
              <v-alert
                v-if="errorMsg"
                type="error"
                variant="tonal"
                class="mb-4"
                density="compact"
              >
                {{ errorMsg }}
              </v-alert>

              <!-- Bouton connexion -->
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

              <!-- Lien inscription -->
              <div class="text-center text-body-2">
                Pas encore de compte ?
                <router-link
                  :to="{ name: 'register' }"
                  class="text-primary font-weight-medium text-decoration-none"
                >
                  Créer mon compte
                </router-link>
              </div>
            </v-form>
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

const form    = ref({ login: '', password: '' })
const loading = ref(false)
const showPwd = ref(false)
const errorMsg = ref('')
const theme   = ref('light')

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

    if (result?.requires2FA) {
      router.push({ name: 'verify-2fa', query: { userId: result.userId } })
      return
    }

    const role = authStore.user?.role
    if (role === 'admin')   router.push({ name: 'admin.dashboard' })
    else if (role === 'student') router.push({ name: 'student.dashboard' })
    else router.push({ name: 'login' })

  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ??
      err.message ??
      'Identifiants incorrects. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-bg {
  background: linear-gradient(135deg, #1a237e 0%, #283593 40%, #3949ab 100%);
  min-height: 100vh;
}

.logo-wrapper {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.3), 0 6px 20px rgba(0,0,0,0.4);
}

.logo-img {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 50%;
}
</style>
