import { Outlet, NavLink } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { SUPPORTED_LOCALES } from '../i18n'
import { ROLES, useAuth } from '../lib/auth'

const navLinkClass = ({ isActive }: { isActive: boolean }) =>
  `rounded px-3 py-2 ${isActive ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'}`

export function AppLayout() {
  const { t, i18n } = useTranslation()
  const { user, hasRole, logout } = useAuth()
  const isAdministrateur = hasRole(ROLES.ADMINISTRATEUR)

  return (
    <div className="min-h-screen flex flex-col">
      <header className="flex items-center justify-between border-b px-4 py-3">
        <span className="font-semibold">{t('app.name')}</span>
        <div className="flex items-center gap-3">
          <select
            aria-label="language"
            value={i18n.resolvedLanguage}
            onChange={(e) => i18n.changeLanguage(e.target.value)}
            className="border rounded px-2 py-1 text-sm"
          >
            {SUPPORTED_LOCALES.map((locale) => (
              <option key={locale} value={locale}>
                {locale === 'fr' ? 'Français' : 'العربية'}
              </option>
            ))}
          </select>
          {user && (
            <span className="text-sm text-gray-500">
              {user.name} · {user.roles.join(', ')}
            </span>
          )}
          <button onClick={logout} className="text-sm text-gray-500 hover:text-gray-900 underline">
            {t('auth.logout')}
          </button>
        </div>
      </header>
      <div className="flex flex-1">
        <nav className="w-56 border-e p-4 flex flex-col gap-1 text-sm">
          <NavLink to="/" end className={navLinkClass}>
            {t('nav.dashboard')}
          </NavLink>
          {isAdministrateur && (
            <NavLink to="/boutiques" className={navLinkClass}>
              {t('nav.boutiques')}
            </NavLink>
          )}
          <NavLink to="/orders" className={navLinkClass}>
            {t('nav.orders')}
          </NavLink>
          <NavLink to="/custom-orders" className={navLinkClass}>
            {t('nav.customOrders')}
          </NavLink>
          <NavLink to="/payments" className={navLinkClass}>
            {t('nav.payments')}
          </NavLink>
          {isAdministrateur && (
            <>
              <NavLink to="/users" className={navLinkClass}>
                {t('nav.users')}
              </NavLink>
              <NavLink to="/roles" className={navLinkClass}>
                {t('nav.roles')}
              </NavLink>
              <NavLink to="/reports" className={navLinkClass}>
                {t('nav.reports')}
              </NavLink>
            </>
          )}
        </nav>
        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
