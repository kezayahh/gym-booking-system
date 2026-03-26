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

const formatCurrency = (value) => {
  return `₱${formatNumber(value)}`
}

const formatStatus = (status) => {
  if (!status) return ''
  return status
    .replace(/_/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase())
}

const getBookingStatusClass = (status) => {
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800'
  if (status === 'confirmed') return 'bg-green-100 text-green-800'
  if (status === 'completed') return 'bg-blue-100 text-blue-800'
  if (status === 'cancelled') return 'bg-red-100 text-red-800'
  if (status === 'no_show') return 'bg-gray-100 text-gray-800'
  return 'bg-gray-100 text-gray-800'
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

const renderMonthlyRevenueChart = () => {
  if (!monthlyRevenueChartRef.value) return

  const revenueData = Array.isArray(dashboard.value.monthlyRevenue)
    ? [...dashboard.value.monthlyRevenue]
    : []

  const sortedRevenueData = revenueData.sort((a, b) => {
    const dateA = new Date(a.month || a.month_label || '')
    const dateB = new Date(b.month || b.month_label || '')
    return dateA - dateB
  })

  const revenueLabels = sortedRevenueData.map(item => item.month_label || item.month || 'Unknown')
  const revenueValues = sortedRevenueData.map(item => Number(item.total || 0))

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
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              return '₱' + Number(context.parsed.y || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
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
              return '₱' + Number(value).toLocaleString('en-US')
            },
          },
        },
      },
    },
  })
}

const renderBookingStatusChart = () => {
  if (!bookingStatusChartRef.value) return

  const bookingStats = dashboard.value.bookingStats || {}

  const statusOrder = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show']
  const statusLabels = statusOrder.map(status => formatStatus(status))
  const statusValues = statusOrder.map(status => Number(bookingStats[status] || 0))

  bookingStatusChartInstance = new Chart(bookingStatusChartRef.value, {
    type: 'doughnut',
    data: {
      labels: statusLabels,
      datasets: [
        {
          data: statusValues,
          backgroundColor: [
            'rgb(234, 179, 8)',
            'rgb(34, 197, 94)',
            'rgb(59, 130, 246)',
            'rgb(239, 68, 68)',
            'rgb(156, 163, 175)',
          ],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
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

const renderCharts = async () => {
  destroyCharts()
  await nextTick()
  renderMonthlyRevenueChart()
  renderBookingStatusChart()
}

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await adminApi.get('/dashboard')

    dashboard.value = {
      admin: {
        name: response.data?.admin?.name || 'Admin',
        email: response.data?.admin?.email || '',
      },
      overview: {
        totalUsers: Number(response.data?.overview?.totalUsers || 0),
        totalBookings: Number(response.data?.overview?.totalBookings || 0),
        totalRevenue: Number(response.data?.overview?.totalRevenue || 0),
        pendingRefunds: Number(response.data?.overview?.pendingRefunds || 0),
        todayBookings: Number(response.data?.overview?.todayBookings || 0),
        todayRevenue: Number(response.data?.overview?.todayRevenue || 0),
      },
      monthlyRevenue: Array.isArray(response.data?.monthlyRevenue) ? response.data.monthlyRevenue : [],
      bookingStats: {
        pending: Number(response.data?.bookingStats?.pending || 0),
        confirmed: Number(response.data?.bookingStats?.confirmed || 0),
        completed: Number(response.data?.bookingStats?.completed || 0),
        cancelled: Number(response.data?.bookingStats?.cancelled || 0),
        no_show: Number(response.data?.bookingStats?.no_show || 0),
      },
      recentBookings: Array.isArray(response.data?.recentBookings) ? response.data.recentBookings : [],
      recentActivities: Array.isArray(response.data?.recentActivities) ? response.data.recentActivities : [],
      upcomingSchedules: Array.isArray(response.data?.upcomingSchedules) ? response.data.upcomingSchedules : [],
    }

    await renderCharts()
  } catch (err) {
    console.error('Dashboard load failed:', err)
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

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-tachometer-alt mr-2 text-blue-500"></i>Dashboard
      </h1>
      <p class="mt-1 text-gray-600">
        Welcome back, {{ dashboard.admin.name }}! Here's what's happening today.
      </p>
    </div>

    <div v-if="loading" class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
      <i class="fas fa-spinner fa-spin mb-3 text-2xl"></i>
      <p>Loading dashboard...</p>
    </div>

    <div v-else>
      <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="transform rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-lg transition hover:scale-105">
          <div class="flex items-center justify-between">
            <div>
              <p class="mb-1 text-sm font-semibold text-blue-100">Total Users</p>
              <h3 class="text-4xl font-bold">{{ dashboard.overview.totalUsers }}</h3>
              <p class="mt-2 text-xs text-blue-100">
                <i class="fas fa-users mr-1"></i>Registered members
              </p>
            </div>
            <div class="rounded-full bg-white/20 p-4">
              <i class="fas fa-users text-4xl"></i>
            </div>
          </div>
        </div>

        <div class="transform rounded-lg bg-gradient-to-br from-green-500 to-green-600 p-6 text-white shadow-lg transition hover:scale-105">
          <div class="flex items-center justify-between">
            <div>
              <p class="mb-1 text-sm font-semibold text-green-100">Total Bookings</p>
              <h3 class="text-4xl font-bold">{{ dashboard.overview.totalBookings }}</h3>
              <p class="mt-2 text-xs text-green-100">
                <i class="fas fa-calendar-plus mr-1"></i>{{ dashboard.overview.todayBookings }} today
              </p>
            </div>
            <div class="rounded-full bg-white/20 p-4">
              <i class="fas fa-calendar-check text-4xl"></i>
            </div>
          </div>
        </div>

        <div class="transform rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-white shadow-lg transition hover:scale-105">
          <div class="flex items-center justify-between">
            <div>
              <p class="mb-1 text-sm font-semibold text-purple-100">Total Revenue</p>
              <h3 class="text-4xl font-bold">{{ formatCurrency(dashboard.overview.totalRevenue) }}</h3>
              <p class="mt-2 text-xs text-purple-100">
                <i class="fas fa-money-bill-wave mr-1"></i>{{ formatCurrency(dashboard.overview.todayRevenue) }} today
              </p>
            </div>
            <div class="rounded-full bg-white/20 p-4">
              <i class="fas fa-dollar-sign text-4xl"></i>
            </div>
          </div>
        </div>

        <div class="transform rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 p-6 text-white shadow-lg transition hover:scale-105">
          <div class="flex items-center justify-between">
            <div>
              <p class="mb-1 text-sm font-semibold text-yellow-100">Pending Refunds</p>
              <h3 class="text-4xl font-bold">{{ dashboard.overview.pendingRefunds }}</h3>
              <p class="mt-2 text-xs text-yellow-100">
                <i class="fas fa-exclamation-circle mr-1"></i>Needs attention
              </p>
            </div>
            <div class="rounded-full bg-white/20 p-4">
              <i class="fas fa-undo text-4xl"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-6 shadow-lg">
          <h3 class="mb-4 text-xl font-bold text-gray-800">
            <i class="fas fa-chart-line mr-2 text-blue-500"></i>Monthly Revenue (Last 6 Months)
          </h3>
          <div class="relative h-[300px]">
            <canvas ref="monthlyRevenueChartRef"></canvas>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-lg">
          <h3 class="mb-4 text-xl font-bold text-gray-800">
            <i class="fas fa-chart-pie mr-2 text-green-500"></i>Booking Status Distribution
          </h3>
          <div class="relative h-[300px]">
            <canvas ref="bookingStatusChartRef"></canvas>
          </div>
        </div>
      </div>

      <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-6 shadow-lg">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">
              <i class="fas fa-calendar-alt mr-2 text-purple-500"></i>Upcoming Schedules
            </h3>
          </div>

          <div class="space-y-3">
            <div
              v-for="schedule in dashboard.upcomingSchedules"
              :key="schedule.id"
              class="flex items-center justify-between rounded-lg bg-gray-50 p-3 transition hover:bg-gray-100"
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
                  {{ formatCurrency(schedule.price_per_slot) }}
                </span>
              </div>
            </div>

            <p
              v-if="dashboard.upcomingSchedules.length === 0"
              class="py-4 text-center text-gray-500"
            >
              No upcoming schedules
            </p>
          </div>
        </div>

        <div class="col-span-2 rounded-lg bg-white p-6 shadow-lg">
          <h3 class="mb-4 text-lg font-bold text-gray-800">
            <i class="fas fa-chart-bar mr-2 text-blue-500"></i>Booking Statistics
          </h3>

          <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-yellow-50 p-4 text-center">
              <div class="text-3xl font-bold text-yellow-600">
                {{ dashboard.bookingStats.pending }}
              </div>
              <div class="mt-1 text-sm text-gray-600">Pending</div>
            </div>

            <div class="rounded-lg bg-green-50 p-4 text-center">
              <div class="text-3xl font-bold text-green-600">
                {{ dashboard.bookingStats.confirmed }}
              </div>
              <div class="mt-1 text-sm text-gray-600">Confirmed</div>
            </div>

            <div class="rounded-lg bg-blue-50 p-4 text-center">
              <div class="text-3xl font-bold text-blue-600">
                {{ dashboard.bookingStats.completed }}
              </div>
              <div class="mt-1 text-sm text-gray-600">Completed</div>
            </div>

            <div class="rounded-lg bg-red-50 p-4 text-center">
              <div class="text-3xl font-bold text-red-600">
                {{ dashboard.bookingStats.cancelled }}
              </div>
              <div class="mt-1 text-sm text-gray-600">Cancelled</div>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-lg bg-white shadow-lg">
          <div class="flex items-center justify-between border-b border-gray-200 p-6">
            <h3 class="text-xl font-bold text-gray-800">
              <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>Recent Bookings
            </h3>

            <router-link
              to="/admin/bookings"
              class="text-sm font-semibold text-blue-600 hover:text-blue-700"
            >
              View All →
            </router-link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Booking #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="booking in dashboard.recentBookings"
                  :key="booking.id"
                  class="hover:bg-gray-50"
                >
                  <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                    {{ booking.booking_number }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                    {{ booking.user_name }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                    {{ booking.schedule_date }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4">
                    <span
                      class="rounded-full px-2 py-1 text-xs font-semibold"
                      :class="getBookingStatusClass(booking.status)"
                    >
                      {{ formatStatus(booking.status) }}
                    </span>
                  </td>
                </tr>

                <tr v-if="dashboard.recentBookings.length === 0">
                  <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox mb-2 text-4xl text-gray-300"></i>
                    <p>No recent bookings</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-lg">
          <div class="border-b border-gray-200 p-6">
            <h3 class="text-xl font-bold text-gray-800">
              <i class="fas fa-history mr-2 text-green-500"></i>Recent Activity
            </h3>
          </div>

          <div class="max-h-96 overflow-y-auto">
            <div class="divide-y divide-gray-200">
              <div
                v-for="activity in dashboard.recentActivities"
                :key="activity.id"
                class="p-4 transition hover:bg-gray-50"
              >
                <div class="flex items-start">
                  <div class="flex-shrink-0">
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-full"
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
                    <p class="mt-1 text-xs text-gray-600">
                      {{ activity.description }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                      <i class="far fa-clock mr-1"></i>{{ activity.time }}
                    </p>
                  </div>
                </div>
              </div>

              <div
                v-if="dashboard.recentActivities.length === 0"
                class="p-12 text-center text-gray-500"
              >
                <i class="fas fa-inbox mb-2 text-4xl text-gray-300"></i>
                <p>No recent activity</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="error"
        class="mt-4 rounded border border-red-300 bg-red-100 px-4 py-3 text-red-700"
      >
        {{ error }}
      </div>
    </div>
  </div>
</template>