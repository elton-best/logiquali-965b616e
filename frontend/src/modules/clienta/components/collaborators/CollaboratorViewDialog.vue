<template>
  <v-dialog
    max-width="800"
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex align-center bg-primary">
        <v-icon class="mr-2">mdi-account-details</v-icon>
        <span>Détails du collaborateur</span>
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="$emit('update:modelValue', false)"
        />
      </v-card-title>

      <v-card-text class="pa-6">
        <v-row v-if="user">
          <v-col class="text-center mb-4" cols="12">
            <v-avatar color="primary" size="80">
              <span class="text-h4">{{ getInitials(user.name || user.username) }}</span>
            </v-avatar>
            <div class="text-h5 mt-3">{{ user.name || user.username }}</div>
            <div class="text-caption text-medium-emphasis">{{ user.email }}</div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-caption text-medium-emphasis">Poste</div>
            <div class="text-body-1 font-weight-medium">
              {{ user.role || 'Non défini' }}
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-caption text-medium-emphasis">Site</div>
            <div class="text-body-1 font-weight-medium">
              <v-chip v-if="user.site" color="primary" size="small" variant="tonal">
                <v-icon size="small" start>mdi-map-marker</v-icon>
                {{ user.site.name }}
              </v-chip>
              <span v-else>Non assigné</span>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-caption text-medium-emphasis">Téléphone</div>
            <div class="text-body-1">{{ user.phone || 'Non renseigné' }}</div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-caption text-medium-emphasis">Statut</div>
            <div class="text-body-1">
              <StatusChip :active="user.is_active" />
            </div>
          </v-col>

          <v-col cols="12">
            <div class="text-caption text-medium-emphasis mb-2">
              Permissions ({{ user.permissions?.length || 0 }})
            </div>
            <v-chip-group column>
              <v-chip
                v-for="permission in user.permissions"
                :key="permission.name"
                color="info"
                size="small"
                variant="tonal"
              >
                {{ permission.name }}
              </v-chip>
            </v-chip-group>
            <div v-if="!user.permissions || user.permissions.length === 0" class="text-caption">
              Aucune permission assignée
            </div>
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn
          color="primary"
          variant="text"
          @click="$emit('update:modelValue', false)"
        >
          Fermer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'

  interface User {
    id: number
    name?: string
    username?: string
    email: string
    role?: string
    phone?: string
    is_active: boolean
    site?: {
      id: number
      name: string
    }
    permissions?: Array<{ name: string }>
  }

  interface Props {
    modelValue: boolean
    user: User | null
  }

  defineProps<Props>()

  defineEmits<{
    'update:modelValue': [value: boolean]
  }>()

  function getInitials (name: string | undefined | null): string {
    if (!name) return '??'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
  }
</script>
