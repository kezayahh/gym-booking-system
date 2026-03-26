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
    const { data } = await api.post('/user/login', form)

    if (data?.success) {
      success.value = data.message || 'Login successful.'
      await router.replace('/user/dashboard')
      return
    }

    error.value = data?.message || 'Login failed.'
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.email?.[0] ||
      'Invalid email or password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="w-full">
    <div
      v-if="success"
      class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
      role="alert"
    >
      <span class="block sm:inline">{{ success }}</span>
    </div>

    <div
      v-if="error"
      class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
      role="alert"
    >
      <span class="block sm:inline">{{ error }}</span>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-2xl">
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-center">
        <i class="fas fa-user-circle text-white text-5xl mb-3"></i>
        <h2 class="text-2xl font-bold text-white">Member Login</h2>
        <p class="text-green-100 mt-2">Welcome back! Please login to your account</p>
      </div>

      <form class="p-8" @submit.prevent="submit">
        <div class="mb-6">
          <label for="email" class="block text-gray-700 font-semibold mb-2">
            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your email"
            required
            autofocus
          >
        </div>

        <div class="mb-6">
          <label for="password" class="block text-gray-700 font-semibold mb-2">
            <i class="fas fa-lock mr-2 text-blue-500"></i>Password
          </label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your password"
            required
          >
        </div>

        <div class="flex items-center justify-between mb-6">
          <label class="flex items-center">
            <input
              v-model="form.remember"
              type="checkbox"
              class="form-checkbox h-4 w-4 text-green-600 rounded"
            >
            <span class="ml-2 text-gray-700 text-sm">Remember me</span>
          </label>
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-3 font-bold text-white shadow-lg transition duration-300 hover:from-blue-700 hover:to-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="loading"
        >
          <i class="fas fa-sign-in-alt mr-2"></i>{{ loading ? 'Logging in...' : 'Login' }}
        </button>

        <div class="mt-6 text-center">
          <p class="text-gray-600 mb-3">Don't have an account?</p>
          <router-link
            to="/user/register"
            class="text-blue-600 hover:text-purple-800 font-semibold"
          >
            <i class="fas fa-user-plus mr-1"></i>Create New Account
          </router-link>
        </div>
      </form>
    </div>

    <div class="mt-4 text-center text-white text-sm bg-black bg-opacity-30 rounded-lg p-3">
      <i class="fas fa-info-circle mr-1"></i>
      <strong>Test User:</strong> user@citygym.com / user123
    </div>
  </div>
</template>