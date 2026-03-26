<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import adminApi from '../../services/adminApi'

const loading = ref(false)
const showVerifyModal = ref(false)
const showFailModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)

const currentPaymentId = ref(null)
const paymentDetails = ref(null)

const stats = reactive({
  totalRevenue: 0,
  completedPayments: 0,
  pendingPayments: 0,
  todayRevenue: 0,
})

const filters = reactive({
  search: '',
  status: '',
  payment_method: '',
  date_from: '',
  date_to: '',
})

const paymentMethodStats = ref([])
const payments = ref([])

const verifyForm = reactive({
  transaction_id: '',
  payment_details: '',
})

const failForm = reactive({
  reason: '',
})

const editForm = reactive({
  payment_method: 'cash',
  transaction_id: '',
  payment_details: '',
})

const paymentMethods = [
  'cash',
  'gcash',
  'maya',
  'credit_card',
  'debit_card',
  'bank_transfer',
]

const currency = (value) => {
  const amount = Number(value || 0)
  return amount.toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const formatMethod = (method) => {
  return (method || '').replaceAll('_', ' ')
}

const initials = (name) => {
  if (!name) return '--'
  return name
    .split(' ')
    .map(part => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const statusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    case 'refunded':
      return 'bg-purple-100 text-purple-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const filteredPayments = computed(() => {
  return payments.value.filter((payment) => {
    const search = filters.search.trim().toLowerCase()

    const matchesSearch =
      !search ||
      payment.payment_number?.toLowerCase().includes(search) ||
      payment.booking?.booking_number?.toLowerCase().includes(search) ||
      payment.user?.name?.toLowerCase().includes(search) ||
      payment.user?.email?.toLowerCase().includes(search) ||
      payment.transaction_id?.toLowerCase().includes(search)

    const matchesStatus =
      !filters.status || payment.status === filters.status

    const matchesMethod =
      !filters.payment_method || payment.payment_method === filters.payment_method

    const paymentDate =
      payment.created_at_raw ||
      payment.created_at_date ||
      payment.created_at?.slice?.(0, 10) ||
      ''

    const matchesDateFrom =
      !filters.date_from || paymentDate >= filters.date_from

    const matchesDateTo =
      !filters.date_to || paymentDate <= filters.date_to

    return matchesSearch && matchesStatus && matchesMethod && matchesDateFrom && matchesDateTo
  })
})

const resetVerifyForm = () => {
  verifyForm.transaction_id = ''
  verifyForm.payment_details = ''
}

const resetFailForm = () => {
  failForm.reason = ''
}

const resetEditForm = () => {
  editForm.payment_method = 'cash'
  editForm.transaction_id = ''
  editForm.payment_details = ''
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.payment_method = ''
  filters.date_from = ''
  filters.date_to = ''
}

const fetchPageData = async () => {
  try {
    const [statsRes, paymentsRes, methodsRes] = await Promise.all([
      adminApi.get('/payments/stats'),
      adminApi.get('/payments'),
      adminApi.get('/payments/method-stats'),
    ])

    stats.totalRevenue = statsRes.data.totalRevenue ?? 0
    stats.completedPayments = statsRes.data.completedPayments ?? 0
    stats.pendingPayments = statsRes.data.pendingPayments ?? 0
    stats.todayRevenue = statsRes.data.todayRevenue ?? 0

    payments.value = paymentsRes.data.payments ?? []
    paymentMethodStats.value = methodsRes.data.paymentMethodStats ?? []
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load payment management data.')
  }
}

const openVerifyModal = (paymentId) => {
  currentPaymentId.value = paymentId
  resetVerifyForm()
  showVerifyModal.value = true
}

const closeVerifyModal = () => {
  showVerifyModal.value = false
  currentPaymentId.value = null
  resetVerifyForm()
}

const submitVerifyForm = async () => {
  loading.value = true

  try {
    const { data } = await adminApi.post(`/payments/${currentPaymentId.value}/verify`, {
      transaction_id: verifyForm.transaction_id,
      payment_details: verifyForm.payment_details,
    })

    alert(data.message || 'Payment verified successfully.')
    closeVerifyModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

const openFailModal = (paymentId) => {
  currentPaymentId.value = paymentId
  resetFailForm()
  showFailModal.value = true
}

const closeFailModal = () => {
  showFailModal.value = false
  currentPaymentId.value = null
  resetFailForm()
}

const submitFailForm = async () => {
  if (!failForm.reason.trim()) {
    alert('Please provide a reason')
    return
  }

  loading.value = true

  try {
    const { data } = await adminApi.post(`/payments/${currentPaymentId.value}/mark-failed`, {
      reason: failForm.reason,
    })

    alert(data.message || 'Payment marked as failed.')
    closeFailModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

const openEditModal = async (paymentId) => {
  currentPaymentId.value = paymentId

  try {
    const { data } = await adminApi.get(`/payments/${paymentId}`)
    const payment = data.payment ?? data

    editForm.payment_method = payment.payment_method || 'cash'
    editForm.transaction_id = payment.transaction_id || ''
    editForm.payment_details = payment.payment_details || ''

    showEditModal.value = true
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load payment details')
  }
}

const closeEditModal = () => {
  showEditModal.value = false
  currentPaymentId.value = null
  resetEditForm()
}

const submitEditForm = async () => {
  loading.value = true

  try {
    const { data } = await adminApi.post(`/payments/${currentPaymentId.value}/update`, {
      payment_method: editForm.payment_method,
      transaction_id: editForm.transaction_id,
      payment_details: editForm.payment_details,
    })

    alert(data.message || 'Payment updated successfully.')
    closeEditModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

const viewDetails = async (paymentId) => {
  try {
    const { data } = await adminApi.get(`/payments/${paymentId}`)
    paymentDetails.value = data.payment ?? data
    showDetailsModal.value = true
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load payment details')
  }
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  paymentDetails.value = null
}

const deletePayment = async (paymentId) => {
  if (!window.confirm('Are you sure you want to delete this payment? This action cannot be undone.')) return

  try {
    const { data } = await adminApi.post(`/payments/${paymentId}/delete`)
    alert(data.message || 'Payment deleted successfully.')
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

const exportCsv = () => {
  const params = new URLSearchParams()

  if (filters.search) params.append('search', filters.search)
  if (filters.status) params.append('status', filters.status)
  if (filters.payment_method) params.append('payment_method', filters.payment_method)
  if (filters.date_from) params.append('date_from', filters.date_from)
  if (filters.date_to) params.append('date_to', filters.date_to)

  const query = params.toString()
  window.location.href = query
    ? `/api/admin/payments/export?${query}`
    : '/api/admin/payments/export'
}

onMounted(fetchPageData)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Payment Management</h1>
      <p class="mt-1 text-gray-600">Track and manage all payment transactions</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Revenue</p>
            <h3 class="mt-1 text-3xl font-bold text-green-600">
              ₱{{ currency(stats.totalRevenue) }}
            </h3>
          </div>
          <div class="rounded-lg bg-green-100 p-3">
            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Completed</p>
            <h3 class="mt-1 text-3xl font-bold text-blue-600">
              {{ stats.completedPayments }}
            </h3>
          </div>
          <div class="rounded-lg bg-blue-100 p-3">
            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Pending</p>
            <h3 class="mt-1 text-3xl font-bold text-yellow-600">
              {{ stats.pendingPayments }}
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
            <p class="text-sm text-gray-600">Today's Revenue</p>
            <h3 class="mt-1 text-3xl font-bold text-teal-600">
              ₱{{ currency(stats.todayRevenue) }}
            </h3>
          </div>
          <div class="rounded-lg bg-teal-100 p-3">
            <svg class="h-8 w-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow">
      <h3 class="mb-4 text-lg font-semibold text-gray-800">Payment Method Breakdown</h3>

      <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
        <div
          v-for="stat in paymentMethodStats"
          :key="stat.payment_method"
          class="rounded-lg bg-gray-50 p-4 text-center"
        >
          <p class="text-sm capitalize text-gray-600">
            {{ formatMethod(stat.payment_method) }}
          </p>
          <p class="mt-1 text-2xl font-bold text-gray-800">
            {{ stat.count }}
          </p>
          <p class="mt-1 text-xs text-teal-600">
            ₱{{ currency(stat.total) }}
          </p>
        </div>
      </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Payment #, booking #..."
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
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Method</label>
          <select
            v-model="filters.payment_method"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Methods</option>
            <option value="cash">Cash</option>
            <option value="gcash">GCash</option>
            <option value="maya">Maya</option>
            <option value="credit_card">Credit Card</option>
            <option value="debit_card">Debit Card</option>
            <option value="bank_transfer">Bank Transfer</option>
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
        <h2 class="text-xl font-semibold text-gray-800">All Payments</h2>

        <button
          @click="exportCsv"
          class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export CSV
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment #</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Booking</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Method</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Transaction ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="!filteredPayments.length">
              <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="mt-2">No payments found</p>
              </td>
            </tr>

            <tr
              v-for="payment in filteredPayments"
              :key="payment.id"
              class="hover:bg-gray-50"
            >
              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm font-medium text-gray-900">
                  {{ payment.payment_number }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ payment.created_at_formatted || payment.created_at }}
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm text-gray-900">
                  {{ payment.booking?.booking_number }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ payment.booking?.schedule?.date_formatted || payment.booking?.schedule?.date }}
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-green-600 font-semibold text-white">
                      {{ initials(payment.user?.name) }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ payment.user?.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ payment.user?.email }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                ₱{{ currency(payment.amount) }}
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold capitalize leading-5 text-gray-800">
                  {{ formatMethod(payment.payment_method) }}
                </span>
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ payment.transaction_id || 'N/A' }}
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize leading-5"
                  :class="statusClass(payment.status)"
                >
                  {{ payment.status }}
                </span>
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                <button @click="viewDetails(payment.id)" class="mr-2 text-blue-600 hover:text-blue-900">
                  View
                </button>

                <button
                  v-if="payment.status === 'pending'"
                  @click="openVerifyModal(payment.id)"
                  class="mr-2 text-green-600 hover:text-green-900"
                >
                  Verify
                </button>

                <button
                  v-if="payment.status === 'pending'"
                  @click="openFailModal(payment.id)"
                  class="mr-2 text-red-600 hover:text-red-900"
                >
                  Mark Failed
                </button>

                <button
                  @click="openEditModal(payment.id)"
                  class="mr-2 text-yellow-600 hover:text-yellow-900"
                >
                  Edit
                </button>

                <button
                  v-if="payment.status !== 'completed'"
                  @click="deletePayment(payment.id)"
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
      v-if="showVerifyModal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeVerifyModal"
        ></div>

        <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
          <form @submit.prevent="submitVerifyForm">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900">Verify Payment</h3>
            </div>

            <div class="px-6 py-4">
              <div class="space-y-4">
                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Transaction ID *</label>
                  <input
                    v-model="verifyForm.transaction_id"
                    type="text"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter transaction ID"
                  >
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Payment Details (Optional)</label>
                  <textarea
                    v-model="verifyForm.payment_details"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    placeholder="Additional payment information..."
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
              <button
                type="button"
                @click="closeVerifyModal"
                class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700 disabled:opacity-50"
              >
                {{ loading ? 'Processing...' : 'Verify Payment' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div
      v-if="showFailModal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeFailModal"
        ></div>

        <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
          <form @submit.prevent="submitFailForm">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900">Mark Payment as Failed</h3>
            </div>

            <div class="px-6 py-4">
              <label class="mb-2 block text-sm font-medium text-gray-700">Reason *</label>
              <textarea
                v-model="failForm.reason"
                rows="4"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                placeholder="Why is this payment failed?"
              ></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
              <button
                type="button"
                @click="closeFailModal"
                class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="rounded-lg bg-red-600 px-4 py-2 text-white transition hover:bg-red-700 disabled:opacity-50"
              >
                {{ loading ? 'Processing...' : 'Mark as Failed' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeEditModal"
        ></div>

        <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
          <form @submit.prevent="submitEditForm">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900">Edit Payment</h3>
            </div>

            <div class="px-6 py-4">
              <div class="space-y-4">
                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Payment Method *</label>
                  <select
                    v-model="editForm.payment_method"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  >
                    <option
                      v-for="method in paymentMethods"
                      :key="method"
                      :value="method"
                    >
                      {{ formatMethod(method) }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Transaction ID</label>
                  <input
                    v-model="editForm.transaction_id"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Payment Details</label>
                  <textarea
                    v-model="editForm.payment_details"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
              <button
                type="button"
                @click="closeEditModal"
                class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700 disabled:opacity-50"
              >
                {{ loading ? 'Processing...' : 'Update Payment' }}
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
            <h3 class="text-lg font-semibold text-gray-900">Payment Details</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="paymentDetails" class="px-6 py-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Payment Number</p>
                <p class="text-base font-medium">{{ paymentDetails.payment_number }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Booking Number</p>
                <p class="text-base font-medium">{{ paymentDetails.booking?.booking_number }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Customer Name</p>
                <p class="text-base font-medium">{{ paymentDetails.user?.name }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Customer Email</p>
                <p class="text-base font-medium">{{ paymentDetails.user?.email }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Amount</p>
                <p class="text-base font-medium">₱{{ currency(paymentDetails.amount) }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Payment Method</p>
                <p class="text-base font-medium capitalize">
                  {{ formatMethod(paymentDetails.payment_method) }}
                </p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Transaction ID</p>
                <p class="text-base font-medium">{{ paymentDetails.transaction_id || 'N/A' }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Status</p>
                <p class="text-base font-medium capitalize">{{ paymentDetails.status }}</p>
              </div>

              <div class="col-span-2">
                <p class="text-sm text-gray-600">Payment Details</p>
                <p class="text-base">{{ paymentDetails.payment_details || 'None' }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Payment Date</p>
                <p class="text-base font-medium">{{ paymentDetails.paid_at || 'Not paid yet' }}</p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Created At</p>
                <p class="text-base font-medium">{{ paymentDetails.created_at }}</p>
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