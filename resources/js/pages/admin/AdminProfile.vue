<script setup>
import { computed, reactive, ref, onMounted } from 'vue'
import adminApi from '../../services/adminApi'

const uploadPhotoInput = ref(null)
const editMode = ref(false)
const profileLoading = ref(false)
const passwordLoading = ref(false)
const pageLoading = ref(true)

const user = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  role: 'admin',
  profile_picture: null,
  profile_picture_url: '/images/default-avatar.png',
  created_at: '',
})

const activity = ref({
  totalActions: 0,
  thisMonth: 0,
  lastLogin: '',
})

const profileForm = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
})

const originalValues = reactive({
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

const profilePicturePreview = computed(() => {
  return user.value.profile_picture_url || '/images/default-avatar.png'
})

function inputClass(readonly) {
  return [
    'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
    readonly ? 'bg-gray-50' : 'bg-white',
  ]
}

async function fetchProfile() {
  pageLoading.value = true

  try {
    const { data } = await adminApi.get('/profile')

    user.value = {
      ...user.value,
      ...(data.user || {}),
    }

    activity.value = {
      ...activity.value,
      ...(data.activity || {}),
    }

    profileForm.name = data.user?.name || ''
    profileForm.email = data.user?.email || ''
    profileForm.phone = data.user?.phone || ''
    profileForm.address = data.user?.address || ''

    originalValues.name = profileForm.name
    originalValues.email = profileForm.email
    originalValues.phone = profileForm.phone
    originalValues.address = profileForm.address
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load profile.')
  } finally {
    pageLoading.value = false
  }
}

function toggleEditMode() {
  editMode.value = true
}

function cancelEditMode() {
  editMode.value = false
  profileForm.name = originalValues.name
  profileForm.email = originalValues.email
  profileForm.phone = originalValues.phone
  profileForm.address = originalValues.address
}

async function submitProfileForm() {
  if (!editMode.value) return

  profileLoading.value = true

  try {
    const { data } = await adminApi.post('/profile/update-info', {
      ...profileForm,
    })

    if (data.success) {
      alert(data.message || 'Profile updated successfully.')

      user.value = {
        ...user.value,
        ...(data.user || {}),
      }

      originalValues.name = profileForm.name
      originalValues.email = profileForm.email
      originalValues.phone = profileForm.phone
      originalValues.address = profileForm.address

      editMode.value = false
    } else {
      alert(data.message || 'An error occurred.')
    }
  } catch (error) {
    console.error(error)
    alert(
      error.response?.data?.message ||
      error.response?.data?.errors?.email?.[0] ||
      error.response?.data?.errors?.name?.[0] ||
      'An error occurred. Please try again.'
    )
  } finally {
    profileLoading.value = false
  }
}

async function submitPasswordForm() {
  passwordLoading.value = true

  try {
    const { data } = await adminApi.post('/profile/update-password', {
      ...passwordForm,
    })

    if (data.success) {
      alert(data.message || 'Password updated successfully.')
      passwordForm.current_password = ''
      passwordForm.new_password = ''
      passwordForm.new_password_confirmation = ''
    } else {
      alert(data.message || 'An error occurred.')
    }
  } catch (error) {
    console.error(error)
    alert(
      error.response?.data?.message ||
      error.response?.data?.errors?.new_password?.[0] ||
      'An error occurred. Please try again.'
    )
  } finally {
    passwordLoading.value = false
  }
}

function triggerPhotoUpload() {
  uploadPhotoInput.value?.click()
}

async function handlePhotoUpload(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('photo', file)

  try {
    const { data } = await adminApi.post('/profile/upload-photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    if (data.success) {
      alert(data.message || 'Profile picture uploaded successfully.')
      user.value.profile_picture_url = data.profile_picture_url || user.value.profile_picture_url
      await fetchProfile()
    } else {
      alert(data.message || 'An error occurred.')
    }
  } catch (error) {
    console.error(error)
    alert(
      error.response?.data?.message ||
      error.response?.data?.errors?.photo?.[0] ||
      'An error occurred. Please try again.'
    )
  } finally {
    event.target.value = ''
  }
}

async function deleteProfilePicture() {
  if (!confirm('Are you sure you want to remove your profile picture?')) return

  try {
    const { data } = await adminApi.post('/profile/delete-photo')

    if (data.success) {
      alert(data.message || 'Profile picture removed successfully.')
      await fetchProfile()
    } else {
      alert(data.message || 'No profile picture to delete.')
    }
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-PH', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  })
}

onMounted(() => {
  fetchProfile()
})
</script>

<template>
  <div>
    <div v-if="pageLoading" class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
      Loading profile...
    </div>

    <div v-else>
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
          <i class="fas fa-user-cog mr-2 text-blue-500"></i>My Profile
        </h1>
        <p class="mt-1 text-gray-600">Manage your admin account settings</p>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
          <div class="rounded-lg bg-white p-6 shadow-lg">
            <div class="text-center">
              <div class="relative inline-block">
                <img
                  :src="profilePicturePreview"
                  alt="Profile Picture"
                  class="h-40 w-40 rounded-full border-4 border-blue-500 object-cover shadow-lg"
                />

                <button
                  type="button"
                  @click="triggerPhotoUpload"
                  class="absolute bottom-0 right-0 rounded-full bg-blue-600 p-3 text-white shadow-lg transition hover:bg-blue-700"
                  title="Change Picture"
                >
                  <i class="fas fa-camera"></i>
                </button>
              </div>

              <input
                ref="uploadPhotoInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handlePhotoUpload"
              />

              <h3 class="mt-4 text-xl font-bold text-gray-800">{{ user.name }}</h3>
              <p class="text-sm text-gray-600">{{ user.email }}</p>

              <div class="mt-4 border-t border-gray-200 pt-4">
                <span class="inline-flex items-center rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 px-4 py-2 text-sm font-bold text-white">
                  <i class="fas fa-user-shield mr-2"></i>Administrator
                </span>
              </div>

              <div class="mt-4 border-t border-gray-200 pt-4">
                <p class="text-sm text-gray-600">Admin Since</p>
                <p class="text-sm font-semibold text-gray-800">{{ formatDate(user.created_at) }}</p>
              </div>

              <button
                v-if="user.profile_picture"
                type="button"
                @click="deleteProfilePicture"
                class="mt-4 w-full rounded-lg bg-red-100 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-200"
              >
                <i class="fas fa-trash mr-2"></i>Remove Picture
              </button>
            </div>
          </div>

          <div class="mt-6 rounded-lg bg-white p-6 shadow-lg">
            <h3 class="mb-4 text-lg font-bold text-gray-800">
              <i class="fas fa-chart-bar mr-2 text-blue-500"></i>Your Activity
            </h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Total Actions</span>
                <span class="font-bold text-gray-800">{{ activity.totalActions }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">This Month</span>
                <span class="font-bold text-blue-600">{{ activity.thisMonth }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Last Login</span>
                <span class="text-xs font-bold text-green-600">{{ activity.lastLogin }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-2">
          <div class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <div class="mb-6 flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-user mr-2 text-blue-500"></i>Personal Information
              </h3>

              <button
                v-if="!editMode"
                type="button"
                @click="toggleEditMode"
                class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700"
              >
                <i class="fas fa-edit mr-2"></i>Edit Profile
              </button>
            </div>

            <form @submit.prevent="submitProfileForm" class="space-y-4">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-user mr-1 text-gray-400"></i>Full Name
                  </label>
                  <input
                    v-model="profileForm.name"
                    type="text"
                    name="name"
                    :readonly="!editMode"
                    :class="inputClass(!editMode)"
                  />
                </div>

                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-envelope mr-1 text-gray-400"></i>Email Address
                  </label>
                  <input
                    v-model="profileForm.email"
                    type="email"
                    name="email"
                    :readonly="!editMode"
                    :class="inputClass(!editMode)"
                  />
                </div>

                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-phone mr-1 text-gray-400"></i>Phone Number
                  </label>
                  <input
                    v-model="profileForm.phone"
                    type="text"
                    name="phone"
                    :readonly="!editMode"
                    :class="inputClass(!editMode)"
                  />
                </div>

                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-shield-alt mr-1 text-gray-400"></i>Role
                  </label>
                  <input
                    type="text"
                    value="Administrator"
                    readonly
                    class="w-full rounded-lg border border-gray-300 bg-yellow-50 px-4 py-2 font-bold text-yellow-800"
                  />
                </div>

                <div class="col-span-2">
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>Address
                  </label>
                  <input
                    v-model="profileForm.address"
                    type="text"
                    name="address"
                    :readonly="!editMode"
                    :class="inputClass(!editMode)"
                  />
                </div>
              </div>

              <div v-if="editMode" class="flex space-x-3 pt-4">
                <button
                  type="submit"
                  :disabled="profileLoading"
                  class="flex-1 rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-blue-700 disabled:opacity-60"
                >
                  <i class="fas fa-save mr-2"></i>
                  {{ profileLoading ? 'Saving...' : 'Save Changes' }}
                </button>

                <button
                  type="button"
                  @click="cancelEditMode"
                  class="rounded-lg bg-gray-300 px-6 py-3 font-semibold text-gray-700 transition hover:bg-gray-400"
                >
                  <i class="fas fa-times mr-2"></i>Cancel
                </button>
              </div>
            </form>
          </div>

          <div class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <h3 class="mb-6 text-xl font-bold text-gray-800">
              <i class="fas fa-lock mr-2 text-blue-500"></i>Change Password
            </h3>

            <form @submit.prevent="submitPasswordForm" class="space-y-4">
              <div>
                <label class="mb-2 block text-sm font-semibold text-gray-700">
                  <i class="fas fa-key mr-1 text-gray-400"></i>Current Password
                </label>
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter current password"
                />
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-lock mr-1 text-gray-400"></i>New Password
                  </label>
                  <input
                    v-model="passwordForm.new_password"
                    type="password"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter new password"
                  />
                  <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-semibold text-gray-700">
                    <i class="fas fa-lock mr-1 text-gray-400"></i>Confirm Password
                  </label>
                  <input
                    v-model="passwordForm.new_password_confirmation"
                    type="password"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    placeholder="Confirm new password"
                  />
                </div>
              </div>

              <button
                type="submit"
                :disabled="passwordLoading"
                class="w-full rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-blue-700 disabled:opacity-60"
              >
                <i class="fas fa-key mr-2"></i>
                {{ passwordLoading ? 'Updating...' : 'Update Password' }}
              </button>
            </form>
          </div>

          <div class="rounded-lg bg-white p-6 shadow-lg">
            <h3 class="mb-6 text-xl font-bold text-gray-800">
              <i class="fas fa-shield-alt mr-2 text-blue-500"></i>Security Settings
            </h3>

            <div class="space-y-4">
              <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                <div>
                  <p class="font-semibold text-gray-800">Two-Factor Authentication</p>
                  <p class="text-sm text-gray-600">Add an extra layer of security</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input type="checkbox" class="peer sr-only" disabled />
                  <div class="peer h-6 w-11 rounded-full bg-gray-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300"></div>
                  <span class="ml-3 text-sm text-gray-500">Coming Soon</span>
                </label>
              </div>

              <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                <div>
                  <p class="font-semibold text-gray-800">Login Notifications</p>
                  <p class="text-sm text-gray-600">Get notified of new logins</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input type="checkbox" class="peer sr-only" checked disabled />
                  <div class="peer h-6 w-11 rounded-full bg-gray-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300"></div>
                  <span class="ml-3 text-sm text-green-600">Active</span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>