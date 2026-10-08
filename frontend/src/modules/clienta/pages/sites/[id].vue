/**
 * Site Detail Page
 * Display and edit site information
 */

<script setup lang="ts">
  import {
    AlertCircle,
    ArrowLeft,
    Building2,
    Calendar,
    CheckCircle2,
    Edit,
    GitBranch,
    Home,
    MapPin,
    Save,
    Trash2,
    Users,
    XCircle,
  } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import siteService, { type Site, type UpdateSiteRequest } from '@/services/siteService'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()

  const site = ref<Site | null>(null)
  const loading = ref(false)
  const editMode = ref(false)
  const deleteDialog = ref(false)

  const form = ref({
    name: '',
    location: '',
    is_headquarter: false,
    is_active: true,
  })

  const errors = ref<Record<string, string>>({})

  async function loadSite () {
    loading.value = true
    try {
      const id = Number((route.params as Record<string, unknown>).id)
      site.value = await siteService.getById(id)

      // Populate form
      form.value = {
        name: site.value.name,
        location: site.value.location || '',
        is_headquarter: site.value.is_headquarter,
        is_active: site.value.is_active,
      }
    } catch (error: any) {
      toast.error(error.message || 'Erreur lors du chargement du site')
      router.push('/company/sites')
    } finally {
      loading.value = false
    }
  }

  function toggleEditMode () {
    if (editMode.value // Cancel edit - restore original values
      && site.value) {
      form.value = {
        name: site.value.name,
        location: site.value.location || '',
        is_headquarter: site.value.is_headquarter,
        is_active: site.value.is_active,
      }
    }
    editMode.value = !editMode.value
    errors.value = {}
  }

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

  async function saveChanges () {
    if (!site.value) return
    if (!validateForm()) {
      toast.error('Veuillez corriger les erreurs dans le formulaire')
      return
    }

    loading.value = true
    try {
      const updateData: UpdateSiteRequest = {
        name: form.value.name.trim(),
        location: form.value.location.trim(),
        is_headquarter: form.value.is_headquarter,
        is_active: form.value.is_active,
      }

      const updatedSite = await siteService.update(site.value.id, updateData)
      site.value = updatedSite
      editMode.value = false
      toast.success('Site modifié avec succès')
    } catch (error: any) {
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
        toast.error('Erreur de validation')
      } else {
        toast.error(error.message || 'Erreur lors de la modification du site')
      }
    } finally {
      loading.value = false
    }
  }

  function openDeleteDialog () {
    deleteDialog.value = true
  }

  function closeDeleteDialog () {
    deleteDialog.value = false
  }

  async function confirmDelete () {
    if (!site.value) return

    loading.value = true
    try {
      await siteService.delete(site.value.id)
      toast.success('Site supprimé avec succès')
      router.push('/company/sites')
    } catch (error: any) {
      toast.error(error.message || 'Erreur lors de la suppression du site')
      closeDeleteDialog()
    } finally {
      loading.value = false
    }
  }

  function formatDate (dateString: string | undefined): string {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date)
  }

  onMounted(() => {
    loadSite()
  })
</script>

<template>
  <div class="p-6 md:p-8 max-w-7xl mx-auto">
    <!-- Loading State -->
    <div v-if="loading && !site" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="w-16 h-16 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4" />
        <p class="text-neutral-500 dark:text-neutral-400">Chargement du site...</p>
      </div>
    </div>

    <template v-else-if="site">
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
      <div class="mb-8 flex flex-col sm:flex-row items-start justify-between gap-4">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
            <Building2 class="w-8 h-8 text-primary-600 dark:text-primary-400" />
          </div>
          <div>
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-50 mb-2">
              {{ site.name }}
            </h1>
            <div class="flex items-center gap-2 text-neutral-600 dark:text-neutral-400">
              <MapPin class="w-4 h-4" />
              <span class="text-sm">{{ site.location || 'Localisation non spécifiée' }}</span>
            </div>
            <div class="flex items-center gap-3 mt-3">
              <span
                v-if="site.is_headquarter"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"
              >
                <Home class="w-3.5 h-3.5" />
                Siège social
              </span>
              <span
                :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium',
                  site.is_active
                    ? 'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30'
                    : 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30'
                ]"
              >
                <component :is="site.is_active ? CheckCircle2 : XCircle" class="w-3.5 h-3.5" />
                {{ site.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="!editMode"
            class="inline-flex items-center gap-2 px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors font-medium"
            @click="toggleEditMode"
          >
            <Edit class="w-5 h-5" />
            Modifier
          </button>
          <button
            class="inline-flex items-center gap-2 px-4 py-2.5 border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors font-medium"
            @click="openDeleteDialog"
          >
            <Trash2 class="w-5 h-5" />
            Supprimer
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="card rounded-xl elevation-0 p-6 border border-neutral-200 dark:border-neutral-700">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
              <Users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-sm text-neutral-500 dark:text-neutral-400">Utilisateurs</p>
              <p class="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
                {{ site.users_count || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="card rounded-xl elevation-0 p-6 border border-neutral-200 dark:border-neutral-700">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
              <GitBranch class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-sm text-neutral-500 dark:text-neutral-400">Processus</p>
              <p class="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
                {{ site.processes_count || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="card rounded-xl elevation-0 p-6 border border-neutral-200 dark:border-neutral-700">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
              <Calendar class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-sm text-neutral-500 dark:text-neutral-400">Créé le</p>
              <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">
                {{ new Date(site.created_at).toLocaleDateString('fr-FR') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Site Information -->
        <div class="card rounded-xl elevation-0 p-6 md:p-8 border border-neutral-200 dark:border-neutral-700">
          <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-6 flex items-center gap-2">
            <Building2 class="w-5 h-5" />
            Informations du site
          </h2>

          <div v-if="!editMode" class="space-y-5">
            <!-- Name (Read-only) -->
            <div>
              <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                Nom du site
              </label>
              <p class="text-base text-neutral-900 dark:text-neutral-100 font-medium">
                {{ site.name }}
              </p>
            </div>

            <!-- Location (Read-only) -->
            <div>
              <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                Localisation
              </label>
              <p class="text-base text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                <MapPin class="w-4 h-4 text-neutral-400" />
                {{ site.location || 'Non spécifié' }}
              </p>
            </div>

            <!-- Headquarter (Read-only) -->
            <div>
              <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                Type de site
              </label>
              <p class="text-base text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                <Home class="w-4 h-4 text-neutral-400" />
                {{ site.is_headquarter ? 'Siège social' : 'Site standard' }}
              </p>
            </div>

            <!-- Status (Read-only) -->
            <div>
              <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                Statut
              </label>
              <span
                :class="[
                  'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium',
                  site.is_active
                    ? 'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30'
                    : 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30'
                ]"
              >
                <component :is="site.is_active ? CheckCircle2 : XCircle" class="w-4 h-4" />
                {{ site.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>
          </div>

          <!-- Edit Form -->
          <form v-else class="space-y-5" @submit.prevent="saveChanges">
            <!-- Name (Edit) -->
            <div>
              <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                Nom du site <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                class="input w-full h-11"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.name }"
                placeholder="Ex: Siège social Paris"
                type="text"
              >
              <p v-if="errors.name" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                {{ errors.name }}
              </p>
            </div>

            <!-- Location (Edit) -->
            <div>
              <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                <MapPin class="w-4 h-4 inline mr-1" />
                Localisation <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.location"
                class="input w-full h-11"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': errors.location }"
                placeholder="Ex: 123 Rue de la République, 75001 Paris"
                type="text"
              >
              <p v-if="errors.location" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                {{ errors.location }}
              </p>
            </div>

            <!-- Is Headquarter (Edit) -->
            <div class="flex items-start gap-3 p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700">
              <input
                id="edit_is_headquarter"
                v-model="form.is_headquarter"
                class="mt-1 w-4 h-4 text-primary-600 bg-white dark:bg-neutral-700 border-neutral-300 dark:border-neutral-600 rounded focus:ring-2 focus:ring-primary-500"
                type="checkbox"
              >
              <div class="flex-1">
                <label class="block text-sm font-medium text-neutral-900 dark:text-neutral-100 cursor-pointer" for="edit_is_headquarter">
                  <Home class="w-4 h-4 inline mr-1.5" />
                  Siège social
                </label>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                  Marquer ce site comme siège social
                </p>
              </div>
            </div>

            <!-- Is Active (Edit) -->
            <div class="flex items-start gap-3 p-4 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700">
              <input
                id="edit_is_active"
                v-model="form.is_active"
                class="mt-1 w-4 h-4 text-primary-600 bg-white dark:bg-neutral-700 border-neutral-300 dark:border-neutral-600 rounded focus:ring-2 focus:ring-primary-500"
                type="checkbox"
              >
              <div class="flex-1">
                <label class="block text-sm font-medium text-neutral-900 dark:text-neutral-100 cursor-pointer" for="edit_is_active">
                  <CheckCircle2 class="w-4 h-4 inline mr-1.5" />
                  Site actif
                </label>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                  Les sites actifs sont accessibles dans le système
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-700">
              <button
                class="px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                :disabled="loading"
                type="button"
                @click="toggleEditMode"
              >
                Annuler
              </button>
              <button
                class="btn-primary inline-flex items-center gap-2 px-4 py-2.5"
                :disabled="loading"
                type="submit"
              >
                <Save class="w-5 h-5" />
                {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Enterprise & Metadata -->
        <div class="space-y-6">
          <!-- Enterprise Info -->
          <div v-if="site.enterprise" class="card rounded-xl elevation-0 p-6 md:p-8 border border-neutral-200 dark:border-neutral-700">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-6 flex items-center gap-2">
              <Building2 class="w-5 h-5" />
              Entreprise
            </h2>

            <div class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                  Nom de l'entreprise
                </label>
                <p class="text-base text-neutral-900 dark:text-neutral-100 font-medium">
                  {{ site.enterprise.name }}
                </p>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div class="card rounded-xl elevation-0 p-6 md:p-8 border border-neutral-200 dark:border-neutral-700">
            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-6 flex items-center gap-2">
              <Calendar class="w-5 h-5" />
              Métadonnées
            </h2>

            <div class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                  Date de création
                </label>
                <p class="text-sm text-neutral-900 dark:text-neutral-100">
                  {{ formatDate(site.created_at) }}
                </p>
              </div>

              <div>
                <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                  Dernière modification
                </label>
                <p class="text-sm text-neutral-900 dark:text-neutral-100">
                  {{ formatDate(site.updated_at) }}
                </p>
              </div>

              <div>
                <label class="block text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-1.5 uppercase tracking-wide">
                  Identifiant
                </label>
                <p class="text-sm text-neutral-900 dark:text-neutral-100 font-mono">
                  #{{ site.id }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Dialog -->
      <Teleport to="body">
        <div
          v-if="deleteDialog"
          class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
          @click.self="closeDeleteDialog"
        >
          <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                <AlertCircle class="w-6 h-6 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">
                  Supprimer le site
                </h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                  Action irréversible
                </p>
              </div>
            </div>

            <p class="text-sm text-neutral-600 dark:text-neutral-300 mb-6">
              Êtes-vous sûr de vouloir supprimer le site <strong>{{ site.name }}</strong> ?
              Toutes les données associées seront perdues définitivement.
            </p>

            <div class="flex items-center justify-end gap-3">
              <button
                class="px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors"
                :disabled="loading"
                @click="closeDeleteDialog"
              >
                Annuler
              </button>
              <button
                class="px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors inline-flex items-center gap-2"
                :disabled="loading"
                @click="confirmDelete"
              >
                <Trash2 class="w-4 h-4" />
                {{ loading ? 'Suppression...' : 'Supprimer' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </template>
  </div>
</template>
