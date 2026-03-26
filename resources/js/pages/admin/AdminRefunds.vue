<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import adminApi from '../../services/adminApi'

const loading = ref(false)
const currentRefundId = ref(null)
const refundDetails = ref(null)

const stats = reactive({
  pendingRefunds: 0,
  pendingAmount: 0,
  approvedRefunds: 0,
  completedRefunds: 0,
  totalRefundAmount: 0,
})

const filters = reactive({
  search: '',
  status: '',
  date_from: '',
  date_to: '',
})

const refunds = ref([])

const showApproveModal = ref(false)
const showRejectModal = ref(false)
const showCompleteModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)

const approveForm = reactive({
  admin_notes: '',
})

const rejectForm = reactive({
  admin_notes: '',
})

const completeForm = reactive({
  transaction_reference: '',
})

const editForm = reactive({
  refund_amount: 0,
  admin_notes: '',
  max_amount: 0,
})

const filteredRefunds = computed(() => {
  return refunds.value.filter((refund) => {
    const search = filters.search.toLowerCase().trim()

    const matchesSearch =
      !search ||
      refund.refund_number?.toLowerCase().includes(search) ||
      refund.booking?.booking_number?.toLowerCase().includes(search) ||
      refund.user?.name?.toLowerCase().includes(search) ||
      refund.user?.email?.toLowerCase().includes(search)

    const matchesStatus = !filters.status || refund.status === filters.status

    const requestedDate = normalizeDate(refund.requested_at_raw || refund.requested_at)
    const matchesDateFrom = !filters.date_from || requestedDate >= filters.date_from
    const matchesDateTo = !filters.date_to || requestedDate <= filters.date_to

    return matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo
  })
})

function normalizeDate(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value).substring(0, 10)
  return date.toISOString().split('T')[0]
}

function formatDate(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-PH', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  })
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function getInitials(name) {
  if (!name) return ''
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}

const fetchPageData = async () => {
  try {
    const [statsRes, refundsRes] = await Promise.all([
      adminApi.get('/refunds/stats'),
      adminApi.get('/refunds'),
    ])

    stats.pendingRefunds = statsRes.data.pendingRefunds ?? 0
    stats.pendingAmount = statsRes.data.pendingAmount ?? 0
    stats.approvedRefunds = statsRes.data.approvedRefunds ?? 0
    stats.completedRefunds = statsRes.data.completedRefunds ?? 0
    stats.totalRefundAmount = statsRes.data.totalRefundAmount ?? 0

    refunds.value = refundsRes.data.refunds ?? []
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load refund management data.')
  }
}

function applyFilters() {
  // client-side filtering only
}

function clearFilters() {
  filters.search = ''
  filters.status = ''
  filters.date_from = ''
  filters.date_to = ''
}

const exportUrl = computed(() => {
  const params = new URLSearchParams()

  if (filters.search) params.set('search', filters.search)
  if (filters.status) params.set('status', filters.status)
  if (filters.date_from) params.set('date_from', filters.date_from)
  if (filters.date_to) params.set('date_to', filters.date_to)

  const query = params.toString()
  return query ? `/api/admin/refunds/export?${query}` : '/api/admin/refunds/export'
})

function openApproveModal(refundId) {
  currentRefundId.value = refundId
  approveForm.admin_notes = ''
  showApproveModal.value = true
}

function closeApproveModal() {
  showApproveModal.value = false
  currentRefundId.value = null
}

async function submitApproveForm() {
  loading.value = true

  try {
    const { data } = await adminApi.post(`/refunds/${currentRefundId.value}/approve`, {
      admin_notes: approveForm.admin_notes,
    })

    alert(data.message || 'Refund approved successfully!')
    closeApproveModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

function openRejectModal(refundId) {
  currentRefundId.value = refundId
  rejectForm.admin_notes = ''
  showRejectModal.value = true
}

function closeRejectModal() {
  showRejectModal.value = false
  currentRefundId.value = null
}

async function submitRejectForm() {
  if (!rejectForm.admin_notes.trim()) {
    alert('Please provide a rejection reason')
    return
  }

  loading.value = true

  try {
    const { data } = await adminApi.post(`/refunds/${currentRefundId.value}/reject`, {
      admin_notes: rejectForm.admin_notes,
    })

    alert(data.message || 'Refund rejected successfully!')
    closeRejectModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

function openCompleteModal(refundId) {
  currentRefundId.value = refundId
  completeForm.transaction_reference = ''
  showCompleteModal.value = true
}

function closeCompleteModal() {
  showCompleteModal.value = false
  currentRefundId.value = null
}

async function submitCompleteForm() {
  loading.value = true

  try {
    const { data } = await adminApi.post(`/refunds/${currentRefundId.value}/complete`, {
      transaction_reference: completeForm.transaction_reference,
    })

    alert(data.message || 'Refund marked as completed successfully!')
    closeCompleteModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

async function openEditModal(refundId) {
  currentRefundId.value = refundId

  try {
    const { data } = await adminApi.get(`/refunds/${refundId}`)
    const refund = data.refund

    editForm.refund_amount = refund.refund_amount
    editForm.admin_notes = refund.admin_notes || ''
    editForm.max_amount = refund.original_amount
    showEditModal.value = true
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load refund details')
  }
}

function closeEditModal() {
  showEditModal.value = false
  currentRefundId.value = null
}

async function submitEditForm() {
  loading.value = true

  try {
    const { data } = await adminApi.post(`/refunds/${currentRefundId.value}/update`, {
      refund_amount: editForm.refund_amount,
      admin_notes: editForm.admin_notes,
    })

    alert(data.message || 'Refund updated successfully!')
    closeEditModal()
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    loading.value = false
  }
}

async function viewDetails(refundId) {
  try {
    const { data } = await adminApi.get(`/refunds/${refundId}`)
    refundDetails.value = data.refund
    showDetailsModal.value = true
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load refund details')
  }
}

function closeDetailsModal() {
  showDetailsModal.value = false
  refundDetails.value = null
}

async function deleteRefund(refundId) {
  if (!confirm('Are you sure you want to delete this refund? This action cannot be undone.')) return

  try {
    const { data } = await adminApi.post(`/refunds/${refundId}/delete`)
    alert(data.message || 'Refund deleted successfully!')
    await fetchPageData()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'An error occurred. Please try again.')
  }
}

onMounted(fetchPageData)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Refund Management</h1>
      <p class="mt-1 text-gray-600">Review and process refund requests</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Pending Requests</p>
            <h3 class="mt-1 text-3xl font-bold text-yellow-600">{{ stats.pendingRefunds }}</h3>
            <p class="mt-1 text-xs text-gray-500">₱{{ formatMoney(stats.pendingAmount) }}</p>
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
            <p class="text-sm text-gray-600">Approved</p>
            <h3 class="mt-1 text-3xl font-bold text-green-600">{{ stats.approvedRefunds }}</h3>
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
            <p class="text-sm text-gray-600">Completed</p>
            <h3 class="mt-1 text-3xl font-bold text-blue-600">{{ stats.completedRefunds }}</h3>
          </div>
          <div class="rounded-lg bg-blue-100 p-3">
            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Refunded</p>
            <h3 class="mt-1 text-3xl font-bold text-red-600">₱{{ formatMoney(stats.totalRefundAmount) }}</h3>
          </div>
          <div class="rounded-lg bg-red-100 p-3">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow">
      <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Refund #, booking #..."
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Date From</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Date To</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="flex items-end gap-2">
          <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
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
      </form>
    </div>

    <div class="rounded-lg bg-white shadow">
      <div class="flex items-center justify-between border-b border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-800">All Refund Requests</h2>
        <a
          :href="exportUrl"
          class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export CSV
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="border-b border-gray-200 bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Refund #</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Booking</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reason</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Requested</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="filteredRefunds.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                <p class="mt-2">No refund requests found</p>
              </td>
            </tr>

            <tr v-for="refund in filteredRefunds" :key="refund.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ refund.refund_number }}</div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm text-gray-900">{{ refund.booking?.booking_number }}</div>
                <div class="text-sm text-gray-500">{{ refund.booking?.schedule?.date_formatted || formatDate(refund.booking?.schedule?.date) }}</div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-red-400 to-red-600 font-semibold text-white">
                      {{ getInitials(refund.user?.name) }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ refund.user?.name }}</div>
                    <div class="text-sm text-gray-500">{{ refund.user?.email }}</div>
                  </div>
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm font-medium text-gray-900">₱{{ formatMoney(refund.refund_amount) }}</div>
                <div class="text-xs text-gray-500">of ₱{{ formatMoney(refund.original_amount) }}</div>
              </td>

              <td class="px-6 py-4">
                <div class="max-w-xs truncate text-sm text-gray-900">{{ refund.reason }}</div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span
                  v-if="refund.status === 'pending'"
                  class="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold leading-5 text-yellow-800"
                >
                  Pending
                </span>
                <span
                  v-else-if="refund.status === 'approved'"
                  class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold leading-5 text-green-800"
                >
                  Approved
                </span>
                <span
                  v-else-if="refund.status === 'rejected'"
                  class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold leading-5 text-red-800"
                >
                  Rejected
                </span>
                <span
                  v-else
                  class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold leading-5 text-blue-800"
                >
                  Completed
                </span>
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ refund.requested_at }}
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                <button @click="viewDetails(refund.id)" class="mr-2 text-blue-600 hover:text-blue-900">
                  View
                </button>

                <template v-if="refund.status === 'pending'">
                  <button @click="openApproveModal(refund.id)" class="mr-2 text-green-600 hover:text-green-900">
                    Approve
                  </button>
                  <button @click="openRejectModal(refund.id)" class="mr-2 text-red-600 hover:text-red-900">
                    Reject
                  </button>
                  <button @click="openEditModal(refund.id)" class="mr-2 text-yellow-600 hover:text-yellow-900">
                    Edit
                  </button>
                </template>

                <template v-if="refund.status === 'approved'">
                  <button @click="openCompleteModal(refund.id)" class="mr-2 text-teal-600 hover:text-teal-900">
                    Complete
                  </button>
                </template>

                <template v-if="refund.status !== 'completed'">
                  <button @click="deleteRefund(refund.id)" class="text-red-600 hover:text-red-900">
                    Delete
                  </button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <transition name="fade">
      <div v-if="showApproveModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeApproveModal"></div>

          <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
            <form @submit.prevent="submitApproveForm">
              <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Approve Refund</h3>
              </div>

              <div class="px-6 py-4">
                <p class="mb-4 text-sm text-gray-600">Are you sure you want to approve this refund request?</p>
                <label class="mb-2 block text-sm font-medium text-gray-700">Admin Notes (Optional)</label>
                <textarea
                  v-model="approveForm.admin_notes"
                  rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="Add any notes..."
                ></textarea>
              </div>

              <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" @click="closeApproveModal" class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="loading" class="rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700 disabled:opacity-50">
                  {{ loading ? 'Processing...' : 'Approve Refund' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeRejectModal"></div>

          <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
            <form @submit.prevent="submitRejectForm">
              <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Reject Refund</h3>
              </div>

              <div class="px-6 py-4">
                <label class="mb-2 block text-sm font-medium text-gray-700">Rejection Reason *</label>
                <textarea
                  v-model="rejectForm.admin_notes"
                  rows="4"
                  required
                  class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="Explain why this refund is being rejected..."
                ></textarea>
              </div>

              <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" @click="closeRejectModal" class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="loading" class="rounded-lg bg-red-600 px-4 py-2 text-white transition hover:bg-red-700 disabled:opacity-50">
                  {{ loading ? 'Processing...' : 'Reject Refund' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showCompleteModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCompleteModal"></div>

          <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
            <form @submit.prevent="submitCompleteForm">
              <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Complete Refund</h3>
              </div>

              <div class="px-6 py-4">
                <p class="mb-4 text-sm text-gray-600">Mark this refund as completed after processing the payment.</p>
                <label class="mb-2 block text-sm font-medium text-gray-700">Transaction Reference (Optional)</label>
                <input
                  v-model="completeForm.transaction_reference"
                  type="text"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., TXN-123456"
                />
              </div>

              <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" @click="closeCompleteModal" class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="loading" class="rounded-lg bg-teal-600 px-4 py-2 text-white transition hover:bg-teal-700 disabled:opacity-50">
                  {{ loading ? 'Processing...' : 'Mark as Completed' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal"></div>

          <div class="relative z-10 inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
            <form @submit.prevent="submitEditForm">
              <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Refund</h3>
              </div>

              <div class="px-6 py-4">
                <div class="space-y-4">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Refund Amount *</label>
                    <input
                      v-model.number="editForm.refund_amount"
                      type="number"
                      step="0.01"
                      min="0"
                      required
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    />
                    <p class="mt-1 text-xs text-gray-500">Maximum: ₱{{ formatMoney(editForm.max_amount) }}</p>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Admin Notes</label>
                    <textarea
                      v-model="editForm.admin_notes"
                      rows="3"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" @click="closeEditModal" class="rounded-lg border border-gray-300 px-4 py-2 transition hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="loading" class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700 disabled:opacity-50">
                  {{ loading ? 'Processing...' : 'Update Refund' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showDetailsModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDetailsModal"></div>

          <div class="relative z-10 inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900">Refund Details</h3>
              <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-if="refundDetails" class="px-6 py-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">Refund Number</p>
                  <p class="text-base font-medium">{{ refundDetails.refund_number }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Status</p>
                  <p class="text-base font-medium capitalize">{{ refundDetails.status }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Booking Number</p>
                  <p class="text-base font-medium">{{ refundDetails.booking?.booking_number }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Payment Number</p>
                  <p class="text-base font-medium">{{ refundDetails.payment?.payment_number }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Customer Name</p>
                  <p class="text-base font-medium">{{ refundDetails.user?.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Customer Email</p>
                  <p class="text-base font-medium">{{ refundDetails.user?.email }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Original Amount</p>
                  <p class="text-base font-medium">₱{{ formatMoney(refundDetails.original_amount) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Refund Amount</p>
                  <p class="text-base font-medium">₱{{ formatMoney(refundDetails.refund_amount) }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-sm text-gray-600">Reason</p>
                  <p class="text-base">{{ refundDetails.reason }}</p>
                </div>
                <div v-if="refundDetails.admin_notes" class="col-span-2">
                  <p class="text-sm text-gray-600">Admin Notes</p>
                  <p class="text-base">{{ refundDetails.admin_notes }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Requested Date</p>
                  <p class="text-base font-medium">{{ refundDetails.requested_at }}</p>
                </div>
                <div v-if="refundDetails.processed_at">
                  <p class="text-sm text-gray-600">Processed Date</p>
                  <p class="text-base font-medium">{{ refundDetails.processed_at }}</p>
                </div>
                <div v-if="refundDetails.processed_by" class="col-span-2">
                  <p class="text-sm text-gray-600">Processed By</p>
                  <p class="text-base font-medium">{{ refundDetails.processed_by?.name }}</p>
                </div>
              </div>
            </div>

            <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4">
              <button @click="closeDetailsModal" class="rounded-lg bg-gray-200 px-4 py-2 text-gray-800 transition hover:bg-gray-300">
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>