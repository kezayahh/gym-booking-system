<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../../services/api'

const loading = ref(false)
const infoLoading = ref(false)
const passwordLoading = ref(false)
const photoLoading = ref(false)
const deletingPhoto = ref(false)

const editMode = ref(false)
const error = ref('')
const success = ref('')

const user = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  created_at: '',
  profile_picture_url: '',
})

const stats = ref({
  total_bookings: 0,
  completed: 0,
  pending: 0,
})

const originalValues = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
})

const infoForm = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
})

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const fileInput = ref(null)
const photoFile = ref(null)

const initials = computed(() => {
  const name = String(user.value.name || 'U').trim()
  if (!name) return 'U'

  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()

  return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase()
})

const hasPhoto = computed(() => !!user.value.profile_picture_url)

const loadProfile = async () => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.get('/api/user/profile')

    user.value = {
      name: data.user?.name || '',
      email: data.user?.email || '',
      phone: data.user?.phone || '',
      address: data.user?.address || '',
      created_at: data.user?.created_at || '',
      profile_picture_url: data.user?.profile_picture_url || '',
    }

    stats.value = {
      total_bookings: data.stats?.total_bookings || 0,
      completed: data.stats?.completed || 0,
      pending: data.stats?.pending || 0,
    }

    infoForm.name = user.value.name
    infoForm.email = user.value.email
    infoForm.phone = user.value.phone
    infoForm.address = user.value.address

    originalValues.name = user.value.name
    originalValues.email = user.value.email
    originalValues.phone = user.value.phone
    originalValues.address = user.value.address
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load profile.'
  } finally {
    loading.value = false
  }
}

const toggleEditMode = () => {
  editMode.value = true
}

const cancelEditMode = () => {
  editMode.value = false
  infoForm.name = originalValues.name
  infoForm.email = originalValues.email
  infoForm.phone = originalValues.phone
  infoForm.address = originalValues.address
}

const submitInfoUpdate = async () => {
  if (!editMode.value) return

  infoLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/api/user/profile/update-info', {
      name: infoForm.name,
      email: infoForm.email,
      phone: infoForm.phone,
      address: infoForm.address,
    })

    if (data?.success) {
      success.value = data.message || 'Profile updated successfully!'
      editMode.value = false
      await loadProfile()
    } else {
      error.value = data?.message || 'Failed to update profile.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.name?.[0] ||
      err?.response?.data?.errors?.email?.[0] ||
      'Failed to update profile.'
  } finally {
    infoLoading.value = false
  }
}

const submitPasswordUpdate = async () => {
  passwordLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/api/user/profile/update-password', {
      current_password: passwordForm.current_password,
      new_password: passwordForm.new_password,
      new_password_confirmation: passwordForm.new_password_confirmation,
    })

    if (data?.success) {
      success.value = data.message || 'Password updated successfully!'
      passwordForm.current_password = ''
      passwordForm.new_password = ''
      passwordForm.new_password_confirmation = ''
    } else {
      error.value = data?.message || 'Failed to update password.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.current_password?.[0] ||
      err?.response?.data?.errors?.new_password?.[0] ||
      'Failed to update password.'
  } finally {
    passwordLoading.value = false
  }
}

const onPhotoChange = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  photoFile.value = file
  await submitPhotoUpload()
}

const submitPhotoUpload = async () => {
  if (!photoFile.value) return

  photoLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const formData = new FormData()
    formData.append('photo', photoFile.value)

    const { data } = await api.post('/api/user/profile/upload-photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    if (data?.success) {
      success.value = data.message || 'Profile picture updated successfully!'
      user.value.profile_picture_url = data.photo_url || user.value.profile_picture_url
      photoFile.value = null

      if (fileInput.value) {
        fileInput.value.value = ''
      }
    } else {
      error.value = data?.message || 'Failed to upload profile picture.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.photo?.[0] ||
      'Failed to upload profile picture.'
  } finally {
    photoLoading.value = false
  }
}

const deleteProfilePicture = async () => {
  deletingPhoto.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/api/user/profile/delete-photo')

    if (data?.success) {
      success.value = data.message || 'Profile picture deleted successfully!'
      user.value.profile_picture_url = ''
    } else {
      error.value = data?.message || 'No profile picture to delete!'
    }
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to delete profile picture.'
  } finally {
    deletingPhoto.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <div class="w-full">
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

    <div v-if="loading" class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-lg">
      Loading profile...
    </div>

    <template v-else>
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
          <i class="fas fa-user-circle text-blue-500 mr-2"></i>My Profile
        </h1>
        <p class="text-gray-600 mt-1">Manage your personal information and settings</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center">
              <div class="relative inline-block">
                <img
                  v-if="hasPhoto"
                  :src="user.profile_picture_url"
                  alt="Profile Picture"
                  class="w-40 h-40 rounded-full object-cover border-4 border-blue-500 shadow-lg"
                >

                <div
                  v-else
                  class="w-40 h-40 rounded-full border-4 border-blue-500 shadow-lg bg-gradient-to-r from-blue-500 to-purple-500 text-white flex items-center justify-center text-5xl font-bold"
                >
                  {{ initials }}
                </div>

                <button
                  type="button"
                  class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full p-3 hover:bg-blue-700 transition shadow-lg"
                  title="Change Picture"
                  @click="fileInput?.click()"
                >
                  <i class="fas fa-camera"></i>
                </button>
              </div>

              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onPhotoChange"
              >

              <h3 class="text-xl font-bold text-gray-800 mt-4">{{ user.name }}</h3>
              <p class="text-gray-600 text-sm">{{ user.email }}</p>

              <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600">Member Since</p>
                <p class="text-sm font-semibold text-gray-800">{{ user.created_at }}</p>
              </div>

              <button
                v-if="hasPhoto"
                type="button"
                class="mt-4 w-full px-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition text-sm font-semibold disabled:opacity-50"
                :disabled="deletingPhoto || photoLoading"
                @click="deleteProfilePicture"
              >
                <i v-if="deletingPhoto" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-trash mr-2"></i>Remove Picture
              </button>
            </div>
          </div>

          <!-- Account Stats -->
          <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
              <i class="fas fa-chart-line text-blue-500 mr-2"></i>Account Stats
            </h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-gray-600 text-sm">Total Bookings</span>
                <span class="font-bold text-gray-800">{{ stats.total_bookings }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-gray-600 text-sm">Completed</span>
                <span class="font-bold text-green-600">{{ stats.completed }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-gray-600 text-sm">Pending</span>
                <span class="font-bold text-yellow-600">{{ stats.pending }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-2">
          <!-- Personal Information -->
          <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-user text-blue-500 mr-2"></i>Personal Information
              </h3>

              <button
                v-if="!editMode"
                type="button"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
                @click="toggleEditMode"
              >
                <i class="fas fa-edit mr-2"></i>Edit Profile
              </button>
            </div>

            <form class="space-y-4" @submit.prevent="submitInfoUpdate">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user text-gray-400 mr-1"></i>Full Name
                  </label>
                  <input
                    v-model="infoForm.name"
                    type="text"
                    :readonly="!editMode"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="!editMode ? 'bg-gray-50' : 'bg-white'"
                  >
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-envelope text-gray-400 mr-1"></i>Email Address
                  </label>
                  <input
                    v-model="infoForm.email"
                    type="email"
                    :readonly="!editMode"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="!editMode ? 'bg-gray-50' : 'bg-white'"
                  >
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-phone text-gray-400 mr-1"></i>Phone Number
                  </label>
                  <input
                    v-model="infoForm.phone"
                    type="text"
                    :readonly="!editMode"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="!editMode ? 'bg-gray-50' : 'bg-white'"
                    placeholder="Not provided"
                  >
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>Address
                  </label>
                  <input
                    v-model="infoForm.address"
                    type="text"
                    :readonly="!editMode"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="!editMode ? 'bg-gray-50' : 'bg-white'"
                    placeholder="Not provided"
                  >
                </div>
              </div>

              <div v-if="editMode" class="flex space-x-3 pt-4">
                <button
                  type="submit"
                  class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg disabled:opacity-50"
                  :disabled="infoLoading"
                >
                  <i v-if="infoLoading" class="fas fa-spinner fa-spin mr-2"></i>
                  <i v-else class="fas fa-save mr-2"></i>Save Changes
                </button>

                <button
                  type="button"
                  class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold"
                  @click="cancelEditMode"
                >
                  <i class="fas fa-times mr-2"></i>Cancel
                </button>
              </div>
            </form>
          </div>

          <!-- Change Password -->
          <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6">
              <i class="fas fa-lock text-blue-500 mr-2"></i>Change Password
            </h3>

            <form class="space-y-4" @submit.prevent="submitPasswordUpdate">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  <i class="fas fa-key text-gray-400 mr-1"></i>Current Password
                </label>
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter current password"
                >
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-lock text-gray-400 mr-1"></i>New Password
                  </label>
                  <input
                    v-model="passwordForm.new_password"
                    type="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter new password"
                  >
                  <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-lock text-gray-400 mr-1"></i>Confirm Password
                  </label>
                  <input
                    v-model="passwordForm.new_password_confirmation"
                    type="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Confirm new password"
                  >
                </div>
              </div>

              <button
                type="submit"
                class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg disabled:opacity-50"
                :disabled="passwordLoading"
              >
                <i v-if="passwordLoading" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-key mr-2"></i>Update Password
              </button>
            </form>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>