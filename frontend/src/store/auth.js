import { defineStore } from 'pinia'
import { getUserInfo } from '@/api/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token')
  }),

  actions: {
    async fetchUser() {
      try {
        const response = await getUserInfo()
        this.user = response.data
        return this.user
      } catch (error) {
        this.logout()
        throw error
      }
    },

    login(token) {
      this.token = token
      this.isAuthenticated = true
      localStorage.setItem('token', token)
    },

    logout() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
      localStorage.removeItem('token')
    },

    async checkAuth() {
      if (this.token && !this.user) {
        await this.fetchUser()
      }
      return this.isAuthenticated
    }
  },

  getters: {
    currentUser: (state) => state.user,
    isAdmin: (state) => state.user?.roles?.some(role => role.code === 'admin')
  }
})