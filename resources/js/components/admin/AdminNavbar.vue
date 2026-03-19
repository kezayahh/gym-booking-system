<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

defineProps({
  admin: {
    type: Object,
    default: () => ({
      name: 'Admin',
      email: 'admin@citygym.com',
    }),
  },
})

defineEmits(['toggle-sidebar'])

const notifications = [
  { id: 1, title: 'New booking received', time: '5 minutes ago' },
  { id: 2, title: 'Payment confirmed', time: '1 hour ago' },
]

const notificationOpen = ref(false)
const profileOpen = ref(false)
const notificationRef = ref(null)
const profileRef = ref(null)

const handleClickOutside = (event) => {
  if (notificationRef.value && !notificationRef.value.contains(event.target)) {
    notificationOpen.value = false
  }

  if (profileRef.value && !profileRef.value.contains(event.target)) {
    profileOpen.value = false
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
          class="text-gray-700 hover:text-gray-900 focus:outline-none md:hidden"
          @click="$emit('toggle-sidebar')"
        >
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>

      <div class="flex items-center space-x-4">
        <div ref="notificationRef" class="relative">
          <button
            class="relative text-gray-700 hover:text-gray-900 focus:outline-none"
            @click.stop="notificationOpen = !notificationOpen"
          >
            <i class="fas fa-bell text-xl"></i>
            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
          </button>

          <div
            v-if="notificationOpen"
            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
          >
            <div class="p-4 border-b border-gray-200" style="background-color: #0b3c3d;">
              <h3 class="font-semibold text-white">Notifications</h3>
            </div>

            <div class="max-h-64 overflow-y-auto">
              <a
                v-for="item in notifications"
                :key="item.id"
                href="/admin/notifications"
                class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100"
              >
                <p class="text-sm text-gray-800 font-medium">{{ item.title }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ item.time }}</p>
              </a>
            </div>

            <div class="p-3 text-center border-t border-gray-200">
              <a href="/admin/notifications" class="text-sm hover:underline" style="color: #0b3c3d;">
                View all notifications
              </a>
            </div>
          </div>
        </div>

        <div ref="profileRef" class="relative">
          <button
            class="flex items-center space-x-2 focus:outline-none"
            @click.stop="profileOpen = !profileOpen"
          >
            <img
              :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(admin.name || 'Admin')}&background=0b3c3d&color=fff`"
              alt="Profile"
              class="w-10 h-10 rounded-full border-2 border-gray-400"
            >
            <span class="hidden md:block text-sm font-medium text-gray-700">
              {{ admin.name || 'Admin' }}
            </span>
            <i class="fas fa-chevron-down text-xs text-gray-600"></i>
          </button>

          <div
            v-if="profileOpen"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
          >
            <div class="p-3 border-b border-gray-200">
              <p class="text-sm font-medium text-gray-800">{{ admin.name || 'Admin' }}</p>
              <p class="text-xs text-gray-500">{{ admin.email || 'admin@citygym.com' }}</p>
            </div>

            <div class="py-2">
              <a
                href="/admin/profile"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                <i class="fas fa-user mr-2"></i> My Profile
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>