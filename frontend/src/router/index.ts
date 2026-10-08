/**
 * router/index.ts
 *
 * Router configuration with modular routes
 */

import type { RouteRecordRaw } from 'vue-router'
import { createRouter, createWebHistory } from 'vue-router'
import { setupGuards } from './guards'
import { moduleRoutes } from './modules'

/**
 * Public routes (auth, landing, etc.)
 */
const publicRoutes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/index.vue'),
    meta: {
      seoKey: 'home',
    },
  },
  {
    path: '/landing',
    name: 'landing',
    component: () => import('@/pages/LandingPage.vue'),
    meta: {
      seoKey: 'landing',
    },
  },
  {
    path: '/pricing',
    name: 'pricing',
    component: () => import('@/pages/PricingPage.vue'),
    meta: {
      seoKey: 'pricing',
    },
  },
  {
    path: '/guide-utilisation',
    name: 'guide-utilisation',
    component: () => import('@/pages/GuideUtilisationPage.vue'),
    meta: {
      seoKey: 'guideUtilisation',
    },
  },
  {
    path: '/politique-confidentialite',
    name: 'politique-confidentialite',
    component: () => import('@/pages/PrivacyPolicyPage.vue'),
    meta: {
      seoKey: 'politiqueConfidentialite',
    },
  },
  {
    path: '/auth/login',
    name: 'login',
    component: () => import('@/pages/auth/LoginModern.vue'),
  },
  {
    path: '/auth/signup',
    name: 'signup',
    component: () => import('@/pages/auth/Signup.vue'),
  },
  {
    path: '/auth/signup/company',
    name: 'signup-company',
    component: () => import('@/pages/auth/signup/CompanySignupModern.vue'),
  },
  {
    path: '/auth/signup/clientb',
    name: 'signup-clientb',
    component: () => import('@/pages/auth/signup/clientb.vue'),
  },
  {
    path: '/auth/signup/individual',
    name: 'signup-individual',
    component: () => import('@/pages/auth/signup/individual.vue'),
  },
  {
    path: '/auth/signup-success',
    name: 'auth-signup-success',
    component: () => import('@/pages/auth/signup-success.vue'),
  },
  {
    path: '/auth/signup-loading',
    name: 'auth-signup-loading',
    component: () => import('@/pages/auth/signup-loading.vue'),
  },
  {
    path: '/auth/forgot-password',
    name: 'forgot-password',
    component: () => import('@/pages/auth/forgot-password.vue'),
  },
  {
    path: '/auth/reset-password',
    name: 'reset-password',
    component: () => import('@/pages/auth/reset-password.vue'),
  },
  {
    path: '/reset-password',
    redirect: to => ({
      path: '/auth/reset-password',
      query: to.query,
    }),
  },
  {
    path: '/auth/email-verification',
    name: 'email-verification',
    component: () => import('@/pages/auth/EmailVerification.vue'),
  },
  {
    path: '/auth/email-verified',
    name: 'email-verified',
    component: () => import('@/pages/auth/email-verified.vue'),
  },
  {
    path: '/auth/email-verification-failed',
    name: 'email-verification-failed',
    component: () => import('@/pages/auth/email-verification-failed.vue'),
  },
  {
    path: '/auth/pending-approval',
    name: 'pending-approval',
    component: () => import('@/pages/auth/pending-approval.vue'),
  },
  {
    path: '/auth/force-password-change',
    name: 'force-password-change',
    component: () => import('@/pages/auth/ForcePasswordChange.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/lock-screen',
    name: 'lock-screen',
    component: () => import('@/pages/LockScreen.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/403',
    name: 'forbidden',
    component: () => import('@/pages/403.vue'),
  },
  {
    path: '/qr-verify/:hash',
    name: 'qr-verify',
    component: () => import('@/pages/qr-verify.vue'),
  },
  {
    path: '/enterprise/configuration',
    name: 'enterprise-configuration',
    redirect: '/company/settings?tab=configuration',
  },
  {
    path: '/enterprise/signatures',
    name: 'enterprise-signatures',
    redirect: '/company/settings?tab=configuration',
  },
  {
    path: '/company/profile',
    name: 'company-profile-legacy',
    redirect: '/company/settings?tab=profil',
  },
  {
    path: '/clienta/:pathMatch(.*)*',
    name: 'clienta-legacy-redirect',
    redirect: to => {
      const params = to.params as Record<string, string | string[] | undefined>
      const raw = params.pathMatch
      const suffix = Array.isArray(raw) ? raw.join('/') : String(raw || '')
      const targetPath = suffix ? `/company/${suffix}` : '/company/dashboard'
      return {
        path: targetPath,
        query: to.query,
        hash: to.hash,
      }
    },
  },
  // Public evaluation form (no auth required)
  {
    path: '/evaluation/:token',
    name: 'public-evaluation',
    component: () => import('@/pages/public/EvaluationForm.vue'),
    meta: {
      public: true,
      layout: 'blank',
    },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [...publicRoutes, ...moduleRoutes],
})

// Setup navigation guards
setupGuards(router)

// Workaround for https://github.com/vitejs/vite/issues/11804
router.onError((err, to) => {
  if (err?.message?.includes?.('Failed to fetch dynamically imported module')) {
    const reloadKey = 'vuetify:dynamic-reload'
    const reloadCount = Number.parseInt(
      localStorage.getItem(reloadKey) || '0',
      10,
    )

    if (reloadCount >= 2) {
      console.error(
        'Dynamic import error persists after multiple reloads',
        err,
      )
      localStorage.removeItem(reloadKey)
      // Rediriger vers la page d'accueil en cas d'échec persistant
      window.location.href = '/'
    } else {
      console.log(
        `Reloading page to fix dynamic import error (attempt ${reloadCount + 1})`,
      )
      localStorage.setItem(reloadKey, String(reloadCount + 1))
      setTimeout(() => {
        window.location.href = to.fullPath
      }, 100)
    }
  } else {
    console.error(err)
  }
})

router.isReady().then(() => {
  localStorage.removeItem('vuetify:dynamic-reload')
})

export default router
