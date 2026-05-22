<template>
  <div class="register-page">
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="9" md="6" lg="5">

          <div class="text-center mb-6">
            <div class="logo-circle mx-auto mb-3">
              <img
                src="https://th.bing.com/th/id/R.bb2cf5d4b7c5c26926598d033caa12d5?rik=qVW4UwQbTi2FBw&riu=http%3a%2f%2fiscae.mr%2fsites%2fdefault%2ffiles%2flogo-iscae.png&ehk=YA1xYsCRE3ywccmaupnq14KGVjvhrs1pJQdhphtJE%2bs%3d&risl=&pid=ImgRaw&r=0"
                alt="ISCAE"
                class="logo-img"
              />
            </div>
            <h1 class="text-h4 font-weight-bold text-white mt-2">ISCAE</h1>
            <p class="text-white opacity-80">Création de votre compte étudiant</p>
          </div>

          <v-card rounded="xl" elevation="8" class="pa-6">

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

            <div v-if="step === 1">
              <h2 class="text-h6 font-weight-bold mb-1 header-title">Vérification de votre identité</h2>
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
                color="iscae-green"
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
                color="iscae-green"
                :disabled="loading"
                hint="Saisissez votre adresse email personnelle (Gmail, Hotmail, etc.)"
                persistent-hint
                @keyup.enter="handleVerifyIdentity"
              />

              <v-btn
                block size="large" rounded="lg" class="mt-4 text-white btn-iscae"
                :loading="loading"
                prepend-icon="mdi-account-check"
                @click="handleVerifyIdentity"
              >
                Vérifier mon identité
              </v-btn>
            </div>

            <div v-if="step === 2">
              <h2 class="text-h6 font-weight-bold mb-1 header-title">Vérification par email</h2>
              <p class="text-body-2 text-medium-emphasis mb-2">
                Un code à 6 chiffres a été envoyé à
                <strong>{{ maskedEmail }}</strong>
              </p>

              <v-card variant="tonal" class="pa-3 mb-4 student-info-card" rounded="lg">
                <div class="d-flex align-center" style="gap:10px">
                  <v-icon class="icon-iscae">mdi-account-school</v-icon>
                  <div>
                    <div class="font-weight-bold text-iscae-dark">{{ studentInfo.name }}</div>
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
                color="iscae-green"
                :disabled="loading"
                @finish="handleVerifyOtp"
              />

              <v-btn
                block size="large" rounded="lg" class="text-white btn-iscae"
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
                  class="text-iscae-green"
                  :disabled="resendCooldown > 0 || loading"
                  @click="handleResendOtp"
                >
                  {{ resendCooldown > 0 ? `Renvoyer dans ${resendCooldown}s` : 'Renvoyer le code' }}
                </v-btn>
              </div>
            </div>

            <div v-if="step === 3">
              <h2 class="text-h6 font-weight-bold mb-1 header-title">Définir votre mot de passe</h2>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Choisissez un mot de passe sécurisé pour votre compte.
              </p>

              <v-text-field
                v-model="form.password"
                label="Mot de passe *"
                prepend-inner-icon="mdi-lock"
                color="iscae-green"
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
                color="iscae-green"
                :type="showPwd ? 'text' : 'password'"
                variant="outlined"
                rounded="lg"
                class="mb-4"
                :disabled="loading"
                :error-messages="pwdMismatch ? ['Les mots de passe ne correspondent pas'] : []"
                @keyup.enter="handleSetPassword"
              />

              <v-btn
                block size="large" rounded="lg" class="text-white btn-iscae-success"
                :loading="loading"
                prepend-icon="mdi-check-circle"
                @click="handleSetPassword"
              >
                Créer mon compte
              </v-btn>
            </div>

            <div v-if="step === 4" class="text-center py-6">
              <v-icon size="72" class="icon-iscae">mdi-check-circle</v-icon>
              <h2 class="text-h6 font-weight-bold mt-3 mb-2 text-iscae-dark">Compte créé avec succès !</h2>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Bienvenue <strong>{{ studentInfo.name }}</strong>. Redirection en cours...
              </p>
              <v-progress-linear indeterminate class="progress-iscae" rounded height="4" />
            </div>

            <div v-if="step < 4" class="text-center mt-4">
              <span class="text-body-2 text-medium-emphasis">Déjà un compte ? </span>
              <router-link :to="{ name: 'login' }" class="text-iscae-green font-weight-medium link-hover">
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

const studentId   = ref(null)
const maskedEmail = ref('')
const studentInfo = ref({ name: '', filiere: '', niveau: '' })

const resendCooldown = ref(0)
let cooldownTimer = null

const pwdMismatch = computed(() =>
  form.value.passwordConfirm.length > 0 &&
  form.value.password !== form.value.passwordConfirm
)

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

    studentId.value = data.student_id
    maskedEmail.value = maskEmail(data.email ?? form.value.email)

    const rawName = data.full_name
    const builtName = ((data.prenom ?? '') + ' ' + (data.nom ?? '')).trim()
    studentInfo.value = {
      name:    rawName || builtName || 'Étudiant',
      filiere: data.filiere ?? '',
      niveau:  data.niveau  ?? '',
    }

    await sendOtp()
  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Erreur de vérification.'
  } finally {
    loading.value = false
  }
}

async function sendOtp() {
  try {
    await api.post('/auth/send-otp', {
      student_id: studentId.value,
      email:      form.value.email.trim().toLowerCase(),
    })
  } catch (err) {
    console.error('[sendOtp] erreur:', err.response?.data)
  } finally {
    step.value = 2
    startResendCooldown()
  }
}

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
    step.value = 3
  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Code OTP invalide.'
    form.value.otp = ''
  } finally {
    loading.value = false
  }
}

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

    authStore.setToken(token)

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

    authStore.initialized = true
    step.value = 4

    setTimeout(() => {
      router.push({ name: 'student.dashboard' })
    }, 2000)

  } catch (err) {
    errorMsg.value = err.response?.data?.message ?? 'Erreur lors de la création du compte.'
  } finally {
    loading.value = false
  }
}

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
/* ── Page Background Harmonisé (Vert & Bleu ISCAE) ── */
.register-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0b8243 0%, #226b77 60%, #79c2c4 100%);
}

/* ── Couleurs de police & titres ── */
.header-title {
  color: #0b8243;
}
.text-iscae-green {
  color: #0b8243 !important;
}
.text-iscae-dark {
  color: #226b77 !important;
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
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}
.logo-img {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 50%;
  pointer-events: none;
}

/* ── Boutons personnalisés ── */
.btn-iscae {
  background-color: #0b8243 !important;
  transition: transform 0.2s, background-color 0.2s;
}
.btn-iscae:hover {
  background-color: #096d37 !important;
  transform: translateY(-1px);
}
.btn-iscae-success {
  background: linear-gradient(90deg, #0b8243 0%, #226b77 100%) !important;
  transition: opacity 0.2s;
}
.btn-iscae-success:hover {
  opacity: 0.9;
}

/* ── Stepper aux couleurs ISCAE ── */
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
/* Étape active : Vert ISCAE */
.step-item.active .step-circle {
  background: #0b8243;
  color: #ffffff;
  box-shadow: 0 2px 10px rgba(11, 130, 67, 0.4);
}
/* Étape terminée : Bleu Vert ISCAE */
.step-item.done .step-circle {
  background: #226b77;
  color: #ffffff;
}

.step-label {
  font-size: 11px;
  color: #757575;
  white-space: nowrap;
}
.step-item.active .step-label {
  color: #0b8243;
  font-weight: 600;
}
.step-item.done .step-label {
  color: #226b77;
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
  background: #226b77;
}

/* ── Carte Infos Étudiant & Divers ── */
.student-info-card {
  background-color: rgba(34, 107, 119, 0.1) !important;
  border: 1px solid rgba(34, 107, 119, 0.2) !important;
}
.icon-iscae {
  color: #0b8243 !important;
}
.progress-iscae {
  color: #0b8243 !important;
}
.link-hover:hover {
  text-decoration: underline;
}
</style>