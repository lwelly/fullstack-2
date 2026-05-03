import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import VueApexCharts from 'vue3-apexcharts'

const app = createApp(App)

// ✅ Tous les plugins AVANT mount()
app.use(createPinia())
app.use(router)
app.use(vuetify)
app.use(VueApexCharts)
app.use(Toast, {
  position:        'top-right',
  timeout:         3500,
  closeOnClick:    true,
  pauseOnHover:    true,
  draggable:       true,
  hideProgressBar: false,
  maxToasts:       4,
  toastDefaults: {
    success: { timeout: 3000, icon: true },
    error:   { timeout: 5000, icon: true },
    warning: { timeout: 4000, icon: true },
    info:    { timeout: 3500, icon: true },
  },
})

// ✅ Un seul mount() à la fin
app.mount('#app')
