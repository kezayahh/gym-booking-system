<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../../services/api'

const loading = ref(false)
const actionLoading = ref(false)
const error = ref('')
const success = ref('')

const notifications = ref([])
const stats = ref({
  totalCount: 0,
  unreadCount: 0,
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
})

const filters = reactive({
  search: '',
  status: '',
})

const confirmDeleteAllOpen = ref(false)
const confirmDeleteOneOpen = ref(false)
const selectedNotification = ref(null)

const normalize = (value) => String(value || '').toLowerCase().trim()

const filteredNotifications = computed(() => {
  const search = normalize(filters.search)
  const status = normalize(filters.status)

  return notifications.value.filter((notification) => {
    const title = normalize(notification.title)
    const message = normalize(notification.message)
    const type = normalize(notification.type)
    const readState = notification.is_read ? 'read' : 'unread'

    const matchesSearch =
      !search ||
      title.includes(search) ||
      message.includes(search) ||
      type.includes(search)

    const matchesStatus =
      !status || readState === status

    return matchesSearch && matchesStatus
  })
})

const hasNotifications = computed(() => filteredNotifications.value.length > 0)

const getTypeIcon = (type) => {
  const t = normalize(type)

  if (t.includes('booking')) return 'fas fa-calendar-check'
  if (t.includes('payment')) return 'fas fa-credit-card'
  if (t.includes('refund')) return 'fas fa-undo'
  if (t.includes('system')) return 'fas fa-cog'
  return 'fas fa-bell'
}

const getTypeWrapClass = (type) => {
  const t = normalize(type)

  if (t.includes('booking')) return 'bg-blue-100 text-blue-600'
  if (t.includes('payment')) return 'bg-green-100 text-green-600'
  if (t.includes('refund')) return 'bg-red-100 text-red-600'
  if (t.includes('system')) return 'bg-purple-100 text-purple-600'
  return 'bg-yellow-100 text-yellow-600'
}

const loadNotifications = async (page = 1) => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.get('/api/user/notifications', {
      params: { page },
    })

    notifications.value = data.notifications?.data || []

    stats.value = {
      totalCount: data.stats?.totalCount || 0,
      unreadCount: data.stats?.unreadCount || 0,
    }

    pagination.current_page = data.notifications?.current_page || 1
    pagination.last_page = data.notifications?.last_page || 1
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load notifications.'
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notification) => {
  if (!notification || notification.is_read) return

  actionLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post(`/api/user/notifications/${notification.id}/read`)

    if (data?.success) {
      notification.is_read = true

      if (stats.value.unreadCount > 0) {
        stats.value.unreadCount--
      }
    } else {
      error.value = data?.message || 'Failed to mark notification as read.'
    }
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to mark notification as read.'
  } finally {
    actionLoading.value = false
  }
}

const markAllAsRead = async () => {
  if (!stats.value.unreadCount) return

  actionLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/api/user/notifications/mark-all-read')

    if (data?.success) {
      notifications.value = notifications.value.map((item) => ({
        ...item,
        is_read: true,
      }))
      stats.value.unreadCount = 0
      success.value = 'All notifications marked as read.'
    } else {
      error.value = 'Failed to mark all notifications as read.'
    }
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to mark all notifications as read.'
  } finally {
    actionLoading.value = false
  }
}

const openDeleteOne = (notification) => {
  selectedNotification.value = notification
  confirmDeleteOneOpen.value = true
}

const closeDeleteOne = () => {
  selectedNotification.value = null
  confirmDeleteOneOpen.value = false
}

const deleteNotification = async () => {
  if (!selectedNotification.value) return

  actionLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post(`/api/user/notifications/${selectedNotification.value.id}/delete`)

    if (data?.success) {
      const wasUnread = !selectedNotification.value.is_read

      notifications.value = notifications.value.filter(
        (item) => item.id !== selectedNotification.value.id
      )

      if (stats.value.totalCount > 0) {
        stats.value.totalCount--
      }

      if (wasUnread && stats.value.unreadCount > 0) {
        stats.value.unreadCount--
      }

      success.value = 'Notification deleted successfully.'
      closeDeleteOne()
    } else {
      error.value = 'Failed to delete notification.'
    }
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to delete notification.'
  } finally {
    actionLoading.value = false
  }
}

const deleteAll = async () => {
  actionLoading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post('/api/user/notifications/delete-all')

    if (data?.success) {
      notifications.value = []
      stats.value.totalCount = 0
      stats.value.unreadCount = 0
      confirmDeleteAllOpen.value = false
      success.value = 'All notifications deleted successfully.'
    } else {
      error.value = 'Failed to delete all notifications.'
    }
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to delete all notifications.'
  } finally {
    actionLoading.value = false
  }
}

const goToPage = async (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  await loadNotifications(page)
}

onMounted(() => {
  loadNotifications()
})
</script>

<template>
  <div>
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

    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">
          <i class="fas fa-bell text-blue-500 mr-2"></i>Notifications
        </h1>
        <p class="text-gray-600 mt-1">View and manage your latest updates</p>
      </div>

      <div class="flex flex-wrap gap-3">
        <button
          class="inline-flex items-center justify-center rounded-lg bg-blue-400 px-6 py-3 font-semibold text-white shadow hover:bg-blue-500 transition disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="actionLoading || !stats.unreadCount"
          @click="markAllAsRead"
        >
          <i class="fas fa-check-double mr-2"></i>Mark All Read
        </button>

        <button
          class="inline-flex items-center justify-center rounded-lg bg-red-400 px-6 py-3 font-semibold text-white shadow hover:bg-red-500 transition disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="actionLoading || !stats.totalCount"
          @click="confirmDeleteAllOpen = true"
        >
          <i class="fas fa-trash-alt mr-2"></i>Delete All
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-blue-100 rounded-full p-4 mr-4 shrink-0">
            <i class="fas fa-bell text-blue-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Total Notifications</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.totalCount }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-yellow-100 rounded-full p-4 mr-4 shrink-0">
            <i class="fas fa-envelope text-yellow-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Unread</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.unreadCount }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input
            v-model="filters.search"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Search title, message, or type"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Notifications</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Notifications Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-6">
        <h3 class="text-xl font-bold text-gray-800">
          <i class="fas fa-list-ul mr-2"></i>All Notifications
        </h3>
      </div>

      <div v-if="loading" class="px-6 py-16 text-center text-gray-500">
        Loading notifications...
      </div>

      <template v-else-if="hasNotifications">
        <div class="divide-y divide-gray-200">
          <div
            v-for="notification in filteredNotifications"
            :key="notification.id"
            class="px-6 py-5 hover:bg-gray-50 transition"
            :class="!notification.is_read ? 'bg-blue-50/40' : ''"
          >
            <div class="flex items-start gap-4">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                :class="getTypeWrapClass(notification.type)"
              >
                <i :class="getTypeIcon(notification.type)"></i>
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <h4 class="text-base font-bold text-gray-800">
                        {{ notification.title }}
                      </h4>

                      <span
                        v-if="!notification.is_read"
                        class="rounded-full bg-blue-100 px-2 py-1 text-[11px] font-semibold text-blue-700"
                      >
                        Unread
                      </span>
                    </div>

                    <p class="mt-2 text-sm text-gray-600">
                      {{ notification.message }}
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-500">
                      <span class="capitalize">
                        <i class="fas fa-tag mr-1"></i>{{ notification.type || 'general' }}
                      </span>
                      <span>
                        <i class="fas fa-clock mr-1"></i>{{ notification.created_at }}
                      </span>
                    </div>
                  </div>

                  <div class="flex shrink-0 items-center gap-2">
                    <button
                      v-if="!notification.is_read"
                      class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition disabled:opacity-50"
                      :disabled="actionLoading"
                      @click="markAsRead(notification)"
                    >
                      <i class="fas fa-check mr-1"></i>Mark Read
                    </button>

                    <button
                      class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 transition disabled:opacity-50"
                      :disabled="actionLoading"
                      @click="openDeleteOne(notification)"
                    >
                      <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="pagination.last_page > 1"
          class="border-t border-gray-200 px-6 py-4"
        >
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </p>

            <div class="flex gap-2">
              <button
                class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 transition disabled:opacity-50"
                :disabled="pagination.current_page === 1"
                @click="goToPage(pagination.current_page - 1)"
              >
                Previous
              </button>

              <button
                class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 transition disabled:opacity-50"
                :disabled="pagination.current_page === pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </template>

      <div v-else class="px-6 py-20 text-center">
        <i class="fas fa-bell-slash text-7xl text-gray-300"></i>
        <p class="mt-4 text-lg font-semibold text-gray-600">No notifications found</p>
        <p class="mt-1 text-sm text-gray-500">Your notifications will appear here.</p>
      </div>
    </div>

    <!-- Delete One Modal -->
    <div
      v-if="confirmDeleteOneOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-800">Delete Notification</h3>
          <button @click="closeDeleteOne" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <p class="text-gray-600 mb-6">
          Are you sure you want to delete
          <strong>{{ selectedNotification?.title }}</strong>?
        </p>

        <div class="flex items-center justify-end gap-3 pt-4 border-t">
          <button
            class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 transition"
            @click="closeDeleteOne"
          >
            Cancel
          </button>

          <button
            class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700 transition disabled:opacity-50"
            :disabled="actionLoading"
            @click="deleteNotification"
          >
            <i class="fas fa-trash mr-2"></i>Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Delete All Modal -->
    <div
      v-if="confirmDeleteAllOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-800">Delete All Notifications</h3>
          <button @click="confirmDeleteAllOpen = false" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <p class="text-gray-600 mb-6">
          Are you sure you want to delete all notifications? This cannot be undone.
        </p>

        <div class="flex items-center justify-end gap-3 pt-4 border-t">
          <button
            class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 transition"
            @click="confirmDeleteAllOpen = false"
          >
            Cancel
          </button>

          <button
            class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700 transition disabled:opacity-50"
            :disabled="actionLoading"
            @click="deleteAll"
          >
            <i class="fas fa-trash-alt mr-2"></i>Delete All
          </button>
        </div>
      </div>
    </div>
  </div>
</template>