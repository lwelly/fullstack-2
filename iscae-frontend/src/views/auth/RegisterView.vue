<template>
  <div class="register-page">
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="9" md="6" lg="5">

          <!-- Logo -->
          <div class="text-center mb-6">
            <div class="logo-circle mx-auto mb-3">
              <img
                src="https://www.genspark.ai/api/files/s/UImvfoE3"
                alt="ISCAE"
                class="logo-img"
              />
            </div>
            <h1 class="text-h4 font-weight-bold text-white mt-2">ISCAE</h1>
            <p class="text-white opacity-80">Création de votre compte étudiant</p>
          </div>

          <v-card rounded="xl" elevation="8" class="pa-6">

            <!-- Stepper -->
            <div class="d-flex align-center justify-center mb-6 stepper">
              <template v-for="(s, i) in steps" :key="i">
                <div class="step-item" :class="{ active: step === i+1, done: step > i+1 }">
                  <div class="step-circle">
                    <v-icon v-if="step > i+1" size="16">mdi-check</v-icon>
                    <span v-else>{{ i+1 }}</span>
                  </div>
                  <span class="step-label">{{ s }}</span>
                </div>
                <div v-if="i < steps.length-1" class="step-line" :class="{ done: step > i+1 }"/>
              </template>
            </div>

            <!-- Erreur globale -->
            <v-alert
              v-if="errorMsg"
              type="error"
              variant="tonal"
              rounded="lg"
              class="mb-4"
              closable
              @click:close="errorMsg=''"
            >
              {{ errorMsg }}
            </v-alert>

            <!-- ═══ ÉTAPE 1 : Identité ═══ -->
            <div v-if="step === 1">
              <h2 class="text-h6 font-weight-bold mb-1">Vérification de votre identité</h2>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Saisissez votre matricule et votre <strong>email personnel</strong>
                (Gmail, Hotmail, etc.) tels qu'enregistrés par l'administration.
              </p>

              <v-text-field
                v-model="form.matricule"
                label="Matricule *"
                prepend-inner-icon="mdi-identifier"
                variant="outlined"
                rounded="lg"
                class="mb-3"
                :disabled="loading"
                @keyup.enter="handleVerifyIdentity"
              />
              <v-text-field
                v-model="form.email"
                label="Email personnel *"
                prepend-inner-icon="mdi-email"
                type="email"
                variant="outlined"
                rounded="lg"
                class="mb-1"
                :disabled="loading"
                hint="Saisissez votre adresse email personnelle (Gmail, Hotmail, etc.)"
                persistent-hint
                @keyup.enter="handleVerifyIdentity"
              />

              <v-btn
                block color="primary" size="large" rounded="lg" class="mt-4"
                :loading="loading"
                prepend-icon="mdi-account-check"
                @click="handleVerifyIdentity"
              >
                Vérifier mon identité
              </v-btn>
            </div>

            <!-- ═══ ÉTAPE 2 : OTP ═══ -->
            <div v-if="step === 2">
              <h2 class="text-h6 font-weight-bold mb-1">Vérification par email</h2>
              <p class="text-body-2 text-medium-emphasis mb-2">
                Un code à 6 chiffres a été envoyé à
                <strong>{{ maskedEmail }}</strong>
              </p>

              <!-- Infos étudiant -->
              <v-card variant="tonal" color="primary" rounded="lg" class="pa-3 mb-4">
                <div class="d-flex align-center" style="gap:10px">
                  <v-icon color="primary">mdi-account-school</v-icon>
                  <div>
                    <div class="font-weight-bold">{{ studentInfo.name }}</div>
                    <div class="text-caption text-medium-emphasis">
                      {{ studentInfo.filiere }}
                      <span v-if="studentInfo.filiere && studentInfo.niveau"> — </span>
                      {{ studentInfo.niveau }}
                    </div>
                  </div>
                </div>
              </v-card>

              <v-otp-input
                v-model="form.otp"
                length="6"
                type="number"
                variant="outlined"
                class="mb-3"
                :disabled="loading"
                @finish="handleVerifyOtp"
              />

              <v-btn
                block color="primary" size="large" rounded="lg"
                :loading="loading"
                prepend-icon="mdi-shield-check"
                @click="handleVerifyOtp"
              >
                Vérifier le code
              </v-btn>

              <div class="text-center mt-3">
                <v-btn
                  variant="text"
                  size="small"
                  :disabled="resendCooldown > 0 || loading"
                  @click="handleResendOtp"
                >
                  {{ resendCooldown > 0 ? `Renvoyer dans ${resendCooldown}s` : 'Renvoyer le code' }}
                </v-btn>
              </div>
            </div>

            <!-- ═══ ÉTAPE 3 : Mot de passe ═══ -->
            <div v-if="step === 3">
              <h2 class="text-h6 font-weight-bold mb-1">Définir votre mot de passe</h2>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Choisissez un mot de passe sécurisé pour votre compte.
              </p>

              <v-text-field
                v-model="form.password"
                label="Mot de passe *"
                prepend-inner-icon="mdi-lock"
                :type="showPwd ? 'text' : 'password'"
                :append-inner-icon="showPwd ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPwd = !showPwd"
                variant="outlined"
                rounded="lg"
                class="mb-3"
                :disabled="loading"
                hint="Minimum 8 caractères"
                persistent-hint
              />
              <v-text-field
                v-model="form.passwordConfirm"
                label="Confirmer le mot de passe *"
                prepend-inner-icon="mdi-lock-check"
                :type="showPwd ? 'text' : 'password'"
                variant="outlined"
                rounded="lg"
                class="mb-4"
                :disabled="loading"
                :error-messages="pwdMismatch ? ['Les mots de passe ne correspondent pas'] : []"
                @keyup.enter="handleSetPassword"
              />

              <v-btn
                block color="success" size="large" rounded="lg"
                :loading="loading"
                prepend-icon="mdi-check-circle"
                @click="handleSetPassword"
              >
                Créer mon compte
              </v-btn>
            </div>

            <!-- ═══ SUCCÈS ═══ -->
            <div v-if="step === 4" class="text-center py-6">
              <v-icon size="72" color="success">mdi-check-circle</v-icon>
              <h2 class="text-h6 font-weight-bold mt-3 mb-2">Compte créé avec succès !</h2>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Bienvenue <strong>{{ studentInfo.name }}</strong>. Redirection en cours...
              </p>
              <v-progress-linear indeterminate color="success" rounded height="4" />
            </div>

            <!-- Lien connexion -->
            <div v-if="step < 4" class="text-center mt-4">
              <span class="text-body-2 text-medium-emphasis">Déjà un compte ? </span>
              <router-link :to="{ name: 'login' }" class="text-primary font-weight-medium">
                Se connecter
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
import { ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const router    = useRouter()
const authStore = useAuthStore()

const step     = ref(1)
const loading  = ref(false)
const errorMsg = ref('')
const showPwd  = ref(false)

const steps = ['Identité', 'Vérification', 'Mot de passe']

const form = ref({
  matricule:       '',
  email:           '',
  otp:             '',
  password:        '',
  passwordConfirm: '',
})

// Données conservées entre les étapes
const studentId   = ref(null)
const maskedEmail = ref('')
const studentInfo = ref({ name: '', filiere: '', niveau: '' })

const resendCooldown = ref(0)
let cooldownTimer = null

// ── Computed ──────────────────────────────────────────────────────────────
const pwdMismatch = computed(() =>
  form.value.passwordConfirm.length > 0 &&
  form.value.password !== form.value.passwordConfirm
)

// ══════════════════════════════════════════════════════════════════════════
// ÉTAPE 1 — Vérifier identité
// POST /api/v1/auth/verify-identity
// ══════════════════════════════════════════════════════════════════════════
async function handleVerifyIdentity() {
  errorMsg.value = ''
  if (!form.value.matricule.trim() || !form.value.email.trim()) {
    errorMsg.value = 'Veuillez remplir tous les champs.'
    return
  }
  loading.value = true
  try {
    const res  = await api.post('/auth/verify-identity', {
      matricule: form.value.matricule.trim().toUpperCase(),
      email:     form.value.email.trim().toLowerCase(),
    })
    const data = res.data?.data ?? res.data

    console.log('[Register] Étape 1 réponse:', data)

    // Sauvegarder l'id étudiant
    studentId.value = data.student_id

    // Masquer l'email
    maskedEmail.value = maskEmail(data.email ?? form.value.email)

    // Construire le nom — évite le mélange ?? et ||
    const rawName = data.full_name
    const builtName = ((data.prenom ?? '') + ' ' + (data.nom ?? '')).trim()
    studentInfo.value = {
      name:    rawName || builtName || 'Étudiant',
      filiere: data.filiere ?? '',
      niveau:  data.niveau  ?? '',
    }

    // Envoyer l'OTP automatiquement
    await sendOtp()

  } catch (err) {
    console.error('[Register] Étape 1 erreur:', err.response?.data)
    errorMsg.value = err.response?.data?.message ?? 'Erreur de vérification.'
  } finally {
    loading.value = false
  }
}

// ══════════════════════════════════════════════════════════════════════════
// ENVOYER OTP
// POST /api/v1/auth/send-otp
// ══════════════════════════════════════════════════════════════════════════
async function sendOtp() {
  console.log('[sendOtp] student_id =', studentId.value)  // ← vérifier
  console.log('[sendOtp] email =', form.value.email)

  try {
    const res = await api.post('/auth/send-otp', {
      student_id: studentId.value,
      email:      form.value.email.trim().toLowerCase(),
    })
    console.log('[sendOtp] réponse:', res.data)
  } catch (err) {
    console.error('[sendOtp] erreur:', err.response?.data)
  } finally {
    step.value = 2
    startResendCooldown()
  }
}

// ══════════════════════════════════════════════════════════════════════════
// RENVOYER OTP
// ══════════════════════════════════════════════════════════════════════════
async function handleResendOtp() {
  if (resendCooldown.value > 0) return
  loading.value  = true
  errorMsg.value = ''
  try {
    await api.post('/auth/send-otp', {
      student_id: studentId.value,
      email:      form.value.email.trim().toLowerCase(),
    })
    startResendCooldown()
  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Erreur renvoi OTP.'
  } finally {
    loading.value = false
  }
}

function startResendCooldown() {
  resendCooldown.value = 60
  clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer)
  }, 1000)
}

// ══════════════════════════════════════════════════════════════════════════
// ÉTAPE 2 — Vérifier OTP
// POST /api/v1/auth/verify-otp
// ══════════════════════════════════════════════════════════════════════════
async function handleVerifyOtp() {
  errorMsg.value = ''
  if (!form.value.otp || form.value.otp.length < 6) {
    errorMsg.value = 'Veuillez saisir le code à 6 chiffres.'
    return
  }
  loading.value = true
  try {
    await api.post('/auth/verify-otp', {
      student_id: studentId.value,
      otp_code:   form.value.otp,
    })

    console.log('[Register] Étape 2 : OTP vérifié ✅')

    // Passer à l'étape 3
    step.value = 3

  } catch (err) {
    console.error('[Register] Étape 2 erreur:', err.response?.data)
    errorMsg.value = err.response?.data?.message ?? 'Code OTP invalide.'
    form.value.otp = ''
  } finally {
    loading.value = false
  }
}

// ══════════════════════════════════════════════════════════════════════════
// ÉTAPE 3 — Créer le compte
// POST /api/v1/auth/register
// ══════════════════════════════════════════════════════════════════════════
async function handleSetPassword() {
  errorMsg.value = ''

  if (!form.value.password || form.value.password.length < 8) {
    errorMsg.value = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }
  if (form.value.password !== form.value.passwordConfirm) {
    errorMsg.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  loading.value = true
  try {
    const res = await api.post('/auth/register', {
      student_id:            studentId.value,
      password:              form.value.password,
      password_confirmation: form.value.passwordConfirm,
    })

    const token = res.data?.token ?? res.data?.data?.token ?? null
    const user  = res.data?.user  ?? res.data?.data?.user  ?? null

    if (!token) {
      errorMsg.value = 'Erreur: token manquant dans la réponse.'
      return
    }

    // ✅ Utiliser setToken du store (clé = 'auth_token')
    authStore.setToken(token)

    // ✅ Charger l'utilisateur
    if (user) {
      authStore.user = user
    } else {
      try {
        const meRes    = await api.get('/auth/me')
        authStore.user = meRes.data?.data ?? meRes.data ?? null
      } catch (e) {
        console.warn('[Register] /auth/me error:', e)
      }
    }

    // ✅ Marquer comme initialisé pour éviter double init
    authStore.initialized = true

    console.log('[Register] isAuthenticated =', authStore.isAuthenticated)
    console.log('[Register] role =', authStore.user?.role)

    // ✅ Afficher succès
    step.value = 4

    // ✅ Rediriger directement sans setTimeout
    setTimeout(() => {
      router.push({ name: 'student.dashboard' })
    }, 2000)

  } catch (err) {
    console.error('[Register] erreur:', err.response?.data)
    errorMsg.value = err.response?.data?.message ?? 'Erreur lors de la création du compte.'
  } finally {
    loading.value = false
  }
}



// ══════════════════════════════════════════════════════════════════════════
// HELPER — Masquer l'email
// ══════════════════════════════════════════════════════════════════════════
function maskEmail(email) {
  if (!email) return '***@***.***'
  const parts = email.split('@')
  if (parts.length !== 2) return email
  const user   = parts[0]
  const domain = parts[1]
  const visible = user.length > 3 ? user.slice(0, 3) : user.slice(0, 1)
  return visible + '***@' + domain
}

onUnmounted(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})
</script>

<style scoped>
/* ── Page ── */
.register-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a237e 0%, #283593 50%, #3949ab 100%);
}

/* ── Logo ── */
.logo-circle {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.logo-img {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 50%;
  pointer-events: none;
}

/* ── Stepper ── */
.stepper { gap: 8px; }

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.step-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e0e0e0;
  color: #757575;
  font-weight: bold;
  font-size: 14px;
  transition: all 0.3s ease;
}
.step-item.active .step-circle {
  background: #1a237e;
  color: #ffffff;
  box-shadow: 0 2px 10px rgba(26, 35, 126, 0.45);
}
.step-item.done .step-circle {
  background: #4caf50;
  color: #ffffff;
}

.step-label {
  font-size: 11px;
  color: #757575;
  white-space: nowrap;
}
.step-item.active .step-label {
  color: #1a237e;
  font-weight: 600;
}
.step-item.done .step-label {
  color: #4caf50;
}

.step-line {
  flex: 1;
  height: 2px;
  background: #e0e0e0;
  min-width: 30px;
  margin-bottom: 18px;
  transition: background 0.3s ease;
}
.step-line.done {
  background: #4caf50;
}
</style>
