<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../../services/api'

const loading = ref(false)
const processing = ref(false)
const requestingRefund = ref(false)
const error = ref('')
const success = ref('')

const payments = ref([])
const pendingPayments = ref([])
const stats = ref({
  totalPaid: 0,
  totalPending: 0,
  transactions: 0,
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
})

const filters = reactive({
  search: '',
  status: '',
  method: '',
})

const paymentModalOpen = ref(false)
const refundModalOpen = ref(false)
const selectedPayment = ref(null)

const paymentForm = reactive({
  payment_method: '',
  reference_number: '',
  receipt: null,
})

const refundForm = reactive({
  reason: '',
})

const receiptInput = ref(null)

const normalize = (value) => String(value || '').toLowerCase().trim()

const formatMoney = (value) => {
  return `₱${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`
}

const getStatusBadgeClass = (status) => {
  const s = normalize(status)

  if (s === 'pending') return 'bg-yellow-100 text-yellow-800'
  if (s === 'completed') return 'bg-green-100 text-green-800'
  if (s === 'failed') return 'bg-red-100 text-red-800'
  if (s === 'refunded') return 'bg-blue-100 text-blue-800'
  return 'bg-gray-100 text-gray-800'
}

const getMethodLabel = (method) => {
  const labels = {
    cash: 'Cash',
    gcash: 'GCash',
    paymaya: 'PayMaya',
    credit_card: 'Credit Card',
    debit_card: 'Debit Card',
    bank_transfer: 'Bank Transfer',
  }

  return labels[method] || method || 'N/A'
}

const requiresReference = computed(() => {
  return ['gcash', 'paymaya', 'bank_transfer'].includes(normalize(paymentForm.payment_method))
})

const filteredPayments = computed(() => {
  const search = normalize(filters.search)
  const status = normalize(filters.status)
  const method = normalize(filters.method)

  return payments.value.filter((payment) => {
    const paymentNumber = normalize(payment.payment_number)
    const bookingNumber = normalize(payment.booking?.booking_number)
    const scheduleDate = normalize(payment.booking?.schedule_date)
    const timeSlot = normalize(payment.booking?.time_slot)
    const paymentStatus = normalize(payment.status)
    const paymentMethod = normalize(payment.payment_method)

    const matchesSearch =
      !search ||
      paymentNumber.includes(search) ||
      bookingNumber.includes(search) ||
      scheduleDate.includes(search) ||
      timeSlot.includes(search)

    const matchesStatus = !status || paymentStatus === status
    const matchesMethod = !method || paymentMethod === method

    return matchesSearch && matchesStatus && matchesMethod
  })
})

const hasPayments = computed(() => filteredPayments.value.length > 0)

const loadPayments = async (page = 1) => {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.get('/api/user/payments', {
      params: { page },
    })

    payments.value = data.payments?.data || []
    pendingPayments.value = data.pendingPayments || []

    stats.value = {
      totalPaid: data.stats?.totalPaid || 0,
      totalPending: data.stats?.totalPending || 0,
      transactions: data.stats?.transactions || 0,
    }

    pagination.current_page = data.payments?.current_page || 1
    pagination.last_page = data.payments?.last_page || 1
    pagination.per_page = data.payments?.per_page || 15
    pagination.total = data.payments?.total || 0
    pagination.from = data.payments?.from || null
    pagination.to = data.payments?.to || null
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      'Failed to load payments.'
  } finally {
    loading.value = false
  }
}

const openPaymentModal = (payment) => {
  selectedPayment.value = payment
  paymentForm.payment_method = ''
  paymentForm.reference_number = ''
  paymentForm.receipt = null
  paymentModalOpen.value = true
  error.value = ''
}

const closePaymentModal = () => {
  paymentModalOpen.value = false
  selectedPayment.value = null
  paymentForm.payment_method = ''
  paymentForm.reference_number = ''
  paymentForm.receipt = null

  if (receiptInput.value) {
    receiptInput.value.value = ''
  }
}

const openRefundModal = (payment) => {
  selectedPayment.value = payment
  refundForm.reason = ''
  refundModalOpen.value = true
  error.value = ''
}

const closeRefundModal = () => {
  refundModalOpen.value = false
  selectedPayment.value = null
  refundForm.reason = ''
}

const onReceiptChange = (event) => {
  paymentForm.receipt = event.target.files?.[0] || null
}

const submitPayment = async () => {
  if (!selectedPayment.value) return

  if (!paymentForm.payment_method) {
    error.value = 'Please select a payment method.'
    return
  }

  if (requiresReference.value && !paymentForm.reference_number.trim()) {
    error.value = 'Reference number is required for this payment method.'
    return
  }

  processing.value = true
  error.value = ''
  success.value = ''

  try {
    const formData = new FormData()
    formData.append('payment_method', paymentForm.payment_method)

    if (paymentForm.reference_number) {
      formData.append('reference_number', paymentForm.reference_number)
    }

    if (paymentForm.receipt) {
      formData.append('receipt', paymentForm.receipt)
    }

    const { data } = await api.post(
      `/api/user/payments/${selectedPayment.value.id}/process`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )

    if (data?.success) {
      success.value = data.message || 'Payment processed successfully.'
      closePaymentModal()
      await loadPayments(pagination.current_page)
    } else {
      error.value = data?.message || 'Failed to process payment.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.payment_method?.[0] ||
      err?.response?.data?.errors?.reference_number?.[0] ||
      err?.response?.data?.errors?.receipt?.[0] ||
      'Failed to process payment.'
  } finally {
    processing.value = false
  }
}

const submitRefundRequest = async () => {
  if (!selectedPayment.value) return

  if (!refundForm.reason.trim()) {
    error.value = 'Please provide a refund reason.'
    return
  }

  requestingRefund.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.post(`/api/user/payments/${selectedPayment.value.id}/refund`, {
      reason: refundForm.reason,
    })

    if (data?.success) {
      success.value = data.message || 'Refund request submitted successfully.'
      closeRefundModal()
      await loadPayments(pagination.current_page)
    } else {
      error.value = data?.message || 'Failed to submit refund request.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.reason?.[0] ||
      'Failed to submit refund request.'
  } finally {
    requestingRefund.value = false
  }
}

const goToPage = async (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  await loadPayments(page)
}

onMounted(() => {
  loadPayments()
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

    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-credit-card text-blue-500 mr-2"></i>Payments
      </h1>
      <p class="text-gray-600 mt-1">Manage your payment transactions and refund requests</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-green-100 rounded-full p-3 mr-4">
            <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Total Paid</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ formatMoney(stats.totalPaid) }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-yellow-100 rounded-full p-3 mr-4">
            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Pending Amount</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ formatMoney(stats.totalPending) }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
          <div class="bg-blue-100 rounded-full p-3 mr-4">
            <i class="fas fa-receipt text-blue-600 text-2xl"></i>
          </div>
          <div>
            <p class="text-gray-500 text-sm">Transactions</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.transactions }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
      <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>Pending Payments
      </h2>

      <div v-if="pendingPayments.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          v-for="payment in pendingPayments"
          :key="payment.id"
          class="border border-yellow-200 bg-yellow-50 rounded-lg p-4"
        >
          <div class="flex items-center justify-between mb-2">
            <p class="font-bold text-gray-800">{{ payment.payment_number }}</p>
            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
              Pending
            </span>
          </div>

          <div class="space-y-1 text-sm text-gray-600">
            <p>
              <i class="fas fa-hashtag text-blue-500 mr-2"></i>
              {{ payment.booking?.booking_number || 'N/A' }}
            </p>
            <p>
              <i class="fas fa-calendar text-blue-500 mr-2"></i>
              {{ payment.booking?.schedule_date || 'N/A' }}
            </p>
            <p>
              <i class="fas fa-clock text-blue-500 mr-2"></i>
              {{ payment.booking?.time_slot || 'N/A' }}
            </p>
            <p class="font-bold text-green-600 pt-1">
              <i class="fas fa-peso-sign mr-2"></i>{{ formatMoney(payment.amount) }}
            </p>
          </div>

          <button
            class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            @click="openPaymentModal(payment)"
          >
            <i class="fas fa-credit-card mr-2"></i>Pay Now
          </button>
        </div>
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        <i class="fas fa-check-circle text-4xl mb-2 text-gray-300"></i>
        <p>No pending payments.</p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input
            v-model="filters.search"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Search payment or booking number"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Method</label>
          <select
            v-model="filters.method"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Methods</option>
            <option value="cash">Cash</option>
            <option value="gcash">GCash</option>
            <option value="paymaya">PayMaya</option>
            <option value="credit_card">Credit Card</option>
            <option value="debit_card">Debit Card</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">
          <i class="fas fa-history mr-2"></i>Payment History
        </h3>
      </div>

      <div v-if="loading" class="p-10 text-center text-gray-500">
        Loading payments...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[1000px]">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Payment #
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Booking
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Method
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="payment in filteredPayments"
              :key="payment.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ payment.payment_number }}</div>
                <div class="text-xs text-gray-500">{{ payment.transaction_id || 'No reference' }}</div>
              </td>

              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ payment.booking?.booking_number || 'N/A' }}</div>
                <div class="text-xs text-gray-500 mt-1">
                  {{ payment.booking?.schedule_date || 'N/A' }} • {{ payment.booking?.time_slot || 'N/A' }}
                </div>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                {{ getMethodLabel(payment.payment_method) }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">{{ formatMoney(payment.amount) }}</div>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getStatusBadgeClass(payment.status)"
                >
                  {{ payment.status_label }}
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                {{ payment.display_date }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center space-x-3">
                  <button
                    v-if="normalize(payment.status) === 'pending'"
                    class="text-blue-600 hover:text-blue-900 font-semibold"
                    @click="openPaymentModal(payment)"
                  >
                    <i class="fas fa-credit-card mr-1"></i>Pay
                  </button>

                  <button
                    v-if="payment.can_refund"
                    class="text-red-600 hover:text-red-900 font-semibold"
                    @click="openRefundModal(payment)"
                  >
                    <i class="fas fa-undo mr-1"></i>Refund
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!hasPayments">
              <td colspan="7" class="px-6 py-12 text-center">
                <div class="text-gray-500">
                  <i class="fas fa-receipt text-6xl mb-4 text-gray-300"></i>
                  <p class="text-lg font-semibold mb-2">No Payments Found</p>
                  <p class="text-sm">Your payment history will appear here.</p>
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
            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} payments
          </p>

          <div class="flex gap-2">
            <button
              class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition disabled:opacity-50"
              :disabled="pagination.current_page === 1"
              @click="goToPage(pagination.current_page - 1)"
            >
              Previous
            </button>

            <button
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
      v-if="paymentModalOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-800">Process Payment</h3>
          <button @click="closePaymentModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <div v-if="selectedPayment" class="bg-blue-50 p-4 rounded-lg mb-4">
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Payment #</span>
              <span class="font-semibold">{{ selectedPayment.payment_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Booking</span>
              <span class="font-semibold">{{ selectedPayment.booking?.booking_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Schedule</span>
              <span class="font-semibold">
                {{ selectedPayment.booking?.schedule_date }} • {{ selectedPayment.booking?.time_slot }}
              </span>
            </div>
            <div class="flex justify-between pt-2 border-t border-blue-200">
              <span class="text-gray-700 font-medium">Amount</span>
              <span class="font-bold text-green-600">{{ formatMoney(selectedPayment.amount) }}</span>
            </div>
          </div>
        </div>

        <form class="space-y-4" @submit.prevent="submitPayment">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Payment Method <span class="text-red-500">*</span>
            </label>
            <select
              v-model="paymentForm.payment_method"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">Select payment method</option>
              <option value="cash">Cash</option>
              <option value="gcash">GCash</option>
              <option value="paymaya">PayMaya</option>
              <option value="credit_card">Credit Card</option>
              <option value="debit_card">Debit Card</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>

          <div v-if="requiresReference">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Reference Number <span class="text-red-500">*</span>
            </label>
            <input
              v-model="paymentForm.reference_number"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter reference number"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Receipt (Optional)
            </label>
            <input
              ref="receiptInput"
              type="file"
              accept="image/*"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              @change="onReceiptChange"
            >
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
              @click="closePaymentModal"
            >
              Cancel
            </button>

            <button
              type="submit"
              :disabled="processing"
              class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
            >
              <i v-if="processing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-credit-card mr-2"></i>
              {{ processing ? 'Processing...' : 'Submit Payment' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="refundModalOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-800">Request Refund</h3>
          <button @click="closeRefundModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <p class="text-gray-600 mb-4">
          Submit a refund request for
          <strong>{{ selectedPayment?.payment_number }}</strong>.
        </p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Refund Reason
            </label>
            <textarea
              v-model="refundForm.reason"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              placeholder="Please provide your reason..."
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t">
            <button
              type="button"
              @click="closeRefundModal"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
            >
              Back
            </button>

            <button
              type="button"
              @click="submitRefundRequest"
              :disabled="requestingRefund"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50"
            >
              <i v-if="requestingRefund" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-paper-plane mr-2"></i>
              {{ requestingRefund ? 'Submitting...' : 'Submit Request' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>