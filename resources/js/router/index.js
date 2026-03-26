import { createRouter, createWebHistory } from 'vue-router'

import AuthLayout from '../layouts/AuthLayout.vue'
import UserLayout from '../layouts/UserLayout.vue'
import AdminLayout from '../layouts/AdminLayout.vue'

import UserLogin from '../auth/UserLogin.vue'
import UserRegister from '../auth/UserRegister.vue'
import AdminLogin from '../auth/AdminLogin.vue'

import UserDashboard from '../pages/user/UserDashboard.vue'
import UserSchedule from '../pages/user/UserSchedule.vue'
import UserBookings from '../pages/user/UserBookings.vue'
import UserPayments from '../pages/user/UserPayments.vue'
import UserNotifications from '../pages/user/UserNotifications.vue'
import UserProfile from '../pages/user/UserProfile.vue'

import AdminDashboard from '../pages/admin/AdminDashboard.vue'
import AdminUsers from '../pages/admin/AdminUsers.vue'
import AdminSchedules from '../pages/admin/AdminSchedules.vue'
import AdminBookings from '../pages/admin/AdminBookings.vue'
import AdminPayments from '../pages/admin/AdminPayments.vue'
import AdminRefunds from '../pages/admin/AdminRefunds.vue'
import AdminReports from '../pages/admin/AdminReports.vue'
import AdminProfile from '../pages/admin/AdminProfile.vue'


const NotFoundPage = {
  template: `
    <div class="flex min-h-[60vh] items-center justify-center">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-800">404</h1>
        <p class="mt-2 text-gray-600">Page not found.</p>
      </div>
    </div>
  `,
}

const routes = [
  {
    path: '/user',
    component: AuthLayout,
    children: [
      { path: 'login', name: 'user.login', component: UserLogin },
      { path: 'register', name: 'user.register', component: UserRegister },
    ],
  },
  {
    path: '/admin',
    component: AuthLayout,
    children: [
      { path: 'login', name: 'admin.login', component: AdminLogin },
    ],
  },
  {
    path: '/user',
    component: UserLayout,
    children: [
      { path: 'dashboard', name: 'user.dashboard', component: UserDashboard },
      { path: 'schedule', name: 'user.schedule', component: UserSchedule },
      { path: 'bookings', name: 'user.bookings', component: UserBookings },
      { path: 'payments', name: 'user.payments', component: UserPayments },
      { path: 'notifications', name: 'user.notifications', component: UserNotifications },
      { path: 'profile', name: 'user.profile', component: UserProfile },
    ],
  },
  {
    path: '/admin',
    component: AdminLayout,
    children: [
      { path: 'dashboard', name: 'admin.dashboard', component: AdminDashboard },
      { path: 'users', name: 'admin.users', component: AdminUsers },
      { path: 'schedules', name: 'admin.schedules', component: AdminSchedules },
      { path: 'bookings', name: 'admin.bookings', component: AdminBookings },
      { path: 'payments', name: 'admin.payments', component: AdminPayments },
      { path: 'refunds', name: 'admin.refunds', component: AdminRefunds },
      { path: 'reports', name: 'admin.reports', component: AdminReports },
      { path: 'profile', name: 'admin.profile', component: AdminProfile },
    ],
  },
  {
    path: '/',
    redirect: '/user/login',
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundPage,
  },
  
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router