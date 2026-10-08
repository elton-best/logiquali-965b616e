import api from '@/api/client'
import { COUNTRY_OPTIONS } from '@/constants/countries'

export interface GeoCountry {
  code: string
  name: string
}

export interface GeoCity {
  name: string
}

const citiesCache = new Map<string, GeoCity[]>()
let countriesCache: GeoCountry[] | null = null

function normalizeText (value: string): string {
  return value
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .trim()
    .toUpperCase()
}

export function normalizeCountryCode (input: string): string {
  const raw = input?.trim()
  if (!raw) {
    return ''
  }

  const normalized = normalizeText(raw)
  if (normalized.length === 2) {
    return normalized
  }

  if (normalized === 'BENIN') {
    return 'BJ'
  }
  if (normalized === 'FRANCE') {
    return 'FR'
  }

  const match = COUNTRY_OPTIONS.find(country => normalizeText(country.name) === normalized)
  return match?.code || normalized
}

function normalizeCountries (input: unknown): GeoCountry[] {
  if (!Array.isArray(input)) {
    return []
  }

  return input
    .filter((item): item is GeoCountry =>
      Boolean(item)
      && typeof (item as GeoCountry).code === 'string'
      && typeof (item as GeoCountry).name === 'string',
    )
    .map(item => ({
      code: item.code.toUpperCase(),
      name: item.name,
    }))
}

function normalizeCities (input: unknown): GeoCity[] {
  if (!Array.isArray(input)) {
    return []
  }

  return input
    .filter((item): item is GeoCity =>
      Boolean(item) && typeof (item as GeoCity).name === 'string',
    )
    .map(item => ({
      name: item.name,
    }))
}

export const geoCatalogService = {
  async getCountries (force = false): Promise<GeoCountry[]> {
    if (!force && countriesCache) {
      return countriesCache
    }

    try {
      const response = await api.get('/geo/countries', {
        headers: {
          'X-Skip-Auth-Redirect': 'true',
          'X-Skip-Error-Toast': 'true',
        },
      })
      const countries = normalizeCountries(response.data?.data || response.data)

      if (countries.length > 0) {
        countriesCache = countries
        return countries
      }
    } catch (error) {
      console.warn('[geoCatalogService] Fallback countries used:', error)
    }

    countriesCache = COUNTRY_OPTIONS.map(country => ({
      code: country.code,
      name: country.name,
    }))

    return countriesCache
  },

  async getCitiesByCountry (countryCode: string, force = false): Promise<GeoCity[]> {
    const normalizedCode = normalizeCountryCode(countryCode)
    if (!normalizedCode) {
      return []
    }

    if (!force && citiesCache.has(normalizedCode)) {
      return citiesCache.get(normalizedCode) || []
    }

    try {
      const response = await api.get(`/geo/countries/${normalizedCode}/cities`, {
        headers: {
          'X-Skip-Auth-Redirect': 'true',
          'X-Skip-Error-Toast': 'true',
        },
      })
      const cities = normalizeCities(response.data?.data || response.data)
      citiesCache.set(normalizedCode, cities)
      return cities
    } catch (error) {
      console.warn(`[geoCatalogService] Failed to load cities for ${normalizedCode}:`, error)
      citiesCache.set(normalizedCode, [])
      return []
    }
  },
}
