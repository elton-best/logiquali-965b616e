<template>
  <div>
    <!-- Informations personnelles -->
    <div class="mb-6">
      <h3 class="text-h6 mb-4 d-flex align-center">
        <v-icon class="mr-2" color="primary">mdi-account</v-icon>
        Informations personnelles
      </h3>
      <v-row>
        <v-col cols="12" md="6">
          <v-text-field
            density="comfortable"
            label="Prénom *"
            :model-value="modelValue.first_name"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            variant="outlined"
            @update:model-value="updateField('first_name', $event)"
          />
        </v-col>

        <v-col cols="12" md="6">
          <v-text-field
            density="comfortable"
            label="Nom *"
            :model-value="modelValue.last_name"
            prepend-inner-icon="mdi-account"
            :rules="[rules.required]"
            variant="outlined"
            @update:model-value="updateField('last_name', $event)"
          />
        </v-col>

        <v-col cols="12" md="6">
          <v-text-field
            density="comfortable"
            label="Email *"
            :model-value="modelValue.email"
            prepend-inner-icon="mdi-email"
            :rules="[rules.required, rules.email]"
            type="email"
            variant="outlined"
            @update:model-value="updateField('email', $event)"
          />
        </v-col>

        <v-col cols="12" md="6">
          <v-text-field
            density="comfortable"
            label="Téléphone (optionnel)"
            :model-value="modelValue.phone"
            prepend-inner-icon="mdi-phone"
            variant="outlined"
            @update:model-value="updateField('phone', $event)"
          />
        </v-col>

        <v-col cols="12" md="6">
          <v-text-field
            density="comfortable"
            label="Poste *"
            :model-value="modelValue.role"
            placeholder="Ex: Responsable Qualité, Auditeur Interne..."
            prepend-inner-icon="mdi-briefcase"
            :rules="[rules.required]"
            variant="outlined"
            @update:model-value="updateField('role', $event)"
          />
        </v-col>
      </v-row>

      <v-alert v-if="!editMode" class="mt-4" type="info" variant="tonal">
        <v-icon class="mr-2">mdi-information</v-icon>
        Un mot de passe sera généré automatiquement et envoyé par email au collaborateur.
      </v-alert>
    </div>

    <!-- Affectation Site -->
    <div class="mb-6">
      <h3 class="text-h6 mb-4 d-flex align-center">
        <v-icon class="mr-2" color="primary">mdi-map-marker</v-icon>
        Affectation Site
      </h3>
      <v-row>
        <v-col cols="12" md="6">
          <v-select
            density="comfortable"
            item-title="name"
            item-value="id"
            :items="sites"
            label="Site *"
            :model-value="modelValue.site_id"
            prepend-inner-icon="mdi-map-marker"
            :rules="[rules.required]"
            variant="outlined"
            @update:model-value="updateField('site_id', $event); $emit('site-change')"
          />
        </v-col>

        <v-col cols="12" md="6">
          <v-select
            density="comfortable"
            :items="userTypeOptions"
            label="Type d'utilisateur *"
            :model-value="modelValue.user_type"
            prepend-inner-icon="mdi-account-cog"
            :rules="[rules.required]"
            variant="outlined"
            @update:model-value="updateField('user_type', $event)"
          />
        </v-col>

        <v-col cols="12">
          <v-switch
            color="success"
            label="Compte actif"
            :model-value="modelValue.is_active"
            @update:model-value="updateField('is_active', $event)"
          />
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script setup lang="ts">
  interface FormData {
    first_name: string
    last_name: string
    email: string
    phone: string
    role: string
    site_id: number | null
    user_type: string
    is_active: boolean
  }

  interface Site {
    id: number
    name: string
  }

  interface UserTypeOption {
    title: string
    value: string
  }

  interface Props {
    modelValue: FormData
    sites: Site[]
    userTypeOptions: UserTypeOption[]
    editMode: boolean
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    'update:modelValue': [value: FormData]
    'site-change': []
  }>()

  const rules = {
    required: (v: any) => !!v || 'Champ requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
  }

  function updateField (field: keyof FormData, value: any) {
    emit('update:modelValue', { ...props.modelValue, [field]: value } as FormData)
  }
</script>
