<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import UserSidebar from '../components/user/UserSidebar.vue'
import UserNavbar from '../components/user/UserNavbar.vue'
import api from '../services/api'

const sidebarOpen = ref(true)
const logoutModal = ref(false)
const router = useRouter()

const user = ref({
  name: 'User',
  role: 'Member',
})

const unreadNotifications = ref(0)

const loadLayoutData = async () => {
  try {
    const [profileResponse, notificationsResponse] = await Promise.all([
      api.get('/api/user/profile'),
      api.get('/api/user/notifications'),
    ])

    user.value = {
      name: profileResponse.data?.user?.name || 'User',
      role: 'Member',
    }

    unreadNotifications.value = notificationsResponse.data?.stats?.unreadCount || 0
  } catch (error) {
    console.error('Failed to load layout data:', error)
  }
}

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch (error) {
    console.error('Logout failed:', error)
  } finally {
    router.push('/user/login')
  }
}

onMounted(() => {
  loadLayoutData()
})
</script>

<template>
  <div
    class="flex h-screen overflow-hidden bg-gray-100 font-sans antialiased"
    style="--primary-dark:#0b3c3d; --primary-hover:#144b4d; --primary-light:#003941; --accent-teal:#00b5b0;"
  >
    <UserSidebar
      :sidebar-open="sidebarOpen"
      :user="user"
      :unread-notifications="unreadNotifications"
      @close="sidebarOpen = false"
      @request-logout="logoutModal = true"
    />

    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/30 z-40 md:hidden"
      @click="sidebarOpen = false"
    ></div>

    <div class="flex-1 flex flex-col overflow-hidden">
      <UserNavbar
        :user="user"
        :unread-notifications="unreadNotifications"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />

      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <router-view />
      </main>
    </div>

    <div v-if="logoutModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="logoutModal = false"
        ></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                <i class="fas fa-sign-out-alt text-red-600"></i>
              </div>

              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  Confirm Logout
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                    Are you sure you want to logout?
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="button"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm"
              @click="handleLogout"
            >
              Yes, Logout
            </button>

            <button
              type="button"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              @click="logoutModal = false"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>