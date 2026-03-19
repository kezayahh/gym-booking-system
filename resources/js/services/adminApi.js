import axios from 'axios'

const adminApi = axios.create({
  baseURL: '/admin-api',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  },
  withCredentials: true,
})

export default adminApi