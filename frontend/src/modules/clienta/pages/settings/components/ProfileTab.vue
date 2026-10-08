<template>
  <div>
    <h3 class="text-h6 mb-6">Informations personnelles</h3>

    <v-card class="mb-6" variant="outlined">
      <v-card-text>
        <div class="d-flex align-center gap-4">
          <v-avatar color="primary" size="80">
            <v-img v-if="photoPreviewUrl" cover :src="photoPreviewUrl" />
            <v-icon v-else size="40">mdi-account</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-subtitle-1 font-weight-medium">
              Photo de profil
            </div>
            <div class="text-caption text-medium-emphasis mb-2">
              JPG, PNG ou GIF. Max 2MB.
            </div>
            <input
              ref="photoInput"
              accept="image/png,image/jpeg,image/jpg,image/gif"
              class="d-none"
              type="file"
              @change="emit('photo-selected', $event)"
            >
            <v-btn
              v-if="canUpdateSettings"
              :loading="photoUploading"
              prepend-icon="mdi-upload"
              size="small"
              variant="outlined"
              @click="photoInput?.click()"
            >
              Télécharger une photo
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-card class="mb-6" variant="outlined">
      <v-card-text>
        <div class="d-flex align-center gap-4">
          <v-avatar color="grey-lighten-3" rounded="lg" size="80">
            <v-img v-if="signaturePreviewUrl" :src="signaturePreviewUrl" />
            <v-icon v-else color="grey-darken-1" size="40">mdi-draw</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-subtitle-1 font-weight-medium">
              Signature collaborateur
            </div>
            <div class="text-caption text-medium-emphasis mb-2">
              PNG, JPG, WEBP. Max 2MB. Obligatoire pour finaliser la première
              connexion.
            </div>
            <input
              ref="signatureInput"
              accept="image/png,image/jpeg,image/jpg"
              class="d-none"
              type="file"
              @change="emit('signature-selected', $event)"
            >
            <v-btn
              v-if="canUpdateSettings"
              :loading="signatureUploading"
              prepend-icon="mdi-upload"
              size="small"
              variant="outlined"
              @click="signatureInput?.click()"
            >
              Importer la signature
            </v-btn>
          </div>
        </div>
      </v-card-text>
    </v-card>

    <v-card class="mb-6" variant="outlined">
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.firstName"
              density="comfortable"
              label="Prénom"
              prepend-inner-icon="mdi-account"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.lastName"
              density="comfortable"
              label="Nom"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.email"
              density="comfortable"
              label="Email"
              prepend-inner-icon="mdi-email"
              :readonly="!canUpdateSettings"
              type="email"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.phone"
              density="comfortable"
              label="Téléphone"
              prepend-inner-icon="mdi-phone"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.position"
              density="comfortable"
              label="Poste"
              prepend-inner-icon="mdi-briefcase"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-select
              v-model="profileForm.language"
              density="comfortable"
              :items="languages"
              label="Langue"
              prepend-inner-icon="mdi-translate"
              :readonly="!canUpdateSettings"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card v-if="canUpdateSettings" class="mb-6" variant="outlined">
      <v-card-text>
        <h4 class="text-subtitle-1 font-weight-medium mb-4">
          Changer le mot de passe
        </h4>
        <v-row>
          <v-col cols="12">
            <v-text-field
              v-model="profileForm.currentPassword"
              :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
              density="comfortable"
              label="Mot de passe actuel"
              prepend-inner-icon="mdi-lock"
              :type="showCurrentPassword ? 'text' : 'password'"
              variant="outlined"
              @click:append-inner="showCurrentPassword = !showCurrentPassword"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.newPassword"
              :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
              density="comfortable"
              label="Nouveau mot de passe"
              prepend-inner-icon="mdi-lock-outline"
              :type="showNewPassword ? 'text' : 'password'"
              variant="outlined"
              @click:append-inner="showNewPassword = !showNewPassword"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="profileForm.confirmPassword"
              :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
              density="comfortable"
              label="Confirmer le mot de passe"
              prepend-inner-icon="mdi-lock-check"
              :type="showConfirmPassword ? 'text' : 'password'"
              variant="outlined"
              @click:append-inner="showConfirmPassword = !showConfirmPassword"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <div class="d-flex justify-end">
      <v-btn
        v-if="canUpdateSettings"
        color="primary"
        :loading="profileSaving"
        prepend-icon="mdi-content-save"
        size="large"
        @click="emit('save')"
      >
        Enregistrer les modifications
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { ref } from 'vue'

  defineProps({
    canUpdateSettings: {
      type: Boolean,
      required: true,
    },
    profileForm: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    languages: {
      type: Array as PropType<string[]>,
      required: true,
    },
    signaturePreviewUrl: {
      type: String,
      required: true,
    },
    photoPreviewUrl: {
      type: String,
      required: true,
    },
    photoUploading: {
      type: Boolean,
      required: true,
    },
    signatureUploading: {
      type: Boolean,
      required: true,
    },
    profileSaving: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'save'): void
    (event: 'signature-selected', fileEvent: Event): void
    (event: 'photo-selected', fileEvent: Event): void
  }>()

  const signatureInput = ref<HTMLInputElement | null>(null)
  const photoInput = ref<HTMLInputElement | null>(null)
  const showCurrentPassword = ref(false)
  const showNewPassword = ref(false)
  const showConfirmPassword = ref(false)
</script>
