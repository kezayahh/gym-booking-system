<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminSidebar from '../components/admin/AdminSidebar.vue'
import AdminNavbar from '../components/admin/AdminNavbar.vue'
import api from '../services/api'

const router = useRouter()
const sidebarOpen = ref(false)
const loading = ref(true)

const admin = ref({
  name: 'Admin',
  email: 'admin@citygym.com',
  role: 'Administrator',
})

const fetchAdmin = async () => {
  try {
    const { data } = await api.get('/api/admin/me')

    if (data?.authenticated && data?.user) {
      admin.value = {
        name: data.user.name || 'Admin',
        email: data.user.email || 'admin@citygym.com',
        role: data.user.role || 'Administrator',
      }
    } else {
      router.replace('/admin/login')
    }
  } catch (error) {
    console.error('Failed to fetch admin:', error)
    router.replace('/admin/login')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAdmin()
})
</script>

<template>
  <div
    class="flex h-screen overflow-hidden bg-gray-100 font-sans antialiased"
    style="--primary-dark:#0b3c3d; --primary-hover:#144b4d; --primary-light:#003941; --accent-teal:#00b5b0;"
  >
    <AdminSidebar
      :admin="admin"
      :sidebar-open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 bg-black/30 md:hidden"
      @click="sidebarOpen = false"
    ></div>

    <div class="flex flex-1 flex-col overflow-hidden">
      <AdminNavbar
        :admin="admin"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />

      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <div v-if="loading" class="py-10 text-center text-gray-500">
          Loading admin panel...
        </div>

        <router-view v-else />
      </main>
    </div>
  </div>
</template>