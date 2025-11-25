import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_URL = 'http://localhost:8000/api'

interface User {
  id: number
  name: string
  email: string
  stubs_balance: number
  is_admin: boolean
}

export const useUserStore = defineStore('user', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => !!user.value?.is_admin)

  // Initialize axios defaults
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  async function login(email: string, password: string) {
    try {
      const response = await axios.post(`${API_URL}/auth/login`, {
        email,
        password
      })

      if (response.data.success) {
        user.value = response.data.user
        token.value = response.data.token
        localStorage.setItem('auth_token', response.data.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
        return { success: true }
      }
      return { success: false, message: 'Login failed' }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Login failed'
      }
    }
  }

  async function register(name: string, email: string, password: string, password_confirmation: string) {
    try {
      const response = await axios.post(`${API_URL}/auth/register`, {
        name,
        email,
        password,
        password_confirmation
      })

      if (response.data.success) {
        user.value = response.data.user
        token.value = response.data.token
        localStorage.setItem('auth_token', response.data.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
        return { success: true }
      }
      return { success: false, message: 'Registration failed' }
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Registration failed'
      }
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await axios.post(`${API_URL}/auth/logout`)
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  async function fetchUser() {
    try {
      if (!token.value) return

      const response = await axios.get(`${API_URL}/auth/me`)
      if (response.data.success) {
        user.value = response.data.user
      }
    } catch (error) {
      console.error('Fetch user error:', error)
      // If token is invalid, clear auth
      logout()
    }
  }

  function updateStubsBalance(newBalance: number) {
    if (user.value) {
      user.value.stubs_balance = newBalance
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    isAdmin,
    login,
    register,
    logout,
    fetchUser,
    updateStubsBalance
  }
})

