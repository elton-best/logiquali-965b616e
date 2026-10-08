<template>
  <v-dialog v-model="modelValue" max-width="760">
    <v-card v-if="selectedPersonDetails">
      <v-card-title class="dialog-header">
        <span>Détails collaborateur</span>
        <button class="btn-close" @click="emit('close')">
          <v-icon>mdi-close</v-icon>
        </button>
      </v-card-title>

      <v-card-text class="dialog-content">
        <div class="details-grid">
          <div class="details-item">
            <span class="details-label">Nom complet</span>
            <span class="details-value">{{ selectedPersonDetails.fullName }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Email</span>
            <span class="details-value">{{ selectedPersonDetails.email || "—" }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Téléphone</span>
            <span class="details-value">{{ selectedPersonDetails.telephone || "—" }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Poste</span>
            <span class="details-value">{{ selectedPersonDetails.poste || "—" }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Rôle d’accès</span>
            <span class="details-value">{{ selectedPersonDetails.role || "—" }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Site</span>
            <span class="details-value">{{ selectedPersonDetails.siteName || "—" }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Date de prise de service</span>
            <span class="details-value">{{ selectedPersonDetails.datePriseService || "—" }}</span>
          </div>
          <div class="details-item">
            <span class="details-label">Permissions actives</span>
            <span class="details-value">{{ selectedPersonDetails.activeScopedPermissionsCount || 0 }}</span>
          </div>
          <div class="details-item details-item-full">
            <span class="details-label">Adresse</span>
            <span class="details-value">{{ selectedPersonDetails.adresse || "—" }}</span>
          </div>
        </div>
      </v-card-text>

      <v-card-actions class="dialog-actions">
        <button class="btn-secondary" @click="emit('close')">
          Fermer
        </button>
        <button class="btn-primary" @click="emit('edit')">
          <v-icon size="20">mdi-pencil</v-icon>
          Modifier
        </button>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  export type PersonnelDetails = {
    fullName: string
    email: string
    telephone: string
    poste: string
    role?: string
    siteName?: string
    datePriseService: string
    activeScopedPermissionsCount: number
    adresse: string
  }

  const props = defineProps<{
    modelValue: boolean
    selectedPersonDetails: PersonnelDetails | null
  }>()

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'close'): void
    (event: 'edit'): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })
</script>

<style scoped>
.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 1.1rem;
  font-weight: 700;
}

.dialog-content {
  padding: 16px;
}

.btn-close {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.details-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.details-item-full {
  grid-column: 1 / -1;
}

.details-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.details-value {
  font-size: 0.85rem;
  color: #1e293b;
  font-weight: 600;
}

.dialog-actions {
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.85rem;
  border: none;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(91, 141, 217, 0.3);
}

.btn-primary:hover {
  box-shadow: 0 6px 20px rgba(91, 141, 217, 0.4);
  transform: translateY(-2px);
}

.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.85rem;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

@media (max-width: 768px) {
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>
