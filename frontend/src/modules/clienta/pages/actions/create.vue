/** * Create Action Page * Form to create a new corrective/preventive action */

<script setup lang="ts">
  import type {
    ActionCategory,
    ActionPriority,
    ActionType,
    CreateActionDTO,
    SourceType,
  } from '@/api/services/actions.service'
  import { ArrowLeft, CheckSquare, Save } from 'lucide-vue-next'
  import { onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AppDatePickerField from '@/components/common/AppDatePickerField.vue'
  import { useActions } from '@/modules/clienta/composables/useActions'
  import { useAudits } from '@/modules/clienta/composables/useAudits'
  import { useNonConformities } from '@/modules/clienta/composables/useNonConformities'
  import { useProcesses } from '@/modules/clienta/composables/useProcesses'
  import { useRisks } from '@/modules/clienta/composables/useRisks'
  import { useSites } from '@/modules/clienta/composables/useSites'
  import { useUsers } from '@/modules/clienta/composables/useUsers'

  const router = useRouter()
  const { createAction, loading, error } = useActions()
  const { sites, fetchSites } = useSites()
  const { users, fetchUsers } = useUsers()
  const { processes, fetchProcesses } = useProcesses()
  const { ncs, fetchNonConformities } = useNonConformities()
  const { audits, fetchAudits } = useAudits()
  const { risks, fetchRisks } = useRisks()

  const formData = ref<CreateActionDTO>({
    title: '',
    description: '',
    type: 'corrective',
    category: 'qualite',
    priority: 'medium',
    site_id: 0,
    process_id: 0,
    due_date: '',
    resources_needed: '',
    estimated_cost: undefined,
    effectiveness_criteria: '',
    source_type: undefined,
    source_id: undefined,
    responsible_id: undefined,
  })

  const actionTypes: { value: ActionType, label: string, description: string }[]
    = [
      {
        value: 'corrective',
        label: 'Corrective',
        description: 'Correction d\'une non-conformité existante',
      },
      {
        value: 'preventive',
        label: 'Préventive',
        description: 'Prévention d\'une non-conformité potentielle',
      },
    ]

  const categories: { value: ActionCategory, label: string }[] = [
    { value: 'qualite', label: 'Qualité' },
    { value: 'hygiene', label: 'Hygiène' },
    { value: 'securite', label: 'Sécurité' },
    { value: 'environnement', label: 'Environnement' },
  ]

  const priorities: {
    value: ActionPriority
    label: string
    description: string
  }[] = [
    {
      value: 'low',
      label: 'Basse',
      description: 'Pas d\'urgence, planification flexible',
    },
    { value: 'medium', label: 'Moyenne', description: 'Planification normale' },
    {
      value: 'high',
      label: 'Haute',
      description: 'Attention requise, échéance prioritaire',
    },
    {
      value: 'urgent',
      label: 'Urgente',
      description: 'Action immédiate nécessaire',
    },
  ]

  const sourceTypes: { value: SourceType, label: string }[] = [
    { value: 'nc', label: 'Non-Conformité' },
    { value: 'audit', label: 'Audit' },
    { value: 'risk', label: 'Risque' },
  ]

  const sourceItems = ref<any[]>([])

  async function loadSourceItems () {
    if (!formData.value.source_type) {
      sourceItems.value = []
      return
    }

    try {
      switch (formData.value.source_type) {
        case 'nc': {
          await fetchNonConformities({ per_page: 100 })
          sourceItems.value = ncs.value

          break
        }
        case 'audit': {
          await fetchAudits({ per_page: 100 })
          sourceItems.value = audits.value

          break
        }
        case 'risk': {
          await fetchRisks({ per_page: 100 })
          sourceItems.value = risks.value

          break
        }
      // No default
      }
    } catch (error_) {
      console.error('Failed to load source items:', error_)
    }
  }

  async function handleSubmit () {
    try {
      // Validate required fields
      if (!formData.value.title) {
        alert('Le titre est requis')
        return
      }
      if (!formData.value.description) {
        alert('La description est requise')
        return
      }
      if (!formData.value.site_id) {
        alert('Le site est requis')
        return
      }
      if (!formData.value.process_id) {
        alert('Le processus est requis')
        return
      }

      await createAction(formData.value)
      router.push('/company/actions')
    } catch (error_) {
      console.error('Failed to create action:', error_)
    }
  }

  function goBack () {
    router.push('/company/actions')
  }

  onMounted(() => {
    fetchSites({ per_page: 100 })
    fetchUsers({ per_page: 100 })
    fetchProcesses({ per_page: 200 })
  })
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <button
        class="inline-flex items-center gap-2 text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50 mb-4"
        @click="goBack"
      >
        <ArrowLeft class="w-4 h-4" />
        Retour à la liste
      </button>

      <h1
        class="text-2xl font-bold text-neutral-900 dark:text-neutral-50 flex items-center gap-2"
      >
        <CheckSquare class="w-7 h-7" />
        Nouvelle Action
      </h1>
      <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
        Créer une nouvelle action corrective ou préventive
      </p>
    </div>

    <!-- Error Message -->
    <div
      v-if="error"
      class="card p-4 mb-6 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20"
    >
      <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>

    <!-- Form -->
    <form class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Basic Information -->
      <div class="card p-6">
        <h2
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Informations générales
        </h2>

        <div class="space-y-4">
          <!-- Title -->
          <div>
            <label class="label">
              Titre <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.title"
              class="input w-full"
              placeholder="Ex: Révision des procédures de sécurité"
              required
              type="text"
            >
          </div>

          <!-- Description -->
          <div>
            <label class="label">
              Description <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="formData.description"
              class="input w-full"
              placeholder="Décrivez l'action en détail..."
              required
              rows="4"
            />
          </div>

          <!-- Type and Category -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Type -->
            <div>
              <label class="label">
                Type d'action <span class="text-red-500">*</span>
              </label>
              <select v-model="formData.type" class="input w-full" required>
                <option
                  v-for="type in actionTypes"
                  :key="type.value"
                  :value="type.value"
                >
                  {{ type.label }} - {{ type.description }}
                </option>
              </select>
            </div>

            <!-- Category -->
            <div>
              <label class="label">
                Catégorie QHSE <span class="text-red-500">*</span>
              </label>
              <select v-model="formData.category" class="input w-full" required>
                <option
                  v-for="cat in categories"
                  :key="cat.value"
                  :value="cat.value"
                >
                  {{ cat.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Priority -->
          <div>
            <label class="label">
              Priorité <span class="text-red-500">*</span>
            </label>
            <select v-model="formData.priority" class="input w-full" required>
              <option
                v-for="priority in priorities"
                :key="priority.value"
                :value="priority.value"
              >
                {{ priority.label }} - {{ priority.description }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Source Link -->
      <div class="card p-6">
        <h2
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Origine (optionnel)
        </h2>

        <div class="space-y-4">
          <!-- Source Type -->
          <div>
            <label class="label">Type de source</label>
            <select
              v-model="formData.source_type"
              class="input w-full"
              @change="loadSourceItems"
            >
              <option :value="undefined">Aucune source</option>
              <option
                v-for="source in sourceTypes"
                :key="source.value"
                :value="source.value"
              >
                {{ source.label }}
              </option>
            </select>
          </div>

          <!-- Source Item -->
          <div v-if="formData.source_type">
            <label class="label">Élément source</label>
            <select v-model="formData.source_id" class="input w-full">
              <option :value="undefined">Sélectionner...</option>
              <option
                v-for="item in sourceItems"
                :key="item.id"
                :value="item.id"
              >
                {{ item.reference }} - {{ item.title }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Assignment & Schedule -->
      <div class="card p-6">
        <h2
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Affectation et planning
        </h2>

        <div class="space-y-4">
          <!-- Site and Responsible -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Site -->
            <div>
              <label class="label">
                Site <span class="text-red-500">*</span>
              </label>
              <select
                v-model.number="formData.site_id"
                class="input w-full"
                required
              >
                <option :value="0">Sélectionner un site</option>
                <option v-for="site in sites" :key="site.id" :value="site.id">
                  {{ site.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="label">
                Processus <span class="text-red-500">*</span>
              </label>
              <select
                v-model.number="formData.process_id"
                class="input w-full"
                required
              >
                <option :value="0">Sélectionner un processus</option>
                <option
                  v-for="process in processes"
                  :key="process.id"
                  :value="process.id"
                >
                  {{
                    process.name || process.code || `Processus #${process.id}`
                  }}
                </option>
              </select>
            </div>

            <div>
              <label class="label">Responsable</label>
              <select
                v-model.number="formData.responsible_id"
                class="input w-full"
              >
                <option :value="undefined">Non assigné</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Due Date -->
          <div>
            <AppDatePickerField
              v-model="formData.due_date"
              label="Date d'échéance"
              mode="date"
            />
          </div>
        </div>
      </div>

      <!-- Resources & Costs -->
      <div class="card p-6">
        <h2
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Ressources et coûts
        </h2>

        <div class="space-y-4">
          <!-- Resources Needed -->
          <div>
            <label class="label">Ressources nécessaires</label>
            <textarea
              v-model="formData.resources_needed"
              class="input w-full"
              placeholder="Listez les ressources humaines, matérielles, financières..."
              rows="3"
            />
          </div>

          <!-- Estimated Cost -->
          <div>
            <label class="label">Coût estimé (€)</label>
            <input
              v-model.number="formData.estimated_cost"
              class="input w-full"
              min="0"
              placeholder="0.00"
              step="0.01"
              type="number"
            >
          </div>
        </div>
      </div>

      <!-- Effectiveness Criteria -->
      <div class="card p-6">
        <h2
          class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4"
        >
          Critères d'efficacité
        </h2>

        <div>
          <label class="label"> Critères d'évaluation de l'efficacité </label>
          <textarea
            v-model="formData.effectiveness_criteria"
            class="input w-full"
            placeholder="Définissez comment l'efficacité de cette action sera évaluée..."
            rows="3"
          />
          <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
            Ces critères seront utilisés pour vérifier l'efficacité de l'action
            une fois terminée
          </p>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end gap-4">
        <button
          class="btn-secondary"
          :disabled="loading"
          type="button"
          @click="goBack"
        >
          Annuler
        </button>
        <button
          class="btn-primary inline-flex items-center gap-2"
          :disabled="loading"
          type="submit"
        >
          <Save class="w-5 h-5" />
          {{ loading ? "Enregistrement..." : "Créer l'action" }}
        </button>
      </div>
    </form>
  </div>
</template>
