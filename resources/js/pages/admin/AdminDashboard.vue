<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-tachometer-alt text-blue-500 mr-2"></i>Dashboard
      </h1>
      <p class="text-gray-600 mt-1">
        Welcome back, {{ dashboard.admin.name }}! Here's what's happening today.
      </p>
    </div>

    <div v-if="loading" class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
      <i class="fas fa-spinner fa-spin text-2xl mb-3"></i>
      <p>Loading dashboard...</p>
    </div>

    <div v-else>
      <!-- Overview Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-blue-100 text-sm font-semibold mb-1">Total Users</p>
              <h3 class="text-4xl font-bold">{{ dashboard.overview.totalUsers }}</h3>
              <p class="text-blue-100 text-xs mt-2">
                <i class="fas fa-users mr-1"></i>Registered members
              </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
              <i class="fas fa-users text-4xl"></i>
            </div>
          </div>
        </div>

        <!-- Total Bookings -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-green-100 text-sm font-semibold mb-1">Total Bookings</p>
              <h3 class="text-4xl font-bold">{{ dashboard.overview.totalBookings }}</h3>
              <p class="text-green-100 text-xs mt-2">
                <i class="fas fa-calendar-plus mr-1"></i>{{ dashboard.overview.todayBookings }} today
              </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
              <i class="fas fa-calendar-check text-4xl"></i>
            </div>
          </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-purple-100 text-sm font-semibold mb-1">Total Revenue</p>
              <h3 class="text-4xl font-bold">₱{{ formatNumber(dashboard.overview.totalRevenue) }}</h3>
              <p class="text-purple-100 text-xs mt-2">
                <i class="fas fa-money-bill-wave mr-1"></i>₱{{ formatNumber(dashboard.overview.todayRevenue) }} today
              </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
              <i class="fas fa-dollar-sign text-4xl"></i>
            </div>
          </div>
        </div>

        <!-- Pending Refunds -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white transform hover:scale-105 transition">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-yellow-100 text-sm font-semibold mb-1">Pending Refunds</p>
              <h3 class="text-4xl font-bold">{{ dashboard.overview.pendingRefunds }}</h3>
              <p class="text-yellow-100 text-xs mt-2">
                <i class="fas fa-exclamation-circle mr-1"></i>Needs attention
              </p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
              <i class="fas fa-undo text-4xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Revenue Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-line text-blue-500 mr-2"></i>Monthly Revenue (Last 6 Months)
          </h3>
          <div style="position: relative; height: 300px;">
            <canvas ref="monthlyRevenueChartRef"></canvas>
          </div>
        </div>

        <!-- Booking Status Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-pie text-green-500 mr-2"></i>Booking Status Distribution
          </h3>
          <div style="position: relative; height: 300px;">
            <canvas ref="bookingStatusChartRef"></canvas>
          </div>
        </div>
      </div>

      <!-- Upcoming Schedules & Booking Stats -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Upcoming Schedules -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800">
              <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>Upcoming Schedules
            </h3>
          </div>
          <div class="space-y-3">
            <div
              v-for="schedule in dashboard.upcomingSchedules"
              :key="schedule.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
            >
              <div>
                <p class="text-sm font-semibold text-gray-800">
                  {{ schedule.date }}
                </p>
                <p class="text-xs text-gray-600">{{ schedule.time_slot }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-gray-600">
                  {{ schedule.available_slots }}/{{ schedule.total_capacity }}
                </p>
                <span class="text-xs font-bold text-green-600">
                  ₱{{ formatNumber(schedule.price_per_slot) }}
                </span>
              </div>
            </div>

            <p
              v-if="dashboard.upcomingSchedules.length === 0"
              class="text-center text-gray-500 py-4"
            >
              No upcoming schedules
            </p>
          </div>
        </div>

        <!-- Booking Statistics -->
        <div class="bg-white rounded-lg shadow-lg p-6 col-span-2">
          <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>Booking Statistics
          </h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
              <div class="text-3xl font-bold text-yellow-600">
                {{ dashboard.bookingStats.pending ?? 0 }}
              </div>
              <div class="text-sm text-gray-600 mt-1">Pending</div>
            </div>

            <div class="text-center p-4 bg-green-50 rounded-lg">
              <div class="text-3xl font-bold text-green-600">
                {{ dashboard.bookingStats.confirmed ?? 0 }}
              </div>
              <div class="text-sm text-gray-600 mt-1">Confirmed</div>
            </div>

            <div class="text-center p-4 bg-blue-50 rounded-lg">
              <div class="text-3xl font-bold text-blue-600">
                {{ dashboard.bookingStats.completed ?? 0 }}
              </div>
              <div class="text-sm text-gray-600 mt-1">Completed</div>
            </div>

            <div class="text-center p-4 bg-red-50 rounded-lg">
              <div class="text-3xl font-bold text-red-600">
                {{ dashboard.bookingStats.cancelled ?? 0 }}
              </div>
              <div class="text-sm text-gray-600 mt-1">Cancelled</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Bookings & Recent Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Bookings -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
          <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">
              <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Recent Bookings
            </h3>
            <router-link
              to="/admin/bookings"
              class="text-blue-600 hover:text-blue-700 text-sm font-semibold"
            >
              View All →
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="booking in dashboard.recentBookings"
                  :key="booking.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ booking.booking_number }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ booking.user_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ booking.schedule_date }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="px-2 py-1 text-xs font-semibold rounded-full"
                      :class="getBookingStatusClass(booking.status)"
                    >
                      {{ formatStatus(booking.status) }}
                    </span>
                  </td>
                </tr>

                <tr v-if="dashboard.recentBookings.length === 0">
                  <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                    <p>No recent bookings</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800">
              <i class="fas fa-history text-green-500 mr-2"></i>Recent Activity
            </h3>
          </div>

          <div class="overflow-y-auto max-h-96">
            <div class="divide-y divide-gray-200">
              <div
                v-for="activity in dashboard.recentActivities"
                :key="activity.id"
                class="p-4 hover:bg-gray-50 transition"
              >
                <div class="flex items-start">
                  <div class="flex-shrink-0">
                    <div
                      class="w-8 h-8 rounded-full flex items-center justify-center"
                      :class="getActivityBadgeClass(activity.action)"
                    >
                      <i
                        class="text-xs"
                        :class="getActivityIcon(activity.action)"
                      ></i>
                    </div>
                  </div>

                  <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                      {{ activity.user_name }}
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                      {{ activity.description }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                      <i class="far fa-clock mr-1"></i>{{ activity.time }}
                    </p>
                  </div>
                </div>
              </div>

              <div
                v-if="dashboard.recentActivities.length === 0"
                class="p-12 text-center text-gray-500"
              >
                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                <p>No recent activity</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="error" class="mt-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Chart from 'chart.js/auto'
import adminApi from '../../services/adminApi'

const loading = ref(true)
const error = ref('')

const monthlyRevenueChartRef = ref(null)
const bookingStatusChartRef = ref(null)

let monthlyRevenueChartInstance = null
let bookingStatusChartInstance = null

const dashboard = ref({
  admin: {
    name: 'Admin',
    email: '',
  },
  overview: {
    totalUsers: 0,
    totalBookings: 0,
    totalRevenue: 0,
    pendingRefunds: 0,
    todayBookings: 0,
    todayRevenue: 0,
  },
  monthlyRevenue: [],
  bookingStats: {
    pending: 0,
    confirmed: 0,
    completed: 0,
    cancelled: 0,
    no_show: 0,
  },
  recentBookings: [],
  recentActivities: [],
  upcomingSchedules: [],
})

const formatNumber = (value) => {
  return Number(value || 0).toLocaleString('en-US')
}

const formatStatus = (status) => {
  if (!status) return ''
  return status
    .replace('_', ' ')
    .replace(/\b\w/g, char => char.toUpperCase())
}

const getBookingStatusClass = (status) => {
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800'
  if (status === 'confirmed') return 'bg-green-100 text-green-800'
  if (status === 'completed') return 'bg-blue-100 text-blue-800'
  return 'bg-red-100 text-red-800'
}

const getActivityIcon = (action) => {
  if (action === 'created') return 'fas fa-plus text-green-600'
  if (action === 'updated') return 'fas fa-edit text-blue-600'
  if (action === 'deleted') return 'fas fa-trash text-red-600'
  return 'fas fa-info text-gray-600'
}

const getActivityBadgeClass = (action) => {
  if (action === 'created') return 'bg-green-100'
  if (action === 'updated') return 'bg-blue-100'
  if (action === 'deleted') return 'bg-red-100'
  return 'bg-gray-100'
}

const destroyCharts = () => {
  if (monthlyRevenueChartInstance) {
    monthlyRevenueChartInstance.destroy()
    monthlyRevenueChartInstance = null
  }

  if (bookingStatusChartInstance) {
    bookingStatusChartInstance.destroy()
    bookingStatusChartInstance = null
  }
}

const renderCharts = async () => {
  destroyCharts()
  await nextTick()

  const sortedRevenueData = [...dashboard.value.monthlyRevenue].sort((a, b) => {
    return new Date(a.month) - new Date(b.month)
  })

  const revenueLabels = sortedRevenueData.map(item => item.month_label)
  const revenueValues = sortedRevenueData.map(item => Number(item.total))

  if (monthlyRevenueChartRef.value) {
    monthlyRevenueChartInstance = new Chart(monthlyRevenueChartRef.value, {
      type: 'line',
      data: {
        labels: revenueLabels,
        datasets: [
          {
            label: 'Revenue (₱)',
            data: revenueValues,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                return '₱' + Number(context.parsed.y || 0).toLocaleString('en-US', {
                  minimumFractionDigits: 2,
                })
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return '₱' + Number(value).toLocaleString()
              },
            },
          },
        },
      },
    })
  }

  const bookingStats = dashboard.value.bookingStats
  const statusLabels = Object.keys(bookingStats).map(status =>
    status
      .replace('_', ' ')
      .replace(/\b\w/g, char => char.toUpperCase())
  )
  const statusValues = Object.values(bookingStats)

  if (bookingStatusChartRef.value) {
    bookingStatusChartInstance = new Chart(bookingStatusChartRef.value, {
      type: 'doughnut',
      data: {
        labels: statusLabels,
        datasets: [
          {
            data: statusValues,
            backgroundColor: [
              'rgb(234, 179, 8)',   // Pending
              'rgb(34, 197, 94)',   // Confirmed
              'rgb(59, 130, 246)',  // Completed
              'rgb(239, 68, 68)',   // Cancelled
              'rgb(156, 163, 175)', // No Show
            ],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              usePointStyle: true,
            },
          },
        },
      },
    })
  }
}

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await adminApi.get('/dashboard')
    dashboard.value = response.data
    await renderCharts()
  } catch (err) {
    console.error(err)
    error.value = err?.response?.data?.message || 'Failed to load dashboard data.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboard()
})

onBeforeUnmount(() => {
  destroyCharts()
})
</script>
