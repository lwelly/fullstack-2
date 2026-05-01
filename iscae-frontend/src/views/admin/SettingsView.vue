<template>
  <div style="max-width:640px">
    <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 20px">Paramètres</h1>

    <div class="card">
      <div class="card-section">
        <h2 class="section-title">Informations générales</h2>
        <div class="field-label">Nom de l'établissement</div>
        <v-text-field v-model="form.school_name" variant="outlined" density="compact" rounded="lg" hide-details class="mb-3" />
        <div class="field-label">Email de contact</div>
        <v-text-field v-model="form.contact_email" variant="outlined" density="compact" rounded="lg" hide-details class="mb-3" />
        <div class="field-label">Année académique</div>
        <v-text-field v-model="form.academic_year" variant="outlined" density="compact" rounded="lg" hide-details />
      </div>

      <v-divider />

      <div class="card-section">
        <h2 class="section-title">Réclamations</h2>
        <div class="field-label">Délai de traitement (jours)</div>
        <v-text-field v-model="form.processing_days" type="number" variant="outlined" density="compact" rounded="lg" hide-details class="mb-3" style="max-width:180px" />
        <v-checkbox v-model="form.allow_student_register" label="Autoriser l'inscription des étudiants" color="#0F2D5E" hide-details />
      </div>

      <div class="card-footer">
        <v-btn
          color="#0F2D5E" variant="flat" size="small" rounded="lg"
          :loading="saving" @click="save"
        >
          <v-icon start size="16">mdi-content-save</v-icon>
          Enregistrer
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/api/axios'

const toast  = useToast()
const saving = ref(false)
const form   = ref({
  school_name:           'ISCAE',
  contact_email:         '',
  academic_year:         '2025-2026',
  processing_days:       7,
  allow_student_register: true,
})

async function save() {
  saving.value = true
  try {
    await api.put('/admin/settings', form.value)
    toast.success('Paramètres enregistrés.')
  } catch {
    toast.error('Erreur lors de la sauvegarde.')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/admin/settings')
    Object.assign(form.value, res.data?.data ?? {})
  } catch { /* utilise les valeurs par défaut */ }
})
</script>

<style scoped>
.card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; }
.card-section { padding:20px 24px; }
.card-footer { padding:14px 24px; background:#FAFAFA; border-top:1px solid #F3F4F6; }
.section-title { font-size:14px; font-weight:600; color:#111827; margin:0 0 14px; }
.field-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:6px; }
</style>
