<template>
  <v-dialog v-model="modelValue" max-width="900" persistent scrollable>
    <v-card>
      <v-card-title class="dialog-header">
        <span>{{ editMode ? "Modifier" : "Nouveau" }} collaborateur</span>
        <button class="btn-close" :disabled="loading" @click="emit('close')">
          <v-icon>mdi-close</v-icon>
        </button>
      </v-card-title>

      <v-card-text
        class="dialog-content"
        style="max-height: 60vh; overflow-y: auto"
      >
        <div v-if="loading" class="panel-loader-overlay">
          <UnifiedLoader
            description="Validation des informations du collaborateur"
            :show-skeleton="true"
            title="Enregistrement en cours..."
            variant="local"
          />
        </div>

        <TabsContainer v-model="localDialogTab" :tabs="dialogTabs" />

        <!-- Étape 1: Informations -->
        <div v-if="localDialogTab === 'info'" class="form-section">
          <div class="form-grid">
            <div class="form-field">
              <label class="field-label required">Nom</label>
              <input
                v-model="form.nom"
                class="field-input"
                placeholder="Nom de famille"
              >
            </div>

            <div class="form-field">
              <label class="field-label required">Prénoms</label>
              <input
                v-model="form.prenoms"
                class="field-input"
                placeholder="Prénoms"
              >
            </div>

            <div class="form-field">
              <label class="field-label required">Email</label>
              <input
                v-model="form.email"
                class="field-input"
                placeholder="email@exemple.com"
                type="email"
              >
              <p
                v-if="form.email.trim() && !isEmailFormatValid"
                class="field-error"
              >
                Format attendu: Nom_utilisateur@domaine.extension
              </p>
            </div>

            <div class="form-field">
              <label class="field-label">Téléphone</label>
              <input
                v-model="form.telephone"
                class="field-input"
                placeholder="+225 01 02 03 04 05"
              >
            </div>

            <div class="form-field">
              <label class="field-label required">Poste</label>
              <input
                v-model="form.poste"
                class="field-input"
                placeholder="Intitulé du poste"
              >
            </div>

            <div class="form-field">
              <label class="field-label required">Site d'affectation</label>
              <select v-model.number="form.site_id" class="field-input">
                <option :value="null">Sélectionner un site</option>
                <option
                  v-for="site in availableSiteOptions"
                  :key="site.id"
                  :value="site.id"
                >
                  {{ site.name }}
                </option>
              </select>
            </div>

            <div class="form-field">
              <label class="field-label">Date de prise de service</label>
              <div class="date-picker-wrapper">
                <v-menu :close-on-content-click="false">
                  <template #activator="{ props }">
                    <input
                      v-bind="props"
                      :value="formatDateDisplay(datePickerModel)"
                      class="field-input"
                      placeholder="jj/mm/aaaa"
                      readonly
                    >
                  </template>
                  <v-date-picker
                    v-model="datePickerModel"
                    locale="fr"
                    title="Sélectionner la date"
                    @update:model-value="handleDateChange"
                  />
                </v-menu>
              </div>
            </div>

            <div class="form-field full-width">
              <label class="field-label">Adresse</label>
              <textarea
                v-model="form.adresse"
                class="field-textarea"
                placeholder="Adresse complète"
                rows="2"
              />
            </div>
          </div>
        </div>

        <!-- Étape 2: Rôle -->
        <div v-if="localDialogTab === 'role'">
          <RoleSelector v-model="form.role" :roles="roles" />

          <div v-if="isCustomRole" class="form-section mt-4">
            <div class="form-field full-width">
              <label class="field-label required">Nom du rôle personnalisé</label>
              <input
                v-model.trim="form.custom_role_name"
                class="field-input"
                placeholder="Ex: Responsable QHSE terrain"
              >
              <p class="text-caption text-medium-emphasis mt-2 mb-0">
                Un seul rôle personnalisé est maintenu par entreprise. Le nom
                saisi met à jour ce rôle.
              </p>
            </div>
          </div>
        </div>

        <!-- Étape 3: Permissions -->
        <div v-if="localDialogTab === 'permissions'">
          <PermissionsPicker
            :active-scoped-permissions-count="activeScopedPermissionsCount"
            :direct-permissions-count="directPermissionsCount"
            :effective-permissions-count="effectivePermissionsCount"
            :format-permission-label="formatPermissionLabel"
            :has-role="Boolean(form.role)"
            :inherited-role-permissions="inheritedRolePermissions"
            :is-default-read-permission="isDefaultReadPermission"
            :is-inherited-permission="isInheritedPermission"
            :is-permission-checked="isPermissionChecked"
            :is-permission-locked="isPermissionLocked"
            :permission-groups="permissionGroups"
            :toggle-permission="togglePermission"
          />
        </div>
      </v-card-text>

      <v-card-actions class="dialog-actions">
        <button
          class="btn-secondary"
          :disabled="loading"
          @click="emit('close')"
        >
          Annuler
        </button>
        <button
          v-if="localDialogTab === 'info'"
          class="btn-primary"
          :disabled="loading || !isStepValid"
          @click="localDialogTab = 'role'"
        >
          Suivant
          <v-icon size="20">mdi-arrow-right</v-icon>
        </button>
        <button
          v-else-if="localDialogTab === 'role'"
          class="btn-primary"
          :disabled="loading || !isRoleStepValid"
          @click="localDialogTab = 'permissions'"
        >
          Suivant
          <v-icon size="20">mdi-arrow-right</v-icon>
        </button>
        <button
          v-else
          class="btn-primary"
          :disabled="loading"
          @click="emit('save')"
        >
          <v-icon size="20">mdi-check</v-icon>
          {{ editMode ? "Modifier" : "Créer un collaborateur" }}
        </button>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'
  import TabsContainer from '@/components/leadership/TabsContainer.vue'
  import PermissionsPicker from '@/modules/clienta/pages/leadership/components/PermissionsPicker.vue'
  import RoleSelector from '@/modules/clienta/pages/leadership/components/RoleSelector.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  type DialogTab = { value: string, label: string, icon: string }

  type RoleOption = {
    value: string
    label: string
    description: string
    icon: string
    color: string
  }

  type SiteOption = { id: number, name: string }

  type PermissionGroup = {
    id: string
    name: string
    icon: string
    color: string
    permissions: string[]
  }

  type PersonnelForm = {
    nom: string
    prenoms: string
    telephone: string
    email: string
    poste: string
    role: string
    site_id: number | null
    adresse: string
    datePriseService: string
    custom_role_name: string
    permissions: string[]
  }

  const props = defineProps<{
    modelValue: boolean
    dialogTab: string
    dialogTabs: DialogTab[]
    editMode: boolean
    loading: boolean
    form: PersonnelForm
    availableSiteOptions: SiteOption[]
    roles: RoleOption[]
    inheritedRolePermissions: string[]
    directPermissionsCount: number
    effectivePermissionsCount: number
    activeScopedPermissionsCount: number
    permissionGroups: PermissionGroup[]
    isStepValid: boolean
    isRoleStepValid: boolean
    isCustomRole: boolean
    formatPermissionLabel: (permission: string) => string
    isPermissionChecked: (permission: string) => boolean
    isPermissionLocked: (permission: string) => boolean
    isInheritedPermission: (permission: string) => boolean
    isDefaultReadPermission: (permission: string) => boolean
    togglePermission: (permission: string, checked: boolean) => void
  }>()

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'update:dialogTab', value: string): void
    (event: 'close'): void
    (event: 'save'): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => {
      emit('update:modelValue', value)
      if (!value) {
        emit('close')
      }
    },
  })

  const localDialogTab = computed({
    get: () => props.dialogTab,
    set: value => emit('update:dialogTab', value),
  })

  const isEmailFormatValid = computed(() => {
    const email = String(props.form.email || '').trim()
    if (!email) {
      return false
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)
  })

  // Date picker state and handlers
  const datePickerModel = ref<Date | null>(null)

  // Sync datePickerModel with form.datePriseService on mount
  watch(
    () => props.form.datePriseService,
    (newVal) => {
      if (newVal && typeof newVal === 'string') {
        const parsed = new Date(newVal)
        if (!isNaN(parsed.getTime())) {
          datePickerModel.value = parsed
        }
      } else if (!newVal) {
        datePickerModel.value = null
      }
    },
    { immediate: true },
  )

  function formatDateDisplay(date: Date | null): string {
    if (!date) {
      return ''
    }
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()
    return `${day}/${month}/${year}`
  }

  function handleDateChange(date: Date | null) {
    if (date) {
      // Update form with ISO string
      const isoString = date.toISOString().split('T')[0]
      props.form.datePriseService = isoString
    } else {
      props.form.datePriseService = ''
    }
  }
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

.btn-close {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
}

.dialog-content {
  padding: 16px;
}

.panel-loader-overlay {
  position: sticky;
  top: 0;
  z-index: 9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 0 14px;
  margin-bottom: 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(2px);
}

.form-section {
  margin-top: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-error {
  margin: 0;
  color: #dc2626;
  font-size: 0.75rem;
  font-weight: 600;
}

.form-field.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1e293b;
}

.field-label.required::after {
  content: "*";
  color: #ef4444;
  margin-left: 4px;
}

.field-input,
.field-textarea {
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.date-picker-wrapper .field-input {
  cursor: pointer;
}

.field-input:focus,
.field-textarea:focus {
  outline: none;
  border-color: #4471c4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.12);
}

.field-textarea {
  resize: vertical;
  font-family: inherit;
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

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
