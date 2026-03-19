<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({
      name: 'User',
    }),
  },
  unreadNotifications: {
    type: Number,
    default: 0,
  },
})

defineEmits(['toggle-sidebar'])

const open = ref(false)
const dropdownRef = ref(null)

const avatarUrl = computed(() => {
  const name = encodeURIComponent(props.user?.name || 'User')
  return `https://ui-avatars.com/api/?name=${name}&background=3b82f6&color=fff`
})

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
      <div class="flex items-center space-x-4">
        <button
          class="text-gray-500 hover:text-gray-700 focus:outline-none lg:hidden"
          @click="$emit('toggle-sidebar')"
        >
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>

      <div class="flex items-center space-x-4">
        <div ref="dropdownRef" class="relative">
          <button
            class="relative text-gray-500 hover:text-gray-700 focus:outline-none"
            @click.stop="open = !open"
          >
            <i class="fas fa-bell text-xl"></i>

            <span
              v-if="props.unreadNotifications > 0"
              class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
            >
              {{ props.unreadNotifications }}
            </span>
          </button>

          <div
            v-if="open"
            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
          >
            <div class="p-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-800">Notifications</h3>
            </div>

            <div class="p-4 text-sm text-gray-500">
              Click below to view your notifications.
            </div>

            <div class="p-3 text-center border-t border-gray-200">
              <RouterLink
                to="/user/notifications"
                class="text-sm text-blue-600 hover:text-blue-700"
              >
                View all notifications
              </RouterLink>
            </div>
          </div>
        </div>

        <RouterLink
          to="/user/profile"
          class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors"
        >
          <img :src="avatarUrl" alt="Profile" class="w-10 h-10 rounded-full border-2 border-gray-300">
          <span class="hidden md:block text-sm font-medium">Profile</span>
        </RouterLink>
      </div>
    </div>
  </header>
</template>