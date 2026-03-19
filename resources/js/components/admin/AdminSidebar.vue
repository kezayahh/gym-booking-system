<script setup>
import { useRoute } from 'vue-router'

defineProps({
  admin: {
    type: Object,
    default: () => ({
      name: 'Admin',
      email: 'admin@citygym.com',
    }),
  },
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])
const route = useRoute()

const menuItems = [
  { name: 'Dashboard', path: '/admin/dashboard', icon: 'fas fa-gauge-high' },
  { name: 'Users', path: '/admin/users', icon: 'fas fa-users' },
  { name: 'Schedules', path: '/admin/schedules', icon: 'fas fa-calendar-days' },
  { name: 'Bookings', path: '/admin/bookings', icon: 'fas fa-calendar-check' },
  { name: 'Payments', path: '/admin/payments', icon: 'fas fa-money-bill-wave' },
  { name: 'Refunds', path: '/admin/refunds', icon: 'fas fa-rotate-left' },
  { name: 'Reports', path: '/admin/reports', icon: 'fas fa-chart-bar' },
  { name: 'Profile', path: '/admin/profile', icon: 'fas fa-user-gear' },
]

const csrfToken =
  typeof window !== 'undefined'
    ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    : ''

const isActive = (path) => route.path === path
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 overflow-y-auto transition-all duration-300 transform md:relative md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    style="background-color: #0b3c3d;"
  >
    <div class="p-6">
      <div class="flex justify-center mb-8">
        <div class="flex flex-col items-center text-center">
          <img
            :src="'/images/logo.png'"
            alt="Gym Logo"
            class="h-24 w-24 object-contain"
          >
          <h1 class="text-lg font-bold text-white mt-3">City Gymnasium</h1>
          <p class="text-xs text-blue-200">Admin Panel</p>
        </div>
      </div>

      <div class="bg-white/10 rounded-lg p-4 mb-6">
        <div class="flex items-center space-x-3">
          <img
            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(admin.name || 'Admin')}&background=ffffff&color=0b3c3d`"
            alt="Admin Avatar"
            class="w-12 h-12 rounded-full border-2 border-white"
          >
          <div class="flex-1">
            <p class="text-white font-semibold text-sm">{{ admin.name || 'Admin' }}</p>
            <p class="text-blue-200 text-xs">Administrator</p>
          </div>
        </div>
      </div>

      <nav class="space-y-2">
        <router-link
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center space-x-4 px-4 py-3 rounded-lg transition-colors duration-200"
          :class="isActive(item.path)
            ? 'bg-white/20 text-white'
            : 'text-blue-100 hover:bg-white/10 hover:text-white'"
          @click="emit('close')"
        >
          <i :class="[item.icon, 'w-6 text-center text-lg']"></i>
          <span class="font-medium">{{ item.name }}</span>
        </router-link>

        <div class="border-t border-white/20 my-4"></div>

        <form method="POST" action="/logout">
          <input type="hidden" name="_token" :value="csrfToken">
          <button
            type="submit"
            class="w-full flex items-center space-x-4 px-4 py-3 rounded-lg transition-colors duration-200 text-red-300 hover:bg-red-500/20 hover:text-red-200"
          >
            <i class="fas fa-right-from-bracket w-6 text-center text-lg"></i>
            <span class="font-medium">Logout</span>
          </button>
        </form>
      </nav>
    </div>
  </aside>
</template>