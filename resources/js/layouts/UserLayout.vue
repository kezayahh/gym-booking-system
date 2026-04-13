<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import UserSidebar from '../components/user/UserSidebar.vue'
import UserNavbar from '../components/user/UserNavbar.vue'
import api from '../services/api'

const sidebarOpen = ref(false)
const logoutModal = ref(false)
const loading = ref(true)
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
      role: profileResponse.data?.user?.role || 'Member',
    }

    unreadNotifications.value =
      notificationsResponse.data?.stats?.unreadCount ||
      notificationsResponse.data?.unreadCount ||
      0
  } catch (error) {
    console.error('Failed to load layout data:', error)
  } finally {
    loading.value = false
  }
}

const handleLogout = async () => {
  try {
    await api.post('/user/logout')
  } catch (error) {
    console.error('Logout failed:', error)
  } finally {
    logoutModal.value = false
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
      class="fixed inset-0 z-40 bg-black/30 md:hidden"
      @click="sidebarOpen = false"
    ></div>

    <div class="flex flex-1 flex-col overflow-hidden">
      <UserNavbar
        :user="user"
        :unread-notifications="unreadNotifications"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />

      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <div v-if="loading" class="py-10 text-center text-gray-500">
          Loading user panel...
        </div>

        <router-view v-else />
      </main>
    </div>

    <div v-if="logoutModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="logoutModal = false"
        ></div>

        <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

        <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                <i class="fas fa-sign-out-alt text-red-600"></i>
              </div>

              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
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

          <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
            <button
              type="button"
              class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm"
              @click="handleLogout"
            >
              Yes, Logout
            </button>

            <button
              type="button"
              class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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