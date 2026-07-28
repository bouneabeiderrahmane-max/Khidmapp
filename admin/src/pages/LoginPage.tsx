import { useState, type FormEvent } from 'react'
import { Navigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useAuth } from '../lib/auth'

export function LoginPage() {
  const { t } = useTranslation()
  const { login, loginError, isLoggingIn, isAuthenticated } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  if (isAuthenticated) {
    return <Navigate to="/" replace />
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    try {
      await login(email, password)
    } catch {
      // loginError est déjà renseigné par useAuth ; rien de plus à faire ici.
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-sm">
        <h1 className="text-xl font-semibold text-center mb-1">{t('app.name')}</h1>
        <p className="text-sm text-gray-500 text-center mb-6">{t('auth.login')}</p>

        <form onSubmit={handleSubmit} className="bg-white border rounded-lg p-6 flex flex-col gap-4 shadow-sm">
          <label className="flex flex-col gap-1 text-sm">
            <span className="text-gray-700">{t('auth.email')}</span>
            <input
              type="email"
              required
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
            />
          </label>

          <label className="flex flex-col gap-1 text-sm">
            <span className="text-gray-700">{t('auth.password')}</span>
            <input
              type="password"
              required
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
            />
          </label>

          {loginError && (
            <p role="alert" className="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">
              {loginError}
            </p>
          )}

          <button
            type="submit"
            disabled={isLoggingIn}
            className="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium disabled:opacity-50"
          >
            {isLoggingIn ? t('common.loading') : t('auth.login')}
          </button>
        </form>
      </div>
    </div>
  )
}
