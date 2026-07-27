import { Outlet, NavLink } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { SUPPORTED_LOCALES } from '../i18n'

export function AppLayout() {
  const { t, i18n } = useTranslation()

  return (
    <div className="min-h-screen flex flex-col">
      <header className="flex items-center justify-between border-b px-4 py-3">
        <span className="font-semibold">{t('app.name')}</span>
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
      </header>
      <div className="flex flex-1">
        <nav className="w-56 border-e p-4 flex flex-col gap-2 text-sm">
          <NavLink to="/">{t('nav.dashboard')}</NavLink>
          <NavLink to="/boutiques">{t('nav.boutiques')}</NavLink>
          <NavLink to="/orders">{t('nav.orders')}</NavLink>
          <NavLink to="/payments">{t('nav.payments')}</NavLink>
          <NavLink to="/users">{t('nav.users')}</NavLink>
          <NavLink to="/roles">{t('nav.roles')}</NavLink>
          <NavLink to="/reports">{t('nav.reports')}</NavLink>
        </nav>
        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
