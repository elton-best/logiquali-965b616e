<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white p-4">
    <div class="max-w-4xl mx-auto py-8">
      <!-- Header -->
      <div class="flex items-center gap-2 mb-6">
        <Shield class="w-8 h-8 text-blue-600" />
        <span class="font-bold text-xl">Best Experts-Group</span>
      </div>

      <!-- Progress -->
      <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</div>
            <span class="font-medium text-blue-600">Documents</span>
          </div>
          <div class="flex-1 h-0.5 bg-gray-300" />
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-semibold">2</div>
            <span class="font-medium text-gray-600">Validation</span>
          </div>
          <div class="flex-1 h-0.5 bg-gray-300" />
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-semibold">3</div>
            <span class="font-medium text-gray-600">Activation</span>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Documents légaux requis</h1>
        <p class="text-gray-600 mb-6">
          Pour valider votre compte entreprise <strong>{{ enterpriseName }}</strong>, veuillez téléverser les documents suivants.
        </p>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
          <h4 class="font-medium text-blue-900 mb-2 flex items-center gap-2">
            <AlertCircle class="w-5 h-5" />
            Important
          </h4>
          <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
            <li>Les documents doivent être clairs et lisibles</li>
            <li>Formats acceptés : PDF, JPG, PNG (max 10 Mo par fichier)</li>
            <li>La validation prend généralement 24 à 48 heures</li>
            <li>Vous serez notifié par email une fois la validation effectuée</li>
          </ul>
        </div>

        <!-- Document Upload List -->
        <div class="space-y-4 mb-8">
          <div
            v-for="doc in documents"
            :key="doc.id"
            class="border border-gray-200 rounded-lg p-6 hover:border-gray-300 transition-colors"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-start gap-3 flex-1">
                <component :is="getStatusIcon(doc.status)" />
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-gray-900">{{ doc.name }}</h3>
                    <span v-if="doc.required" class="text-red-500 text-sm">*</span>
                  </div>
                  <p class="text-sm text-gray-600">{{ doc.description }}</p>
                </div>
              </div>
              <component :is="getStatusBadge(doc.status)" />
            </div>

            <div v-if="doc.file" class="bg-gray-50 rounded-lg p-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <FileText class="w-5 h-5 text-gray-400" />
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ doc.file.name }}</p>
                  <p class="text-xs text-gray-500">
                    {{ (doc.file.size / 1024 / 1024).toFixed(2) }} Mo
                  </p>
                </div>
              </div>
              <button
                class="p-1 hover:bg-gray-200 rounded transition-colors"
                @click="handleRemoveFile(doc.id)"
              >
                <X class="w-5 h-5 text-gray-500" />
              </button>
            </div>
            <label v-else class="block">
              <input
                accept=".pdf,.jpg,.jpeg,.png"
                class="hidden"
                type="file"
                @change="(e) => handleFileSelect(doc.id, e)"
              >
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer">
                <Upload class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                <p class="text-sm font-medium text-gray-700">
                  Cliquez pour téléverser
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  PDF, JPG ou PNG (max 10 Mo)
                </p>
              </div>
            </label>
          </div>
        </div>

        <!-- Progress Summary -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Progression</span>
            <span class="text-sm font-semibold text-gray-900">
              {{ uploadedCount }} / {{ requiredCount }}
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${progressPercentage}%` }"
            />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
          <button
            class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!allRequiredUploaded"
            @click="handleSubmit"
          >
            Soumettre pour validation
          </button>
          <button
            class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium"
            @click="router.push('/auth/login')"
          >
            Plus tard
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, CheckCircle, FileText, Shield, Upload, X } from 'lucide-vue-next'
  import { computed, h, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuth } from '@/modules/shared/composables/useAuth'

  interface Document {
    id: string
    name: string
    description: string
    required: boolean
    file?: File
    status: 'not-uploaded' | 'uploaded' | 'validated' | 'rejected'
  }

  const router = useRouter()
  const { user } = useAuth()
  const enterpriseName = computed(() => user?.enterprise?.name || 'votre entreprise')

  const documents = ref<Document[]>([
    {
      id: 'rccm',
      name: 'RCCM (Registre du Commerce)',
      description: 'Document d\'immatriculation au registre du commerce',
      required: true,
      status: 'not-uploaded',
    },
    {
      id: 'ifu',
      name: 'IFU (Identifiant Fiscal Unique)',
      description: 'Numéro d\'identification fiscale de l\'entreprise',
      required: true,
      status: 'not-uploaded',
    },
    {
      id: 'cni-recto',
      name: 'CNI Administrateur (Recto)',
      description: 'Carte d\'identité nationale de l\'administrateur - face avant',
      required: true,
      status: 'not-uploaded',
    },
    {
      id: 'cni-verso',
      name: 'CNI Administrateur (Verso)',
      description: 'Carte d\'identité nationale de l\'administrateur - face arrière',
      required: true,
      status: 'not-uploaded',
    },
  ])

  function handleFileSelect (docId: string, event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
      documents.value = documents.value.map(doc =>
        doc.id === docId
          ? { ...doc, file, status: 'uploaded' as const }
          : doc,
      )
    }
  }

  function handleRemoveFile (docId: string) {
    documents.value = documents.value.map(doc =>
      doc.id === docId
        ? { ...doc, file: undefined, status: 'not-uploaded' as const }
        : doc,
    )
  }

  const allRequiredUploaded = computed(() =>
    documents.value
      .filter(doc => doc.required)
      .every(doc => doc.status === 'uploaded'),
  )

  const uploadedCount = computed(() =>
    documents.value.filter(d => d.status === 'uploaded').length,
  )

  const requiredCount = computed(() =>
    documents.value.filter(d => d.required).length,
  )

  const progressPercentage = computed(() =>
    (uploadedCount.value / requiredCount.value) * 100,
  )

  function handleSubmit () {
    if (allRequiredUploaded.value) {
      router.push('/kyc/validation-pending')
    }
  }

  function getStatusIcon (status: Document['status']) {
    switch (status) {
      case 'uploaded': {
        return h(CheckCircle, { class: 'w-5 h-5 text-green-600' })
      }
      case 'not-uploaded': {
        return h(AlertCircle, { class: 'w-5 h-5 text-gray-400' })
      }
      default: {
        return null
      }
    }
  }

  function getStatusBadge (status: Document['status']) {
    const config = {
      'not-uploaded': { label: 'Non fourni', style: 'bg-gray-100 text-gray-700' },
      'uploaded': { label: 'Téléversé', style: 'bg-green-100 text-green-700' },
      'validated': { label: 'Validé', style: 'bg-blue-100 text-blue-700' },
      'rejected': { label: 'Rejeté', style: 'bg-red-100 text-red-700' },
    }
    const { label, style } = config[status]
    return h('span', { class: `px-3 py-1 rounded-full text-xs font-medium ${style}` }, label)
  }
</script>
