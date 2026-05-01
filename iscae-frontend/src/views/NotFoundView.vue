<template>
  <div class="notfound-page">
    <div class="notfound-content">
      <div class="notfound-icon">
        <v-icon size="64" color="#E5E7EB">mdi-alert-circle-outline</v-icon>
      </div>
      <h1 class="notfound-code">404</h1>
      <h2 class="notfound-title">Page introuvable</h2>
      <p class="notfound-desc">La page que vous recherchez n'existe pas ou a été déplacée.</p>
      <v-btn color="accent" variant="flat" rounded="lg" @click="goBack">
        Retour
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/login')
}
</script>

<style scoped>
.notfound-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #F4F6FA;
}

.notfound-content {
  text-align: center;
  padding: 40px;
}

.notfound-icon { margin-bottom: 16px; }

.notfound-code {
  font-size: 80px;
  font-weight: 800;
  color: #E5E7EB;
  margin: 0;
  line-height: 1;
}

.notfound-title {
  font-size: 22px;
  font-weight: 700;
  color: #111827;
  margin: 12px 0 8px;
}

.notfound-desc {
  font-size: 14px;
  color: #6B7280;
  margin-bottom: 24px;
}

:global(.v-theme--iscaeDark) .notfound-page  { background: #0A0E1A; }
:global(.v-theme--iscaeDark) .notfound-title { color: #F9FAFB; }
</style>
