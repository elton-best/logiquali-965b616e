import type { RouteLocationNormalizedLoaded } from 'vue-router'
import i18n from '@/i18n'

type SupportedLocale = 'fr' | 'en'

const DEFAULT_LOCALE: SupportedLocale = 'fr'
const APP_NAME = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'
const DEFAULT_SITE_NAME = APP_NAME
const DEFAULT_DESCRIPTION = `${APP_NAME} centralise qualite, conformite et gouvernance pour accelerer les decisions avec tracabilite.`
const DEFAULT_KEYWORDS = `${APP_NAME},logiciel gestion qualite,plateforme conformite,workflow qualite,gouvernance,permissions`

function normalizeLocale (locale: unknown): SupportedLocale {
  return String(locale) === 'en' ? 'en' : DEFAULT_LOCALE
}

function getSiteOrigin (): string {
  const configuredUrl = import.meta.env.VITE_SITE_URL as string | undefined
  if (!configuredUrl) {
    return window.location.origin
  }

  try {
    return new URL(configuredUrl).origin
  } catch {
    return window.location.origin
  }
}

function buildAbsoluteUrl (path: string, locale?: SupportedLocale): string {
  const target = new URL(path || '/', getSiteOrigin())

  if (locale) {
    target.searchParams.set('lang', locale)
  }

  return target.toString()
}

function upsertMetaTag (
  key: 'name' | 'property',
  value: string,
  content: string,
): void {
  if (!content) {
    return
  }

  const selector = `meta[${key}="${value}"]`
  const existing = document.head.querySelector<HTMLMetaElement>(selector)

  if (existing) {
    existing.setAttribute('content', content)
    return
  }

  const meta = document.createElement('meta')
  meta.setAttribute(key, value)
  meta.setAttribute('content', content)
  document.head.append(meta)
}

function upsertCanonicalLink (href: string): void {
  const existing = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')

  if (existing) {
    existing.setAttribute('href', href)
    return
  }

  const link = document.createElement('link')
  link.setAttribute('rel', 'canonical')
  link.setAttribute('href', href)
  document.head.append(link)
}

function upsertAlternateLink (hrefLang: string, href: string): void {
  const selector = `link[rel="alternate"][hreflang="${hrefLang}"]`
  const existing = document.head.querySelector<HTMLLinkElement>(selector)

  if (existing) {
    existing.setAttribute('href', href)
    return
  }

  const link = document.createElement('link')
  link.setAttribute('rel', 'alternate')
  link.setAttribute('hreflang', hrefLang)
  link.setAttribute('href', href)
  document.head.append(link)
}

function getTranslatedValue (
  key: string,
  fallback: string,
): string {
  const translated = i18n.global.t(key, { appName: APP_NAME })
  return translated === key ? fallback : String(translated)
}

function getCurrentLocale (): SupportedLocale {
  const localeSource = (i18n.global as any)?.locale
  const rawLocale = (localeSource && typeof localeSource === 'object' && 'value' in localeSource)
    ? localeSource.value
    : localeSource

  return normalizeLocale(rawLocale)
}

export function isPublicSeoRoute (route: RouteLocationNormalizedLoaded): boolean {
  return typeof route.meta?.seoKey === 'string' && route.meta.seoKey.length > 0
}

export function updateSeoForRoute (route: RouteLocationNormalizedLoaded): void {
  const locale = getCurrentLocale()
  const seoKey = String(route.meta.seoKey)
  const routeSeoKey = `seo.routes.${seoKey}`

  const title = getTranslatedValue(`${routeSeoKey}.title`, getTranslatedValue('seo.defaults.title', DEFAULT_SITE_NAME))
  const description = getTranslatedValue(`${routeSeoKey}.description`, getTranslatedValue('seo.defaults.description', DEFAULT_DESCRIPTION))
  const keywords = getTranslatedValue(`${routeSeoKey}.keywords`, getTranslatedValue('seo.defaults.keywords', DEFAULT_KEYWORDS))
  const ogTitle = getTranslatedValue(`${routeSeoKey}.ogTitle`, title)
  const ogDescription = getTranslatedValue(`${routeSeoKey}.ogDescription`, description)

  const canonicalUrl = buildAbsoluteUrl(route.path)
  const frAlternate = buildAbsoluteUrl(route.path, 'fr')
  const enAlternate = buildAbsoluteUrl(route.path, 'en')

  document.documentElement.setAttribute('lang', locale)
  document.title = title

  upsertMetaTag('name', 'description', description)
  upsertMetaTag('name', 'keywords', keywords)

  upsertMetaTag('property', 'og:type', 'website')
  upsertMetaTag('property', 'og:site_name', APP_NAME)
  upsertMetaTag('property', 'og:title', ogTitle)
  upsertMetaTag('property', 'og:description', ogDescription)
  upsertMetaTag('property', 'og:url', canonicalUrl)
  upsertMetaTag('property', 'og:locale', locale === 'fr' ? 'fr_FR' : 'en_US')

  upsertMetaTag('name', 'twitter:card', 'summary_large_image')
  upsertMetaTag('name', 'twitter:title', ogTitle)
  upsertMetaTag('name', 'twitter:description', ogDescription)

  upsertCanonicalLink(canonicalUrl)
  upsertAlternateLink('fr', frAlternate)
  upsertAlternateLink('en', enAlternate)
  upsertAlternateLink('x-default', canonicalUrl)
}

export function updateDocumentLanguageOnly (): void {
  const locale = getCurrentLocale()
  document.documentElement.setAttribute('lang', locale)
}
