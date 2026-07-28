import axios from 'axios'
import i18n from '../i18n'

export const TOKEN_KEY = 'khidmapp_admin_token'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1',
})

api.interceptors.request.use((config) => {
  config.headers['Accept-Language'] = i18n.resolvedLanguage ?? 'fr'

  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

// 401 = jeton absent/expiré/invalide : la session locale n'est plus valable.
// 403 (compte bloqué, ou permission refusée sur une route précise) n'est
// volontairement PAS traité ici — ni un blocage de compte ni un refus de
// permission ne doivent déconnecter silencieusement l'admin ; chaque page
// affiche son propre message d'erreur pour ce cas.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && window.location.pathname !== '/login') {
      localStorage.removeItem(TOKEN_KEY)
      window.location.assign('/login')
    }

    return Promise.reject(error)
  },
)
