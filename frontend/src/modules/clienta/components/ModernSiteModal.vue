<template>
  <v-dialog
    :fullscreen="$vuetify.display.mobile"
    max-width="900"
    :model-value="modelValue"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card class="modern-site-modal">
      <!-- Header avec gradient -->
      <v-card-title class="pa-0">
        <div class="modal-header">
          <div class="d-flex align-center gap-3">
            <v-avatar color="white" size="48">
              <v-icon color="primary" size="32">mdi-office-building-plus</v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ editMode ? 'Modifier le site' : 'Nouveau Site' }}</div>
              <div class="text-caption">{{ editMode ? 'Modifiez les informations du site' : 'Créez un nouveau site pour votre organisation' }}</div>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="close" />
        </div>
      </v-card-title>

      <v-divider />

      <!-- Progress indicator -->
      <v-progress-linear
        color="primary"
        height="4"
        :model-value="(step / 3) * 100"
      />

      <!-- Content -->
      <v-card-text class="pa-6" style="max-height: 60vh; overflow-y: auto;">
        <v-window v-model="step" class="elevation-0">
          <!-- Step 1: Informations du site -->
          <v-window-item :value="1">
            <div class="step-content">
              <div class="step-header mb-6">
                <v-icon color="primary" size="40">mdi-information</v-icon>
                <h3 class="text-h6 mt-2">Informations du Site</h3>
                <p class="text-caption text-medium-emphasis">Renseignez les informations de base</p>
              </div>

              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="form.name"
                    density="comfortable"
                    label="Nom du site *"
                    placeholder="Ex: Siège Social Paris"
                    prepend-inner-icon="mdi-office-building"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.code"
                    density="comfortable"
                    label="Code (optionnel)"
                    placeholder="Ex: SITE-001"
                    prepend-inner-icon="mdi-barcode"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.phone"
                    density="comfortable"
                    label="Téléphone"
                    placeholder="+33 1 23 45 67 89"
                    prepend-inner-icon="mdi-phone"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-text-field
                    v-model="form.address"
                    density="comfortable"
                    label="Adresse *"
                    placeholder="123 Rue de la République"
                    prepend-inner-icon="mdi-map-marker"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="8">
                  <v-text-field
                    v-model="form.city"
                    density="comfortable"
                    label="Ville *"
                    placeholder="Paris"
                    prepend-inner-icon="mdi-city"
                    :rules="[rules.required]"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="form.postal_code"
                    density="comfortable"
                    label="Code postal"
                    placeholder="75001"
                    prepend-inner-icon="mdi-mailbox"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-text-field
                    v-model="form.email"
                    density="comfortable"
                    label="Email"
                    placeholder="contact@site.com"
                    prepend-inner-icon="mdi-email"
                    type="email"
                    variant="outlined"
                  />
                </v-col>

                <v-col cols="12">
                  <v-switch
                    v-model="form.is_headquarter"
                    color="primary"
                    hide-details
                    label="Siège social"
                  />
                </v-col>

                <v-col cols="12">
                  <v-switch
                    v-model="form.is_active"
                    color="success"
                    hide-details
                    label="Site actif"
                  />
                </v-col>
              </v-row>
            </div>
          </v-window-item>

          <!-- Step 2: Gérant -->
          <v-window-item :value="2">
            <div class="step-content">
              <div class="step-header mb-6">
                <v-icon color="primary" size="40">mdi-account-tie</v-icon>
                <h3 class="text-h6 mt-2">Gérant du Site</h3>
                <p class="text-caption text-medium-emphasis">Choisissez ou créez un gérant</p>
              </div>

              <v-radio-group v-model="managerType" class="mb-4">
                <v-radio label="Sélectionner un collaborateur existant" value="existing" />
                <v-radio label="Créer un nouveau gérant" value="new" />
              </v-radio-group>

              <div v-if="managerType === 'existing'">
                <v-autocomplete
                  v-model="form.manager_id"
                  density="comfortable"
                  item-title="full_name"
                  item-value="id"
                  :items="collaborators"
                  label="Sélectionner un gérant *"
                  :loading="loadingCollaborators"
                  prepend-inner-icon="mdi-account-search"
                  :rules="[rules.required]"
                  variant="outlined"
                >
                  <template #item="{ props: optionProps, item }">
                    <v-list-item v-bind="optionProps">
                      <template #prepend>
                        <v-avatar color="primary">
                          <span class="text-caption">{{ getInitials(item.raw.full_name) }}</span>
                        </v-avatar>
                      </template>
                      <v-list-item-title>{{ item.raw.full_name }}</v-list-item-title>
                      <v-list-item-subtitle>{{ item.raw.email }}</v-list-item-subtitle>
                    </v-list-item>
                  </template>
                </v-autocomplete>
              </div>

              <div v-else>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.new_manager.first_name"
                      density="comfortable"
                      label="Prénom *"
                      prepend-inner-icon="mdi-account"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.new_manager.last_name"
                      density="comfortable"
                      label="Nom *"
                      prepend-inner-icon="mdi-account"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.new_manager.email"
                      density="comfortable"
                      label="Email *"
                      prepend-inner-icon="mdi-email"
                      :rules="[rules.required, rules.email]"
                      type="email"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.new_manager.phone"
                      density="comfortable"
                      label="Téléphone"
                      prepend-inner-icon="mdi-phone"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.new_manager.position"
                      density="comfortable"
                      label="Poste *"
                      prepend-inner-icon="mdi-briefcase"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
              </div>
            </div>
          </v-window-item>

          <!-- Step 3: Récapitulatif -->
          <v-window-item :value="3">
            <div class="step-content">
              <div class="step-header mb-6">
                <v-icon color="success" size="40">mdi-check-circle</v-icon>
                <h3 class="text-h6 mt-2">Récapitulatif</h3>
                <p class="text-caption text-medium-emphasis">Vérifiez les informations avant de valider</p>
              </div>

              <v-card class="mb-4" variant="outlined">
                <v-card-title class="bg-primary text-white">
                  <v-icon start>mdi-office-building</v-icon>
                  Informations du site
                </v-card-title>
                <v-card-text class="pa-4">
                  <v-row dense>
                    <v-col cols="6"><strong>Nom:</strong></v-col>
                    <v-col cols="6">{{ form.name }}</v-col>
                    <v-col cols="6"><strong>Adresse:</strong></v-col>
                    <v-col cols="6">{{ form.address }}, {{ form.postal_code }} {{ form.city }}</v-col>
                    <v-col cols="6"><strong>Type:</strong></v-col>
                    <v-col cols="6">
                      <v-chip :color="form.is_headquarter ? 'primary' : 'default'" size="small">
                        {{ form.is_headquarter ? 'Siège social' : 'Site secondaire' }}
                      </v-chip>
                    </v-col>
                    <v-col cols="6"><strong>Statut:</strong></v-col>
                    <v-col cols="6">
                      <v-chip :color="form.is_active ? 'success' : 'error'" size="small">
                        {{ form.is_active ? 'Actif' : 'Inactif' }}
                      </v-chip>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <v-card variant="outlined">
                <v-card-title class="bg-success text-white">
                  <v-icon start>mdi-account-tie</v-icon>
                  Gérant
                </v-card-title>
                <v-card-text class="pa-4">
                  <div v-if="managerType === 'existing' && selectedManager">
                    <div class="d-flex align-center gap-3">
                      <v-avatar color="primary" size="48">
                        <span>{{ getInitials(selectedManager.full_name) }}</span>
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold">{{ selectedManager.full_name }}</div>
                        <div class="text-caption">{{ selectedManager.email }}</div>
                      </div>
                    </div>
                  </div>
                  <div v-else>
                    <div class="font-weight-bold">{{ form.new_manager.first_name }} {{ form.new_manager.last_name }}</div>
                    <div class="text-caption">{{ form.new_manager.email }}</div>
                    <div class="text-caption">{{ form.new_manager.position }}</div>
                  </div>
                </v-card-text>
              </v-card>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-divider />

      <!-- Actions -->
      <v-card-actions class="pa-4">
        <v-btn
          v-if="step > 1"
          prepend-icon="mdi-chevron-left"
          variant="outlined"
          @click="step--"
        >
          Précédent
        </v-btn>

        <v-spacer />

        <v-btn variant="outlined" @click="close">
          Annuler
        </v-btn>

        <v-btn
          v-if="step < 3"
          append-icon="mdi-chevron-right"
          color="primary"
          :disabled="!canProceed"
          @click="step++"
        >
          Suivant
        </v-btn>

        <v-btn
          v-else
          color="success"
          :loading="loading"
          prepend-icon="mdi-check"
          @click="submit"
        >
          {{ editMode ? 'Modifier' : 'Créer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/modules/shared/composables/useToast'

  const props = defineProps<{
    modelValue: boolean
    editMode?: boolean
    siteData?: any
    collaborators?: any[]
    loadingCollaborators?: boolean
  }>()

  const emit = defineEmits(['update:modelValue', 'success'])

  const toast = useToast()
  const step = ref(1)
  const loading = ref(false)
  const managerType = ref<'existing' | 'new'>('existing')

  const form = ref({
    name: '',
    code: '',
    address: '',
    city: '',
    postal_code: '',
    phone: '',
    email: '',
    is_headquarter: false,
    is_active: true,
    manager_id: null,
    new_manager: {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      position: '',
    },
  })

  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    email: (v: string) => !v || /.+@.+\..+/.test(v) || 'Email invalide',
  }

  const selectedManager = computed(() => {
    if (!form.value.manager_id || !props.collaborators) return null
    return props.collaborators.find(c => c.id === form.value.manager_id)
  })

  const canProceed = computed(() => {
    if (step.value === 1) {
      return !!(form.value.name && form.value.address && form.value.city)
    }
    if (step.value === 2) {
      if (managerType.value === 'existing') {
        return !!form.value.manager_id
      } else {
        const m = form.value.new_manager
        return !!(m.first_name && m.last_name && m.email && m.position)
      }
    }
    return true
  })

  function getInitials (name: string): string {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) {
      return ((parts[0]?.[0] || '') + (parts.at(-1)?.[0] || '')).toUpperCase()
    }
    return name.slice(0, 2).toUpperCase()
  }

  function close () {
    emit('update:modelValue', false)
    step.value = 1
  }

  async function submit () {
    loading.value = true
    try {
      const payload: any = {
        name: form.value.name,
        location: `${form.value.address}, ${form.value.postal_code} ${form.value.city}`,
        is_headquarter: form.value.is_headquarter,
        is_active: form.value.is_active,
      }

      if (managerType.value === 'existing' && form.value.manager_id) {
        payload.manager_id = form.value.manager_id
      } else if (managerType.value === 'new') {
        payload.new_manager = form.value.new_manager
      }

      const response = await api.post('/sites', payload)
      toast.success('Site créé avec succès')
      emit('success', response.data)
      close()
    } catch (error: any) {
      console.error('Erreur création site:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la création du site')
    } finally {
      loading.value = false
    }
  }

  watch(() => props.modelValue, val => {
    if (val && props.siteData) {
      // Load edit data
      form.value = { ...form.value, ...props.siteData }
    }
  })
</script>

<style scoped>
.modern-site-modal {
  border-radius: 16px !important;
}

.modal-header {
  background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
  color: white;
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.step-content {
  animation: fadeIn 0.3s ease-in-out;
}

.step-header {
  text-align: center;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
