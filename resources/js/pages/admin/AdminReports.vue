<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import Chart from 'chart.js/auto'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import * as XLSX from 'xlsx'
import adminApi from '../../services/adminApi'

const filters = reactive({
  start: getDefaultStartDate(),
  end: getToday(),
})

const stats = reactive({
  totalBookings: 0,
  totalRevenue: 0,
  totalUsers: 0,
  totalRefunds: 0,
})

const revenueChartData = ref({ labels: [], values: [] })
const bookingStatusData = ref({ labels: [], values: [] })
const paymentMethodsData = ref({ labels: [], values: [] })
const monthlyComparisonData = ref({ labels: [], values: [] })
const topUsers = ref([])
const recentActivities = ref([])

const revenueChartRef = ref(null)
const bookingStatusChartRef = ref(null)
const paymentMethodsChartRef = ref(null)
const monthlyComparisonChartRef = ref(null)

let revenueChartInstance = null
let bookingStatusChartInstance = null
let paymentMethodsChartInstance = null
let monthlyComparisonChartInstance = null

const reportSummaryRows = computed(() => [
  ['Total Bookings', String(stats.totalBookings ?? 0)],
  ['Total Revenue', `₱${formatMoney(stats.totalRevenue ?? 0)}`],
  ['Total Users', String(stats.totalUsers ?? 0)],
  ['Total Refunds', `₱${formatMoney(stats.totalRefunds ?? 0)}`],
])

function getToday() {
  return new Date().toISOString().split('T')[0]
}

function getDefaultStartDate() {
  const date = new Date()
  date.setDate(date.getDate() - 30)
  return date.toISOString().split('T')[0]
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function formatDateTime(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleString('en-PH', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

function capitalize(value) {
  if (!value) return ''
  return value.charAt(0).toUpperCase() + value.slice(1)
}

function getRankDisplay(index) {
  if (index === 0) return '🥇'
  if (index === 1) return '🥈'
  if (index === 2) return '🥉'
  return String(index + 1)
}

function activityBadgeClass(action) {
  if (action === 'created') return 'bg-green-100 text-green-800'
  if (action === 'updated') return 'bg-blue-100 text-blue-800'
  if (action === 'deleted') return 'bg-red-100 text-red-800'
  return 'bg-gray-100 text-gray-800'
}

async function fetchReports() {
  try {
    const { data } = await adminApi.get('/reports', {
      params: {
        start: filters.start,
        end: filters.end,
      },
    })

    filters.start = data.startDate || filters.start
    filters.end = data.endDate || filters.end

    stats.totalBookings = Number(data.stats?.totalBookings || 0)
    stats.totalRevenue = Number(data.stats?.totalRevenue || 0)
    stats.totalUsers = Number(data.stats?.totalUsers || 0)
    stats.totalRefunds = Number(data.stats?.totalRefunds || 0)

    revenueChartData.value = data.revenueChartData || { labels: [], values: [] }
    bookingStatusData.value = data.bookingStatusData || { labels: [], values: [] }
    paymentMethodsData.value = data.paymentMethodsData || { labels: [], values: [] }
    monthlyComparisonData.value = data.monthlyComparisonData || { labels: [], values: [] }

    topUsers.value = Array.isArray(data.topUsers) ? data.topUsers : []
    recentActivities.value = Array.isArray(data.recentActivities) ? data.recentActivities : []

    await nextTick()
    renderCharts()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Failed to load reports.')
  }
}

function applyDateFilter() {
  fetchReports()
}

function exportReport(type) {
  if (type === 'pdf') exportPDF()
  if (type === 'excel') exportExcel()
}

function exportPDF() {
  const doc = new jsPDF()

  doc.text('City Gymnasium - Reports', 14, 15)
  doc.setFontSize(10)
  doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 22)
  doc.text(`Date Range: ${filters.start} to ${filters.end}`, 14, 28)

  autoTable(doc, {
    startY: 34,
    head: [['Metric', 'Value']],
    body: reportSummaryRows.value,
  })

  autoTable(doc, {
    startY: doc.lastAutoTable.finalY + 10,
    head: [['Rank', 'User', 'Bookings', 'Spent', 'Status']],
    body: topUsers.value.map((user, index) => [
      getRankDisplay(index),
      user.name,
      user.bookings_count,
      `₱${formatMoney(user.total_spent)}`,
      capitalize(user.status),
    ]),
  })

  doc.save('gym-report.pdf')
}

function exportExcel() {
  const data = [
    ['City Gymnasium - Reports'],
    ['Generated:', new Date().toLocaleDateString()],
    ['Date Range:', `${filters.start} to ${filters.end}`],
    [],
    ['Metric', 'Value'],
    ...reportSummaryRows.value,
    [],
    ['Top Users'],
    ['Rank', 'User', 'Bookings', 'Spent', 'Status'],
    ...topUsers.value.map((user, index) => [
      getRankDisplay(index),
      user.name,
      user.bookings_count,
      Number(user.total_spent || 0),
      user.status,
    ]),
  ]

  const ws = XLSX.utils.aoa_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Report')
  XLSX.writeFile(wb, 'gym-report.xlsx')
}

function exportCsv() {
  const params = new URLSearchParams()
  if (filters.start) params.set('start', filters.start)
  if (filters.end) params.set('end', filters.end)

  const query = params.toString()
  window.location.href = query
    ? `/api/admin/reports/export-csv?${query}`
    : '/api/admin/reports/export-csv'
}

function destroyCharts() {
  if (revenueChartInstance) {
    revenueChartInstance.destroy()
    revenueChartInstance = null
  }
  if (bookingStatusChartInstance) {
    bookingStatusChartInstance.destroy()
    bookingStatusChartInstance = null
  }
  if (paymentMethodsChartInstance) {
    paymentMethodsChartInstance.destroy()
    paymentMethodsChartInstance = null
  }
  if (monthlyComparisonChartInstance) {
    monthlyComparisonChartInstance.destroy()
    monthlyComparisonChartInstance = null
  }
}

function renderCharts() {
  destroyCharts()

  if (revenueChartRef.value) {
    revenueChartInstance = new Chart(revenueChartRef.value, {
      type: 'line',
      data: {
        labels: revenueChartData.value.labels?.length ? revenueChartData.value.labels : ['No Data'],
        datasets: [
          {
            label: 'Revenue (₱)',
            data: revenueChartData.value.values?.length ? revenueChartData.value.values : [0],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true },
          tooltip: {
            callbacks: {
              label(context) {
                return '₱' + Number(context.parsed.y || 0).toLocaleString()
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback(value) {
                return '₱' + Number(value).toLocaleString()
              },
            },
          },
        },
      },
    })
  }

  if (bookingStatusChartRef.value) {
    bookingStatusChartInstance = new Chart(bookingStatusChartRef.value, {
      type: 'doughnut',
      data: {
        labels: bookingStatusData.value.labels?.length ? bookingStatusData.value.labels : ['No Data'],
        datasets: [
          {
            data: bookingStatusData.value.values?.length ? bookingStatusData.value.values : [1],
            backgroundColor: [
              'rgb(234, 179, 8)',
              'rgb(34, 197, 94)',
              'rgb(59, 130, 246)',
              'rgb(239, 68, 68)',
              'rgb(156, 163, 175)',
            ],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
        },
      },
    })
  }

  if (paymentMethodsChartRef.value) {
    paymentMethodsChartInstance = new Chart(paymentMethodsChartRef.value, {
      type: 'bar',
      data: {
        labels: paymentMethodsData.value.labels?.length ? paymentMethodsData.value.labels : ['No Data'],
        datasets: [
          {
            label: 'Payments',
            data: paymentMethodsData.value.values?.length ? paymentMethodsData.value.values : [0],
            backgroundColor: [
              'rgb(34, 197, 94)',
              'rgb(59, 130, 246)',
              'rgb(168, 85, 247)',
              'rgb(234, 179, 8)',
              'rgb(239, 68, 68)',
            ],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          y: { beginAtZero: true },
        },
      },
    })
  }

  if (monthlyComparisonChartRef.value) {
    monthlyComparisonChartInstance = new Chart(monthlyComparisonChartRef.value, {
      type: 'bar',
      data: {
        labels: monthlyComparisonData.value.labels?.length ? monthlyComparisonData.value.labels : ['No Data'],
        datasets: [
          {
            label: 'Revenue (₱)',
            data: monthlyComparisonData.value.values?.length ? monthlyComparisonData.value.values : [0],
            backgroundColor: 'rgb(59, 130, 246)',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label(context) {
                return '₱' + Number(context.parsed.y || 0).toLocaleString()
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback(value) {
                return '₱' + Number(value).toLocaleString()
              },
            },
          },
        },
      },
    })
  }
}

onMounted(() => {
  fetchReports()
})

onBeforeUnmount(() => {
  destroyCharts()
})
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">
        <i class="mr-2 text-blue-500 fas fa-chart-line"></i>Reports & Analytics
      </h1>
      <p class="mt-1 text-gray-600">View comprehensive reports and analytics</p>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow-lg">
      <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-[200px] flex-1">
          <label class="mb-2 block text-sm font-semibold text-gray-700">
            <i class="mr-1 fas fa-calendar text-gray-400"></i>Start Date
          </label>
          <input
            v-model="filters.start"
            type="date"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="min-w-[200px] flex-1">
          <label class="mb-2 block text-sm font-semibold text-gray-700">
            <i class="mr-1 fas fa-calendar text-gray-400"></i>End Date
          </label>
          <input
            v-model="filters.end"
            type="date"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <button
          @click="applyDateFilter"
          class="rounded-lg bg-blue-600 px-6 py-2 font-semibold text-white transition hover:bg-blue-700"
        >
          <i class="mr-2 fas fa-filter"></i>Apply Filter
        </button>

        <button
          @click="exportReport('pdf')"
          class="rounded-lg bg-red-600 px-6 py-2 font-semibold text-white transition hover:bg-red-700"
        >
          <i class="mr-2 fas fa-file-pdf"></i>Export PDF
        </button>

        <button
          @click="exportReport('excel')"
          class="rounded-lg bg-green-600 px-6 py-2 font-semibold text-white transition hover:bg-green-700"
        >
          <i class="mr-2 fas fa-file-excel"></i>Export Excel
        </button>

        <button
          @click="exportCsv"
          class="rounded-lg bg-gray-700 px-6 py-2 font-semibold text-white transition hover:bg-gray-800"
        >
          <i class="mr-2 fas fa-file-csv"></i>Export CSV
        </button>
      </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-lg bg-white p-6 shadow-lg">
        <div class="flex items-center">
          <div class="mr-4 rounded-full bg-blue-100 p-3">
            <i class="fas fa-calendar-check text-2xl text-blue-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Bookings</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.totalBookings }}</h3>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-lg">
        <div class="flex items-center">
          <div class="mr-4 rounded-full bg-green-100 p-3">
            <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Revenue</p>
            <h3 class="text-2xl font-bold text-gray-800">₱{{ formatMoney(stats.totalRevenue) }}</h3>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-lg">
        <div class="flex items-center">
          <div class="mr-4 rounded-full bg-purple-100 p-3">
            <i class="fas fa-users text-2xl text-purple-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Users</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.totalUsers }}</h3>
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-lg">
        <div class="flex items-center">
          <div class="mr-4 rounded-full bg-yellow-100 p-3">
            <i class="fas fa-undo text-2xl text-yellow-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Refunds</p>
            <h3 class="text-2xl font-bold text-gray-800">₱{{ formatMoney(stats.totalRefunds) }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="rounded-lg bg-white p-6 shadow-lg">
        <h3 class="mb-4 text-xl font-bold text-gray-800">
          <i class="mr-2 fas fa-chart-line text-blue-500"></i>Revenue Trend
        </h3>
        <div class="relative h-64">
          <canvas ref="revenueChartRef"></canvas>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-lg">
        <h3 class="mb-4 text-xl font-bold text-gray-800">
          <i class="mr-2 fas fa-chart-pie text-green-500"></i>Booking Status Distribution
        </h3>
        <div class="relative h-64">
          <canvas ref="bookingStatusChartRef"></canvas>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-lg">
        <h3 class="mb-4 text-xl font-bold text-gray-800">
          <i class="mr-2 fas fa-credit-card text-purple-500"></i>Payment Methods
        </h3>
        <div class="relative h-64">
          <canvas ref="paymentMethodsChartRef"></canvas>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-lg">
        <h3 class="mb-4 text-xl font-bold text-gray-800">
          <i class="mr-2 fas fa-calendar text-orange-500"></i>Monthly Revenue Comparison
        </h3>
        <div class="relative h-64">
          <canvas ref="monthlyComparisonChartRef"></canvas>
        </div>
      </div>
    </div>

    <div class="mb-8 overflow-hidden rounded-lg bg-white shadow-lg">
      <div class="border-b border-gray-200 p-6">
        <h3 class="text-xl font-bold text-gray-800">
          <i class="mr-2 fas fa-trophy text-yellow-500"></i>Top Users by Bookings
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rank</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Bookings</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Spent</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="topUsers.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                <i class="mb-4 text-6xl text-gray-300 fas fa-inbox"></i>
                <p>No data available for the selected period</p>
              </td>
            </tr>

            <tr v-for="(user, index) in topUsers" :key="user.id || index" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4">
                <span class="text-2xl">
                  {{ getRankDisplay(index) }}
                </span>
              </td>

              <td class="px-6 py-4">
                <div class="flex items-center">
                  <img
                    :src="user.profile_picture_url"
                    :alt="user.name"
                    class="mr-3 h-10 w-10 rounded-full object-cover"
                  />
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-xs text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span class="text-sm font-bold text-gray-900">{{ user.bookings_count }}</span>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span class="text-sm font-bold text-green-600">₱{{ formatMoney(user.total_spent) }}</span>
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold leading-5"
                  :class="user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                >
                  {{ capitalize(user.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-lg">
      <div class="border-b border-gray-200 p-6">
        <h3 class="text-xl font-bold text-gray-800">
          <i class="mr-2 fas fa-history text-blue-500"></i>Recent Activity
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date & Time</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Activity</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="recentActivities.length === 0">
              <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                <i class="mb-4 text-6xl text-gray-300 fas fa-inbox"></i>
                <p>No recent activity</p>
              </td>
            </tr>

            <tr v-for="(activity, index) in recentActivities" :key="activity.id || index" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ formatDateTime(activity.created_at) }}
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ activity.user?.name || 'System' }}
              </td>

              <td class="whitespace-nowrap px-6 py-4">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold leading-5"
                  :class="activityBadgeClass(activity.action)"
                >
                  {{ capitalize(activity.action) }}
                </span>
              </td>

              <td class="px-6 py-4 text-sm text-gray-900">
                {{ activity.description }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>