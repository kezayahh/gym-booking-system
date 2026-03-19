<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import AdminLayout from '../../layouts/AdminLayout.vue'

const loading = ref(true)
const tableLoading = ref(false)
const modalLoading = ref(false)
const showModal = ref(false)
const isEdit = ref(false)
const editingUserId = ref(null)

const admin = ref({
  name: 'Admin',
  email: 'admin@citygym.com',
})

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
    const response = await axios.get(`/admin/users/${userId}`)
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
    const response = await axios.get('/admin/users-data', {
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
    const url = isEdit.value
      ? `/admin/users/${editingUserId.value}/update`
      : '/admin/users/store'

    const response = await axios.post(url, formData)

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
    const response = await axios.post(`/admin/users/${userId}/toggle-status`)

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
    const response = await axios.delete(`/admin/users/${userId}`)

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
  <AdminLayout :admin="admin">
    <div v-if="loading" class="py-10 text-center text-gray-500">
      Loading users...
    </div>

    <template v-else>
      <div>
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
          <p class="text-gray-600 mt-1">Manage system users and their permissions</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Total Users</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-1">{{ stats.totalUsers }}</h3>
              </div>
              <div class="bg-blue-100 p-3 rounded-lg">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Active Users</p>
                <h3 class="text-3xl font-bold text-green-600 mt-1">{{ stats.activeUsers }}</h3>
              </div>
              <div class="bg-green-100 p-3 rounded-lg">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Suspended</p>
                <h3 class="text-3xl font-bold text-red-600 mt-1">{{ stats.suspendedUsers }}</h3>
              </div>
              <div class="bg-red-100 p-3 rounded-lg">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Administrators</p>
                <h3 class="text-3xl font-bold text-purple-600 mt-1">{{ stats.totalAdmins }}</h3>
              </div>
              <div class="bg-purple-100 p-3 rounded-lg">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Name, email, phone..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @keyup.enter="submitFilters"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
              <select
                v-model="filters.role"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Roles</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                v-model="filters.status"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>

            <div class="flex items-end gap-2">
              <button
                type="button"
                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                @click="submitFilters"
              >
                Filter
              </button>

              <button
                type="button"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                @click="clearFilters"
              >
                Clear
              </button>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">All Users</h2>
            <button
              class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition flex items-center gap-2"
              @click="openCreateModal"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add New User
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>

              <tbody v-if="!tableLoading" class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="user in users"
                  :key="user.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white font-semibold">
                          {{ user.initials }}
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ user.phone || 'N/A' }}</div>
                    <div class="text-sm text-gray-500">{{ shortAddress(user.address) }}</div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                      :class="roleClass(user.role)"
                    >
                      {{ user.role === 'admin' ? 'Admin' : 'User' }}
                    </span>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                      :class="statusClass(user.status)"
                    >
                      {{ user.status === 'active' ? 'Active' : 'Suspended' }}
                    </span>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ user.joined }}
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      class="text-blue-600 hover:text-blue-900 mr-3"
                      @click="openEditModal(user.id)"
                    >
                      Edit
                    </button>

                    <button
                      class="text-yellow-600 hover:text-yellow-900 mr-3"
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

          <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-600">
              Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} users
            </p>

            <div class="flex items-center gap-2">
              <button
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="pagination.current_page <= 1"
                @click="loadUsers(pagination.current_page - 1)"
              >
                Previous
              </button>

              <span class="text-sm text-gray-600">
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
              </span>

              <button
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
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
          <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div
              class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
              @click="closeModal"
            ></div>

            <div class="relative inline-block w-full max-w-2xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle">
              <form @submit.prevent="submitForm">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-900">
                    {{ isEdit ? 'Edit User' : 'Create New User' }}
                  </h3>
                </div>

                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                  <div v-if="modalLoading && isEdit" class="py-8 text-center text-gray-500">
                    Loading user data...
                  </div>

                  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                      <input
                        v-model="formData.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                      <span v-if="errors.name" class="text-red-600 text-sm">{{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}</span>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                      <input
                        v-model="formData.email"
                        type="email"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                      <span v-if="errors.email" class="text-red-600 text-sm">{{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}</span>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Password <span v-if="isEdit">(leave blank to keep current)</span><span v-else>*</span>
                      </label>
                      <input
                        v-model="formData.password"
                        type="password"
                        :required="!isEdit"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                      <span v-if="errors.password" class="text-red-600 text-sm">{{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}</span>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password <span v-if="!isEdit">*</span>
                      </label>
                      <input
                        v-model="formData.password_confirmation"
                        type="password"
                        :required="!isEdit"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                      <input
                        v-model="formData.phone"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                      <input
                        v-model="formData.date_of_birth"
                        type="date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                      <select
                        v-model="formData.gender"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                      <select
                        v-model="formData.role"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                      <select
                        v-model="formData.status"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                      </select>
                    </div>

                    <div class="md:col-span-2">
                      <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                      <textarea
                        v-model="formData.address"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      ></textarea>
                    </div>
                  </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                  <button
                    type="button"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                    @click="closeModal"
                  >
                    Cancel
                  </button>

                  <button
                    id="user-submit-btn"
                    type="submit"
                    :disabled="modalLoading"
                    class="px-4 py-2 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-1 text-white disabled:opacity-60 disabled:cursor-not-allowed"
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
  </AdminLayout>
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