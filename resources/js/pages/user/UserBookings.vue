<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../../services/api'

const loading = ref(false)
const detailsLoading = ref(false)
const cancelling = ref(false)
const error = ref('')
const success = ref('')

const bookings = ref([])
const stats = ref({
  total: 0,
  pending: 0,
  confirmed: 0,
  cancelled: 0,
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
})

const detailsModalOpen = ref(false)
const cancelModalOpen = ref(false)
const selectedBooking = ref(null)
const bookingDetails = ref(null)

const cancelForm = reactive({
  reason: '',
})

const formatMoney = (value) => {
  return `₱${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`
}

const normalize = (value) => String(value || '').toLowerCase().trim()

const getStatusBadgeClass = (status) => {
  const s = normalize(status)

  if (s === 'pending') return 'bg-yellow-100 text-yellow-800'
  if (s === 'confirmed') return 'bg-green-100 text-green-800'
  if (s === 'cancelled') return 'bg-red-100 text-red-800'
  if (s === 'completed') return 'bg-blue-100 text-blue-800'
  return 'bg-gray-100 text-gray-800'
}

const canCancelBooking = (booking) => {
  return !!booking?.can_cancel
}

const canPayBooking = (booking) => {
  return !booking?.is_paid && normalize(booking?.status) === 'pending'
}

const hasBookings = computed(() => bookings.value.length > 0)

const loadBookings = async (page = 1) => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.get('/api/user/bookings', {
      params: { page },
    })

    bookings.value = data.bookings?.data || []

    stats.value = {
      total: data.stats?.total || 0,
      pending: data.stats?.pending || 0,
      confirmed: data.stats?.confirmed || 0,
      cancelled: data.stats?.cancelled || 0,
    }

    pagination.current_page = data.bookings?.current_page || 1
    pagination.last_page = data.bookings?.last_page || 1
    pagination.per_page = data.bookings?.per_page || 10
    pagination.total = data.bookings?.total || 0
    pagination.from = data.bookings?.from || null
    pagination.to = data.bookings?.to || null
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load bookings.'
  } finally {
    loading.value = false
  }
}

const openDetailsModal = async (booking) => {
  selectedBooking.value = booking
  bookingDetails.value = null
  detailsModalOpen.value = true
  detailsLoading.value = true
  error.value = ''

  try {
    const { data } = await api.get(`/api/user/bookings/${booking.id}/details`)
    bookingDetails.value = data.data || null
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to load booking details.'
  } finally {
    detailsLoading.value = false
  }
}

const closeDetailsModal = () => {
  detailsModalOpen.value = false
  selectedBooking.value = null
  bookingDetails.value = null
}

const openCancelModal = (booking) => {
  selectedBooking.value = booking
  cancelForm.reason = ''
  cancelModalOpen.value = true
  error.value = ''
}

const closeCancelModal = () => {
  cancelModalOpen.value = false
  selectedBooking.value = null
  cancelForm.reason = ''
}

const submitCancelBooking = async () => {
  if (!selectedBooking.value) return

  if (!cancelForm.reason.trim()) {
    error.value = 'Please provide a cancellation reason.'
    return
  }

  cancelling.value = true
  error.value = ''
  success.value = ''

  try {
    await api.get('/sanctum/csrf-cookie')

    const { data } = await api.post(
      `/api/user/bookings/${selectedBooking.value.id}/cancel`,
      {
        reason: cancelForm.reason,
      },
      {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      }
    )

    if (data?.success) {
      success.value = data.message || 'Booking cancelled successfully.'
      closeCancelModal()
      await loadBookings(pagination.current_page)
    } else {
      error.value = data?.message || 'Failed to cancel booking.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.reason?.[0] ||
      'Failed to cancel booking.'
  } finally {
    cancelling.value = false
  }
}

const goToPage = async (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  await loadBookings(page)
}

onMounted(() => {
  loadBookings()
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

    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">
          <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>My Bookings
        </h1>
        <p class="text-gray-600 mt-1">View and manage your gym bookings</p>
      </div>

      <router-link
        to="/user/schedule"
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-lg"
      >
        <i class="fas fa-plus mr-2"></i>New Booking
      </router-link>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-blue-100 rounded-full p-3 mr-4">
            <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Total Bookings</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.total }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-yellow-100 rounded-full p-3 mr-4">
            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Pending</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.pending }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-green-100 rounded-full p-3 mr-4">
            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Confirmed</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.confirmed }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-red-100 rounded-full p-3 mr-4">
            <i class="fas fa-times-circle text-red-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Cancelled</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.cancelled }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">
          <i class="fas fa-list mr-2"></i>All Bookings
        </h3>
      </div>

      <div v-if="loading" class="p-10 text-center text-gray-500">
        Loading bookings...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Booking #
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date &amp; Time
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Slots
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Payment
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="booking in bookings"
              :key="booking.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ booking.booking_number }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ booking.created_at }}
                </div>
              </td>

              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">
                  <i class="fas fa-calendar text-blue-500 mr-1"></i>
                  {{ booking.schedule?.date || 'N/A' }}
                </div>
                <div class="text-xs text-gray-500">
                  <i class="fas fa-clock text-blue-500 mr-1"></i>
                  {{ booking.schedule?.time_slot || 'N/A' }}
                </div>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">
                  <i class="fas fa-users text-gray-400 mr-1"></i>
                  {{ booking.number_of_slots }}
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">
                  {{ formatMoney(booking.total_amount) }}
                </div>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getStatusBadgeClass(booking.status)"
                >
                  <template v-if="normalize(booking.status) === 'pending'">
                    <i class="fas fa-clock mr-1"></i>
                  </template>
                  <template v-else-if="normalize(booking.status) === 'confirmed'">
                    <i class="fas fa-check-circle mr-1"></i>
                  </template>
                  <template v-else-if="normalize(booking.status) === 'cancelled'">
                    <i class="fas fa-times-circle mr-1"></i>
                  </template>
                  <template v-else-if="normalize(booking.status) === 'completed'">
                    <i class="fas fa-flag-checkered mr-1"></i>
                  </template>
                  {{ booking.status_label }}
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  v-if="booking.is_paid"
                  class="text-xs text-green-600 font-semibold"
                >
                  <i class="fas fa-check-circle mr-1"></i>Paid
                </span>
                <span
                  v-else
                  class="text-xs text-red-600 font-semibold"
                >
                  <i class="fas fa-exclamation-circle mr-1"></i>Unpaid
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center space-x-2">
                  <router-link
                    v-if="canPayBooking(booking)"
                    to="/user/payments"
                    class="text-blue-600 hover:text-blue-900 font-semibold"
                    title="Pay Now"
                  >
                    <i class="fas fa-credit-card mr-1"></i>Pay
                  </router-link>

                  <button
                    v-if="canCancelBooking(booking)"
                    type="button"
                    class="text-red-600 hover:text-red-900 font-semibold"
                    title="Cancel Booking"
                    @click="openCancelModal(booking)"
                  >
                    <i class="fas fa-times-circle mr-1"></i>Cancel
                  </button>

                  <button
                    type="button"
                    class="text-gray-600 hover:text-gray-900"
                    title="View Details"
                    @click="openDetailsModal(booking)"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!hasBookings">
              <td colspan="7" class="px-6 py-12 text-center">
                <div class="text-gray-500">
                  <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                  <p class="text-lg font-semibold mb-2">No Bookings Yet</p>
                  <p class="text-sm mb-4">Start by creating your first booking!</p>
                  <router-link
                    to="/user/schedule"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                  >
                    <i class="fas fa-plus mr-2"></i>Create Booking
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pagination.last_page > 1"
        class="p-6 border-t border-gray-200"
      >
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">
            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} bookings
          </p>

          <div class="flex gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition disabled:opacity-50"
              :disabled="pagination.current_page === 1"
              @click="goToPage(pagination.current_page - 1)"
            >
              Previous
            </button>

            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition disabled:opacity-50"
              :disabled="pagination.current_page === pagination.last_page"
              @click="goToPage(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="detailsModalOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-800">Booking Details</h3>
          <button type="button" @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <div v-if="detailsLoading" class="py-8 text-center text-gray-500">
          Loading booking details...
        </div>

        <div v-else-if="bookingDetails" class="space-y-3">
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Booking Number</span>
            <span class="font-semibold">{{ bookingDetails.booking_number }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Date</span>
            <span class="font-semibold">{{ bookingDetails.date }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Time</span>
            <span class="font-semibold">{{ bookingDetails.time }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Slots</span>
            <span class="font-semibold">{{ bookingDetails.slots }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Total</span>
            <span class="font-semibold text-green-600">{{ formatMoney(bookingDetails.total) }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Status</span>
            <span
              class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
              :class="getStatusBadgeClass(bookingDetails.status)"
            >
              {{ bookingDetails.status }}
            </span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-gray-600">Payment</span>
            <span
              class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
              :class="getStatusBadgeClass(bookingDetails.payment)"
            >
              {{ bookingDetails.payment }}
            </span>
          </div>
          <div v-if="bookingDetails.requests" class="pt-2">
            <p class="text-gray-600 mb-1">Special Requests</p>
            <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded-lg">
              {{ bookingDetails.requests }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="cancelModalOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-800">Cancel Booking</h3>
          <button type="button" @click="closeCancelModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <p class="text-gray-600 mb-4">
          Are you sure you want to cancel booking
          <strong>{{ selectedBooking?.booking_number }}</strong>?
        </p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Reason for Cancellation
            </label>
            <textarea
              v-model="cancelForm.reason"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              placeholder="Please provide a reason..."
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t">
            <button
              type="button"
              @click.prevent="closeCancelModal"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
            >
              Back
            </button>

            <button
              type="button"
              @click.prevent="submitCancelBooking"
              :disabled="cancelling"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50"
            >
              <i v-if="cancelling" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-times-circle mr-2"></i>
              {{ cancelling ? 'Cancelling...' : 'Confirm Cancel' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>