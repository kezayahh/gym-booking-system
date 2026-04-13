<script setup>
import { ref, onMounted, nextTick } from 'vue'
import api from '../../services/api'

const dashboard = ref({
  user: {
    name: '',
    email: '',
    memberSince: '',
    initials: '',
  },
  stats: {
    totalBookings: 0,
    confirmedBookings: 0,
    totalSpent: 0,
    pendingPayments: 0,
  },
  upcomingBookings: [],
  recentPayments: [],
  notifications: [],
  unreadNotifications: 0,
})

const loading = ref(true)

const formatMoney = (value) => Number(value || 0).toFixed(2)

const loadDashboard = async () => {
  try {
    const response = await api.get('/api/user/dashboard')
    const data = response?.data || {}

    dashboard.value = {
      user: {
        name: data?.user?.name || '',
        email: data?.user?.email || '',
        memberSince: data?.user?.memberSince || data?.user?.member_since || '',
        initials: data?.user?.initials || '',
      },
      stats: {
        totalBookings: Number(data?.stats?.totalBookings || data?.stats?.total_bookings || 0),
        confirmedBookings: Number(data?.stats?.confirmedBookings || data?.stats?.confirmed_bookings || 0),
        totalSpent: Number(data?.stats?.totalSpent || data?.stats?.total_spent || 0),
        pendingPayments: Number(data?.stats?.pendingPayments || data?.stats?.pending_payments || 0),
      },
      upcomingBookings: Array.isArray(data?.upcomingBookings)
        ? data.upcomingBookings
        : Array.isArray(data?.upcoming_bookings)
          ? data.upcoming_bookings
          : [],
      recentPayments: Array.isArray(data?.recentPayments)
        ? data.recentPayments
        : Array.isArray(data?.recent_payments)
          ? data.recent_payments
          : [],
      notifications: Array.isArray(data?.notifications) ? data.notifications : [],
      unreadNotifications: Number(
        data?.unreadNotifications ||
        data?.unread_notifications ||
        0
      ),
    }
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  } finally {
    loading.value = false
    await nextTick()
    initMap()
  }
}

const initMap = () => {
  if (typeof window === 'undefined' || typeof window.L === 'undefined') return

  const el = document.getElementById('gym-map')
  if (!el || el.dataset.loaded === 'true') return

  const gymLat = 9.79118
  const gymLng = 125.4936

  const map = window.L.map('gym-map').setView([gymLat, gymLng], 18)

  window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  window.L.marker([gymLat, gymLng])
    .addTo(map)
    .bindPopup('Surigao City Gymnasium')
    .openPopup()

  el.dataset.loaded = 'true'
}

const loadLeaflet = async () => {
  if (typeof window === 'undefined') return
  if (window.L) return

  const cssId = 'leaflet-css'
  if (!document.getElementById(cssId)) {
    const link = document.createElement('link')
    link.id = cssId
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)
  }

  await new Promise((resolve, reject) => {
    const existing = document.getElementById('leaflet-js')
    if (existing) {
      if (window.L) {
        resolve()
        return
      }

      existing.addEventListener('load', resolve, { once: true })
      existing.addEventListener('error', reject, { once: true })
      return
    }

    const script = document.createElement('script')
    script.id = 'leaflet-js'
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    script.onload = resolve
    script.onerror = reject
    document.body.appendChild(script)
  })
}

const formatDate = (date) => {
  if (!date) return 'No date'
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: '2-digit',
  })
}

const formatShortDate = (date) => {
  if (!date) return 'No date'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  })
}

const getNotificationClass = (type) => {
  if (type === 'booking_confirmed') return 'border-green-500 bg-green-50'
  if (type === 'payment_received') return 'border-blue-500 bg-blue-50'
  return 'border-gray-500 bg-gray-50'
}

const getPaymentBadgeClass = (status) => {
  if (status === 'completed') return 'bg-green-100 text-green-800'
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const getPaymentIconWrapClass = (status) => {
  if (status === 'completed') return 'bg-green-100'
  if (status === 'pending') return 'bg-yellow-100'
  return 'bg-red-100'
}

const getPaymentIconClass = (status) => {
  if (status === 'completed') return 'fas fa-check text-green-600'
  if (status === 'pending') return 'fas fa-clock text-yellow-600'
  return 'fas fa-times text-red-600'
}

onMounted(async () => {
  try {
    await loadLeaflet()
  } catch (error) {
    console.warn('Leaflet failed to load (map will be disabled):', error)
  }

  await loadDashboard()
})
</script>

<template>
  <div v-if="loading" class="py-10 text-center text-gray-500">
    Loading dashboard...
  </div>

  <template v-else>
    <div>
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
          <i class="fas fa-home mr-2 text-blue-500"></i>Welcome back, {{ dashboard.user.name || 'User' }}!
        </h1>
        <p class="mt-1 text-gray-600">Here's what's happening with your gym bookings</p>
      </div>

      <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
        <div class="rounded-lg bg-white p-6 shadow-lg transition hover:shadow-xl">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-blue-100 p-3">
              <i class="fas fa-clipboard-list text-2xl text-blue-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Bookings</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ dashboard.stats.totalBookings }}</h3>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg transition hover:shadow-xl">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-green-100 p-3">
              <i class="fas fa-check-circle text-2xl text-green-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Confirmed</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ dashboard.stats.confirmedBookings }}</h3>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg transition hover:shadow-xl">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-purple-100 p-3">
              <i class="fas fa-money-bill-wave text-2xl text-purple-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total Spent</p>
              <h3 class="text-2xl font-bold text-gray-800">₱{{ formatMoney(dashboard.stats.totalSpent) }}</h3>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg transition hover:shadow-xl">
          <div class="flex items-center">
            <div class="mr-4 rounded-full bg-yellow-100 p-3">
              <i class="fas fa-hourglass-half text-2xl text-yellow-600"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Pending Payment</p>
              <h3 class="text-2xl font-bold text-gray-800">₱{{ formatMoney(dashboard.stats.pendingPayments) }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        <router-link
          to="/user/schedule"
          class="transform rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white shadow-lg transition hover:scale-105 hover:from-blue-600 hover:to-blue-700"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="mb-2 text-lg font-bold">Book a Slot</h3>
              <p class="text-sm text-blue-100">Schedule your gym session</p>
            </div>
            <i class="fas fa-calendar-plus text-4xl opacity-50"></i>
          </div>
        </router-link>

        <router-link
          to="/user/bookings"
          class="transform rounded-lg bg-gradient-to-r from-green-500 to-green-600 p-6 text-white shadow-lg transition hover:scale-105 hover:from-green-600 hover:to-green-700"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="mb-2 text-lg font-bold">My Bookings</h3>
              <p class="text-sm text-green-100">View your reservations</p>
            </div>
            <i class="fas fa-list text-4xl opacity-50"></i>
          </div>
        </router-link>

        <router-link
          to="/user/payments"
          class="transform rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 p-6 text-white shadow-lg transition hover:scale-105 hover:from-purple-600 hover:to-purple-700"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="mb-2 text-lg font-bold">Payments</h3>
              <p class="text-sm text-purple-100">Manage your payments</p>
            </div>
            <i class="fas fa-credit-card text-4xl opacity-50"></i>
          </div>
        </router-link>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
          <div class="rounded-lg bg-white p-6 shadow-lg">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Upcoming Bookings
              </h3>
              <router-link to="/user/bookings" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                View All →
              </router-link>
            </div>

            <template v-if="dashboard.upcomingBookings && dashboard.upcomingBookings.length">
              <div
                v-for="(booking, index) in dashboard.upcomingBookings"
                :key="booking.id || index"
                class="mb-3 rounded-lg border border-gray-200 p-4 transition hover:border-blue-500 hover:shadow-md"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="mb-2 flex items-center gap-2">
                      <span class="text-sm font-bold text-gray-800">
                        {{ booking.booking_number || `BK-${index + 1}` }}
                      </span>

                      <span
                        v-if="booking.status === 'confirmed'"
                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800"
                      >
                        <i class="fas fa-check-circle mr-1"></i>Confirmed
                      </span>
                      <span
                        v-else
                        class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800"
                      >
                        <i class="fas fa-clock mr-1"></i>Pending
                      </span>
                    </div>

                    <div class="space-y-1">
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-calendar mr-2 text-blue-500"></i>
                        {{ formatDate(booking.schedule_date || booking.date) }}
                      </p>
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-2 text-blue-500"></i>
                        {{ booking.time_slot || booking.schedule_time || booking.time || 'No time available' }}
                      </p>
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-users mr-2 text-blue-500"></i>
                        {{ booking.number_of_slots || booking.slots || 1 }} slot(s)
                      </p>
                    </div>
                  </div>

                  <div class="text-right">
                    <p class="text-lg font-bold text-teal-500">
                      ₱{{ formatMoney(booking.total_amount || booking.amount) }}
                    </p>
                    <router-link
                      v-if="!booking.isPaid && booking.payment_status !== 'paid'"
                      to="/user/payments"
                      class="mt-2 inline-block rounded-lg bg-green-600 px-3 py-1 text-xs text-white transition hover:bg-green-700"
                    >
                      Pay Now
                    </router-link>
                  </div>
                </div>
              </div>
            </template>

            <div v-else class="py-8 text-center text-gray-500">
              <i class="fas fa-calendar-times mb-3 text-5xl text-gray-300"></i>
              <p class="font-semibold">No upcoming bookings</p>
              <p class="mt-1 text-sm">Book a slot to get started!</p>
              <router-link
                to="/user/schedule"
                class="mt-4 inline-block rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700"
              >
                <i class="fas fa-plus mr-2"></i>Book Now
              </router-link>
            </div>
          </div>

          <div class="mt-6 rounded-lg bg-white p-6 shadow-lg">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-receipt mr-2 text-green-500"></i>Recent Payments
              </h3>
              <router-link to="/user/payments" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                View All →
              </router-link>
            </div>

            <div class="space-y-3">
              <template v-if="dashboard.recentPayments && dashboard.recentPayments.length">
                <div
                  v-for="(payment, index) in dashboard.recentPayments"
                  :key="payment.id || index"
                  class="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition hover:bg-gray-100"
                >
                  <div class="flex items-center space-x-3">
                    <div
                      class="flex h-10 w-10 items-center justify-center rounded-full"
                      :class="getPaymentIconWrapClass(payment.status)"
                    >
                      <i :class="getPaymentIconClass(payment.status)"></i>
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-gray-800">
                        {{ payment.payment_number || `PAY-${index + 1}` }}
                      </p>
                      <p class="text-xs text-gray-500">
                        {{ formatShortDate(payment.created_at || payment.date) }}
                      </p>
                    </div>
                  </div>

                  <div class="text-right">
                    <p class="font-bold text-gray-800">₱{{ formatMoney(payment.amount) }}</p>
                    <span
                      class="rounded-full px-2 py-1 text-xs capitalize"
                      :class="getPaymentBadgeClass(payment.status)"
                    >
                      {{ payment.status || 'unknown' }}
                    </span>
                  </div>
                </div>
              </template>

              <div v-else class="py-6 text-center text-gray-500">
                <i class="fas fa-wallet mb-2 text-4xl text-gray-300"></i>
                <p class="text-sm">No payment history yet</p>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-1">
          <div class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-bell mr-2 text-yellow-500"></i>Notifications
                <span
                  v-if="dashboard.unreadNotifications > 0"
                  class="ml-2 rounded-full bg-red-500 px-2 py-1 text-xs text-white"
                >
                  {{ dashboard.unreadNotifications }}
                </span>
              </h3>
              <router-link to="/user/notifications" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                View All →
              </router-link>
            </div>

            <div class="space-y-3">
              <template v-if="dashboard.notifications && dashboard.notifications.length">
                <div
                  v-for="(notification, index) in dashboard.notifications.slice(0, 4)"
                  :key="notification.id || index"
                  class="rounded border-l-4 p-3"
                  :class="getNotificationClass(notification.type)"
                >
                  <p class="text-sm font-semibold text-gray-800">{{ notification.title }}</p>
                  <p class="mt-1 text-xs text-gray-600">{{ notification.message }}</p>
                  <p class="mt-1 text-xs text-gray-400">
                    {{ notification.created_at ? formatShortDate(notification.created_at) : 'Recently' }}
                  </p>
                </div>
              </template>

              <div v-else class="py-6 text-center text-gray-500">
                <i class="fas fa-bell-slash mb-2 text-4xl text-gray-300"></i>
                <p class="text-sm">No notifications</p>
              </div>
            </div>
          </div>

          <div class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <h3 class="mb-4 text-xl font-bold text-gray-800">
              <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>Gym Location
            </h3>

            <div
              id="gym-map"
              class="w-full rounded-lg border border-gray-200"
              style="height: 260px;"
            ></div>

            <p class="mt-3 text-xs text-gray-500">
              Surigao City Gymnasium · Borromeo St, Surigao City
            </p>

            <a
              href="https://www.google.com/maps/search/?api=1&query=9.79118,125.4936"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-700"
            >
              <i class="fas fa-external-link-alt mr-1"></i>Open in Google Maps
            </a>
          </div>

          <div class="mt-6 rounded-lg bg-white p-6 shadow-lg">
            <h3 class="mb-4 text-xl font-bold text-gray-800">
              <i class="fas fa-user-circle mr-2 text-teal-500"></i>Member Profile
            </h3>

            <div class="mb-4 flex items-center">
              <div
                class="mr-4 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-500 text-xl font-bold text-white"
              >
                {{ dashboard.user.initials || 'U' }}
              </div>
              <div>
                <p class="font-semibold text-gray-800">{{ dashboard.user.name || 'User' }}</p>
                <p class="text-sm text-gray-500">{{ dashboard.user.email || 'No email' }}</p>
              </div>
            </div>

            <div class="space-y-2 text-sm text-gray-600">
              <p>
                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                Member Since: {{ dashboard.user.memberSince || 'N/A' }}
              </p>
            </div>

            <router-link
              to="/user/profile"
              class="mt-4 inline-block w-full rounded-lg bg-teal-600 px-4 py-2 text-center text-white transition hover:bg-teal-700"
            >
              View Profile
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </template>
</template>

<style scoped>
#gym-map {
  z-index: 1;
}

.leaflet-container {
  font-family: inherit;
}
</style>