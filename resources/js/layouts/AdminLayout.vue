<script setup>
import { ref } from 'vue'
import AdminSidebar from '../components/admin/AdminSidebar.vue'
import AdminNavbar from '../components/admin/AdminNavbar.vue'

const admin = {
  name: 'Admin',
  email: 'admin@citygym.com',
}

const sidebarOpen = ref(false)
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

    <div class="flex-1 flex flex-col overflow-hidden">
      <AdminNavbar
        :admin="admin"
        @toggle-sidebar="sidebarOpen = !sidebarOpen"
      />

      <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>