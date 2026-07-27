import axios from 'axios'
import i18n from '../i18n'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1',
})

api.interceptors.request.use((config) => {
  config.headers['Accept-Language'] = i18n.resolvedLanguage ?? 'fr'

  const token = localStorage.getItem('khidmapp_admin_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})
