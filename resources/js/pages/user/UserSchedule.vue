<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()

const now = new Date()

const filters = reactive({
  month: now.getMonth() + 1,
  year: now.getFullYear(),
  date: '',
})

const schedules = ref([])
const upcomingSchedules = ref([])
const selectedDateSchedules = ref([])
const loading = ref(false)
const dateLoading = ref(false)
const bookingLoading = ref(false)
const error = ref('')
const success = ref('')

const selectedDate = ref('')
const bookingModalOpen = ref(false)
const bookingStep = ref('booking')
const selectedSchedule = ref(null)
const currentBookingData = ref(null)

const bookingForm = reactive({
  schedule_id: '',
  payment_method: '',
  special_requests: '',
})

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const currency = (value) => {
  return `₱${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`
}

const monthYear = computed(() => {
  return new Date(filters.year, filters.month - 1, 1).toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric',
  })
})

const scheduleMap = computed(() => {
  const map = {}

  schedules.value.forEach((schedule) => {
    if (!map[schedule.date]) {
      map[schedule.date] = []
    }
    map[schedule.date].push(schedule)
  })

  return map
})

const formatDateLong = (date) => {
  if (!date) return 'Select a date to view slots'
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const formatCardDate = (date) => {
  if (!date) return { weekday: '', date: '' }

  const d = new Date(date)

  return {
    weekday: d.toLocaleDateString('en-US', { weekday: 'long' }),
    date: d.toLocaleDateString('en-US', {
      month: 'short',
      day: '2-digit',
      year: 'numeric',
    }),
  }
}

const isToday = (date) => {
  const today = new Date()
  const d = new Date(date)

  return (
    d.getFullYear() === today.getFullYear() &&
    d.getMonth() === today.getMonth() &&
    d.getDate() === today.getDate()
  )
}

const isPastDate = (date) => {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return d < today
}

const calendarDays = computed(() => {
  const firstDay = new Date(filters.year, filters.month - 1, 1)
  const lastDay = new Date(filters.year, filters.month, 0)

  const startDayOfWeek = firstDay.getDay()
  const daysInMonth = lastDay.getDate()
  const days = []

  for (let i = 0; i < startDayOfWeek; i++) {
    days.push({
      key: `empty-${i}`,
      empty: true,
    })
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const date = `${filters.year}-${String(filters.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    const daySchedules = scheduleMap.value[date] || []

    const availableSchedules = daySchedules.filter(
      (schedule) => !schedule.is_full && !schedule.user_has_booking
    )
    const bookedSchedules = daySchedules.filter((schedule) => schedule.user_has_booking)
    const fullSchedules = daySchedules.filter((schedule) => schedule.is_full)

    let status = 'none'

    if (isPastDate(date)) {
      status = 'past'
    } else if (bookedSchedules.length > 0) {
      status = 'booked'
    } else if (availableSchedules.length > 0) {
      status = 'available'
    } else if (fullSchedules.length > 0) {
      status = 'full'
    }

    days.push({
      key: date,
      empty: false,
      day,
      date,
      hasSchedules: daySchedules.length > 0,
      availableCount: availableSchedules.length,
      bookedCount: bookedSchedules.length,
      fullCount: fullSchedules.length,
      isToday: isToday(date),
      isPast: isPastDate(date),
      isSelected: selectedDate.value === date,
      status,
    })
  }

  return days
})

const getCalendarDayClass = (day) => {
  if (day.empty) {
    return 'min-h-[92px]'
  }

  const base =
    'min-h-[92px] rounded-xl border p-3 transition-all duration-200 relative text-left shadow-sm'

  if (day.isPast) {
    return `${base} bg-gray-100 border-gray-200 text-gray-400 cursor-not-allowed`
  }

  if (day.isSelected) {
    return `${base} bg-blue-50 border-blue-500 ring-2 ring-blue-200 cursor-pointer`
  }

  if (day.status === 'booked') {
    return `${base} bg-indigo-50 border-indigo-300 hover:border-indigo-500 cursor-pointer`
  }

  if (day.status === 'available') {
    return `${base} bg-green-50 border-green-300 hover:border-green-500 hover:shadow-md cursor-pointer`
  }

  if (day.status === 'full') {
    return `${base} bg-red-50 border-red-300 hover:border-red-500 cursor-pointer`
  }

  return `${base} bg-white border-gray-200 hover:border-blue-400 hover:shadow-md cursor-pointer`
}

const loadSchedules = async () => {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/api/user/schedule', {
      params: {
        month: filters.month,
        year: filters.year,
      },
    })

    schedules.value = data.schedules || []
    upcomingSchedules.value = data.upcomingSchedules || []

    if (selectedDate.value) {
      await loadSchedulesByDate(selectedDate.value)
    }
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load schedules.'
  } finally {
    loading.value = false
  }
}

const loadSchedulesByDate = async (date = filters.date) => {
  if (!date) {
    selectedDateSchedules.value = []
    return
  }

  dateLoading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/api/user/schedule/date', {
      params: {
        date,
      },
    })

    selectedDateSchedules.value = data || []
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load schedules for selected date.'
  } finally {
    dateLoading.value = false
  }
}

const selectDate = async (day) => {
  if (day.empty || day.isPast || !day.hasSchedules) return

  selectedDate.value = day.date
  filters.date = day.date
  await loadSchedulesByDate(day.date)
}

const changeMonth = async (direction) => {
  if (direction === -1) {
    if (filters.month === 1) {
      filters.month = 12
      filters.year--
    } else {
      filters.month--
    }
  } else {
    if (filters.month === 12) {
      filters.month = 1
      filters.year++
    } else {
      filters.month++
    }
  }

  selectedDate.value = ''
  filters.date = ''
  selectedDateSchedules.value = []
  await loadSchedules()
}

const openBookingModal = async (scheduleId) => {
  error.value = ''
  success.value = ''

  try {
    const { data } = await api.get(`/api/user/schedule/${scheduleId}`)
    selectedSchedule.value = data
    bookingForm.schedule_id = data.id
    bookingForm.payment_method = ''
    bookingForm.special_requests = ''
    bookingStep.value = 'booking'
    bookingModalOpen.value = true
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load schedule details.'
  }
}

const closeBookingModal = () => {
  bookingModalOpen.value = false
  bookingStep.value = 'booking'
  selectedSchedule.value = null
  currentBookingData.value = null
  bookingForm.schedule_id = ''
  bookingForm.payment_method = ''
  bookingForm.special_requests = ''
}

const submitBooking = async () => {
  if (!bookingForm.schedule_id) {
    error.value = 'Please select a schedule.'
    return
  }

  if (!bookingForm.payment_method) {
    error.value = 'Please select a payment method.'
    return
  }

  bookingLoading.value = true
  error.value = ''

  try {
    const { data } = await api.post('/api/user/bookings/store', {
      schedule_id: bookingForm.schedule_id,
      number_of_slots: 1,
      payment_method: bookingForm.payment_method,
      special_requests: bookingForm.special_requests || null,
    })

    if (data?.success) {
      currentBookingData.value = {
        ...data.booking,
        payment_method: bookingForm.payment_method,
        schedule: selectedSchedule.value,
      }

      bookingStep.value = 'payment'
      success.value = data.message || 'Booking created successfully.'

      await loadSchedules()
      if (selectedDate.value) {
        await loadSchedulesByDate(selectedDate.value)
      }
    } else {
      error.value = data?.message || 'Booking failed.'
    }
  } catch (err) {
    console.error(err)
    error.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.schedule_id?.[0] ||
      'Failed to create booking.'
  } finally {
    bookingLoading.value = false
  }
}

const getPaymentMethodName = (method) => {
  const names = {
    cash: '💵 Cash (Pay at Gym)',
    gcash: '📱 GCash',
    maya: '💳 Maya (PayMaya)',
    bank_transfer: '🏦 Bank Transfer',
  }

  return names[method] || method
}

const paymentInstructions = computed(() => {
  if (!currentBookingData.value) return ''

  const booking = currentBookingData.value
  const total = Number(booking.total_amount || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

  if (booking.payment_method === 'cash') {
    return `
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h4 class="font-bold text-yellow-800 mb-3 flex items-center">
          <i class="fas fa-money-bill-wave text-yellow-600 mr-2"></i>Cash Payment Instructions
        </h4>
        <div class="space-y-2 text-sm text-yellow-900">
          <p>✅ Your booking has been confirmed!</p>
          <p>💵 Please pay <strong>₱${total}</strong> at the gym reception before your session.</p>
          <p>📍 <strong>Location:</strong> City Gymnasium Reception Desk</p>
          <p>⏰ <strong>Payment due:</strong> Before your scheduled session</p>
          <p class="pt-2 border-t border-yellow-300 mt-2">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Note:</strong> Please arrive 15 minutes early to complete payment.
          </p>
        </div>
      </div>
    `
  }

  if (booking.payment_method === 'gcash') {
    return `
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-bold text-blue-800 mb-3 flex items-center">
          <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>GCash Payment Instructions
        </h4>
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <div class="bg-white p-4 rounded-lg border border-blue-200 text-center">
              <div class="bg-gray-100 p-4 rounded-lg mb-3">
                <i class="fas fa-qrcode text-6xl text-blue-600"></i>
              </div>
              <p class="text-sm text-gray-600 mb-2">Scan this QR code in your GCash app</p>
              <p class="text-xs text-gray-500">(QR Code placeholder - Add actual QR in production)</p>
            </div>
          </div>
          <div class="space-y-3 text-sm">
            <div>
              <p class="font-semibold text-gray-700">Amount to Pay:</p>
              <p class="text-xl font-bold text-blue-600">₱${total}</p>
            </div>
            <div>
              <p class="font-semibold text-gray-700">GCash Number:</p>
              <p class="font-mono text-lg">0917-123-4567</p>
            </div>
            <div>
              <p class="font-semibold text-gray-700">Account Name:</p>
              <p>City Gymnasium</p>
            </div>
            <div class="pt-2 border-t">
              <p class="text-xs text-gray-600">
                <i class="fas fa-exclamation-circle mr-1"></i>
                After payment, please take a screenshot of your receipt and upload it in the Payments section.
              </p>
            </div>
          </div>
        </div>
      </div>
    `
  }

  if (booking.payment_method === 'maya') {
    return `
      <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
        <h4 class="font-bold text-purple-800 mb-3 flex items-center">
          <i class="fas fa-credit-card text-purple-600 mr-2"></i>Maya Payment Instructions
        </h4>
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <div class="bg-white p-4 rounded-lg border border-purple-200 text-center">
              <div class="bg-gray-100 p-4 rounded-lg mb-3">
                <i class="fas fa-qrcode text-6xl text-purple-600"></i>
              </div>
              <p class="text-sm text-gray-600 mb-2">Scan this QR code in your Maya app</p>
              <p class="text-xs text-gray-500">(QR Code placeholder - Add actual QR in production)</p>
            </div>
          </div>
          <div class="space-y-3 text-sm">
            <div>
              <p class="font-semibold text-gray-700">Amount to Pay:</p>
              <p class="text-xl font-bold text-purple-600">₱${total}</p>
            </div>
            <div>
              <p class="font-semibold text-gray-700">Maya Number:</p>
              <p class="font-mono text-lg">0917-123-4567</p>
            </div>
            <div>
              <p class="font-semibold text-gray-700">Account Name:</p>
              <p>City Gymnasium</p>
            </div>
            <div class="pt-2 border-t">
              <p class="text-xs text-gray-600">
                <i class="fas fa-exclamation-circle mr-1"></i>
                After payment, please take a screenshot of your receipt and upload it in the Payments section.
              </p>
            </div>
          </div>
        </div>
      </div>
    `
  }

  if (booking.payment_method === 'bank_transfer') {
    return `
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="font-bold text-green-800 mb-3 flex items-center">
          <i class="fas fa-university text-green-600 mr-2"></i>Bank Transfer Instructions
        </h4>
        <div class="space-y-3 text-sm">
          <div class="bg-white p-4 rounded-lg border border-green-200">
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <p class="font-semibold text-gray-700 mb-1">Bank Name:</p>
                <p class="text-lg">BDO Unibank</p>
              </div>
              <div>
                <p class="font-semibold text-gray-700 mb-1">Account Number:</p>
                <p class="font-mono text-lg">0123-4567-8901</p>
              </div>
              <div>
                <p class="font-semibold text-gray-700 mb-1">Account Name:</p>
                <p>City Gymnasium Corp.</p>
              </div>
              <div>
                <p class="font-semibold text-gray-700 mb-1">Amount to Transfer:</p>
                <p class="text-xl font-bold text-green-600">₱${total}</p>
              </div>
            </div>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <p class="font-semibold text-yellow-800 mb-2">
              <i class="fas fa-exclamation-triangle mr-1"></i>Important Instructions:
            </p>
            <ol class="list-decimal list-inside space-y-1 text-yellow-900">
              <li>Transfer the exact amount shown above</li>
              <li>Use your booking number <strong>${booking.booking_number}</strong> as reference</li>
              <li>Take a photo of your transfer receipt</li>
              <li>Upload the receipt in the Payments section</li>
              <li>Wait for admin verification</li>
            </ol>
          </div>
        </div>
      </div>
    `
  }

  return ''
})

const goToPayments = () => {
  closeBookingModal()
  router.push('/user/payments')
}

onMounted(() => {
  loadSchedules()
})
</script>

<template>
  <div>
    <div
      v-if="success"
      class="mb-4 rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-green-700"
    >
      {{ success }}
    </div>

    <div
      v-if="error"
      class="mb-4 rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-red-700"
    >
      {{ error }}
    </div>

    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Schedule &amp; Availability
      </h1>
      <p class="text-gray-600 mt-1">View available time slots and book your gym session</p>
    </div>

    <div v-if="loading" class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-lg">
      Loading schedules...
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <div class="rounded-lg bg-white p-6 shadow-lg">
            <div class="mb-6 flex items-center justify-between">
              <button
                class="rounded-lg bg-blue-500 px-4 py-2 text-white transition hover:bg-blue-600"
                @click="changeMonth(-1)"
              >
                <i class="fas fa-chevron-left"></i>
              </button>

              <h2 class="text-2xl font-bold text-gray-800">{{ monthYear }}</h2>

              <button
                class="rounded-lg bg-blue-500 px-4 py-2 text-white transition hover:bg-blue-600"
                @click="changeMonth(1)"
              >
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>

            <div class="calendar-container">
              <div class="mb-2 grid grid-cols-7 gap-2">
                <div
                  v-for="day in daysOfWeek"
                  :key="day"
                  class="py-2 text-center font-semibold text-gray-600"
                >
                  {{ day }}
                </div>
              </div>

              <div class="grid grid-cols-7 gap-3">
                <div
                  v-for="day in calendarDays"
                  :key="day.key"
                  :class="getCalendarDayClass(day)"
                  @click="selectDate(day)"
                >
                  <template v-if="!day.empty">
                    <div class="flex items-start justify-between">
                      <span
                        class="text-base font-bold"
                        :class="day.isToday ? 'text-blue-600' : 'text-gray-800'"
                      >
                        {{ day.day }}
                      </span>

                      <span
                        v-if="day.status === 'booked'"
                        class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] text-white"
                      >
                        Booked
                      </span>
                    </div>

                    <div class="mt-3">
                      <template v-if="day.status === 'available'">
                        <div class="text-xs font-medium text-green-700">
                          {{ day.availableCount }} slot(s)
                        </div>
                      </template>

                      <template v-else-if="day.status === 'full'">
                        <div class="text-xs font-medium text-red-700">
                          Full
                        </div>
                      </template>

                      <template v-else-if="day.status === 'past'">
                        <div class="text-xs font-medium text-gray-500">
                          Past
                        </div>
                      </template>

                      <template v-else-if="day.status === 'booked'">
                        <div class="text-xs font-medium text-indigo-700">
                          Already booked
                        </div>
                      </template>

                      <template v-else>
                        <div class="text-xs font-medium text-gray-400">
                          -
                        </div>
                      </template>
                    </div>
                  </template>
                </div>
              </div>
            </div>

            <div class="mt-6 flex items-center justify-center space-x-6 text-sm">
              <div class="flex items-center">
                <span class="mr-2 h-4 w-4 rounded bg-green-500"></span>
                <span class="text-gray-600">Available</span>
              </div>
              <div class="flex items-center">
                <span class="mr-2 h-4 w-4 rounded bg-red-500"></span>
                <span class="text-gray-600">Full</span>
              </div>
              <div class="flex items-center">
                <span class="mr-2 h-4 w-4 rounded bg-gray-300"></span>
                <span class="text-gray-600">Past</span>
              </div>
            </div>
          </div>
        </div>

        <div>
          <div class="rounded-lg bg-white p-6 shadow-lg">
            <h3 class="mb-4 text-xl font-bold text-gray-800">
              <i class="fas fa-clock mr-2 text-blue-500"></i>Available Slots
            </h3>

            <p class="mb-4 text-sm text-gray-600">{{ formatDateLong(selectedDate) }}</p>

            <div class="max-h-96 space-y-3 overflow-y-auto pr-1">
              <div v-if="dateLoading" class="py-8 text-center text-gray-500">
                Loading date schedules...
              </div>

              <template v-else-if="selectedDateSchedules.length">
                <div
                  v-for="slot in selectedDateSchedules"
                  :key="slot.id"
                  class="rounded-lg border border-gray-200 p-4 transition hover:border-blue-500"
                >
                  <div class="mb-2 flex items-start justify-between">
                    <div>
                      <p class="font-bold text-gray-800">{{ slot.time_slot }}</p>
                      <p class="text-xs text-gray-500">{{ slot.start_time }} - {{ slot.end_time }}</p>
                    </div>

                    <span
                      v-if="slot.user_has_booking"
                      class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-800"
                    >
                      Booked
                    </span>
                    <span
                      v-else-if="slot.is_full"
                      class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800"
                    >
                      Full
                    </span>
                    <span
                      v-else
                      class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800"
                    >
                      Available
                    </span>
                  </div>

                  <div class="space-y-1 text-sm text-gray-600">
                    <p>
                      <i class="fas fa-users mr-2 text-blue-500"></i>
                      {{ slot.available_slots }}/{{ slot.total_capacity }} slots available
                    </p>
                    <p>
                      <i class="fas fa-peso-sign mr-2 text-green-500"></i>
                      {{ currency(slot.total_price || slot.price || slot.price_per_slot) }}
                    </p>
                    <p v-if="slot.notes">
                      <i class="fas fa-sticky-note mr-2 text-yellow-500"></i>
                      {{ slot.notes }}
                    </p>
                  </div>

                  <button
                    v-if="!slot.is_full && !slot.user_has_booking"
                    class="mt-3 w-full rounded-lg bg-blue-500 px-4 py-2 text-sm text-white transition hover:bg-blue-600"
                    @click="openBookingModal(slot.id)"
                  >
                    <i class="fas fa-plus mr-1"></i>Book Now
                  </button>
                </div>
              </template>

              <div v-else class="py-8 text-center text-gray-500">
                <i class="fas fa-calendar-day mb-2 text-4xl text-gray-300"></i>
                <p class="text-sm">Click on a date to see available time slots</p>
              </div>
            </div>
          </div>

          <div class="mt-6 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 p-6 text-white shadow-lg">
            <h4 class="mb-3 text-lg font-bold">
              <i class="fas fa-info-circle mr-2"></i>Quick Info
            </h4>
            <div class="space-y-2 text-sm">
              <p><i class="fas fa-clock mr-2"></i>Operating Hours: 8:00 AM - 10:00 PM</p>
              <p><i class="fas fa-users mr-2"></i>Capacity depends on schedule</p>
              <p><i class="fas fa-peso-sign mr-2"></i>Price: ₱100 per hour (sample)</p>
              <p><i class="fas fa-calendar-check mr-2"></i>Book up to 30 days in advance</p>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 rounded-lg bg-white p-6 shadow-lg">
        <h3 class="mb-4 text-xl font-bold text-gray-800">
          <i class="fas fa-star mr-2 text-yellow-500"></i>Upcoming Available Slots (Next 7 Days)
        </h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <template v-if="upcomingSchedules.length">
            <div
              v-for="schedule in upcomingSchedules"
              :key="schedule.id"
              class="cursor-pointer rounded-lg border border-gray-200 p-4 transition hover:border-blue-500 hover:shadow-md"
              @click="openBookingModal(schedule.id)"
            >
              <div class="mb-2 flex items-start justify-between">
                <div>
                  <p class="text-sm text-gray-500">{{ formatCardDate(schedule.date).weekday }}</p>
                  <h4 class="text-lg font-bold text-gray-800">{{ formatCardDate(schedule.date).date }}</h4>
                </div>

                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                  Available
                </span>
              </div>

              <div class="mb-2 flex items-center text-sm text-gray-600">
                <i class="fas fa-clock mr-2 text-blue-500"></i>
                <span>{{ schedule.time_slot }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="font-bold text-blue-600">
                  ₱{{ Number(schedule.price_per_slot || schedule.total_price || 0).toFixed(0) }}/hour
                </span>

                <button class="rounded-lg bg-blue-500 px-4 py-2 text-sm text-white transition hover:bg-blue-600">
                  <i class="fas fa-plus mr-1"></i>Book Now
                </button>
              </div>
            </div>
          </template>

          <div v-else class="col-span-1 py-8 text-center text-gray-500 md:col-span-2 lg:col-span-3">
            <i class="fas fa-calendar-times mb-2 text-4xl text-gray-300"></i>
            <p>No available slots in the next 7 days</p>
          </div>
        </div>
      </div>
    </template>

    <div
      v-if="bookingModalOpen"
      class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
    >
      <div class="relative top-10 mx-auto w-full max-w-2xl rounded-lg border bg-white p-5 shadow-lg">
        <div v-if="bookingStep === 'booking'" class="mt-3">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">
              <i class="fas fa-calendar-check mr-2 text-blue-500"></i>Book Your Session
            </h3>

            <button @click="closeBookingModal" class="text-gray-400 transition hover:text-gray-500">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="mb-4 rounded-lg bg-blue-50 p-4" v-if="selectedSchedule">
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Date:</span>
                <span class="font-semibold">{{ selectedSchedule.date }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Time:</span>
                <span class="font-semibold">{{ selectedSchedule.time_slot }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Duration:</span>
                <span class="font-semibold">{{ selectedSchedule.duration_hours }} hour(s)</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Available Slots:</span>
                <span class="font-semibold">{{ selectedSchedule.available_slots }}</span>
              </div>
              <div class="flex justify-between border-t border-blue-200 pt-2">
                <span class="font-medium text-gray-700">Price:</span>
                <span class="font-bold text-green-600">
                  {{ currency(selectedSchedule.price_per_slot || selectedSchedule.total_price || 0) }}
                </span>
              </div>
            </div>
          </div>

          <form class="space-y-4" @submit.prevent="submitBooking">
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Payment Method <span class="text-red-500">*</span>
              </label>

              <select
                v-model="bookingForm.payment_method"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="">Select payment method</option>
                <option value="cash">💵 Cash (Pay at Gym)</option>
                <option value="gcash">📱 GCash</option>
                <option value="maya">💳 Maya (PayMaya)</option>
                <option value="bank_transfer">🏦 Bank Transfer</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">
                Special Requests (Optional)
              </label>

              <textarea
                v-model="bookingForm.special_requests"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                placeholder="Any special requirements or notes..."
              ></textarea>
            </div>

            <div class="rounded-lg bg-gradient-to-r from-green-50 to-blue-50 p-4">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">Total Amount:</span>
                <span class="text-2xl font-bold text-green-600">
                  {{ currency(selectedSchedule?.price_per_slot || selectedSchedule?.total_price || 0) }}
                </span>
              </div>
            </div>

            <div class="flex space-x-3 pt-4">
              <button
                type="button"
                @click="closeBookingModal"
                class="flex-1 rounded-lg bg-gray-200 px-4 py-3 font-medium text-gray-700 transition hover:bg-gray-300"
              >
                Cancel
              </button>

              <button
                type="submit"
                :disabled="bookingLoading"
                class="flex-1 rounded-lg bg-blue-600 px-4 py-3 font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
              >
                <i v-if="bookingLoading" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-arrow-right mr-2"></i>
                {{ bookingLoading ? 'Processing...' : 'Continue to Payment' }}
              </button>
            </div>
          </form>
        </div>

        <div v-else class="mt-3">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">
              <i class="fas fa-credit-card mr-2 text-green-500"></i>Payment Instructions
            </h3>

            <button @click="closeBookingModal" class="text-gray-400 transition hover:text-gray-500">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
            <div class="flex items-start">
              <i class="fas fa-check-circle mr-3 mt-1 text-2xl text-green-500"></i>
              <div>
                <h4 class="text-lg font-bold text-green-800">Booking Created Successfully!</h4>
                <p class="mt-1 text-sm text-green-700">
                  Booking Number:
                  <span class="font-mono font-bold">{{ currentBookingData?.booking_number }}</span>
                </p>
              </div>
            </div>
          </div>

          <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <h4 class="mb-3 font-bold text-gray-800">
              <i class="fas fa-receipt mr-2 text-blue-500"></i>Booking Summary
            </h4>

            <div class="space-y-2 text-sm" v-if="currentBookingData">
              <div class="flex justify-between">
                <span class="text-gray-600">Date:</span>
                <span class="font-semibold">{{ currentBookingData.schedule?.date }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Time:</span>
                <span class="font-semibold">{{ currentBookingData.schedule?.time_slot }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Payment Method:</span>
                <span class="font-semibold">{{ getPaymentMethodName(currentBookingData.payment_method) }}</span>
              </div>
              <div class="flex justify-between border-t border-blue-300 pt-2">
                <span class="font-bold text-gray-800">Total Amount:</span>
                <span class="text-lg font-bold text-green-600">
                  {{ currency(currentBookingData.total_amount) }}
                </span>
              </div>
            </div>
          </div>

          <div class="mb-6" v-html="paymentInstructions"></div>

          <div class="flex space-x-3">
            <button
              @click="goToPayments"
              class="flex-1 rounded-lg bg-blue-600 px-4 py-3 font-medium text-white transition hover:bg-blue-700"
            >
              <i class="fas fa-wallet mr-2"></i>Go to My Payments
            </button>

            <button
              @click="closeBookingModal"
              class="flex-1 rounded-lg bg-gray-200 px-4 py-3 font-medium text-gray-700 transition hover:bg-gray-300"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>