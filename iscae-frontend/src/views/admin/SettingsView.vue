<template>
  <div class="settings-page">

    <!-- ── En-tête ──────────────────────────────────────── -->
    <div class="page-header mb-6">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3">
        <div class="d-flex align-center gap-3">
          <v-icon size="32" color="primary">mdi-cog</v-icon>
          <div>
            <h1 class="text-h5 font-weight-bold">Paramètres système</h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Configuration générale de l'application ISCAE
            </p>
          </div>
        </div>
        
      </div>
    </div>

    <!-- ── Chargement ────────────────────────────────────── -->
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="primary" size="56" />
      <p class="mt-4 text-medium-emphasis">Chargement des paramètres…</p>
    </div>

    <template v-else>
      <!-- ── Onglets par groupe ─────────────────────────── -->
      <v-tabs v-model="activeTab" color="primary" class="mb-4" show-arrows>
        <v-tab
          v-for="g in groups"
          :key="g.key"
          :value="g.key"
        >
          <v-icon start :color="g.color">{{ g.icon }}</v-icon>
          {{ g.label }}
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <v-window-item
          v-for="g in groups"
          :key="g.key"
          :value="g.key"
        >
          <v-card elevation="2" rounded="xl">
            <!-- En-tête de groupe -->
            <v-card-title class="pa-6 pb-3 d-flex align-center gap-2">
              <v-icon :color="g.color">{{ g.icon }}</v-icon>
              <span>{{ g.label }}</span>
              <v-chip size="x-small" :color="g.color" variant="tonal" class="ml-2">
                {{ (settings[g.key] || []).length }} paramètre(s)
              </v-chip>
            </v-card-title>

            <v-divider />

            <v-card-text class="pa-6">
              <v-row dense>
                <v-col
                  v-for="item in (settings[g.key] || [])"
                  :key="item.key"
                  cols="12"
                  :md="item.type === 'json' ? 12 : 6"
                >
                  <!-- ── Booléen → switch ──────────────── -->
                  <div v-if="item.type === 'boolean'" class="setting-row pa-4 rounded-lg mb-2">
                    <div class="d-flex align-center justify-space-between">
                      <div>
                        <div class="text-body-1 font-weight-medium">{{ item.label }}</div>
                        <div class="text-caption text-medium-emphasis">{{ item.key }}</div>
                      </div>
                      <v-switch
                        v-model="form[item.key]"
                        color="primary"
                        hide-details
                        density="compact"
                        inset
                      />
                    </div>
                  </div>

                  <!-- ── JSON → textarea ──────────────── -->
                  <div v-else-if="item.type === 'json'" class="mb-2">
                    <v-label class="text-body-2 font-weight-medium mb-1 d-block">
                      {{ item.label }}
                      <span class="text-caption text-medium-emphasis ml-1">({{ item.key }})</span>
                    </v-label>
                    <!-- Tags pour les extensions de fichiers -->
                    <div v-if="item.key === 'allowed_file_types'">
                      <div class="d-flex flex-wrap gap-2 mb-2">
                        <v-chip
                          v-for="(ext, i) in (Array.isArray(form[item.key]) ? form[item.key] : [])"
                          :key="ext"
                          size="small"
                          color="primary"
                          variant="tonal"
                          closable
                          @click:close="removeExt(item.key, i)"
                        >
                          .{{ ext }}
                        </v-chip>
                      </div>
                      <v-text-field
                        v-model="newExt"
                        placeholder="Ajouter une extension (ex: pdf)"
                        variant="outlined"
                        density="compact"
                        hide-details
                        prepend-inner-icon="mdi-file-plus"
                        @keyup.enter="addExt(item.key)"
                      >
                        <template #append-inner>
                          <v-btn size="x-small" color="primary" variant="tonal" @click="addExt(item.key)">
                            Ajouter
                          </v-btn>
                        </template>
                      </v-text-field>
                    </div>
                    <!-- JSON brut pour les autres cas -->
                    <v-textarea
                      v-else
                      v-model="jsonRaw[item.key]"
                      variant="outlined"
                      density="compact"
                      rows="3"
                      font-family="monospace"
                      placeholder='["valeur1","valeur2"]'
                      :rules="[v => isValidJson(v) || 'JSON invalide']"
                    />
                  </div>

                  <!-- ── Entier → number field ────────── -->
                  <v-text-field
                    v-else-if="item.type === 'integer'"
                    v-model.number="form[item.key]"
                    :label="item.label"
                    :hint="item.key"
                    persistent-hint
                    type="number"
                    min="0"
                    variant="outlined"
                    density="comfortable"
                    class="mb-2"
                    :prepend-inner-icon="intIcon(item.key)"
                    :rules="[v => v >= 0 || 'Valeur positive requise']"
                  />

                  <!-- ── String → text field ──────────── -->
                  <v-text-field
                    v-else
                    v-model="form[item.key]"
                    :label="item.label"
                    :hint="item.key"
                    persistent-hint
                    variant="outlined"
                    density="comfortable"
                    class="mb-2"
                    :prepend-inner-icon="strIcon(item.key)"
                  />
                </v-col>
              </v-row>
            </v-card-text>

            <!-- Bouton de sauvegarde par groupe -->
            <v-card-actions class="pa-6 pt-0">
              <v-spacer />
              <v-btn
                :color="g.color"
                variant="tonal"
                :loading="savingGroup === g.key"
                prepend-icon="mdi-content-save"
                @click="saveGroup(g.key)"
              >
                Sauvegarder {{ g.label }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-window-item>
      </v-window>

      <!-- ── Résumé dernière modification ─────────────────── -->
     
    </template>

    <!-- ── Snackbar ───────────────────────────────────────── -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      :timeout="4000"
      location="bottom right"
      rounded="lg"
    >
      <v-icon class="mr-2">
        {{ snack.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
      </v-icon>
      {{ snack.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/api/axios'

// ── Configuration des groupes ──────────────────────────────
const GROUP_META = {
  general:     { label: 'Général',       icon: 'mdi-earth',            color: 'primary'  },
  academic:    { label: 'Académique',    icon: 'mdi-school',           color: 'indigo'   },
  security:    { label: 'Sécurité',      icon: 'mdi-shield-lock',      color: 'red'      },
  session:     { label: 'Session',       icon: 'mdi-clock-outline',    color: 'orange'   },
  uploads:     { label: 'Fichiers',      icon: 'mdi-upload',           color: 'teal'     },
  reclamation: { label: 'Réclamations',  icon: 'mdi-file-document',    color: 'purple'   },
}

// ── State ──────────────────────────────────────────────────
const loading     = ref(true)
const saving      = ref(false)
const savingGroup = ref(null)
const activeTab   = ref('general')
const lastSaved   = ref(null)
const newExt      = ref('')

// settings = { general: [...], security: [...], ... }
const settings = ref({})

// form = { key: valeur_castée, ... } — objet plat pour tous les inputs
const form    = reactive({})
// jsonRaw = { key: string_json } — pour les champs JSON (textarea brut)
const jsonRaw = reactive({})

const snack = ref({ show: false, text: '', color: 'success' })

// ── Computed : groupes ordonnés ────────────────────────────
const groups = computed(() => {
  return Object.keys(settings.value)
    .map(key => ({
      key,
      ...(GROUP_META[key] ?? { label: key, icon: 'mdi-tune', color: 'grey' }),
    }))
    .sort((a, b) => {
      const order = ['general', 'academic', 'reclamation', 'security', 'session', 'uploads']
      return (order.indexOf(a.key) + 1 || 99) - (order.indexOf(b.key) + 1 || 99)
    })
})

// ── Helpers ────────────────────────────────────────────────
function notify(text, color = 'success') {
  snack.value = { show: true, text, color }
}

function isValidJson(str) {
  try { JSON.parse(str); return true } catch { return false }
}

function intIcon(key) {
  const map = {
    otp_expiry_minutes:       'mdi-timer-outline',
    otp_max_attempts:         'mdi-shield-alert',
    login_max_attempts:       'mdi-login',
    login_lockout_minutes:    'mdi-lock-clock',
    admin_2fa_after_days:     'mdi-two-factor-authentication',
    device_trust_days:        'mdi-devices',
    session_lifetime_minutes: 'mdi-clock',
    max_file_upload_mb:       'mdi-file-upload',
    reclamation_max_per_student: 'mdi-counter',
  }
  return map[key] ?? 'mdi-numeric'
}

function strIcon(key) {
  const map = {
    app_name:             'mdi-application',
    academic_year_current:'mdi-calendar-range',
  }
  return map[key] ?? 'mdi-form-textbox'
}

function nowFr() {
  return new Date().toLocaleString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

// ── Extensions (champ json allowed_file_types) ─────────────
function addExt(key) {
  const val = newExt.value.trim().toLowerCase().replace(/^\./, '')
  if (!val) return
  if (!Array.isArray(form[key])) form[key] = []
  if (!form[key].includes(val)) form[key].push(val)
  newExt.value = ''
}

function removeExt(key, index) {
  if (Array.isArray(form[key])) form[key].splice(index, 1)
}

// ── Construire le payload pour une liste de clés ───────────
function buildPayload(keys) {
  const payload = {}
  for (const key of keys) {
    // Trouver le type de ce paramètre
    let type = 'string'
    for (const group of Object.values(settings.value)) {
      const item = group.find(i => i.key === key)
      if (item) { type = item.type; break }
    }
    if (type === 'json') {
      // Priorité : tableau réactif (ex: allowed_file_types) ou raw JSON textarea
      payload[key] = Array.isArray(form[key]) ? form[key] : (jsonRaw[key] ?? '[]')
    } else {
      payload[key] = form[key]
    }
  }
  return payload
}

// ── Chargement ─────────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/settings')
    const data = res.data?.data ?? {}
    settings.value = data

    // Remplir form et jsonRaw
    for (const group of Object.values(data)) {
      for (const item of group) {
        if (item.type === 'json') {
          // Stocker à la fois le tableau (pour les tags) et la string brute
          form[item.key]    = Array.isArray(item.value) ? [...item.value] : item.value
          jsonRaw[item.key] = JSON.stringify(item.value, null, 2)
        } else {
          form[item.key] = item.value
        }
      }
    }

    // Activer le premier onglet disponible
    if (groups.value.length > 0) activeTab.value = groups.value[0].key

  } catch (err) {
    console.error('[SettingsView] load error', err)
    notify('Impossible de charger les paramètres.', 'error')
  } finally {
    loading.value = false
  }
}

// ── Sauvegarde d'un groupe ─────────────────────────────────
async function saveGroup(groupKey) {
  savingGroup.value = groupKey
  const keys    = (settings.value[groupKey] ?? []).map(i => i.key)
  const payload = buildPayload(keys)
  try {
    const res = await api.put('/admin/settings', { settings: payload })
    notify(res.data?.message ?? 'Paramètres sauvegardés.')
    lastSaved.value = nowFr()
    await load()   // recharger pour synchroniser les valeurs castées
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Erreur lors de la sauvegarde.'
    notify(msg, 'error')
  } finally {
    savingGroup.value = null
  }
}

// ── Sauvegarde globale (tous les groupes) ──────────────────
async function saveAll() {
  saving.value = true
  const allKeys = Object.values(settings.value).flat().map(i => i.key)
  const payload = buildPayload(allKeys)
  try {
    const res = await api.put('/admin/settings', { settings: payload })
    notify(res.data?.message ?? 'Tous les paramètres sauvegardés.')
    lastSaved.value = nowFr()
    await load()
  } catch (err) {
    const msg = err.response?.data?.message ?? 'Erreur lors de la sauvegarde globale.'
    notify(msg, 'error')
  } finally {
    saving.value = false
  }
}

// ── Lifecycle ──────────────────────────────────────────────
onMounted(load)
</script>

<style scoped>
.settings-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 24px 16px;
}

.page-header {
  border-bottom: 2px solid rgba(var(--v-theme-primary), .15);
  padding-bottom: 16px;
}

.setting-row {
  background: rgba(var(--v-theme-surface-variant), .5);
  border: 1px solid rgba(var(--v-theme-on-surface), .08);
  transition: background .2s;
}

.setting-row:hover {
  background: rgba(var(--v-theme-primary), .04);
}
</style>
