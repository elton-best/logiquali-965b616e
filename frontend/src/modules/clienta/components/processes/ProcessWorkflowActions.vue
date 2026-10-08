<template>
  <v-card variant="outlined">
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-arrow-decision</v-icon>
      Actions de Workflow
    </v-card-title>
    <v-divider />
    <v-card-text>
      <div class="d-flex flex-column gap-3">
        <!-- Verify Action -->
        <v-btn
          v-if="canVerify"
          block
          color="orange"
          prepend-icon="mdi-check"
          size="large"
          @click="showVerifyDialog = true"
        >
          Vérifier et passer en révision
        </v-btn>

        <!-- Validate Action -->
        <v-btn
          v-if="canValidate"
          block
          color="success"
          prepend-icon="mdi-check-circle"
          size="large"
          @click="showValidateDialog = true"
        >
          Valider et activer
        </v-btn>

        <!-- Reject Action -->
        <v-btn
          v-if="canReject"
          block
          color="error"
          prepend-icon="mdi-close-circle"
          size="large"
          variant="outlined"
          @click="showRejectDialog = true"
        >
          Rejeter
        </v-btn>

        <!-- Edit Action -->
        <v-btn
          v-if="canEdit"
          block
          color="primary"
          prepend-icon="mdi-pencil"
          size="large"
          variant="outlined"
          @click="emit('edit')"
        >
          Modifier le processus
        </v-btn>

        <!-- Info Messages -->
        <v-alert
          v-if="!canVerify && !canValidate && !canReject && !canEdit"
          density="compact"
          text="Aucune action disponible pour ce processus"
          type="info"
          variant="tonal"
        />

        <v-alert
          v-if="process.status === 'draft'"
          density="compact"
          text="Le processus doit être vérifié avant d'être validé"
          type="info"
          variant="tonal"
        />

        <v-alert
          v-if="process.status === 'in_review'"
          density="compact"
          text="Le processus est en révision et attend validation"
          type="warning"
          variant="tonal"
        />

        <v-alert
          v-if="process.status === 'active'"
          density="compact"
          icon="mdi-check-circle"
          text="Ce processus est actif et opérationnel"
          type="success"
          variant="tonal"
        />
      </div>
    </v-card-text>
  </v-card>

  <!-- Verify Dialog -->
  <v-dialog v-model="showVerifyDialog" max-width="500">
    <v-card>
      <v-card-title>Vérifier le processus</v-card-title>
      <v-divider />
      <v-card-text>
        <p class="mb-4">
          Êtes-vous sûr de vouloir vérifier ce processus et le passer en révision ?
        </p>
        <v-textarea
          v-model="verifyComment"
          label="Commentaire (optionnel)"
          placeholder="Ajoutez un commentaire sur la vérification..."
          rows="3"
          variant="outlined"
        />
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn @click="showVerifyDialog = false">Annuler</v-btn>
        <v-btn color="orange" @click="handleVerify">Vérifier</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Validate Dialog -->
  <v-dialog v-model="showValidateDialog" max-width="500">
    <v-card>
      <v-card-title>Valider le processus</v-card-title>
      <v-divider />
      <v-card-text>
        <p class="mb-4">
          Êtes-vous sûr de vouloir valider ce processus et l'activer ?
        </p>
        <v-textarea
          v-model="validateComment"
          label="Commentaire (optionnel)"
          placeholder="Ajoutez un commentaire sur la validation..."
          rows="3"
          variant="outlined"
        />
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn @click="showValidateDialog = false">Annuler</v-btn>
        <v-btn color="success" @click="handleValidate">Valider</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Reject Dialog -->
  <v-dialog v-model="showRejectDialog" max-width="500">
    <v-card>
      <v-card-title>Rejeter le processus</v-card-title>
      <v-divider />
      <v-card-text>
        <p class="mb-4">
          Le processus sera retourné en brouillon. Veuillez indiquer la raison du rejet.
        </p>
        <v-textarea
          v-model="rejectReason"
          :error-messages="rejectError"
          label="Raison du rejet *"
          placeholder="Expliquez pourquoi le processus est rejeté..."
          rows="4"
          variant="outlined"
        />
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn @click="showRejectDialog = false">Annuler</v-btn>
        <v-btn color="error" @click="handleReject">Rejeter</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import processWorkflowService from '@/services/processWorkflowService'

  interface Props {
    process: any
    currentUser: any
  }

  const props = defineProps<Props>()

  const emit = defineEmits<{
    (e: 'verify' | 'validate', comment?: string): void
    (e: 'reject', reason: string): void
    (e: 'edit'): void
  }>()

  const showVerifyDialog = ref(false)
  const showValidateDialog = ref(false)
  const showRejectDialog = ref(false)
  const verifyComment = ref('')
  const validateComment = ref('')
  const rejectReason = ref('')
  const rejectError = ref<string[]>([])

  const canVerify = computed(() =>
    processWorkflowService.canVerify(props.process, props.currentUser),
  )

  const canValidate = computed(() =>
    processWorkflowService.canValidate(props.process, props.currentUser),
  )

  const canReject = computed(() =>
    processWorkflowService.canReject(props.process, props.currentUser),
  )

  const canEdit = computed(() =>
    processWorkflowService.canEdit(props.process, props.currentUser),
  )

  function handleVerify () {
    emit('verify', verifyComment.value || undefined)
    showVerifyDialog.value = false
    verifyComment.value = ''
  }

  function handleValidate () {
    emit('validate', validateComment.value || undefined)
    showValidateDialog.value = false
    validateComment.value = ''
  }

  function handleReject () {
    if (!rejectReason.value.trim()) {
      rejectError.value = ['La raison du rejet est obligatoire']
      return
    }

    rejectError.value = []
    emit('reject', rejectReason.value)
    showRejectDialog.value = false
    rejectReason.value = ''
  }
</script>
