/**
 * KYC Pending Page
 * Shown after Client A submits registration
 */

<script setup lang="ts">
  import { CheckCircle2, Clock, FileText, Mail } from 'lucide-vue-next'
  import { onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useKYCStore } from '@/stores/kyc'

  const router = useRouter()
  const kycStore = useKYCStore()

  onMounted(() => {
    if (!kycStore.currentRequest) {
      router.push('/signup-client-a')
    }
  })
</script>

<template>
  <div class="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
      <!-- Success Card -->
      <div class="card p-8 text-center">
        <!-- Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full mb-6">
          <Clock class="w-10 h-10 text-blue-600 dark:text-blue-400" />
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-50 mb-3">
          Demande en cours de traitement
        </h1>

        <p class="text-lg text-neutral-600 dark:text-neutral-400 mb-8">
          Votre demande d'inscription a été soumise avec succès !
        </p>

        <!-- Reference Number -->
        <div v-if="kycStore.currentRequest" class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-lg mb-8">
          <FileText class="w-4 h-4 text-neutral-600 dark:text-neutral-400" />
          <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
            Référence:
            <span class="font-mono text-primary-600 dark:text-primary-400">
              {{ kycStore.currentRequest.reference }}
            </span>
          </span>
        </div>

        <!-- Steps -->
        <div class="bg-neutral-50 dark:bg-neutral-900 rounded-lg p-6 mb-8 text-left">
          <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 mb-4">
            Prochaines étapes :
          </h3>

          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 w-6 h-6 bg-accent-100 dark:bg-accent-900/30 rounded-full flex items-center justify-center mt-0.5">
                <CheckCircle2 class="w-4 h-4 text-accent-600 dark:text-accent-400" />
              </div>
              <div>
                <p class="font-medium text-neutral-900 dark:text-neutral-100">
                  1. Vérification de vos documents
                </p>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                  Notre équipe va examiner les documents fournis
                </p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mt-0.5">
                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">2</span>
              </div>
              <div>
                <p class="font-medium text-neutral-900 dark:text-neutral-100">
                  2. Validation de votre compte
                </p>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                  Traitement sous 24-48 heures ouvrables
                </p>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 w-6 h-6 bg-neutral-200 dark:bg-neutral-700 rounded-full flex items-center justify-center mt-0.5">
                <span class="text-xs font-semibold text-neutral-500">3</span>
              </div>
              <div>
                <p class="font-medium text-neutral-900 dark:text-neutral-100">
                  3. Notification par email
                </p>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                  Vous recevrez un lien pour accéder à votre compte
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Email Notification -->
        <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
          <div class="flex items-start gap-3">
            <Mail class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
            <div class="text-left">
              <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-1">
                Email de confirmation envoyé
              </p>
              <p class="text-sm text-blue-700 dark:text-blue-300">
                Un email de confirmation a été envoyé à
                <span class="font-semibold">{{ kycStore.currentRequest?.admin_email || 'votre adresse' }}</span>.
                Consultez votre boîte de réception (et vos spams).
              </p>
            </div>
          </div>
        </div>

        <!-- Contact Support -->
        <div class="text-sm text-neutral-600 dark:text-neutral-400">
          <p class="mb-2">Des questions ?</p>
          <a
            class="text-primary-600 dark:text-primary-400 hover:underline font-medium"
            href="mailto:support@qualibest.com"
          >
            Contactez notre support
          </a>
        </div>

        <!-- Back to Home -->
        <div class="mt-8">
          <router-link class="btn-outline" to="/">
            Retour à l'accueil
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
