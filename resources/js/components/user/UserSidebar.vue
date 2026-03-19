<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object,
    default: () => ({
      name: 'User',
      role: 'Member',
    }),
  },
  unreadNotifications: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close', 'request-logout'])

const route = useRoute()

const menuItems = [
  { name: 'Dashboard', path: '/user/dashboard', icon: 'fas fa-home' },
  { name: 'Schedule', path: '/user/schedule', icon: 'fas fa-calendar-alt' },
  { name: 'My Bookings', path: '/user/bookings', icon: 'fas fa-clipboard-list' },
  { name: 'Payments', path: '/user/payments', icon: 'fas fa-credit-card' },
  { name: 'Notifications', path: '/user/notifications', icon: 'fas fa-bell' },
  { name: 'Profile', path: '/user/profile', icon: 'fas fa-user-cog' },
]

const logoUrl = '/images/logo.png'

const userInitial = computed(() => {
  return (props.user?.name || 'U').charAt(0).toUpperCase()
})

const isActive = (path) => route.path === path
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 overflow-y-auto transform transition-all duration-300 md:relative md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    style="background-color: var(--primary-dark);"
  >
    <div class="p-6">
      <div class="flex justify-center mb-8">
        <div class="flex flex-col items-center text-center">
          <img :src="logoUrl" alt="Gym" class="h-32 w-32 mx-auto object-contain">
          <h1 class="text-xl font-bold text-white mt-3">City Gymnasium</h1>
          <p class="text-xs text-blue-200">Member Portal</p>
        </div>
      </div>

      <div class="bg-white/10 rounded-lg p-4 mb-6">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white text-lg font-semibold">
            {{ userInitial }}
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-white font-semibold text-sm truncate">
              {{ props.user?.name || 'User' }}
            </p>
            <p class="text-blue-200 text-xs">
              {{ props.user?.role || 'Member' }}
            </p>
          </div>
        </div>
      </div>

      <nav class="space-y-2">
        <router-link
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200"
          :class="isActive(item.path) ? 'bg-white/20 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white'"
          @click="emit('close')"
        >
          <i :class="[item.icon, 'w-5']"></i>
          <span class="font-medium">{{ item.name }}</span>

          <span
            v-if="item.name === 'Notifications' && props.unreadNotifications > 0"
            class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"
          >
            {{ props.unreadNotifications }}
          </span>
        </router-link>

        <div class="border-t border-white/20 my-4"></div>

        <button
          type="button"
          class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors duration-200 text-red-300 hover:bg-red-500/20 hover:text-red-200"
          @click="emit('request-logout')"
        >
          <i class="fas fa-sign-out-alt w-5"></i>
          <span class="font-medium">Logout</span>
        </button>
      </nav>
    </div>
  </aside>
</template>