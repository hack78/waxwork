import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', {
  state: () => ({
    isLoading: false,
    notifications: [],
    weworkConfig: {
      corpId: '',
      agentId: '',
      enabled: false
    },
    theme: localStorage.getItem('theme') || 'light'
  }),

  actions: {
    setLoading(loading) {
      this.isLoading = loading
    },

    addNotification(notification) {
      this.notifications.push(notification)
    },

    removeNotification(id) {
      this.notifications = this.notifications.filter(n => n.id !== id)
    },

    setWeworkConfig(config) {
      this.weworkConfig = config
    },

    toggleTheme() {
      this.theme = this.theme === 'light' ? 'dark' : 'light'
      localStorage.setItem('theme', this.theme)
      this.applyTheme()
    },

    applyTheme() {
      document.documentElement.setAttribute('data-theme', this.theme)
    }
  },

  getters: {
    unreadNotifications: (state) => {
      return state.notifications.filter(n => !n.read)
    }
  }
})