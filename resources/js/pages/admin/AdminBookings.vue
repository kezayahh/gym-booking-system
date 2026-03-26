<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import adminApi from '../../services/adminApi'

const loading = ref(false)
const showCreateModal = ref(false)
const showCancelModal = ref(false)
const showDetailsModal = ref(false)
const cancellingBookingId = ref(null)
const bookingDetails = ref(null)

const stats = reactive({
  totalBookings: 0,
  pendingBookings: 0,
  confirmedBookings: 0,
  todayRevenue: 0,
})

const filters = reactive({
  search: '',
  status: '',
  date_from: '',
  date_to: '',
})

const bookings = ref([])
const users = ref([])
const availableSchedules = ref([])

const createForm = reactive({
  user_id: '',
  schedule_id: '',
  number_of_slots: 1,
  special_requests: '',
})

const cancelForm = reactive({
  cancellation_reason: '',
})

const currency = (value) => {
  const amount = Number(value || 0)
  return amount.toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const initials = (name) => {
  if (!name) return '--'
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const statusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'confirmed':
      return 'bg-blue-100 text-blue-800'
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const paymentStatusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'failed':
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const filteredBookings = computed(() => {
  return bookings.value.filter((booking) => {
    const search = filters.search.trim().toLowerCase()
    const bookingDate = booking.schedule?.date || ''

    const matchesSearch =
      !search ||
      booking.booking_number?.toLowerCase().includes(search) ||
      booking.user?.name?.toLowerCase().includes(search) ||
      booking.user?.email?.toLowerCase().includes(search)

    const matchesStatus = !filters.status || booking.status === filters.status
    const matchesDateFrom = !filters.date_from || bookingDate >= filters.date_from
    const matchesDateTo = !filters.date_to || bookingDate <= filters.date_to

    return matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo
  })
})

const resetCreateForm = () => {
  createForm.user_id = ''
  createForm.schedule_id = ''
  createForm.number_of_slots = 1
  createForm.special_requests = ''
}

const openCreateModal = () => {
  resetCreateForm()
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  resetCreateForm()
}

const openCancelModal = (bookingId) => {
  cancellingBookingId.value = bookingId
  cancelForm.cancellation_reason = ''
  showCancelModal.value = true
}

const closeCancelModal = () => {
  showCancelModal.value = false
  cancellingBookingId.value = null
  cancelForm.cancellation_reason = ''
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  bookingDetails.value = null
}

const fetchPageData = async () => {
  loading.value = true

  try {
    const [statsRes, bookingsRes, usersRes, schedulesRes] = await Promise.all([
      adminApi.get('/bookings/stats'),
      adminApi.get('/bookings'),
      adminApi.get('/users/active'),
      adminApi.get('/schedules/available'),
    ])

    stats.totalBookings = statsRes.data.totalBookings ?? 0
    stats.pendingBookings = statsRes.data.pendingBookings ?? 0
    stats.confirmedBookings = statsRes.data.confirmedBookings ?? 0
    stats.todayRevenue = statsRes.data.todayRevenue ?? 0

    bookings.value = bookingsRes.data.bookings ?? bookingsRes.data ?? []
    users.value = usersRes.data.users ?? usersRes.data ?? []
    availableSchedules.value = schedulesRes.data.schedules ?? schedulesRes.data ?? []
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load booking management data.')
  } finally {
    loading.value = false
  }
}

const submitCreateForm = async () => {
  loading.value = true

  try {
    const { data } = await adminApi.post('/bookings', {
      user_id: createForm.user_id,
      schedule_id: createForm.schedule_id,
      number_of_slots: createForm.number_of_slots,
      special_requests: createForm.special_requests,
    })

    alert(data.message || 'Booking created successfully.')
    closeCreateModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

const submitCancelForm = async () => {
  if (!cancelForm.cancellation_reason.trim()) {
    alert('Please provide a cancellation reason.')
    return
  }

  loading.value = true

  try {
    const { data } = await adminApi.post(`/bookings/${cancellingBookingId.value}/cancel`, {
      cancellation_reason: cancelForm.cancellation_reason,
    })

    alert(data.message || 'Booking cancelled successfully.')
    closeCancelModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

const viewDetails = async (bookingId) => {
  try {
    const { data } = await adminApi.get(`/bookings/${bookingId}`)
    bookingDetails.value = data.booking ?? data
    showDetailsModal.value = true
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load booking details.')
  }
}

const confirmBooking = async (bookingId) => {
  if (!window.confirm('Are you sure you want to confirm this booking?')) return

  try {
    const { data } = await adminApi.post(`/bookings/${bookingId}/confirm`)
    alert(data.message || 'Booking confirmed successfully.')
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

const completeBooking = async (bookingId) => {
  if (!window.confirm('Are you sure you want to mark this booking as completed?')) return

  try {
    const { data } = await adminApi.post(`/bookings/${bookingId}/complete`)
    alert(data.message || 'Booking completed successfully.')
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

const deleteBooking = async (bookingId) => {
  if (!window.confirm('Are you sure you want to delete this booking? This action cannot be undone.')) return

  try {
    const { data } = await adminApi.delete(`/bookings/${bookingId}`)
    alert(data.message || 'Booking deleted successfully.')
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.date_from = ''
  filters.date_to = ''
}

onMounted(fetchPageData)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Booking Management</h1>
      <p class="mt-1 text-gray-600">Manage all gym bookings and schedules</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Bookings</p>
            <h3 class="mt-1 text-3xl font-bold text-gray-800">
              {{ stats.totalBookings }}
            </h3>
          </div>
          <div class="rounded-lg bg-blue-100 p-3">
            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Pending</p>
            <h3 class="mt-1 text-3xl font-bold text-yellow-600">
              {{ stats.pendingBookings }}
            </h3>
          </div>
          <div class="rounded-lg bg-yellow-100 p-3">
            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Confirmed</p>
            <h3 class="mt-1 text-3xl font-bold text-green-600">
              {{ stats.confirmedBookings }}
            </h3>
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
            <p class="text-sm text-gray-600">Today's Revenue</p>
            <h3 class="mt-1 text-3xl font-bold text-teal-600">
              ₱{{ currency(stats.todayRevenue) }}
            </h3>
          </div>
          <div class="rounded-lg bg-teal-100 p-3">
            <svg class="h-8 w-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Booking #, user name..."
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Date From</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Date To</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div class="flex items-end gap-2">
          <button
            type="button"
            class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700"
          >
            Filter
          </button>
          <button
            type="button"
            @click="clearFilters"
            class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
          >
            Clear
          </button>
        </div>
      </div>
    </div>

    <div class="rounded-lg bg-white shadow">
      <div class="flex items-center justify-between border-b border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-800">All Bookings</h2>
        <button
          @click="openCreateModal"
          class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-white transition hover:bg-teal-700"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create Booking
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Booking #</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Schedule</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Slots</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="!filteredBookings.length">
              <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="mt-2">No bookings found</p>
              </td>
            </tr>

            <tr
              v-for="booking in filteredBookings"
              :key="booking.id"
              class="hover:bg-gray-50"
            >
              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm font-medium text-gray-900">
                  {{ booking.booking_number }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ booking.created_at_formatted || booking.created_at }}
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 font-semibold text-white">
                      {{ initials(booking.user?.name) }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ booking.user?.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ booking.user?.email }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm text-gray-900">
                  {{ booking.schedule?.date_formatted || booking.schedule?.date }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ booking.schedule?.timeSlot }}
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ booking.number_of_slots }}
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                ₱{{ currency(booking.total_amount) }}
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize leading-5"
                  :class="statusClass(booking.status)"
                >
                  {{ booking.status }}
                </span>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span
                  v-if="booking.payment"
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize leading-5"
                  :class="paymentStatusClass(booking.payment.status)"
                >
                  {{ booking.payment.status === 'completed' ? 'Paid' : booking.payment.status }}
                </span>

                <span
                  v-else
                  class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold leading-5 text-gray-800"
                >
                  No Payment
                </span>
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                <button @click="viewDetails(booking.id)" class="mr-2 text-blue-600 hover:text-blue-900">
                  View
                </button>

                <button
                  v-if="booking.status === 'pending'"
                  @click="confirmBooking(booking.id)"
                  class="mr-2 text-green-600 hover:text-green-900"
                >
                  Confirm
                </button>

                <button
                  v-if="booking.status === 'confirmed'"
                  @click="completeBooking(booking.id)"
                  class="mr-2 text-teal-600 hover:text-teal-900"
                >
                  Complete
                </button>

                <button
                  v-if="['pending', 'confirmed'].includes(booking.status)"
                  @click="openCancelModal(booking.id)"
                  class="mr-2 text-red-600 hover:text-red-900"
                >
                  Cancel
                </button>

                <button
                  @click="deleteBooking(booking.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeCreateModal"
        ></div>

        <div class="relative z-10 inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
          <form @submit.prevent="submitCreateForm">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900">Create New Booking</h3>
            </div>

            <div class="px-6 py-4">
              <div class="grid grid-cols-1 gap-4">
                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Select User *</label>
                  <select
                    v-model="createForm.user_id"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Choose a user...</option>
                    <option
                      v-for="user in users"
                      :key="user.id"
                      :value="user.id"
                    >
                      {{ user.name }} ({{ user.email }})
                    </option>
                  </select>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Select Schedule *</label>
                  <select
                    v-model="createForm.schedule_id"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Choose a schedule...</option>
                    <option
                      v-for="schedule in availableSchedules"
                      :key="schedule.id"
                      :value="schedule.id"
                    >
                      {{ schedule.date_formatted || schedule.date }} - {{ schedule.timeSlot }}
                      ({{ schedule.availableSlots }} slots available - ₱{{ currency(schedule.price_per_slot) }}/slot)
                    </option>
                  </select>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Number of Slots *</label>
                  <input
                    v-model="createForm.number_of_slots"
                    type="number"
                    min="1"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Special Requests</label>
                  <textarea
                    v-model="createForm.special_requests"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
              <button
                type="button"
                @click="closeCreateModal"
                class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="rounded-lg bg-teal-600 px-4 py-2 text-white transition hover:bg-teal-700 disabled:opacity-50"
              >
                {{ loading ? 'Processing...' : 'Create Booking' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div
      v-if="showCancelModal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeCancelModal"
        ></div>

        <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
          <form @submit.prevent="submitCancelForm">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900">Cancel Booking</h3>
            </div>

            <div class="px-6 py-4">
              <label class="mb-2 block text-sm font-medium text-gray-700">Cancellation Reason *</label>
              <textarea
                v-model="cancelForm.cancellation_reason"
                rows="4"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                placeholder="Please provide a reason for cancellation..."
              ></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
              <button
                type="button"
                @click="closeCancelModal"
                class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
              >
                Back
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="rounded-lg bg-red-600 px-4 py-2 text-white transition hover:bg-red-700 disabled:opacity-50"
              >
                {{ loading ? 'Processing...' : 'Cancel Booking' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div
      v-if="showDetailsModal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeDetailsModal"
        ></div>

        <div class="relative z-10 inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
          <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Booking Details</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="bookingDetails" class="px-6 py-4">
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">Booking Number</p>
                  <p class="text-base font-medium">{{ bookingDetails.booking_number }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Status</p>
                  <p class="text-base font-medium capitalize">{{ bookingDetails.status }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Customer Name</p>
                  <p class="text-base font-medium">{{ bookingDetails.user?.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Customer Email</p>
                  <p class="text-base font-medium">{{ bookingDetails.user?.email }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Number of Slots</p>
                  <p class="text-base font-medium">{{ bookingDetails.number_of_slots }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Total Amount</p>
                  <p class="text-base font-medium">₱{{ currency(bookingDetails.total_amount) }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-sm text-gray-600">Special Requests</p>
                  <p class="text-base">{{ bookingDetails.special_requests || 'None' }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4">
            <button
              @click="closeDetailsModal"
              class="rounded-lg bg-gray-200 px-4 py-2 text-gray-800 transition hover:bg-gray-300"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>