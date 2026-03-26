<script setup>
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()

defineProps({
  admin: {
    type: Object,
    default: () => ({
      name: 'Admin',
      email: 'admin@citygym.com',
      role: 'Administrator',
    }),
  },
})

defineEmits(['toggle-sidebar'])

const logout = async () => {
  try {
    await api.post('/admin/logout')
    localStorage.removeItem('user')
    router.replace('/admin/login')
  } catch (error) {
    console.error('Logout failed:', error)
    router.replace('/admin/login')
  }
}
</script>

<template>
  <header class="bg-white shadow-sm border-b px-4 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="md:hidden inline-flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100"
        @click="$emit('toggle-sidebar')"
      >
        <i class="fas fa-bars text-lg"></i>
      </button>

      <div>
        <h1 class="text-lg font-bold text-gray-800">Gym Booking System</h1>
        <p class="text-sm text-gray-500">Admin Panel</p>
      </div>
    </div>

    <div class="flex items-center gap-4">
      <div class="hidden sm:block text-right">
        <p class="text-sm font-semibold text-gray-800">{{ admin.name }}</p>
        <p class="text-xs text-gray-500">{{ admin.email }}</p>
      </div>

      <button
        @click="logout"
        class="inline-flex items-center gap-2 rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold text-white hover:bg-red-600 transition"
      >
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </button>
    </div>
  </header>
</template>