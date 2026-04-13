<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import adminApi from '../../services/adminApi'

const loading = ref(true)
const saving = ref(false)
const error = ref('')

const schedules = ref([])

const stats = reactive({
  totalSchedules: 0,
  availableSchedules: 0,
  fullSchedules: 0,
  totalCapacity: 0,
})

const filters = reactive({
  date: '',
  status: '',
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showBulkCreateModal = ref(false)

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const today = getLocalDateString()

const days = [
  'Sunday',
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
]

const createFormDefault = () => ({
  date: '',
  total_capacity: 20,
  start_time: '',
  end_time: '',
  price_per_slot: 100,
  notes: '',
})

const editFormDefault = () => ({
  id: null,
  date: '',
  total_capacity: 20,
  start_time: '',
  end_time: '',
  price_per_slot: 100,
  notes: '',
})

const bulkFormDefault = () => ({
  start_date: '',
  end_date: '',
  days_of_week: [],
  time_slots: [
    {
      start_time: '',
      end_time: '',
      capacity: 20,
      price: 100,
    },
  ],
})

const createForm = reactive(createFormDefault())
const editForm = reactive(editFormDefault())
const bulkForm = reactive(bulkFormDefault())

const filteredSchedules = computed(() => {
  return schedules.value.filter((schedule) => {
    const matchesDate = !filters.date || normalizeDate(schedule.date) === filters.date
    const matchesStatus = !filters.status || schedule.status === filters.status
    return matchesDate && matchesStatus
  })
})

function normalizeDate(date) {
  if (!date) return ''
  return String(date).length > 10 ? String(date).substring(0, 10) : String(date)
}

function parseLocalDate(date) {
  if (!date) return null

  if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
    const [year, month, day] = date.split('-').map(Number)
    return new Date(year, month - 1, day)
  }

  const parsed = new Date(date)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function formatDate(date) {
  const parsed = parseLocalDate(date)
  if (!parsed) return ''

  return parsed.toLocaleDateString('en-PH', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  })
}

function formatDay(date) {
  const parsed = parseLocalDate(date)
  if (!parsed) return ''

  return parsed.toLocaleDateString('en-PH', { weekday: 'long' })
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function capitalize(value) {
  if (!value) return ''
  return value.charAt(0).toUpperCase() + value.slice(1)
}

function resetObject(target, defaults) {
  Object.assign(target, defaults())
}

function recomputeStats() {
  stats.totalSchedules = schedules.value.length
  stats.availableSchedules = schedules.value.filter((s) => s.status === 'available').length
  stats.fullSchedules = schedules.value.filter((s) => s.status === 'full').length
  stats.totalCapacity = schedules.value.reduce((sum, s) => sum + Number(s.total_capacity || 0), 0)
}

async function loadSchedules() {
  loading.value = true
  error.value = ''

  try {
    const response = await adminApi.get('/schedules', {
      params: {
        date: filters.date,
        status: filters.status,
      },
    })

    schedules.value = Array.isArray(response.data?.schedules) ? response.data.schedules : []
    const apiStats = response.data?.stats || null

    if (apiStats) {
      stats.totalSchedules = Number(apiStats.totalSchedules || 0)
      stats.availableSchedules = Number(apiStats.availableSchedules || 0)
      stats.fullSchedules = Number(apiStats.fullSchedules || 0)
      stats.totalCapacity = Number(apiStats.totalCapacity || 0)
    } else {
      recomputeStats()
    }
  } catch (err) {
    console.error('Failed to load schedules:', err)
    error.value = err?.response?.data?.message || 'Failed to load schedules.'
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  resetObject(createForm, createFormDefault)
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
  resetObject(createForm, createFormDefault)
}

function openBulkCreateModal() {
  resetObject(bulkForm, bulkFormDefault)
  showBulkCreateModal.value = true
}

function closeBulkCreateModal() {
  showBulkCreateModal.value = false
  resetObject(bulkForm, bulkFormDefault)
}

function openEditModal() {
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  resetObject(editForm, editFormDefault)
}

function addTimeSlot() {
  bulkForm.time_slots.push({
    start_time: '',
    end_time: '',
    capacity: 20,
    price: 100,
  })
}

function removeTimeSlot(index) {
  bulkForm.time_slots.splice(index, 1)
}

function applyFilters() {
  loadSchedules()
}

function resetFilters() {
  filters.date = ''
  filters.status = ''
  loadSchedules()
}

async function submitCreateSchedule() {
  saving.value = true

  try {
    const payload = {
      ...createForm,
      date: normalizeDate(createForm.date),
      total_capacity: Number(createForm.total_capacity),
      price_per_slot: Number(createForm.price_per_slot),
    }

    const response = await adminApi.post('/schedules', payload)

    if (response.data?.success) {
      alert(response.data.message || 'Schedule created successfully.')
      closeCreateModal()
      await loadSchedules()
    } else {
      alert(response.data?.message || 'Failed to create schedule.')
    }
  } catch (err) {
    console.error(err)
    alert(
      err?.response?.data?.message ||
      err?.response?.data?.errors?.date?.[0] ||
      'An error occurred. Please try again.'
    )
  } finally {
    saving.value = false
  }
}

async function submitBulkCreate() {
  saving.value = true

  try {
    const payload = {
      start_date: normalizeDate(bulkForm.start_date),
      end_date: normalizeDate(bulkForm.end_date),
      days_of_week: bulkForm.days_of_week.map(Number),
      time_slots: bulkForm.time_slots.map((slot) => ({
        start_time: slot.start_time,
        end_time: slot.end_time,
        capacity: Number(slot.capacity),
        price: Number(slot.price),
      })),
    }

    const response = await adminApi.post('/schedules/bulk-create', payload)

    if (response.data?.success) {
      alert(response.data.message || 'Schedules created successfully.')
      closeBulkCreateModal()
      await loadSchedules()
    } else {
      alert(response.data?.message || 'Failed to bulk create schedules.')
    }
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'An error occurred. Please try again.')
  } finally {
    saving.value = false
  }
}

async function viewSchedule(id) {
  try {
    const response = await adminApi.get(`/schedules/${id}`)
    const schedule = response.data?.schedule || response.data

    alert(
      [
        `Date: ${schedule.date || ''}`,
        `Time: ${schedule.time_slot || ''}`,
        `Capacity: ${schedule.total_capacity || 0}`,
        `Price per Hour: ₱${formatMoney(schedule.price_per_slot)}`,
        `Status: ${capitalize(schedule.status || '')}`,
        `Notes: ${schedule.notes || 'None'}`,
      ].join('\n')
    )
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'Failed to load schedule details.')
  }
}

async function editSchedule(id) {
  try {
    const response = await adminApi.get(`/schedules/${id}`)
    const s = response.data?.schedule || response.data

    editForm.id = s.id
    editForm.date = normalizeDate(s.date)
    editForm.total_capacity = Number(s.total_capacity || 20)
    editForm.start_time = String(s.start_time || '').substring(0, 5)
    editForm.end_time = String(s.end_time || '').substring(0, 5)
    editForm.price_per_slot = Number(s.price_per_slot || 0)
    editForm.notes = s.notes ?? ''

    openEditModal()
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'An error occurred while loading the schedule.')
  }
}

async function submitEditSchedule() {
  saving.value = true

  try {
    const response = await adminApi.put(`/schedules/${editForm.id}`, {
      ...editForm,
      date: normalizeDate(editForm.date),
      total_capacity: Number(editForm.total_capacity),
      price_per_slot: Number(editForm.price_per_slot),
      status: 'available',
    })

    if (response.data?.success) {
      alert(response.data.message || 'Schedule updated successfully.')
      closeEditModal()
      await loadSchedules()
    } else {
      alert(response.data?.message || 'Failed to update schedule.')
    }
  } catch (err) {
    console.error(err)
    alert(
      err?.response?.data?.message ||
      err?.response?.data?.errors?.date?.[0] ||
      'An error occurred. Please try again.'
    )
  } finally {
    saving.value = false
  }
}

async function deleteSchedule(id) {
  if (!confirm('Are you sure you want to delete this schedule?')) return

  try {
    const response = await adminApi.delete(`/schedules/${id}`)

    if (response.data?.success) {
      alert(response.data.message || 'Schedule deleted successfully.')
      await loadSchedules()
    } else {
      alert(response.data?.message || 'Failed to delete schedule.')
    }
  } catch (err) {
    console.error(err)
    alert(err?.response?.data?.message || 'An error occurred. Please try again.')
  }
}

onMounted(() => {
  loadSchedules()
})
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Schedule Management
      </h1>
      <p class="mt-1 text-gray-600">Create and manage gym schedules</p>
    </div>

    <div v-if="loading" class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
      Loading schedules...
    </div>

    <div v-else>
      <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
        <div class="rounded-lg bg-white p-6 shadow-lg">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-blue-100 p-3">
              <i class="fas fa-calendar text-2xl text-blue-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Schedules</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ stats.totalSchedules }}</h3>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-green-100 p-3">
              <i class="fas fa-check-circle text-2xl text-green-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Available</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ stats.availableSchedules }}</h3>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-red-100 p-3">
              <i class="fas fa-times-circle text-2xl text-red-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Full</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ stats.fullSchedules }}</h3>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-purple-100 p-3">
              <i class="fas fa-users text-2xl text-purple-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Capacity</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ stats.totalCapacity }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-6 rounded-lg bg-white p-6 shadow-lg">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
          <div class="flex gap-3">
            <button
              @click="openCreateModal"
              class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-blue-700"
            >
              <i class="fas fa-plus mr-2"></i>Create Schedule
            </button>
            <button
              @click="openBulkCreateModal"
              class="rounded-lg bg-green-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-green-700"
            >
              <i class="fas fa-calendar-plus mr-2"></i>Bulk Create
            </button>
          </div>
        </div>

        <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-4 md:grid-cols-4">
          <input
            v-model="filters.date"
            type="date"
            class="rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
          />

          <select
            v-model="filters.status"
            class="rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="available">Available</option>
            <option value="full">Full</option>
            <option value="closed">Closed</option>
          </select>

          <button
            type="submit"
            class="rounded-lg bg-gray-600 px-6 py-2 font-semibold text-white transition hover:bg-gray-700"
          >
            <i class="fas fa-search mr-2"></i>Filter
          </button>

          <button
            type="button"
            @click="resetFilters"
            class="rounded-lg bg-gray-300 px-6 py-2 text-center font-semibold text-gray-700 transition hover:bg-gray-400"
          >
            <i class="fas fa-redo mr-2"></i>Reset
          </button>
        </form>
      </div>

      <div class="overflow-hidden rounded-lg bg-white shadow-lg">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time Slot</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Capacity</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Price / Hour</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-if="filteredSchedules.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                  <i class="fas fa-inbox mb-4 text-6xl text-gray-300"></i>
                  <p class="mb-2 text-lg font-semibold">No Schedules Found</p>
                  <p class="text-sm">Create your first schedule to get started</p>
                </td>
              </tr>

              <tr v-for="schedule in filteredSchedules" :key="schedule.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">
                    {{ formatDate(schedule.date) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ formatDay(schedule.date) }}
                  </div>
                </td>

                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                  {{ schedule.time_slot }}
                </td>

                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm font-semibold text-gray-900">
                    {{ schedule.total_capacity }}
                  </div>
                </td>

                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm font-bold text-gray-900">
                    ₱{{ formatMoney(schedule.price_per_slot) }}/hour
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ schedule.duration_hours }} hour(s) • Total:
                    ₱{{ formatMoney(schedule.total_price) }}
                  </div>
                </td>

                <td class="whitespace-nowrap px-6 py-4">
                  <span
                    v-if="schedule.status === 'available'"
                    class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold leading-5 text-green-800"
                  >
                    Available
                  </span>

                  <span
                    v-else-if="schedule.status === 'full'"
                    class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold leading-5 text-red-800"
                  >
                    Full
                  </span>

                  <span
                    v-else
                    class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold leading-5 text-gray-800"
                  >
                    {{ capitalize(schedule.status) }}
                  </span>
                </td>

                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                  <div class="flex space-x-2">
                    <button
                      @click="viewSchedule(schedule.id)"
                      class="text-blue-600 hover:text-blue-900"
                      title="View"
                    >
                      <i class="fas fa-eye"></i>
                    </button>

                    <template v-if="Number(schedule.booked_slots || 0) === 0">
                      <button
                        @click="editSchedule(schedule.id)"
                        class="text-green-600 hover:text-green-900"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </button>

                      <button
                        @click="deleteSchedule(schedule.id)"
                        class="text-red-600 hover:text-red-900"
                        title="Delete"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </template>

                    <span v-else class="text-gray-400" title="Cannot edit/delete - has bookings">
                      <i class="fas fa-lock"></i>
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="error"
        class="mt-4 rounded border border-red-300 bg-red-100 px-4 py-3 text-red-700"
      >
        {{ error }}
      </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-800">Create New Schedule</h3>
          <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <form @submit.prevent="submitCreateSchedule" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Date *</label>
              <input
                v-model="createForm.date"
                type="date"
                required
                :min="today"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Total Capacity *</label>
              <input
                v-model.number="createForm.total_capacity"
                type="number"
                required
                min="1"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Start Time *</label>
              <input
                v-model="createForm.start_time"
                type="time"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">End Time *</label>
              <input
                v-model="createForm.end_time"
                type="time"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-2">
              <label class="mb-2 block text-sm font-semibold text-gray-700">Price per Hour *</label>
              <input
                v-model.number="createForm.price_per_slot"
                type="number"
                required
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-2">
              <label class="mb-2 block text-sm font-semibold text-gray-700">Notes (Optional)</label>
              <textarea
                v-model="createForm.notes"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
                placeholder="Any special notes..."
              ></textarea>
            </div>
          </div>

          <div class="flex space-x-3 pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
            >
              <i class="fas fa-save mr-2"></i>Create Schedule
            </button>
            <button
              type="button"
              @click="closeCreateModal"
              class="rounded-lg bg-gray-300 px-6 py-3 font-semibold text-gray-700 transition hover:bg-gray-400"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-800">Edit Schedule</h3>
          <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <form @submit.prevent="submitEditSchedule" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Date *</label>
              <input
                v-model="editForm.date"
                type="date"
                required
                :min="today"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Total Capacity *</label>
              <input
                v-model.number="editForm.total_capacity"
                type="number"
                required
                min="1"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Start Time *</label>
              <input
                v-model="editForm.start_time"
                type="time"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">End Time *</label>
              <input
                v-model="editForm.end_time"
                type="time"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-2">
              <label class="mb-2 block text-sm font-semibold text-gray-700">Price per Hour *</label>
              <input
                v-model.number="editForm.price_per_slot"
                type="number"
                required
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="col-span-2">
              <label class="mb-2 block text-sm font-semibold text-gray-700">Notes (Optional)</label>
              <textarea
                v-model="editForm.notes"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
                placeholder="Any special notes..."
              ></textarea>
            </div>
          </div>

          <div class="flex space-x-3 pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 rounded-lg bg-green-600 px-6 py-3 font-semibold text-white transition hover:bg-green-700 disabled:opacity-60"
            >
              <i class="fas fa-save mr-2"></i>Update Schedule
            </button>
            <button
              type="button"
              @click="closeEditModal"
              class="rounded-lg bg-gray-300 px-6 py-3 font-semibold text-gray-700 transition hover:bg-gray-400"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showBulkCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div class="max-h-screen w-full max-w-4xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-gray-800">Bulk Create Schedules</h3>
          <button @click="closeBulkCreateModal" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <p class="mb-4 text-sm text-gray-600">Create multiple schedules for selected days and time slots</p>

        <form @submit.prevent="submitBulkCreate" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">Start Date *</label>
              <input
                v-model="bulkForm.start_date"
                type="date"
                required
                :min="today"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-gray-700">End Date *</label>
              <input
                v-model="bulkForm.end_date"
                type="date"
                required
                :min="today"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-gray-700">Select Days *</label>
            <div class="grid grid-cols-4 gap-2">
              <label
                v-for="(day, index) in days"
                :key="index"
                class="flex cursor-pointer items-center space-x-2 rounded border p-2 hover:bg-gray-50"
              >
                <input
                  v-model="bulkForm.days_of_week"
                  type="checkbox"
                  :value="index"
                  class="form-checkbox"
                />
                <span class="text-sm">{{ day }}</span>
              </label>
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-semibold text-gray-700">Time Slots *</label>

            <div
              v-for="(slot, index) in bulkForm.time_slots"
              :key="index"
              class="mb-2 grid grid-cols-4 gap-2"
            >
              <input
                v-model="slot.start_time"
                type="time"
                required
                class="rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
              <input
                v-model="slot.end_time"
                type="time"
                required
                class="rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
              <input
                v-model.number="slot.capacity"
                type="number"
                required
                min="1"
                placeholder="Capacity"
                class="rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
              />
              <div class="flex gap-2">
                <input
                  v-model.number="slot.price"
                  type="number"
                  required
                  min="0"
                  step="0.01"
                  placeholder="Price per Hour"
                  class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-blue-500"
                />
                <button
                  v-if="bulkForm.time_slots.length > 1"
                  type="button"
                  @click="removeTimeSlot(index)"
                  class="rounded-lg bg-red-100 px-3 py-2 text-red-600 hover:bg-red-200"
                  title="Remove"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>

            <button
              type="button"
              @click="addTimeSlot"
              class="text-sm font-semibold text-blue-600 hover:text-blue-700"
            >
              <i class="fas fa-plus mr-1"></i>Add Another Time Slot
            </button>
          </div>

          <div class="flex space-x-3 pt-4">
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 rounded-lg bg-green-600 px-6 py-3 font-semibold text-white transition hover:bg-green-700 disabled:opacity-60"
            >
              <i class="fas fa-calendar-plus mr-2"></i>Create Schedules
            </button>
            <button
              type="button"
              @click="closeBulkCreateModal"
              class="rounded-lg bg-gray-300 px-6 py-3 font-semibold text-gray-700 transition hover:bg-gray-400"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>