<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  address: '',
  date_of_birth: '',
  gender: '',
})

const loading = ref(false)
const error = ref('')
const success = ref('')

const submit = async () => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/user/register', form)

    if (data?.success) {
      success.value = data.message || 'Registration successful.'
      await router.replace('/user/dashboard')
      return
    }

    error.value = data?.message || 'Registration failed.'
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.name?.[0] ||
      err?.response?.data?.errors?.email?.[0] ||
      err?.response?.data?.errors?.phone?.[0] ||
      err?.response?.data?.errors?.password?.[0] ||
      'Registration failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Create Account</h2>

    <div
      v-if="success"
      class="mb-4 rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-sm text-green-700"
    >
      {{ success }}
    </div>

    <div
      v-if="error"
      class="mb-4 rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-700"
    >
      {{ error }}
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <input v-model="form.name" type="text" placeholder="Full Name" class="w-full border rounded-lg px-4 py-2" required>
      <input v-model="form.email" type="email" placeholder="Email" class="w-full border rounded-lg px-4 py-2" required>
      <input v-model="form.phone" type="text" placeholder="Phone" class="w-full border rounded-lg px-4 py-2" required>
      <input v-model="form.address" type="text" placeholder="Address" class="w-full border rounded-lg px-4 py-2">
      <input v-model="form.date_of_birth" type="date" class="w-full border rounded-lg px-4 py-2">

      <select v-model="form.gender" class="w-full border rounded-lg px-4 py-2">
        <option value="">Select gender</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="other">Other</option>
      </select>

      <input v-model="form.password" type="password" placeholder="Password" class="w-full border rounded-lg px-4 py-2" required>
      <input v-model="form.password_confirmation" type="password" placeholder="Confirm Password" class="w-full border rounded-lg px-4 py-2" required>

      <button class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold disabled:opacity-50" :disabled="loading">
        {{ loading ? 'Creating account...' : 'Register' }}
      </button>

      <p class="text-center text-sm text-gray-600">
        Already registered?
        <router-link to="/user/login" class="text-blue-600 font-semibold">Login</router-link>
      </p>
    </form>
  </div>
</template>