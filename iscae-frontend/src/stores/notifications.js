import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const loading       = ref(false)

  const unreadCount = computed(() =>
    notifications.value.filter(n => !n.is_read).length
  )

  async function fetchAll() {
    loading.value = true
    try {
      const res = await api.get('/student/notifications')
      notifications.value = res.data?.data ?? res.data ?? []
    } catch {
      notifications.value = []
    } finally {
      loading.value = false
    }
  }

  async function markRead(id) {
    try {
      await api.put(`/student/notifications/${id}/read`)
    } catch { /* continue */ }
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) {
      notifications.value[idx] = {
        ...notifications.value[idx],
        is_read: true,
        read_at: new Date().toISOString(),
      }
    }
  }

  async function markAllRead() {
    try {
      await api.put('/student/notifications/read-all')
    } catch { /* continue */ }
    notifications.value = notifications.value.map(n => ({
      ...n,
      is_read: true,
      read_at: n.read_at ?? new Date().toISOString(),
    }))
  }

  return { notifications, loading, unreadCount, fetchAll, markRead, markAllRead }
})
