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
    class="fixed inset-y-0 left-0 z-50 w-64 min-w-[16rem] max-w-[16rem] basis-[16rem] shrink-0 overflow-y-auto transition-all duration-300 md:static md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    style="background-color: var(--primary-dark);"
  >
    <div class="p-6">
      <div class="mb-8 flex justify-center">
        <div class="flex flex-col items-center text-center">
          <img :src="logoUrl" alt="Gym" class="mx-auto h-24 w-24 object-contain" />
          <h1 class="mt-3 text-xl font-bold text-white">City Gymnasium</h1>
          <p class="text-xs text-blue-200">Member Portal</p>
        </div>
      </div>

      <div class="mb-6 rounded-lg bg-white/10 p-4">
        <div class="flex items-center space-x-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-blue-400 to-purple-500 text-lg font-semibold text-white">
            {{ userInitial }}
          </div>

          <div class="flex-1 min-w-0">
            <p class="truncate text-sm font-semibold text-white">
              {{ props.user?.name || 'User' }}
            </p>
            <p class="text-xs text-blue-200">
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
          class="flex items-center space-x-3 rounded-lg px-4 py-3 transition-colors duration-200"
          :class="isActive(item.path) ? 'bg-white/20 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white'"
          @click="emit('close')"
        >
          <i :class="[item.icon, 'w-5']"></i>
          <span class="font-medium">{{ item.name }}</span>

          <span
            v-if="item.name === 'Notifications' && props.unreadNotifications > 0"
            class="ml-auto rounded-full bg-red-500 px-2 py-1 text-xs text-white"
          >
            {{ props.unreadNotifications }}
          </span>
        </router-link>

        <div class="my-4 border-t border-white/20"></div>

        <button
          type="button"
          class="flex w-full items-center space-x-3 rounded-lg px-4 py-3 text-red-300 transition-colors duration-200 hover:bg-red-500/20 hover:text-red-200"
          @click="emit('request-logout')"
        >
          <i class="fas fa-sign-out-alt w-5"></i>
          <span class="font-medium">Logout</span>
        </button>
      </nav>
    </div>
  </aside>
</template>