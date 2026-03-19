import { createRouter, createWebHistory } from 'vue-router'

import AuthLayout from '../layouts/AuthLayout.vue'
import UserLayout from '../layouts/UserLayout.vue'

import UserLogin from '../pages/user/UserLogin.vue'
import UserRegister from '../pages/user/UserRegister.vue'
import UserDashboard from '../pages/user/UserDashboard.vue'
import UserSchedule from '../pages/user/UserSchedule.vue'
import UserBookings from '../pages/user/UserBookings.vue'
import UserPayments from '../pages/user/UserPayments.vue'
import UserNotifications from '../pages/user/UserNotifications.vue'
import UserProfile from '../pages/user/UserProfile.vue'

const routes = [
  {
    path: '/user',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        name: 'user.login',
        component: UserLogin,
      },
      {
        path: 'register',
        name: 'user.register',
        component: UserRegister,
      },
    ],
  },
  {
    path: '/user',
    component: UserLayout,
    children: [
      {
        path: 'dashboard',
        name: 'user.dashboard',
        component: UserDashboard,
      },
      {
        path: 'schedule',
        name: 'user.schedule',
        component: UserSchedule,
      },
      {
        path: 'bookings',
        name: 'user.bookings',
        component: UserBookings,
      },
      {
        path: 'payments',
        name: 'user.payments',
        component: UserPayments,
      },
      {
        path: 'notifications',
        name: 'user.notifications',
        component: UserNotifications,
      },
      {
        path: 'profile',
        name: 'user.profile',
        component: UserProfile,
      },
    ],
  },
  {
    path: '/',
    redirect: '/user/login',
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/user/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router