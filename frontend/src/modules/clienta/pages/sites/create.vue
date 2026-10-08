/**
 * Create Site Page
 * Form to create a new site
 */

<script setup lang="ts">
  import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    Home,
    MapPin,
    Save,
  } from 'lucide-vue-next'
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import siteService, { type CreateSiteRequest } from '@/services/siteService'

  const router = useRouter()
  const toast = useToast()

  const loading = ref(false)
  const form = ref({
    name: '',
    location: '',
    is_headquarter: false,
    is_active: true,
  })

  const errors = ref<Record<string, string>>({})

  function validateForm (): boolean {
    errors.value = {}

    if (!form.value.name || form.value.name.trim() === '') {
      errors.value.name = 'Le nom est requis'
    }

    if (!form.value.location || form.value.location.trim() === '') {
      errors.value.location = 'La localisation est requise'
    }

    return Object.keys(errors.value).length === 0
  }

  async function handleSubmit () {
    if (!validateForm()) {
      toast.error('Veuillez corriger les erreurs dans le formulaire')
      return
    }

    loading.value = true
    try {
      const siteData: CreateSiteRequest = {
        name: form.value.name.trim(),
        location: form.value.location.trim(),
        city: form.value.location.trim(),
        is_headquarter: form.value.is_headquarter,
        is_active: form.value.is_active,
      }

      await siteService.create(siteData)
      toast.success('Site créé avec succès')
      router.push('/company/sites')
    } catch (error: any) {
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
        toast.error('Erreur de validation')
      } else {
        toast.error(error.message || 'Erreur lors de la création du site')
      }
    } finally {
      loading.value = false
    }
  }
</script>

<template>
  <div class="p-6 md:p-8 max-w-4xl mx-auto">
    <!-- Breadcrumbs -->
    <nav class="mb-6">
      <button
        class="inline-flex items-center gap-2 text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors"
        @click="router.push('/company/sites')"
      >
        <ArrowLeft class="w-5 h-5" />
        <span class="text-sm font-medium">Retour aux sites</span>
      </button>
    </nav>

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-50 flex items-center gap-3 mb-2">
        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
          <Building2 class="w-7 h-7 text-primary-600 dark:text-primary-400" />
        </div>
        Nouveau site
      </h1>
      <p class="text-neutral-600 dark:text-neutral-400">
        Créez un nouveau site pour votre entreprise
      </p>
    </div>

    <form class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Basic Information -->
      <div class="card rounded-xl elevation-0 p-6 md:p-8 border border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-6 flex items-center gap-2">
          <Building2 class="w-5 h-5" />
          Informations générales
        </h2>

        <div class="space-y-5">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              Nom du site <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              class="input w-full h-11"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.name }"
              placeholder="Ex: Siège social Paris, Usine de Lyon..."
              type="text"
            >
            <p v-if="errors.name" class="mt-1.5 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
              {{ errors.name }}
            </p>
          </div>

          <!-- Location -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
              <MapPin class="w-4 h-4 inline mr-1" />
              Localisation <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.location"
              class="input w-full h-11"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.location }"
              placeholder="Ex: 123 Rue de la République, 75001 Paris, France"
              type="text"
            >
            <p v-if="errors.location" class="mt-1.5 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
              {{ errors.location }}
            </p>
            <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
              Adresse complète du site (rue, code postal, ville, pays)
            </p>
          </div>
        </div>
      </div>

      <!-- Site Configuration -->
      <div class="card rounded-xl elevation-0 p-6 md:p-8 border border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-6 flex items-center gap-2">
          <CheckCircle2 class="w-5 h-5" />
          Configuration
        </h2>

        <div class="space-y-5">
          <!-- Is Headquarter -->
          <div class="flex items-start gap-3 p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700">
            <input
              id="is_headquarter"
              v-model="form.is_headquarter"
              class="mt-1 w-4 h-4 text-primary-600 bg-white dark:bg-neutral-700 border-neutral-300 dark:border-neutral-600 rounded focus:ring-2 focus:ring-primary-500"
              type="checkbox"
            >
            <div class="flex-1">
              <label class="block text-sm font-medium text-neutral-900 dark:text-neutral-100 cursor-pointer" for="is_headquarter">
                <Home class="w-4 h-4 inline mr-1.5" />
                Siège social
              </label>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                Cochez cette case si ce site est le siège social de l'entreprise
              </p>
            </div>
          </div>

          <!-- Is Active -->
          <div class="flex items-start gap-3 p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700">
            <input
              id="is_active"
              v-model="form.is_active"
              class="mt-1 w-4 h-4 text-primary-600 bg-white dark:bg-neutral-700 border-neutral-300 dark:border-neutral-600 rounded focus:ring-2 focus:ring-primary-500"
              type="checkbox"
            >
            <div class="flex-1">
              <label class="block text-sm font-medium text-neutral-900 dark:text-neutral-100 cursor-pointer" for="is_active">
                <CheckCircle2 class="w-4 h-4 inline mr-1.5" />
                Site actif
              </label>
              <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                Les sites actifs sont accessibles aux utilisateurs et peuvent être utilisés dans le système
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-4">
        <button
          class="px-5 py-2.5 border border-neutral-300 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors font-medium"
          :disabled="loading"
          type="button"
          @click="router.push('/company/sites')"
        >
          Annuler
        </button>
        <button
          class="btn-primary inline-flex items-center gap-2 px-5 py-2.5"
          :disabled="loading"
          type="submit"
        >
          <Save class="w-5 h-5" />
          {{ loading ? 'Création en cours...' : 'Créer le site' }}
        </button>
      </div>
    </form>
  </div>
</template>
