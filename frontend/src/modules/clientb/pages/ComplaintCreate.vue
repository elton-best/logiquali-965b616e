<template>
  <ClientBLayout current-page="/clientb/complaints">
    <v-card class="mb-4" elevation="0">
      <v-card-text class="pa-6">
        <div class="d-flex align-center mb-4">
          <v-btn
            class="me-2"
            icon
            variant="text"
            @click="goBack"
          >
            <v-icon>mdi-arrow-left</v-icon>
          </v-btn>
          <div>
            <h1 class="text-h4 font-weight-bold">
              Nouvelle réclamation
            </h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Remplissez le formulaire ci-dessous pour déposer votre réclamation
            </p>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-row>
      <v-col cols="12" md="8">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <!-- Section 1: Informations d'Identification -->
          <v-card class="mb-4" elevation="0">
            <v-card-title class="pa-4 bg-primary text-white">
              <v-icon class="me-2">mdi-account</v-icon>
              1. Informations d'Identification
            </v-card-title>
            <v-card-text class="pa-6">
              <v-alert class="mb-4" type="info" variant="tonal">
                <div class="d-flex align-center">
                  <v-icon class="me-3">mdi-information</v-icon>
                  <div class="text-body-2">
                    Les informations ci-dessous sont extraites de votre profil. Vous pouvez les modifier si nécessaire.
                  </div>
                </div>
              </v-alert>

              <v-row>
                <v-col cols="12">
                  <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                    Nom complet <span class="text-error">*</span>
                  </label>
                  <v-text-field
                    v-model="form.full_name"
                    bg-color="grey-lighten-4"
                    density="comfortable"
                    hide-details="auto"
                    placeholder="Votre nom complet"
                    prepend-inner-icon="mdi-account"
                    readonly
                    variant="outlined"
                  />
                </v-col>
              </v-row>

              <v-row class="mt-2">
                <v-col cols="12" md="6">
                  <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                    Adresse Email <span class="text-error">*</span>
                  </label>
                  <v-text-field
                    v-model="form.email"
                    bg-color="grey-lighten-4"
                    density="comfortable"
                    hide-details="auto"
                    placeholder="email@example.com"
                    prepend-inner-icon="mdi-email"
                    readonly
                    type="email"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                    Numéro de téléphone <span class="text-error">*</span>
                  </label>
                  <v-text-field
                    v-model="form.phone"
                    bg-color="grey-lighten-4"
                    density="comfortable"
                    hide-details="auto"
                    placeholder="+XXX XX XX XX XX"
                    prepend-inner-icon="mdi-phone"
                    readonly
                    variant="outlined"
                  />
                </v-col>
              </v-row>

              <v-row class="mt-2">
                <v-col cols="12">
                  <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                    Adresse postale (optionnel)
                  </label>
                  <v-textarea
                    v-model="form.address"
                    :error-messages="errors.address"
                    hide-details="auto"
                    placeholder="Votre adresse complète"
                    prepend-inner-icon="mdi-map-marker"
                    rows="2"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Section 2: Détails de la Réclamation -->
          <v-card class="mb-4" elevation="0">
            <v-card-title class="pa-4 bg-warning text-white">
              <v-icon class="me-2">mdi-file-document</v-icon>
              2. Détails de la Réclamation
            </v-card-title>
            <v-card-text class="pa-6">
              <!-- Site (Autocomplete) -->
              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Site concerné <span class="text-error">*</span>
                </label>
                <v-autocomplete
                  v-model="form.site_id"
                  v-model:search="siteSearch"
                  clearable
                  density="comfortable"
                  :error-messages="errors.site_id"
                  hide-details="auto"
                  :item-title="siteLabel"
                  item-value="id"
                  :items="computedSitesOptions"
                  :loading="sitesLoading"
                  placeholder="Tapez au moins 2 lettres pour rechercher un site..."
                  :rules="[rules.required]"
                  variant="outlined"
                >
                  <template #prepend-inner>
                    <v-icon>mdi-map-marker</v-icon>
                  </template>
                  <template #no-data>
                    <v-list-item>
                      <v-list-item-title class="text-caption">
                        {{ siteSearch && siteSearch.length >= 2
                          ? 'Aucun site trouvé'
                          : 'Tapez au moins 2 lettres pour rechercher' }}
                      </v-list-item-title>
                    </v-list-item>
                  </template>
                </v-autocomplete>
              </div>

              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Objet de la réclamation <span class="text-error">*</span>
                </label>
                <v-text-field
                  v-model="form.title"
                  counter="100"
                  density="comfortable"
                  :error-messages="errors.title"
                  hide-details="auto"
                  maxlength="100"
                  placeholder="Résumé court du problème"
                  :rules="[rules.required]"
                  variant="outlined"
                />
              </div>

              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Description détaillée de la réclamation <span class="text-error">*</span>
                </label>
                <v-textarea
                  v-model="form.description"
                  counter
                  :error-messages="errors.description"
                  hide-details="auto"
                  placeholder="Décrivez en détail les faits ou votre mécontentement..."
                  rows="6"
                  :rules="[rules.required, rules.minLength(50)]"
                  variant="outlined"
                />
              </div>

              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Catégorie (optionnel)
                </label>
                <v-select
                  v-model="form.category"
                  clearable
                  density="comfortable"
                  hide-details="auto"
                  :items="categories"
                  :loading="categoriesLoading"
                  placeholder="Sélectionnez une catégorie"
                  variant="outlined"
                >
                  <template #prepend-inner>
                    <v-icon>mdi-tag-outline</v-icon>
                  </template>
                </v-select>
              </div>

              <div>
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Priorité
                </label>
                <v-radio-group v-model="form.priority" hide-details inline>
                  <v-radio
                    color="primary"
                    label="Basse"
                    value="low"
                  />
                  <v-radio
                    color="warning"
                    label="Moyenne"
                    value="medium"
                  />
                  <v-radio
                    color="error"
                    label="Haute"
                    value="high"
                  />
                  <v-radio
                    color="error"
                    label="Urgente"
                    value="urgent"
                  />
                </v-radio-group>
              </div>
            </v-card-text>
          </v-card>

          <!-- Section 3: Préférences et Attentes -->
          <v-card class="mb-4" elevation="0">
            <v-card-title class="pa-4 bg-info text-white">
              <v-icon class="me-2">mdi-cog</v-icon>
              3. Préférences et Attentes
            </v-card-title>
            <v-card-text class="pa-6">
              <div class="mb-4">
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Mode de réponse souhaité
                </label>
                <v-radio-group v-model="form.wants_email_response" hide-details>
                  <v-radio
                    color="primary"
                    :value="true"
                  >
                    <template #label>
                      <div class="d-flex align-center">
                        <v-icon class="me-2" color="primary">mdi-email-check</v-icon>
                        <span>OUI - Je souhaite recevoir la réponse par email</span>
                      </div>
                    </template>
                  </v-radio>
                  <v-radio
                    color="error"
                    :value="false"
                  >
                    <template #label>
                      <div class="d-flex align-center">
                        <v-icon class="me-2" color="error">mdi-email-off</v-icon>
                        <span>NON - Je ne souhaite pas recevoir de réponse par email</span>
                      </div>
                    </template>
                  </v-radio>
                </v-radio-group>
              </div>

              <div>
                <label class="text-subtitle-2 font-weight-bold mb-2 d-block">
                  Propositions de solutions souhaitées (optionnel)
                </label>
                <v-textarea
                  v-model="form.expected_solution"
                  counter
                  hide-details="auto"
                  placeholder="Décrivez ce que vous attendez de l'entreprise pour régler le litige..."
                  rows="4"
                  variant="outlined"
                />
              </div>
            </v-card-text>
          </v-card>

          <!-- Section 4: Validation -->
          <v-card class="mb-4" elevation="0">
            <v-card-title class="pa-4 bg-primary text-white">
              <v-icon class="me-2">mdi-check-circle</v-icon>
              4. Validation et Traçabilité
            </v-card-title>
            <v-card-text class="pa-6">
              <v-alert class="mb-4" type="info" variant="tonal">
                <div class="d-flex align-center">
                  <v-icon class="me-3">mdi-information</v-icon>
                  <div>
                    <strong>Date de la réclamation :</strong> {{ currentDate }}<br>
                    <strong>Référence :</strong> Sera générée automatiquement après validation
                  </div>
                </div>
              </v-alert>

              <v-checkbox
                v-model="form.signature_accepted"
                color="primary"
                hide-details="auto"
                :rules="[rules.required]"
              >
                <template #label>
                  <span class="text-body-2">
                    Je certifie que les informations fournies sont exactes et je valide
                    cette réclamation. <span class="text-error">*</span>
                  </span>
                </template>
              </v-checkbox>
            </v-card-text>
          </v-card>

          <!-- Actions -->
          <v-card elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex gap-3">
                <v-btn
                  color="primary"
                  :disabled="submitting"
                  :loading="submitting"
                  prepend-icon="mdi-send"
                  size="large"
                  type="submit"
                >
                  Envoyer la réclamation
                </v-btn>
                <v-btn
                  :disabled="submitting"
                  size="large"
                  variant="outlined"
                  @click="goBack"
                >
                  Annuler
                </v-btn>
              </div>
            </v-card-text>
          </v-card>
        </v-form>
      </v-col>

      <!-- Info sidebar -->
      <v-col cols="12" md="4">
        <v-card
          class="mb-4"
          color="info"
          elevation="0"
          sticky
          variant="tonal"
        >
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon class="me-2" color="info" size="24">
                mdi-information-outline
              </v-icon>
              <h3 class="text-subtitle-1 font-weight-bold">
                Informations
              </h3>
            </div>
            <ul class="text-body-2 ps-4">
              <li class="mb-2">Tous les champs marqués d'un * sont obligatoires</li>
              <li class="mb-2">Nous traitons les réclamations sous 48h</li>
              <li class="mb-2">Vous recevrez une notification lors de la réponse</li>
              <li class="mb-2">Vous pouvez modifier votre réclamation avant qu'elle soit traitée</li>
              <li>Une référence unique sera générée automatiquement</li>
            </ul>
          </v-card-text>
        </v-card>

        <v-card color="warning" elevation="0" variant="tonal">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-3">
              <v-icon class="me-2" color="warning" size="24">
                mdi-lightbulb-outline
              </v-icon>
              <h3 class="text-subtitle-1 font-weight-bold">
                Conseils
              </h3>
            </div>
            <ul class="text-body-2 ps-4">
              <li class="mb-2">Soyez précis dans votre description</li>
              <li class="mb-2">Mentionnez les dates et heures si pertinent</li>
              <li class="mb-2">Indiquez toute référence de commande ou document</li>
              <li>Proposez des solutions si vous en avez</li>
            </ul>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Success dialog -->
    <v-dialog v-model="successDialog" max-width="500" persistent>
      <v-card>
        <v-card-text class="pa-8 text-center">
          <v-icon class="mb-4" color="primary" size="64">
            mdi-check-circle-outline
          </v-icon>
          <h2 class="text-h5 font-weight-bold mb-3">
            Réclamation envoyée avec succès !
          </h2>
          <p class="text-body-1 mb-4">
            Votre réclamation a été enregistrée avec la référence :
          </p>
          <v-chip class="mb-4" color="primary" size="large">
            {{ createdComplaint?.reference }}
          </v-chip>
          <p class="text-body-2 text-medium-emphasis mb-2">
            <strong>Date :</strong> {{ currentDate }}
          </p>
          <p class="text-body-2 text-medium-emphasis">
            Nous allons traiter votre demande dans les plus brefs délais.
            Vous recevrez une notification dès qu'une réponse sera disponible.
          </p>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-btn
            block
            color="primary"
            size="large"
            variant="tonal"
            @click="goToComplaint"
          >
            Voir ma réclamation
          </v-btn>
        </v-card-actions>
        <v-card-actions class="pa-4 pt-0">
          <v-btn
            block
            variant="text"
            @click="goToDashboard"
          >
            Retour au tableau de bord
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import type { Complaint } from '@/services/complaintService'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import { complaintService } from '@/services/complaintService'
  import { useAuthStore } from '@/stores/auth'
  import ClientBLayout from '../components/ClientBLayout.vue'

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  // Get user data from auth store
  const currentUser = computed(() => authStore.user)

  // Form
  const formRef = ref()
  const form = ref({
    // Section 1: Identification (auto-rempli depuis authStore)
    full_name: '',
    address: '',
    phone: '',
    email: '',

    // Section 2: Détails
    site_id: null as number | null,
    title: '',
    description: '',
    category: '',
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',

    // Section 3: Préférences
    wants_email_response: true,
    expected_solution: '',

    // Section 4: Validation
    signature_accepted: false,
  })

  // Initialize form with user data
  function initializeForm () {
    const user = currentUser.value
    if (user) {
      form.value.full_name = user.name || user.username || ''
      form.value.email = user.email || ''
      form.value.phone = user.phone || ''
      form.value.address = 'N/A'
    }
  }

  // State
  const submitting = ref(false)
  const sitesLoading = ref(false)
  const categoriesLoading = ref(false)
  const successDialog = ref(false)
  const createdComplaint = ref<Complaint | null>(null)

  // Data
  const sitesOptions = ref<any[]>([])
  const allSites = ref<any[]>([])
  const selectedSiteLabel = ref('')
  const siteSearch = ref('')
  const categories = ref<string[]>([])
  const errors = ref<Record<string, string>>({})

  // Current date
  const currentDate = computed(() => {
    const date = new Date()
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  })

  // Validation rules
  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (min: number) => (v: string) =>
      (v && v.length >= min) || `Minimum ${min} caractères requis`,
  }

  // Load all sites on mount
  async function loadSites () {
    try {
      console.log('🔍 Chargement des sites...')
      sitesLoading.value = true
      // Utiliser /sites/search au lieu de /sites car Client B n'a pas accès à /sites
      const { data } = await api.get('/sites/search', {
        params: { q: '', per_page: 100 },
      })
      console.log('📦 Réponse API /sites/search:', data)
      const sites = data.data || data || []
      console.log('🏢 Sites trouvés:', sites.length, sites)
      const mapped = sites.map((site: any) => ({
        id: site.id,
        label: `${site.name} - ${site.city || site.location || ''}`,
        ...site,
      }))
      allSites.value = mapped
      sitesOptions.value = mapped
      console.log('✅ Sites options:', sitesOptions.value)
    } catch (error) {
      console.error('❌ Error loading sites:', error)
      toast.error('Erreur lors du chargement des sites')
    } finally {
      sitesLoading.value = false
    }
  }

  // Search sites with debounce
  let searchTimeout: ReturnType<typeof setTimeout>
  function searchSites (query: string | null) {
    clearTimeout(searchTimeout)

    if (!query || query.length < 2) {
      sitesOptions.value = allSites.value
      sitesLoading.value = false
      return
    }

    searchTimeout = setTimeout(async () => {
      try {
        sitesLoading.value = true
        const { data } = await api.get('/sites/search', {
          params: { q: query },
        })
        const sites = data.data || []
        sitesOptions.value = sites.map((site: any) => ({
          id: site.id,
          label: `${site.name} - ${site.city || site.location || ''}`,
          ...site,
        }))
      } catch (error) {
        console.error('Error searching sites:', error)
      } finally {
        sitesLoading.value = false
      }
    }, 300)
  }

  // Load categories
  async function loadCategories () {
    try {
      categoriesLoading.value = true
      // const data = await complaintService.getCategories()
      // categories.value = data
      categories.value = [
        'Service client',
        'Livraison',
        'Qualité produit',
        'Facturation',
        'Autre',
      ]
    } catch (error) {
      console.error('Error loading categories:', error)
      categories.value = [
        'Service client',
        'Livraison',
        'Qualité produit',
        'Facturation',
        'Autre',
      ]
    } finally {
      categoriesLoading.value = false
    }
  }

  // Submit form
  async function handleSubmit () {
    errors.value = {}

    // Validate form
    const { valid } = await formRef.value.validate()
    if (!valid) {
      toast.error('Veuillez remplir tous les champs obligatoires')
      return
    }

    try {
      submitting.value = true

      const complaintData = {
        // Map form data to API format
        title: form.value.title,
        description: form.value.description,
        site_id: form.value.site_id!,
        category: form.value.category || undefined,
        priority: form.value.priority,

        // Additional fields
        customer_name: form.value.full_name,
        customer_address: form.value.address || undefined,
        customer_phone: form.value.phone,
        customer_email: form.value.email,
        wants_email_response: form.value.wants_email_response,
        expected_solution: form.value.expected_solution || undefined,
      }

      const complaint = await complaintService.createComplaint(complaintData)
      createdComplaint.value = complaint
      successDialog.value = true
    } catch (error: any) {
      console.error('Error creating complaint:', error)

      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      } else {
        toast.error(error.response?.data?.message || 'Erreur lors de la création de la réclamation')
      }
    } finally {
      submitting.value = false
    }
  }

  // Navigation
  function goBack () {
    router.push('/clientb/complaints')
  }

  function goToComplaint () {
    if (createdComplaint.value) {
      router.push(`/clientb/complaints/${createdComplaint.value.id}`)
    }
  }

  function goToDashboard () {
    router.push('/clientb/dashboard')
  }

  // Initialize
  onMounted(() => {
    loadCategories()
    loadSites()
    initializeForm()
  })

  function siteLabel (site: any) {
    return site?.label || site?.name || site?.title || `Site #${site?.id ?? ''}`
  }

  const computedSitesOptions = computed(() => {
    const selectedId = form.value.site_id
    if (!selectedId) return sitesOptions.value
    const existing = sitesOptions.value.find(item => Number(item.id) === Number(selectedId))
    if (existing) return sitesOptions.value
    if (selectedSiteLabel.value) {
      return [{ id: selectedId, label: selectedSiteLabel.value }, ...sitesOptions.value]
    }
    return sitesOptions.value
  })

  watch(() => form.value.site_id, siteId => {
    if (!siteId) return
    const found = sitesOptions.value.find(item => Number(item.id) === Number(siteId))
    if (found) {
      selectedSiteLabel.value = found.label || found.name || found.title || selectedSiteLabel.value
    }
  })

  watch(siteSearch, query => {
    searchSites(query)
  })
</script>

<style scoped>
.gap-3 {
  gap: 12px;
}
</style>
