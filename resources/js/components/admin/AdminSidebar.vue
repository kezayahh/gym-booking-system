<script setup>
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'

const props = defineProps({
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
  admin: {
    type: Object,
    default: () => ({
      name: 'Admin',
      email: 'admin@citygym.com',
      role: 'Administrator',
    }),
  },
  logoUrl: {
    type: String,
    default: '/images/logo.png',
  },
})

const emit = defineEmits(['close'])

const route = useRoute()
const router = useRouter()

const navItems = [
  { key: 'admin.dashboard', label: 'Dashboard', to: '/admin/dashboard', icon: 'fas fa-tachometer-alt' },
  { key: 'admin.users', label: 'Users', to: '/admin/users', icon: 'fas fa-users' },
  { key: 'admin.schedules', label: 'Schedules', to: '/admin/schedules', icon: 'fas fa-calendar-alt' },
  { key: 'admin.bookings', label: 'Bookings', to: '/admin/bookings', icon: 'fas fa-calendar-check' },
  { key: 'admin.payments', label: 'Payments', to: '/admin/payments', icon: 'fas fa-money-bill-wave' },
  { key: 'admin.refunds', label: 'Refunds', to: '/admin/refunds', icon: 'fas fa-undo-alt' },
  { key: 'admin.reports', label: 'Reports', to: '/admin/reports', icon: 'fas fa-chart-bar' },
  { key: 'admin.profile', label: 'Profile', to: '/admin/profile', icon: 'fas fa-user-cog' },
]

const isActive = (routeKey) => route.name === routeKey

const handleNavigate = () => {
  emit('close')
}

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
  <aside
    class="fixed inset-y-0 left-0 z-50 w-64 min-w-[16rem] max-w-[16rem] basis-[16rem] shrink-0 overflow-y-auto transition-all duration-300 md:static md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    style="background-color: var(--primary-dark);"
>

    <div class="p-6">
      <div class="mb-8 flex justify-center">
        <div class="flex flex-col items-center text-center">
          <img :src="logoUrl" alt="Gym" class="mx-auto h-24 w-24 object-contain" />
          <h1 class="mt-3 text-xl font-bold text-white">City Gymnasium</h1>
          <p class="text-xs text-blue-200">Admin Panel</p>
        </div>
      </div>

      <div class="mb-6 rounded-lg bg-white/10 p-4">
        <div class="flex items-center space-x-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-yellow-400 to-orange-500">
            <i class="fas fa-user-shield text-lg text-white"></i>
          </div>

          <div class="flex-1 min-w-0">
            <p class="truncate text-sm font-semibold text-white">{{ props.admin.name }}</p>
            <p class="truncate text-xs text-blue-200">{{ props.admin.email }}</p>
          </div>
        </div>
      </div>

      <nav class="space-y-2">
        <router-link
          v-for="item in navItems"
          :key="item.key"
          :to="item.to"
          class="flex items-center space-x-3 rounded-lg px-4 py-3 transition-colors duration-200"
          :class="
            isActive(item.key)
              ? 'bg-white/20 text-white'
              : 'text-blue-100 hover:bg-white/10 hover:text-white'
          "
          @click="handleNavigate"
        >
          <i :class="[item.icon, 'w-5']"></i>
          <span class="font-medium">{{ item.label }}</span>
        </router-link>

        <div class="my-4 border-t border-white/20"></div>

        <button
          type="button"
          @click="logout"
          class="flex w-full items-center space-x-3 rounded-lg px-4 py-3 text-red-300 transition-colors duration-200 hover:bg-red-500/20 hover:text-red-200"
        >
          <i class="fas fa-sign-out-alt w-5"></i>
          <span class="font-medium">Logout</span>
        </button>
      </nav>
    </div>
  </aside>
</template>