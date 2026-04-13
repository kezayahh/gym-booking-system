import axios from 'axios'

const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

const adminApi = axios.create({
  baseURL: '/api/admin',
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
  },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

adminApi.interceptors.response.use(
  response => response,
  error => Promise.reject(error)
)

export default adminApi