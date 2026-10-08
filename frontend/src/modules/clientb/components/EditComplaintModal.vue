<template>
  <v-dialog
    max-width="700"
    :model-value="modelValue"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="bg-primary text-white pa-4 d-flex align-center">
        <v-icon class="mr-2">mdi-pencil</v-icon>
        Modifier la réclamation
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-6">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="form.title"
                density="comfortable"
                label="Titre de la réclamation"
                prepend-inner-icon="mdi-text"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-select
                v-model="form.category"
                density="comfortable"
                item-title="label"
                item-value="value"
                :items="categories"
                label="Catégorie"
                prepend-inner-icon="mdi-tag"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.description"
                label="Description"
                placeholder="Décrivez votre réclamation en détail..."
                prepend-inner-icon="mdi-text-long"
                rows="5"
                :rules="[rules.required]"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12">
              <v-select
                v-model="form.priority"
                density="comfortable"
                :items="priorities"
                label="Priorité"
                prepend-inner-icon="mdi-flag"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn :disabled="loading" variant="text" @click="handleCancel">
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :loading="loading"
          prepend-icon="mdi-content-save"
          @click="handleSubmit"
        >
          Enregistrer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import { complaintService } from '@/services/complaintService'

  interface Complaint {
    [key: string]: any
    id: number
    ref: string
    title: string
    description: string
    category: string
    priority?: string
    status: string
  }

  interface Props {
    modelValue: boolean
    complaint: Complaint | null
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'updated'): void
  }

  const props = defineProps<Props>()
  const emit = defineEmits<Emits>()
  const toast = useToast()

  const formRef = ref()
  const loading = ref(false)

  const form = ref({
    title: '',
    description: '',
    category: '',
    priority: 'medium',
  })

  const categories = [
    { value: 'product_quality', label: 'Qualité du produit' },
    { value: 'service', label: 'Service' },
    { value: 'delivery', label: 'Livraison' },
    { value: 'billing', label: 'Facturation' },
    { value: 'other', label: 'Autre' },
  ]

  const priorities = [
    { value: 'low', title: 'Basse' },
    { value: 'medium', title: 'Moyenne' },
    { value: 'high', title: 'Haute' },
    { value: 'urgent', title: 'Urgente' },
  ]

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
  }

  // Watch for complaint changes and populate form
  watch(
    () => props.complaint,
    newComplaint => {
      if (newComplaint) {
        form.value = {
          title: newComplaint.title || '',
          description: newComplaint.description || '',
          category: newComplaint.category || '',
          priority: newComplaint.priority || 'medium',
        }
      }
    },
    { immediate: true },
  )

  async function handleSubmit () {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    if (!props.complaint) return

    loading.value = true
    try {
      await complaintService.updateComplaint(props.complaint.id, {
        title: form.value.title,
        description: form.value.description,
        category: form.value.category,
        priority: form.value.priority as 'low' | 'medium' | 'high' | 'urgent',
      })
      toast.success('Réclamation modifiée avec succès')
      emit('updated')
      emit('update:modelValue', false)
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur lors de la modification',
      )
    } finally {
      loading.value = false
    }
  }

  function handleCancel () {
    emit('update:modelValue', false)
  }
</script>
