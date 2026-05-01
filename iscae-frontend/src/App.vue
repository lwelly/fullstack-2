<template>
  <router-view />
</template>

<script setup>
import { ref, computed, provide, onMounted } from 'vue'

const isDark = ref(localStorage.getItem('iscae-theme') === 'dark')
const appTheme = computed(() => isDark.value ? 'iscaeDark' : 'iscaeLight')

function toggleTheme() {
  isDark.value = !isDark.value
  localStorage.setItem('iscae-theme', isDark.value ? 'dark' : 'light')
  document.documentElement.style.backgroundColor = isDark.value ? '#0A0E1A' : '#F4F6FA'
}

provide('isDark', isDark)
provide('toggleTheme', toggleTheme)
provide('appTheme', appTheme)

onMounted(() => {
  document.documentElement.style.backgroundColor = isDark.value ? '#0A0E1A' : '#F4F6FA'
  const link = document.createElement('link')
  link.rel  = 'stylesheet'
  link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
  document.head.appendChild(link)
})
</script>

<style>
* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  box-sizing: border-box;
}
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.25); }
.v-theme--iscaeDark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); }
.v-application { font-family: 'Inter', sans-serif !important; line-height: 1.5; }
.v-btn { font-family: 'Inter', sans-serif !important; font-weight: 500 !important; letter-spacing: 0 !important; text-transform: none !important; }
.v-theme--iscaeLight .v-main { background-color: #F4F6FA !important; }
.v-theme--iscaeDark  .v-main { background-color: #0A0E1A !important; }
.cursor-pointer { cursor: pointer !important; }
.text-truncate  { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.Vue-Toastification__toast { font-family: 'Inter', sans-serif !important; font-size: 13px !important; font-weight: 500 !important; border-radius: 10px !important; }
.Vue-Toastification__toast--success { background-color: #16A34A !important; }
.Vue-Toastification__toast--error   { background-color: #DC2626 !important; }
.Vue-Toastification__toast--warning { background-color: #D97706 !important; color: white !important; }
.Vue-Toastification__toast--info    { background-color: #0F2D5E !important; }
</style>
