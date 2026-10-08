/**
 * Format utility functions
 */

import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/fr'

dayjs.extend(relativeTime)
dayjs.locale('fr')

export function formatDate (date: string | Date, format = 'DD/MM/YYYY'): string {
  return dayjs(date).format(format)
}

export function formatDateTime (date: string | Date): string {
  return dayjs(date).format('DD/MM/YYYY HH:mm')
}

export function formatRelativeTime (date: string | Date): string {
  return dayjs(date).fromNow()
}

export function formatCurrency (amount: number, currency = 'XOF'): string {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency,
  }).format(amount)
}

export function formatNumber (value: number, decimals = 0): string {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(value)
}

export function formatFileSize (bytes: number): string {
  if (bytes === 0) {
    return '0 Bytes'
  }
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

export function truncate (text: string, length = 50): string {
  if (text.length <= length) {
    return text
  }
  return text.slice(0, Math.max(0, length)) + '...'
}

export function capitalize (text: string): string {
  return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase()
}

export function slugify (text: string): string {
  return text
    .toString()
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^\w-]+/g, '')
    .replace(/--+/g, '-')
}
