import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import LanguageDetector from 'i18next-browser-languagedetector'
import fr from './locales/fr/common.json'
import ar from './locales/ar/common.json'

export const SUPPORTED_LOCALES = ['fr', 'ar'] as const
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number]
export const RTL_LOCALES: SupportedLocale[] = ['ar']

export function applyDocumentDirection(locale: string) {
  const isRtl = RTL_LOCALES.includes(locale as SupportedLocale)
  document.documentElement.dir = isRtl ? 'rtl' : 'ltr'
  document.documentElement.lang = locale
}

i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources: {
      fr: { common: fr },
      ar: { common: ar },
    },
    fallbackLng: 'fr',
    supportedLngs: SUPPORTED_LOCALES as unknown as string[],
    defaultNS: 'common',
    interpolation: { escapeValue: false },
  })

i18n.on('languageChanged', applyDocumentDirection)
applyDocumentDirection(i18n.resolvedLanguage ?? 'fr')

export default i18n
