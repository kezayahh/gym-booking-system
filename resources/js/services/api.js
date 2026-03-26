import axios from 'axios'

const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

const api = axios.create({
  baseURL: '/',
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
  },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

export default api