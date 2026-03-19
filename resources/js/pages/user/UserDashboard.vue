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
    dashboard.value = response.data
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
      existing.addEventListener('load', resolve, { once: true })
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
          <i class="fas fa-home text-blue-500 mr-2"></i>Welcome back, {{ dashboard.user.name }}!
        </h1>
        <p class="text-gray-600 mt-1">Here's what's happening with your gym bookings</p>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
          <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
              <i class="fas fa-clipboard-list text-blue-600 text-2xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Bookings</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ dashboard.stats.totalBookings }}</h3>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
          <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
              <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Confirmed</p>
              <h3 class="text-2xl font-bold text-gray-800">{{ dashboard.stats.confirmedBookings }}</h3>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
          <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
              <i class="fas fa-money-bill-wave text-purple-600 text-2xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Spent</p>
              <h3 class="text-2xl font-bold text-gray-800">₱{{ formatMoney(dashboard.stats.totalSpent) }}</h3>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
          <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
              <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Pending Payment</p>
              <h3 class="text-2xl font-bold text-gray-800">₱{{ formatMoney(dashboard.stats.pendingPayments) }}</h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <router-link
          to="/user/schedule"
          class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold mb-2">Book a Slot</h3>
              <p class="text-blue-100 text-sm">Schedule your gym session</p>
            </div>
            <i class="fas fa-calendar-plus text-4xl opacity-50"></i>
          </div>
        </router-link>

        <router-link
          to="/user/bookings"
          class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white hover:from-green-600 hover:to-green-700 transition transform hover:scale-105"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold mb-2">My Bookings</h3>
              <p class="text-green-100 text-sm">View your reservations</p>
            </div>
            <i class="fas fa-list text-4xl opacity-50"></i>
          </div>
        </router-link>

        <router-link
          to="/user/payments"
          class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white hover:from-purple-600 hover:to-purple-700 transition transform hover:scale-105"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold mb-2">Payments</h3>
              <p class="text-purple-100 text-sm">Manage your payments</p>
            </div>
            <i class="fas fa-credit-card text-4xl opacity-50"></i>
          </div>
        </router-link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upcoming Bookings -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Upcoming Bookings
              </h3>
              <router-link to="/user/bookings" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                View All →
              </router-link>
            </div>

            <template v-if="dashboard.upcomingBookings && dashboard.upcomingBookings.length">
              <div
                v-for="(booking, index) in dashboard.upcomingBookings"
                :key="booking.id || index"
                class="border border-gray-200 rounded-lg p-4 mb-3 hover:border-blue-500 hover:shadow-md transition"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <span class="text-sm font-bold text-gray-800">
                        {{ booking.booking_number || `BK-${index + 1}` }}
                      </span>

                      <span
                        v-if="booking.status === 'confirmed'"
                        class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
                      >
                        <i class="fas fa-check-circle mr-1"></i>Confirmed
                      </span>
                      <span
                        v-else
                        class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800"
                      >
                        <i class="fas fa-clock mr-1"></i>Pending
                      </span>
                    </div>

                    <div class="space-y-1">
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-calendar text-blue-500 mr-2"></i>
                        {{ formatDate(booking.schedule_date || booking.date) }}
                      </p>
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-clock text-blue-500 mr-2"></i>
                        {{ booking.time_slot || booking.schedule_time || booking.time || 'No time available' }}
                      </p>
                      <p class="text-sm text-gray-600">
                        <i class="fas fa-users text-blue-500 mr-2"></i>
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
                      class="mt-2 inline-block px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition"
                    >
                      Pay Now
                    </router-link>
                  </div>
                </div>
              </div>
            </template>

            <div v-else class="text-center py-8 text-gray-500">
              <i class="fas fa-calendar-times text-5xl mb-3 text-gray-300"></i>
              <p class="font-semibold">No upcoming bookings</p>
              <p class="text-sm mt-1">Book a slot to get started!</p>
              <router-link
                to="/user/schedule"
                class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                <i class="fas fa-plus mr-2"></i>Book Now
              </router-link>
            </div>
          </div>

          <!-- Recent Payments -->
          <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-receipt text-green-500 mr-2"></i>Recent Payments
              </h3>
              <router-link to="/user/payments" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                View All →
              </router-link>
            </div>

            <div class="space-y-3">
              <template v-if="dashboard.recentPayments && dashboard.recentPayments.length">
                <div
                  v-for="(payment, index) in dashboard.recentPayments"
                  :key="payment.id || index"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
                >
                  <div class="flex items-center space-x-3">
                    <div
                      class="w-10 h-10 rounded-full flex items-center justify-center"
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
                      class="text-xs px-2 py-1 rounded-full capitalize"
                      :class="getPaymentBadgeClass(payment.status)"
                    >
                      {{ payment.status || 'unknown' }}
                    </span>
                  </div>
                </div>
              </template>

              <div v-else class="text-center py-6 text-gray-500">
                <i class="fas fa-wallet text-4xl mb-2 text-gray-300"></i>
                <p class="text-sm">No payment history yet</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-1">
          <!-- Notifications -->
          <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-bell text-yellow-500 mr-2"></i>Notifications
                <span
                  v-if="dashboard.unreadNotifications > 0"
                  class="ml-2 px-2 py-1 bg-red-500 text-white text-xs rounded-full"
                >
                  {{ dashboard.unreadNotifications }}
                </span>
              </h3>
              <router-link to="/user/notifications" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                View All →
              </router-link>
            </div>

            <div class="space-y-3">
              <template v-if="dashboard.notifications && dashboard.notifications.length">
                <div
                  v-for="(notification, index) in dashboard.notifications.slice(0, 4)"
                  :key="notification.id || index"
                  class="p-3 border-l-4 rounded"
                  :class="getNotificationClass(notification.type)"
                >
                  <p class="text-sm font-semibold text-gray-800">{{ notification.title }}</p>
                  <p class="text-xs text-gray-600 mt-1">{{ notification.message }}</p>
                  <p class="text-xs text-gray-400 mt-1">
                    {{ notification.created_at ? formatShortDate(notification.created_at) : 'Recently' }}
                  </p>
                </div>
              </template>

              <div v-else class="text-center py-6 text-gray-500">
                <i class="fas fa-bell-slash text-4xl mb-2 text-gray-300"></i>
                <p class="text-sm">No notifications</p>
              </div>
            </div>
          </div>

          <!-- Gym Location -->
          <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Gym Location
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
              class="mt-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-700"
            >
              <i class="fas fa-external-link-alt mr-1"></i>Open in Google Maps
            </a>
          </div>

          <!-- Member Profile Card -->
          <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
              <i class="fas fa-user-circle text-teal-500 mr-2"></i>Member Profile
            </h3>

            <div class="flex items-center mb-4">
              <div
                class="w-14 h-14 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 text-white flex items-center justify-center text-xl font-bold mr-4"
              >
                {{ dashboard.user.initials || 'U' }}
              </div>
              <div>
                <p class="font-semibold text-gray-800">{{ dashboard.user.name || 'User' }}</p>
                <p class="text-sm text-gray-500">{{ dashboard.user.email || 'No email' }}</p>
              </div>
            </div>

            <div class="text-sm text-gray-600 space-y-2">
              <p>
                <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                Member Since: {{ dashboard.user.memberSince || 'N/A' }}
              </p>
            </div>

            <router-link
              to="/user/profile"
              class="mt-4 inline-block w-full text-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition"
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