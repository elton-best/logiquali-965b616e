<template>
  <v-card>
    <v-card-title class="d-flex justify-space-between align-center">
      <span>Certifications</span>
      <v-btn color="primary" @click="openDialog()">
        <v-icon start>mdi-plus</v-icon>
        Ajouter
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="certifications"
        :loading="loading"
      >
        <template #item.status="{ item }">
          <v-chip :color="getStatusColor(item.status)" size="small">
            {{ getStatusLabel(item.status) }}
          </v-chip>
        </template>

        <template #item.expiry_date="{ item }">
          {{ formatDate(item.expiry_date) }}
        </template>

        <template #item.actions="{ item }">
          <v-btn icon size="small" @click="openDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn color="error" icon size="small" @click="deleteCertification(item.id)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <v-dialog v-model="dialog" max-width="600">
      <v-card>
        <v-card-title>{{ editMode ? 'Modifier' : 'Ajouter' }} une certification</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-select
              v-model="form.certification_id"
              :disabled="editMode"
              item-title="name"
              item-value="id"
              :items="catalog"
              label="Certification"
            />
            <v-text-field v-model="form.certification_number" label="Numéro de certification" />
            <v-text-field v-model="form.issue_date" label="Date d'émission" type="date" />
            <v-text-field v-model="form.expiry_date" label="Date d'expiration" type="date" />
            <v-text-field v-model="form.issuing_body" label="Organisme émetteur" />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialog = false">Annuler</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveCertification">
            Enregistrer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
  import type { Certification, EnterpriseCertification } from '@/types/enterprise-config'
  import { onMounted, reactive, ref } from 'vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { certificationService } from '@/services/certificationService'

  interface Props {
    enterpriseId: number
  }

  const props = defineProps<Props>()

  const { showSuccess, showError } = useSnackbar()
  const loading = ref(false)
  const saving = ref(false)
  const dialog = ref(false)
  const editMode = ref(false)
  const formRef = ref()

  const certifications = ref<EnterpriseCertification[]>([])
  const catalog = ref<Certification[]>([])

  const form = reactive({
    id: 0,
    certification_id: 0,
    certification_number: '',
    issue_date: '',
    expiry_date: '',
    issuing_body: '',
  })

  const headers = [
    { title: 'Certification', key: 'certification.name' },
    { title: 'Numéro', key: 'certification_number' },
    { title: 'Expiration', key: 'expiry_date' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      active: 'success',
      expiring_soon: 'warning',
      expired: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      active: 'Active',
      expiring_soon: 'Expire bientôt',
      expired: 'Expirée',
    }
    return labels[status] || status
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  async function loadData () {
    loading.value = true
    try {
      const [certs, cat] = await Promise.all([
        certificationService.getEnterpriseCertifications(props.enterpriseId),
        certificationService.getCatalog(),
      ])
      certifications.value = certs
      catalog.value = cat
    } catch {
      showError('Erreur lors du chargement')
    } finally {
      loading.value = false
    }
  }

  function openDialog (item?: EnterpriseCertification) {
    if (item) {
      editMode.value = true
      Object.assign(form, item)
    } else {
      editMode.value = false
      Object.assign(form, {
        id: 0,
        certification_id: 0,
        certification_number: '',
        issue_date: '',
        expiry_date: '',
        issuing_body: '',
      })
    }
    dialog.value = true
  }

  async function saveCertification () {
    saving.value = true
    try {
      await (editMode.value ? certificationService.updateCertification(props.enterpriseId, form.id, form) : certificationService.addCertification(props.enterpriseId, form))
      showSuccess('Certification enregistrée')
      dialog.value = false
      await loadData()
    } catch {
      showError('Erreur lors de l\'enregistrement')
    } finally {
      saving.value = false
    }
  }

  async function deleteCertification (id: number) {
    if (!confirm('Supprimer cette certification ?')) return
    try {
      await certificationService.deleteCertification(props.enterpriseId, id)
      showSuccess('Certification supprimée')
      await loadData()
    } catch {
      showError('Erreur lors de la suppression')
    }
  }

  onMounted(() => {
    loadData()
  })
</script>
