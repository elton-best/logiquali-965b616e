<template>
  <div class="document-signature-section">
    <!-- Signature Section -->
    <v-divider class="my-6" />

    <div class="pa-6">
      <!-- Checkbox -->
      <v-checkbox
        v-model="hasRead"
        class="mb-4"
        color="primary"
        hide-details
      >
        <template #label>
          <span class="text-body-1 font-weight-medium">
            ☑ J'ai lu et j'approuve ce document
          </span>
        </template>
      </v-checkbox>

      <!-- Signature Button -->
      <v-btn
        color="primary"
        :disabled="!hasRead || hasSigned"
        :loading="loading"
        prepend-icon="mdi-draw"
        size="large"
        @click="openSignatureModal"
      >
        {{ hasSigned ? 'Document déjà signé' : 'Apposer ma signature' }}
      </v-btn>

      <!-- Existing Signatures -->
      <div v-if="signatures.length > 0" class="mt-6">
        <h3 class="text-h6 mb-3">Signatures ({{ signatures.length }})</h3>
        <v-list>
          <v-list-item
            v-for="sig in signatures"
            :key="sig.id"
            class="signature-item"
          >
            <template #prepend>
              <v-avatar color="primary" size="40">
                <v-icon>mdi-check-circle</v-icon>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-medium">
              {{ sig.user.name }}
            </v-list-item-title>
            <v-list-item-subtitle>
              Signé le {{ formatDate(sig.signed_at) }}
            </v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </div>
    </div>

    <!-- Signature Modal -->
    <v-dialog v-model="showModal" max-width="500" persistent>
      <v-card>
        <v-card-title class="d-flex align-center bg-primary text-white">
          <v-icon class="mr-2">mdi-draw</v-icon>
          Confirmation de signature
        </v-card-title>

        <v-card-text class="pt-6">
          <v-alert class="mb-4" type="info" variant="tonal">
            Vous vous apprêtez à signer électroniquement ce document.
            Cette action est irréversible.
          </v-alert>

          <!-- Signature Preview -->
          <div class="signature-preview pa-4 bg-grey-lighten-4 rounded mb-4">
            <p class="text-body-2 text-medium-emphasis mb-2">Aperçu :</p>
            <p class="text-h6 font-italic">
              "Lu et approuvé par {{ userName }}"
            </p>
            <p class="text-caption text-medium-emphasis">
              Le {{ new Date().toLocaleString('fr-FR') }}
            </p>
          </div>

          <!-- Password Verification -->
          <v-text-field
            v-model="password"
            :error-messages="passwordError"
            label="Mot de passe"
            placeholder="Confirmez votre identité"
            prepend-inner-icon="mdi-lock"
            type="password"
            variant="outlined"
            @keyup.enter="confirmSignature"
          />
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="closeModal">
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :disabled="!password"
            :loading="signing"
            @click="confirmSignature"
          >
            Confirmer la signature
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import { useAuthStore } from '@/stores/auth'

  interface Props {
    documentType: string
    documentId: number
    documentContent?: string
  }

  const props = defineProps<Props>()

  const authStore = useAuthStore()
  const hasRead = ref(false)
  const showModal = ref(false)
  const password = ref('')
  const passwordError = ref('')
  const loading = ref(false)
  const signing = ref(false)
  const signatures = ref<any[]>([])
  const hasSigned = ref(false)

  const userName = computed(() => authStore.user?.name || '')

  function openSignatureModal () {
    showModal.value = true
    password.value = ''
    passwordError.value = ''
  }

  function closeModal () {
    showModal.value = false
    password.value = ''
    passwordError.value = ''
  }

  async function confirmSignature () {
    if (!password.value) {
      passwordError.value = 'Mot de passe requis'
      return
    }

    try {
      signing.value = true
      passwordError.value = ''

      await api.post('/document-signatures/sign', {
        document_type: props.documentType,
        document_id: props.documentId,
        password: password.value,
        document_content: props.documentContent,
      })

      // Recharger signatures
      await loadSignatures()
      await checkIfSigned()

      closeModal()
      hasRead.value = false
    } catch (error: any) {
      passwordError.value = error.response?.data?.error || 'Erreur lors de la signature'
    } finally {
      signing.value = false
    }
  }

  async function loadSignatures () {
    try {
      loading.value = true
      const response = await api.get('/document-signatures', {
        params: {
          document_type: props.documentType,
          document_id: props.documentId,
        },
      })
      signatures.value = response.data.signatures
    } catch (error) {
      console.error('Erreur chargement signatures:', error)
    } finally {
      loading.value = false
    }
  }

  async function checkIfSigned () {
    try {
      const response = await api.get('/document-signatures/check-signed', {
        params: {
          document_type: props.documentType,
          document_id: props.documentId,
        },
      })
      hasSigned.value = response.data.has_signed
    } catch (error) {
      console.error('Erreur vérification signature:', error)
    }
  }

  function formatDate (dateString: string) {
    return new Date(dateString).toLocaleString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  onMounted(() => {
    loadSignatures()
    checkIfSigned()
  })
</script>

<style scoped>
.signature-preview {
  border-left: 4px solid rgb(var(--v-theme-primary));
}

.signature-item {
  border-left: 3px solid rgb(var(--v-theme-primary));
  margin-bottom: 8px;
}
</style>
