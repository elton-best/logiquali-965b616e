<template>
  <ClientALayout current-page="profile">
    <div v-if="loading" class="p-6">
      <v-skeleton-loader type="article, article" />
    </div>

    <div v-else-if="user" class="p-6 space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profil utilisateur</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Gérez vos informations personnelles et préférences</p>
      </div>

      <v-alert
        v-if="blockingMessage"
        color="warning"
        icon="mdi-alert-circle-outline"
        variant="tonal"
      >
        <v-alert-title>Action requise</v-alert-title>
        {{ blockingMessage }}
      </v-alert>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Profile Card -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="text-center">
              <div class="relative inline-block mb-4">
                <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center overflow-hidden text-white text-3xl font-bold">
                  <img
                    v-if="profilePhotoUrl"
                    alt="Photo de profil"
                    class="w-full h-full object-cover"
                    :src="profilePhotoUrl"
                  >
                  <span v-else>{{ getInitials(user.name) }}</span>
                </div>
                <input
                  ref="photoInput"
                  accept="image/png,image/jpeg,image/jpg,image/gif"
                  class="hidden"
                  type="file"
                  @change="handlePhotoSelected"
                >
                <button
                  class="absolute bottom-0 right-0 w-8 h-8 bg-indigo-600 hover:bg-indigo-700 rounded-full flex items-center justify-center text-white transition-colors disabled:opacity-60"
                  :disabled="uploadingPhoto"
                  type="button"
                  @click="photoInput?.click()"
                >
                  <Camera class="w-4 h-4" />
                </button>
              </div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h2>
              <p class="text-gray-600 dark:text-gray-400 mt-1">{{ user.email }}</p>
              <span class="inline-block mt-3 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-semibold">
                {{ getRoleLabel(user.user_type) }}
              </span>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Activité</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Dernière connexion</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(user.updated_at) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Compte créé</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(user.created_at) }}</span>
              </div>
              <div v-if="user.site" class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Site</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ user.site.name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Personal Information -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informations personnelles</h2>
              <button
                v-if="!editingInfo"
                class="flex items-center gap-2 px-4 py-2 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                @click="editingInfo = true"
              >
                <Edit2 class="w-4 h-4" />
                Modifier
              </button>
            </div>

            <form class="space-y-4" @submit.prevent="savePersonalInfo">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom complet</label>
                  <input
                    v-model="formData.name"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white disabled:opacity-50 disabled:bg-gray-50 dark:disabled:bg-gray-900"
                    :disabled="!editingInfo"
                    type="text"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom d'utilisateur</label>
                  <input
                    v-model="formData.username"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white disabled:opacity-50 disabled:bg-gray-50 dark:disabled:bg-gray-900"
                    :disabled="true"
                    type="text"
                  >
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                <input
                  v-model="formData.email"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white disabled:opacity-50 disabled:bg-gray-50 dark:disabled:bg-gray-900"
                  :disabled="!editingInfo"
                  type="email"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Téléphone</label>
                <input
                  v-model="formData.phone"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white disabled:opacity-50 disabled:bg-gray-50 dark:disabled:bg-gray-900"
                  :disabled="!editingInfo"
                  type="tel"
                >
              </div>

              <div v-if="editingInfo" class="flex items-center gap-3 pt-4">
                <button
                  class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition-colors disabled:opacity-50"
                  :disabled="saving"
                  type="submit"
                >
                  {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
                <button
                  class="px-6 py-3 border-2 border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                  type="button"
                  @click="cancelEdit"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>

          <!-- Change Password -->
          <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Changer le mot de passe</h2>

            <form class="space-y-4" @submit.prevent="changePassword">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mot de passe actuel</label>
                <input
                  v-model="passwordData.current"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  type="password"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nouveau mot de passe</label>
                <input
                  v-model="passwordData.new"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  type="password"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirmer le mot de passe</label>
                <input
                  v-model="passwordData.confirm"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  type="password"
                >
              </div>

              <button
                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors disabled:opacity-50"
                :disabled="changingPassword"
                type="submit"
              >
                {{ changingPassword ? 'Modification...' : 'Changer le mot de passe' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="p-6">
      <div class="text-center py-12">
        <p class="text-gray-600 dark:text-gray-400">Erreur lors du chargement du profil</p>
      </div>
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { Camera, Edit2 } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import userService, { type User } from '@/services/userService'
  import { getBlockingMessage } from '@/utils/blockingAccess'

  const route = useRoute()
  const toast = useToast()

  const loading = ref(true)
  const user = ref<User | null>(null)
  const editingInfo = ref(false)
  const saving = ref(false)
  const changingPassword = ref(false)
  const uploadingPhoto = ref(false)
  const photoInput = ref<HTMLInputElement | null>(null)
  const blockingMessage = computed(() => getBlockingMessage(
    typeof route.query.blocking === 'string' ? route.query.blocking : undefined,
  ))
  const profilePhotoUrl = computed(() => {
    const raw = String(user.value?.photo_url || user.value?.photo_path || '').trim()
    if (!raw) return ''
    if (/^https?:\/\//i.test(raw)) return raw

    const apiBase = String(import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1')
      .replace(/\/api\/v1\/?$/, '')
    return `${apiBase}/storage/${raw.replace(/^\/+/, '')}`
  })

  const formData = ref({
    name: '',
    username: '',
    email: '',
    phone: '',
  })

  const passwordData = ref({
    current: '',
    new: '',
    confirm: '',
  })

  async function loadProfile () {
    loading.value = true
    try {
      user.value = await userService.getProfile()
      formData.value = {
        name: user.value.name,
        username: user.value.username,
        email: user.value.email,
        phone: user.value.phone || '',
      }
    } catch (error: any) {
      console.error('Error loading profile:', error)
      toast.error('Erreur lors du chargement du profil')
    } finally {
      loading.value = false
    }
  }

  function getInitials (firstName: string, lastName?: string) {
    if (lastName) {
      return `${firstName.charAt(0)}${lastName.charAt(0)}`
    }
    const parts = firstName.split(' ')
    const first = parts[0] || ''
    const second = parts[1] || ''
    if (second) {
      return `${first.charAt(0)}${second.charAt(0)}`
    }
    return firstName.slice(0, 2).toUpperCase()
  }

  function getRoleLabel (userType: string) {
    const labels: Record<string, string> = {
      admin: 'Administrateur',
      entreprise: 'Entreprise',
      user: 'Utilisateur',
      collaborator: 'Collaborateur',
    }
    return labels[userType] || userType
  }

  function formatDate (date: string) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })
  }

  function cancelEdit () {
    editingInfo.value = false
    if (user.value) {
      formData.value = {
        name: user.value.name,
        username: user.value.username,
        email: user.value.email,
        phone: user.value.phone || '',
      }
    }
  }

  async function savePersonalInfo () {
    saving.value = true
    try {
      user.value = await userService.updateProfile({
        name: formData.value.name,
        email: formData.value.email,
        phone: formData.value.phone,
      })
      editingInfo.value = false
      toast.success('Profil mis à jour avec succès')
    } catch (error: any) {
      console.error('Error saving profile:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la sauvegarde')
    } finally {
      saving.value = false
    }
  }

  async function changePassword () {
    if (passwordData.value.new !== passwordData.value.confirm) {
      toast.error('Les mots de passe ne correspondent pas')
      return
    }

    if (passwordData.value.new.length < 8) {
      toast.error('Le mot de passe doit contenir au moins 8 caractères')
      return
    }

    changingPassword.value = true
    try {
      await userService.updateProfile({
        password: passwordData.value.new,
        password_confirmation: passwordData.value.confirm,
      })
      passwordData.value = { current: '', new: '', confirm: '' }
      toast.success('Mot de passe modifié avec succès')
    } catch (error: any) {
      console.error('Error changing password:', error)
      toast.error(error.response?.data?.message || 'Erreur lors du changement de mot de passe')
    } finally {
      changingPassword.value = false
    }
  }

  async function handlePhotoSelected (event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (!file || !user.value?.id) return

    const allowedTypes = new Set(['image/png', 'image/jpeg', 'image/gif'])
    if (!allowedTypes.has(file.type)) {
      toast.error('Format invalide. Utilise uniquement PNG, JPG ou GIF.')
      target.value = ''
      return
    }

    if (file.size > 2 * 1024 * 1024) {
      toast.error('Fichier trop volumineux. Taille maximale: 2 Mo.')
      target.value = ''
      return
    }

    uploadingPhoto.value = true
    try {
      const payload = await userService.uploadPhoto(user.value.id, file)
      user.value = {
        ...user.value,
        photo_path: payload.photo_path,
        photo_url: payload.photo_url,
      }
      toast.success('Photo de profil mise à jour.')
    } catch (error: any) {
      console.error('Error uploading photo:', error)
      toast.error(error.response?.data?.message || 'Erreur lors du téléversement de la photo')
    } finally {
      uploadingPhoto.value = false
      target.value = ''
    }
  }

  onMounted(() => {
    loadProfile()
  })
</script>
