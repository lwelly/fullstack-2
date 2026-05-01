<template>
  <v-app>
    <v-main style="background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%); min-height: 100vh;">
      <v-container fluid class="fill-height">
        <v-row align="center" justify="center">
          <v-col cols="12" sm="9" md="6" lg="5">

            <!-- Header -->
            <div class="text-center mb-6">
              <v-avatar size="72" color="white" class="mb-3 elevation-4">
                <v-icon size="40" color="primary">mdi-school</v-icon>
              </v-avatar>
              <h1 class="text-h4 font-weight-bold text-white">ISCAE</h1>
              <p class="text-subtitle-2 text-white mt-1" style="opacity:0.85">
                Création de votre compte étudiant
              </p>
            </div>

            <!-- Stepper Card -->
            <v-card rounded="xl" elevation="8">

              <!-- Step Indicators -->
              <v-card-text class="pa-5 pb-0">
                <div class="d-flex align-center justify-center ga-2 mb-4">
                  <div v-for="(s, i) in steps" :key="i" class="d-flex align-center">
                    <v-avatar
                      :color="step > i + 1 ? 'success' : step === i + 1 ? 'primary' : 'grey-lighten-2'"
                      size="32"
                    >
                      <v-icon v-if="step > i + 1" color="white" size="16">mdi-check</v-icon>
                      <span v-else class="text-caption font-weight-bold text-white">{{ i + 1 }}</span>
                    </v-avatar>
                    <span
                      class="text-caption ml-1 mr-2 d-none d-sm-block"
                      :class="step === i + 1 ? 'text-primary font-weight-bold' : 'text-grey'"
                    >
                      {{ s }}
                    </span>
                    <v-divider v-if="i < steps.length - 1" style="width:20px" />
                  </div>
                </div>
              </v-card-text>

              <!-- ══════════ STEP 1 — VERIFY IDENTITY ══════════ -->
              <v-card-text v-if="step === 1" class="pa-5">
                <h3 class="text-h6 font-weight-bold text-primary mb-2">
                  Vérification de votre identité
                </h3>
                <p class="text-body-2 text-grey mb-4">
                  Saisissez votre matricule et votre
                  <strong>email personnel</strong> (Gmail, Hotmail, etc.)
                  tels qu'enregistrés par l'administration.
                </p>

                <v-form ref="form1Ref">
                  <v-text-field
                    v-model="form.matricule"
                    label="Matricule *"
                    prepend-inner-icon="mdi-identifier"
                    density="comfortable"
                    class="mb-3"
                    placeholder="20240001"
                    :rules="[v => !!v || 'Matricule requis']"
                    :disabled="verifyLoading"
                  />

                  <v-text-field
                    v-model="form.email"
                    label="Email personnel *"
                    prepend-inner-icon="mdi-email"
                    density="comfortable"
                    class="mb-3"
                    type="email"
                    placeholder="exemple@gmail.com"
                    hint="Saisissez votre adresse email personnelle (Gmail, Hotmail, etc.)"
                    persistent-hint
                    :rules="[
                      v => !!v || 'Email requis',
                      v => /.+@.+\..+/.test(v) || 'Email invalide'
                    ]"
                    :disabled="verifyLoading"
                  />

                  <v-alert v-if="error" type="error" variant="tonal" rounded="lg"
                    class="mb-3" density="compact">
                    {{ error }}
                  </v-alert>

                  <v-btn
                    color="primary" size="large" block
                    :loading="verifyLoading"
                    @click="verifyIdentity"
                  >
                    <v-icon start>mdi-account-check</v-icon>
                    Vérifier mon identité
                  </v-btn>
                </v-form>
              </v-card-text>

              <!-- ══════════ STEP 2 — OTP ══════════ -->
              <v-card-text v-if="step === 2" class="pa-5">
                <h3 class="text-h6 font-weight-bold text-primary mb-1">
                  Code de vérification
                </h3>
                <p class="text-body-2 text-grey mb-4">
                  Un code OTP a été envoyé à
                  <strong>{{ maskedEmail }}</strong>
                </p>

                <!-- Aperçu étudiant -->
                <v-sheet rounded="lg" color="blue-lighten-5" class="pa-3 mb-4" border="sm">
                  <div class="d-flex align-center ga-2">
                    <v-icon color="primary" size="20">mdi-account-circle</v-icon>
                    <div>
                      <p class="text-body-2 font-weight-bold">{{ preloadedName }}</p>
                      <p class="text-caption text-grey">
                        {{ preloadedFiliere }} — {{ preloadedNiveau }}
                      </p>
                    </div>
                  </div>
                </v-sheet>

                <v-form ref="form2Ref">
                  <v-text-field
                    v-model="form.otp_code"
                    label="Code OTP *"
                    prepend-inner-icon="mdi-numeric"
                    density="comfortable"
                    class="mb-4"
                    maxlength="6"
                    style="letter-spacing: 6px; font-size: 22px"
                    :rules="[
                      v => !!v || 'Code requis',
                      v => v.length === 6 || '6 chiffres requis'
                    ]"
                    :disabled="otpLoading"
                  />

                  <v-alert v-if="error" type="error" variant="tonal" rounded="lg"
                    class="mb-3" density="compact">
                    {{ error }}
                  </v-alert>

                  <v-btn
                    color="primary" size="large" block
                    :loading="otpLoading"
                    @click="verifyOTP"
                  >
                    <v-icon start>mdi-check-circle</v-icon>
                    Vérifier le code
                  </v-btn>
                </v-form>

                <div class="text-center mt-3">
                  <v-btn variant="text" color="grey" size="small" @click="step = 1">
                    <v-icon start size="14">mdi-arrow-left</v-icon>
                    Retour
                  </v-btn>
                  <v-btn variant="text" color="primary" size="small"
                    :loading="resendLoading" @click="resendOTP">
                    <v-icon start size="14">mdi-refresh</v-icon>
                    Renvoyer le code
                  </v-btn>
                </div>
              </v-card-text>

              <!-- ══════════ STEP 3 — SET PASSWORD ══════════ -->
              <v-card-text v-if="step === 3" class="pa-5">
                <h3 class="text-h6 font-weight-bold text-primary mb-1">
                  Créer votre mot de passe
                </h3>
                <p class="text-body-2 text-grey mb-4">
                  Choisissez un mot de passe sécurisé pour votre compte.
                </p>

                <v-form ref="form3Ref">
                  <v-text-field
                    v-model="form.password"
                    label="Mot de passe *"
                    :type="showPass ? 'text' : 'password'"
                    prepend-inner-icon="mdi-lock"
                    :append-inner-icon="showPass ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showPass = !showPass"
                    density="comfortable"
                    class="mb-2"
                    :rules="[
                      v => !!v || 'Mot de passe requis',
                      v => v.length >= 8 || 'Minimum 8 caractères',
                      v => /[A-Z]/.test(v) || 'Une majuscule requise',
                      v => /[0-9]/.test(v) || 'Un chiffre requis',
                    ]"
                    :disabled="passLoading"
                  />

                  <!-- Indicateur de force -->
                  <div class="mb-3">
                    <v-progress-linear
                      :model-value="passwordStrength"
                      :color="passwordStrengthColor"
                      rounded height="4" bg-color="grey-lighten-3"
                    />
                    <p class="text-caption mt-1" :class="`text-${passwordStrengthColor}`">
                      Force : {{ passwordStrengthLabel }}
                    </p>
                  </div>

                  <v-text-field
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe *"
                    :type="showPass2 ? 'text' : 'password'"
                    prepend-inner-icon="mdi-lock-check"
                    :append-inner-icon="showPass2 ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showPass2 = !showPass2"
                    density="comfortable"
                    class="mb-3"
                    :rules="[
                      v => !!v || 'Confirmation requise',
                      v => v === form.password || 'Les mots de passe ne correspondent pas'
                    ]"
                    :disabled="passLoading"
                  />

                  <v-alert v-if="error" type="error" variant="tonal" rounded="lg"
                    class="mb-3" density="compact">
                    {{ error }}
                  </v-alert>

                  <v-btn
                    color="success" size="large" block
                    :loading="passLoading"
                    @click="setPassword"
                  >
                    <v-icon start>mdi-account-plus</v-icon>
                    Créer mon compte
                  </v-btn>
                </v-form>
              </v-card-text>

              <!-- ══════════ STEP 4 — SUCCESS ══════════ -->
              <v-card-text v-if="step === 4" class="pa-8 text-center">
                <v-icon size="80" color="success" class="mb-4">mdi-check-circle</v-icon>
                <h3 class="text-h5 font-weight-bold text-success mb-2">
                  Compte Créé !
                </h3>
                <p class="text-body-2 text-grey mb-2">
                  Bienvenue à l'ISCAE, <strong>{{ createdName }}</strong> !
                </p>
                <p class="text-caption text-grey mb-6">
                  Votre compte est actif. Vous pouvez vous connecter dès maintenant.
                </p>
                <v-btn color="primary" size="large" to="/login" prepend-icon="mdi-login">
                  Se connecter
                </v-btn>
              </v-card-text>

              <!-- Footer -->
              <v-card-actions v-if="step < 4" class="justify-center pb-4">
                <span class="text-body-2 text-grey">Déjà un compte ?</span>
                <v-btn variant="text" color="primary" size="small" to="/login" class="ml-1">
                  Se connecter
                </v-btn>
              </v-card-actions>

            </v-card>

            <div class="text-center mt-4">
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
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const toast = useToast()

const step         = ref(1)
const error        = ref('')
const verifyLoading = ref(false)
const resendLoading = ref(false)
const otpLoading   = ref(false)
const passLoading  = ref(false)
const showPass     = ref(false)
const showPass2    = ref(false)

const form1Ref = ref(null)
const form2Ref = ref(null)
const form3Ref = ref(null)

const maskedEmail       = ref('')
const preloadedName     = ref('')
const preloadedFiliere  = ref('')
const preloadedNiveau   = ref('')
const preloadedId       = ref(null)
const registrationToken = ref('')
const createdName       = ref('')

const steps = ['Identité', 'Vérification', 'Mot de passe']

const form = ref({
  matricule:             '',
  email:                 '',
  otp_code:              '',
  password:              '',
  password_confirmation: '',
})

// ── Password Strength ──────────────────────────────────────────────────────
const passwordStrength = computed(() => {
  const p = form.value.password
  if (!p) return 0
  let score = 0
  if (p.length >= 8)          score += 25
  if (p.length >= 12)         score += 15
  if (/[A-Z]/.test(p))        score += 20
  if (/[0-9]/.test(p))        score += 20
  if (/[^A-Za-z0-9]/.test(p)) score += 20
  return Math.min(score, 100)
})

const passwordStrengthColor = computed(() => {
  if (passwordStrength.value < 40) return 'error'
  if (passwordStrength.value < 70) return 'warning'
  return 'success'
})

const passwordStrengthLabel = computed(() => {
  if (passwordStrength.value < 40) return 'Faible'
  if (passwordStrength.value < 70) return 'Moyen'
  return 'Fort'
})

// ── Step 1 — Vérifier identité ────────────────────────────────────────────
async function verifyIdentity() {
  const { valid } = await form1Ref.value.validate()
  if (!valid) return
  verifyLoading.value = true
  error.value = ''
  try {
    const res = await api.post('/auth/register/verify', {
      matricule: form.value.matricule,
      email:     form.value.email,
    })

    // ✅ Un seul niveau de data
    const d = res.data.data
    maskedEmail.value      = d.masked_email
    preloadedName.value    = d.name
    preloadedFiliere.value = d.filiere
    preloadedNiveau.value  = d.niveau
    preloadedId.value      = d.preloaded_id

    // Envoyer l'OTP automatiquement
    await api.post('/auth/register/send-otp', {
      preloaded_id: d.preloaded_id
    })

    toast.info(`Code OTP envoyé à ${d.masked_email}`)
    step.value = 2

  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de vérification.'
  } finally {
    verifyLoading.value = false
  }
}

// ── Renvoyer OTP ──────────────────────────────────────────────────────────
async function resendOTP() {
  if (!preloadedId.value) return
  resendLoading.value = true
  error.value = ''
  try {
    await api.post('/auth/register/send-otp', {
      preloaded_id: preloadedId.value
    })
    toast.success('Nouveau code OTP envoyé !')
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors du renvoi.'
  } finally {
    resendLoading.value = false
  }
}

// ── Step 2 — Vérifier OTP ────────────────────────────────────────────────
async function verifyOTP() {
  const { valid } = await form2Ref.value.validate()
  if (!valid) return
  otpLoading.value = true
  error.value = ''
  try {
    const res = await api.post('/auth/register/verify-otp', {
      preloaded_id: preloadedId.value,
      otp_code:     form.value.otp_code,  // ✅ otp_code correspond au backend
    })
    registrationToken.value = res.data.data?.registration_token
    step.value = 3
  } catch (e) {
    error.value = e.response?.data?.message || 'Code OTP invalide.'
  } finally {
    otpLoading.value = false
  }
}

// ── Step 3 — Créer mot de passe ───────────────────────────────────────────
async function setPassword() {
  const { valid } = await form3Ref.value.validate()
  if (!valid) return
  passLoading.value = true
  error.value = ''
  try {
    const res = await api.post('/auth/register/set-password', {
      registration_token:    registrationToken.value,
      password:              form.value.password,
      password_confirmation: form.value.password_confirmation,
    })
    createdName.value = res.data.data?.user?.name || preloadedName.value
    toast.success('Compte créé avec succès !')
    step.value = 4
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur lors de la création du compte.'
  } finally {
    passLoading.value = false
  }
}
</script>
