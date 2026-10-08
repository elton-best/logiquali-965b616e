<template>
  <v-card flat>
    <v-card-text>
      <div class="d-flex flex-wrap align-center ga-3 mb-6">
        <v-btn
          :color="managerType === 'existing' ? 'primary' : 'default'"
          prepend-icon="mdi-account-search"
          :variant="managerType === 'existing' ? 'flat' : 'outlined'"
          @click="managerType = 'existing'"
        >
          Collaborateur existant
        </v-btn>
        <v-btn
          :color="managerType === 'new' ? 'primary' : 'default'"
          prepend-icon="mdi-account-plus"
          :variant="managerType === 'new' ? 'flat' : 'outlined'"
          @click="managerType = 'new'"
        >
          Nouveau collaborateur
        </v-btn>
      </div>

      <!-- Existing Manager -->
      <v-expand-transition>
        <div v-if="managerType === 'existing'">
          <v-autocomplete
            v-model="modelValue.manager_id"
            clearable
            density="comfortable"
            item-title="full_name"
            item-value="id"
            :items="collaborators"
            label="Sélectionner un responsable de site *"
            :loading="loading"
            prepend-inner-icon="mdi-account-search"
            :rules="managerType === 'existing' ? [required] : []"
            variant="outlined"
          >
            <template #item="{ props: itemProps, item }">
              <v-list-item
                v-bind="itemProps"
                :prepend-avatar="item.raw.avatar || undefined"
              >
                <template v-if="!item.raw.avatar" #prepend>
                  <v-avatar color="primary">
                    <span class="text-white">{{ getInitials(item.raw.full_name) }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title>{{ item.raw.full_name }}</v-list-item-title>
                <v-list-item-subtitle>{{ item.raw.email }} - {{ item.raw.position }}</v-list-item-subtitle>
              </v-list-item>
            </template>
          </v-autocomplete>

          <v-alert
            v-if="modelValue.manager_id"
            class="mt-4"
            density="compact"
            type="info"
            variant="tonal"
          >
            Le responsable de site sélectionné sera notifié par email
          </v-alert>
        </div>
      </v-expand-transition>

      <!-- New Manager Form -->
      <v-expand-transition>
        <div v-if="managerType === 'new'">
          <div class="mb-4">
            <h3 class="text-h6 mb-4 d-flex align-center">
              <v-icon class="mr-2" color="primary">mdi-account</v-icon>
              Informations personnelles
            </h3>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="modelValue.new_manager.first_name"
                  density="comfortable"
                  :error-messages="touched.first_name ? managerErrors.first_name : ''"
                  label="Prénom *"
                  prepend-inner-icon="mdi-account"
                  variant="outlined"
                  @blur="markTouched('first_name')"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="modelValue.new_manager.last_name"
                  density="comfortable"
                  :error-messages="touched.last_name ? managerErrors.last_name : ''"
                  label="Nom *"
                  prepend-inner-icon="mdi-account"
                  variant="outlined"
                  @blur="markTouched('last_name')"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="modelValue.new_manager.email"
                  density="comfortable"
                  :error-messages="touched.email ? managerErrors.email : ''"
                  label="Email *"
                  prepend-inner-icon="mdi-email"
                  type="email"
                  variant="outlined"
                  @blur="markTouched('email')"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="modelValue.new_manager.phone"
                  density="comfortable"
                  label="Téléphone (optionnel)"
                  prepend-inner-icon="mdi-phone"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="modelValue.new_manager.position"
                  density="comfortable"
                  :error-messages="touched.position ? managerErrors.position : ''"
                  label="Poste *"
                  prepend-inner-icon="mdi-briefcase"
                  variant="outlined"
                  @blur="markTouched('position')"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="modelValue.new_manager.start_date"
                  density="comfortable"
                  label="Date de prise de service"
                  prepend-inner-icon="mdi-calendar"
                  type="date"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="modelValue.new_manager.address"
                  auto-grow
                  density="comfortable"
                  label="Adresse"
                  prepend-inner-icon="mdi-map-marker"
                  rows="2"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </div>

          <v-alert
            class="mt-4"
            density="compact"
            type="success"
            variant="tonal"
          >
            Un compte sera créé et le responsable de site recevra un email d'invitation
          </v-alert>
        </div>
      </v-expand-transition>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, reactive, ref, watch } from 'vue'

  interface Collaborator {
    id: number
    full_name: string
    email: string
    position: string
    avatar?: string
  }

  interface NewManager {
    first_name: string
    last_name: string
    email: string
    phone?: string
    position: string
    address?: string
    start_date?: string
  }

  interface ManagerData {
    manager_id: number | null
    new_manager: NewManager
  }

  interface Props {
    modelValue: ManagerData
    collaborators: Collaborator[]
    managerType?: 'existing' | 'new'
    loading?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
    managerType: 'existing',
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: ManagerData): void
    (e: 'update:managerType', value: 'existing' | 'new'): void
  }>()

  const managerType = ref<'existing' | 'new'>(props.managerType)

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
  const touched = reactive({
    first_name: false,
    last_name: false,
    email: false,
    position: false,
  })

  const managerErrors = computed(() => {
    if (managerType.value !== 'new') {
      return {
        first_name: '',
        last_name: '',
        email: '',
        position: '',
      }
    }

    const firstName = String(modelValue.value.new_manager.first_name || '').trim()
    const lastName = String(modelValue.value.new_manager.last_name || '').trim()
    const email = String(modelValue.value.new_manager.email || '').trim()
    const position = String(modelValue.value.new_manager.position || '').trim()

    const errors = {
      first_name: firstName ? '' : 'Le prénom est requis.',
      last_name: lastName ? '' : 'Le nom est requis.',
      email: '',
      position: position ? '' : 'Le poste est requis.',
    }

    if (!email) {
      errors.email = 'L’email est requis.'
    } else if (!/.+@.+\..+/.test(email)) {
      errors.email = 'Format email invalide.'
    }

    return errors
  })

  watch(managerType, newType => {
    emit('update:managerType', newType)
    touched.first_name = false
    touched.last_name = false
    touched.email = false
    touched.position = false
    // Reset the other field when switching
    if (newType === 'existing') {
      modelValue.value.new_manager = {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        position: '',
        address: '',
        start_date: '',
      }
    } else {
      modelValue.value.manager_id = null
    }
  })

  watch(() => props.managerType, newType => {
    if (newType && newType !== managerType.value) {
      managerType.value = newType
    }
  })

  function markTouched (field: keyof typeof touched) {
    touched[field] = true
  }

  function getInitials (name: string) {
    return name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  const required = (v: string | number | null) => !!v || 'Ce champ est requis'
</script>

<style scoped>
</style>
