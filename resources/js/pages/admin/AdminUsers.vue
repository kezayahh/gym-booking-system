<script setup>
import { ref, reactive, onMounted } from 'vue'
import adminApi from '../../services/adminApi'

const loading = ref(true)
const tableLoading = ref(false)
const modalLoading = ref(false)
const showModal = ref(false)
const isEdit = ref(false)
const editingUserId = ref(null)

const stats = ref({
  totalUsers: 0,
  activeUsers: 0,
  suspendedUsers: 0,
  totalAdmins: 0,
})

const users = ref([])

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

const filters = reactive({
  search: '',
  role: '',
  status: '',
})

const errors = ref({})

const formData = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  address: '',
  date_of_birth: '',
  gender: '',
  role: 'user',
  status: 'active',
})

const resetForm = () => {
  formData.name = ''
  formData.email = ''
  formData.password = ''
  formData.password_confirmation = ''
  formData.phone = ''
  formData.address = ''
  formData.date_of_birth = ''
  formData.gender = ''
  formData.role = 'user'
  formData.status = 'active'
  errors.value = {}
  editingUserId.value = null
}

const openCreateModal = () => {
  isEdit.value = false
  modalLoading.value = false
  resetForm()
  showModal.value = true
}

const openEditModal = async (userId) => {
  isEdit.value = true
  modalLoading.value = true
  editingUserId.value = userId
  showModal.value = true
  errors.value = {}

  try {
    const response = await adminApi.get(`/users/${userId}`)
    const user = response.data.user

    formData.name = user.name || ''
    formData.email = user.email || ''
    formData.password = ''
    formData.password_confirmation = ''
    formData.phone = user.phone || ''
    formData.address = user.address || ''
    formData.date_of_birth = user.date_of_birth || ''
    formData.gender = user.gender || ''
    formData.role = user.role || 'user'
    formData.status = user.status || 'active'
  } catch (error) {
    console.error('Error loading user:', error)
    alert('Failed to load user data.')
    closeModal()
  } finally {
    modalLoading.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const shortAddress = (address) => {
  if (!address) return 'No address'
  return address.length > 30 ? `${address.slice(0, 30)}...` : address
}

const roleClass = (role) => {
  return role === 'admin'
    ? 'bg-purple-100 text-purple-800'
    : 'bg-blue-100 text-blue-800'
}

const statusClass = (status) => {
  return status === 'active'
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}

const loadUsers = async (page = 1) => {
  tableLoading.value = true

  try {
    const response = await adminApi.get('/users', {
      params: {
        page,
        search: filters.search,
        role: filters.role,
        status: filters.status,
      },
    })

    const data = response.data

    stats.value = data.stats
    users.value = data.users.data
    pagination.value = {
      current_page: data.users.current_page,
      last_page: data.users.last_page,
      from: data.users.from,
      to: data.users.to,
      total: data.users.total,
    }
  } catch (error) {
    console.error('Error loading users:', error)
    alert(error.response?.data?.message || 'Failed to load users.')
  } finally {
    loading.value = false
    tableLoading.value = false
  }
}

const submitFilters = () => {
  loadUsers(1)
}

const clearFilters = () => {
  filters.search = ''
  filters.role = ''
  filters.status = ''
  loadUsers(1)
}

const submitForm = async () => {
  modalLoading.value = true
  errors.value = {}

  try {
    const response = isEdit.value
      ? await adminApi.put(`/users/${editingUserId.value}`, formData)
      : await adminApi.post('/users', formData)

    if (response.data.success) {
      alert(response.data.message)
      closeModal()
      loadUsers(pagination.value.current_page)
    } else {
      errors.value = response.data.errors || {}
      alert(response.data.message || 'An error occurred')
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      console.error('Error submitting form:', error)
      alert(error.response?.data?.message || 'An error occurred. Please try again.')
    }
  } finally {
    modalLoading.value = false
  }
}

const toggleStatus = async (userId) => {
  if (!confirm("Are you sure you want to change this user's status?")) return

  try {
    const response = await adminApi.patch(`/users/${userId}/toggle-status`)

    if (response.data.success) {
      alert(response.data.message)
      loadUsers(pagination.value.current_page)
    } else {
      alert(response.data.message || 'An error occurred.')
    }
  } catch (error) {
    console.error('Error toggling status:', error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

const deleteUser = async (userId) => {
  if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return

  try {
    const response = await adminApi.delete(`/users/${userId}`)

    if (response.data.success) {
      alert(response.data.message)

      const nextPage =
        users.value.length === 1 && pagination.value.current_page > 1
          ? pagination.value.current_page - 1
          : pagination.value.current_page

      loadUsers(nextPage)
    } else {
      alert(response.data.message || 'An error occurred.')
    }
  } catch (error) {
    console.error('Error deleting user:', error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<template>
  <div v-if="loading" class="py-10 text-center text-gray-500">
    Loading users...
  </div>

  <template v-else>
    <div>
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
        <p class="mt-1 text-gray-600">Manage system users and their permissions</p>
      </div>

      <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
        <div class="rounded-lg bg-white p-6 shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Users</p>
              <h3 class="mt-1 text-3xl font-bold text-gray-800">{{ stats.totalUsers }}</h3>
            </div>
            <div class="rounded-lg bg-blue-100 p-3">
              <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active Users</p>
              <h3 class="mt-1 text-3xl font-bold text-green-600">{{ stats.activeUsers }}</h3>
            </div>
            <div class="rounded-lg bg-green-100 p-3">
              <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Suspended</p>
              <h3 class="mt-1 text-3xl font-bold text-red-600">{{ stats.suspendedUsers }}</h3>
            </div>
            <div class="rounded-lg bg-red-100 p-3">
              <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Administrators</p>
              <h3 class="mt-1 text-3xl font-bold text-purple-600">{{ stats.totalAdmins }}</h3>
            </div>
            <div class="rounded-lg bg-purple-100 p-3">
              <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Name, email, phone..."
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
              @keyup.enter="submitFilters"
            >
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Role</label>
            <select
              v-model="filters.role"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Roles</option>
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
            <select
              v-model="filters.status"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <button
              type="button"
              class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700"
              @click="submitFilters"
            >
              Filter
            </button>

            <button
              type="button"
              class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
              @click="clearFilters"
            >
              Clear
            </button>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white shadow">
        <div class="flex items-center justify-between border-b border-gray-200 p-6">
          <h2 class="text-xl font-semibold text-gray-800">All Users</h2>
          <button
            class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-white transition hover:bg-teal-700"
            @click="openCreateModal"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add New User
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Joined</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
              </tr>
            </thead>

            <tbody v-if="!tableLoading" class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="user in users"
                :key="user.id"
                class="hover:bg-gray-50"
              >
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-teal-600 font-semibold text-white">
                        {{ user.initials }}
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                      <div class="text-sm text-gray-500">{{ user.email }}</div>
                    </div>
                  </div>
                </td>

                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm text-gray-900">{{ user.phone || 'N/A' }}</div>
                  <div class="text-sm text-gray-500">{{ shortAddress(user.address) }}</div>
                </td>

                <td class="whitespace-nowrap px-6 py-4">
                  <span
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold leading-5"
                    :class="roleClass(user.role)"
                  >
                    {{ user.role === 'admin' ? 'Admin' : 'User' }}
                  </span>
                </td>

                <td class="whitespace-nowrap px-6 py-4">
                  <span
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold leading-5"
                    :class="statusClass(user.status)"
                  >
                    {{ user.status === 'active' ? 'Active' : 'Suspended' }}
                  </span>
                </td>

                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                  {{ user.joined }}
                </td>

                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                  <button
                    class="mr-3 text-blue-600 hover:text-blue-900"
                    @click="openEditModal(user.id)"
                  >
                    Edit
                  </button>

                  <button
                    class="mr-3 text-yellow-600 hover:text-yellow-900"
                    @click="toggleStatus(user.id)"
                  >
                    {{ user.status === 'active' ? 'Suspend' : 'Activate' }}
                  </button>

                  <button
                    class="text-red-600 hover:text-red-900"
                    @click="deleteUser(user.id)"
                  >
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="!users.length">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                  </svg>
                  <p class="mt-2">No users found</p>
                </td>
              </tr>
            </tbody>

            <tbody v-else>
              <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  Loading users...
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
          <p class="text-sm text-gray-600">
            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} users
          </p>

          <div class="flex items-center gap-2">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="pagination.current_page <= 1"
              @click="loadUsers(pagination.current_page - 1)"
            >
              Previous
            </button>

            <span class="text-sm text-gray-600">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>

            <button
              class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="loadUsers(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="showModal"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="closeModal"
          ></div>

          <div class="relative inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
            <form @submit.prevent="submitForm">
              <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ isEdit ? 'Edit User' : 'Create New User' }}
                </h3>
              </div>

              <div class="max-h-96 overflow-y-auto px-6 py-4">
                <div v-if="modalLoading && isEdit" class="py-8 text-center text-gray-500">
                  Loading user data...
                </div>

                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Name *</label>
                    <input
                      v-model="formData.name"
                      type="text"
                      required
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                    <span v-if="errors.name" class="text-sm text-red-600">{{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}</span>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Email *</label>
                    <input
                      v-model="formData.email"
                      type="email"
                      required
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                    <span v-if="errors.email" class="text-sm text-red-600">{{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}</span>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                      Password <span v-if="isEdit">(leave blank to keep current)</span><span v-else>*</span>
                    </label>
                    <input
                      v-model="formData.password"
                      type="password"
                      :required="!isEdit"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                    <span v-if="errors.password" class="text-sm text-red-600">{{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}</span>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                      Confirm Password <span v-if="!isEdit">*</span>
                    </label>
                    <input
                      v-model="formData.password_confirmation"
                      type="password"
                      :required="!isEdit"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Phone</label>
                    <input
                      v-model="formData.phone"
                      type="text"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Date of Birth</label>
                    <input
                      v-model="formData.date_of_birth"
                      type="date"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Gender</label>
                    <select
                      v-model="formData.gender"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                    </select>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Role *</label>
                    <select
                      v-model="formData.role"
                      required
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Status *</label>
                    <select
                      v-model="formData.status"
                      required
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="active">Active</option>
                      <option value="suspended">Suspended</option>
                    </select>
                  </div>

                  <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Address</label>
                    <textarea
                      v-model="formData.address"
                      rows="3"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button
                  type="button"
                  class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
                  @click="closeModal"
                >
                  Cancel
                </button>

                <button
                  id="user-submit-btn"
                  type="submit"
                  :disabled="modalLoading"
                  class="rounded-lg px-4 py-2 text-white transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  {{ modalLoading ? 'Processing...' : (isEdit ? 'Update User' : 'Create User') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </template>
</template>

<style scoped>
#user-submit-btn {
  background-color: #0f766e !important;
  color: #ffffff !important;
}

#user-submit-btn:hover:not([disabled]) {
  background-color: #0d5f5a !important;
}

#user-submit-btn[disabled] {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>