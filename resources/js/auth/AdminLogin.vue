<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const loading = ref(false)
const error = ref('')
const success = ref('')

const submit = async () => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/admin/login', form)

    if (data?.success) {
      success.value = data.message || 'Welcome back, Admin!'
      await router.replace('/admin/dashboard')
      return
    }

    error.value = data?.message || 'Login failed.'
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.email?.[0] ||
      err?.response?.data?.errors?.password?.[0] ||
      'Invalid admin email or password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="w-full">
    <div
      v-if="success"
      class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700"
    >
      {{ success }}
    </div>

    <div
      v-if="error"
      class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700"
    >
      {{ error }}
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-2xl">
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-center">
        <i class="fas fa-user-shield mb-3 text-5xl text-white"></i>
        <h2 class="text-2xl font-bold text-white">Admin Login</h2>
        <p class="mt-2 text-blue-100">Access the administration panel</p>
      </div>

      <form class="p-8" @submit.prevent="submit">
        <div class="mb-6">
          <label for="email" class="mb-2 block font-semibold text-gray-700">
            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your admin email"
            required
            autofocus
            autocomplete="email"
          >
        </div>

        <div class="mb-6">
          <label for="password" class="mb-2 block font-semibold text-gray-700">
            <i class="fas fa-lock mr-2 text-blue-500"></i>Password
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          >
        </div>

        <div class="mb-6 flex items-center justify-between">
          <label class="flex items-center">
            <input
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 rounded text-blue-600"
            >
            <span class="ml-2 text-sm text-gray-700">Remember me</span>
          </label>
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-3 font-bold text-white shadow-lg transition duration-300 hover:from-blue-700 hover:to-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="loading"
        >
          <i class="fas fa-sign-in-alt mr-2"></i>{{ loading ? 'Logging in...' : 'Login as Admin' }}
        </button>
      </form>
    </div>

    <div class="mt-4 rounded-lg bg-black bg-opacity-30 p-3 text-center text-sm text-white">
      <i class="fas fa-info-circle mr-1"></i>
      <strong>Test Admin:</strong> admin@citygym.com / admin123
    </div>
  </div>
</template>