<script setup lang="ts">
  import type { IDType } from '@/types/models/kyc'
  import { computed, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import SignupCompanyHeader from '@/pages/auth/components/SignupCompanyHeader.vue'
  import SignupCompanyTerms from '@/pages/auth/components/SignupCompanyTerms.vue'
  import { useKYCStore } from '@/stores/kyc'

  const router = useRouter()
  const kycStore = useKYCStore()

  const currentStep = ref(1)
  const loading = ref(false)

  // Form data
  const companyData = ref({
    company_name: '',
    rccm_number: '',
    ifu_number: '',
    city: '',
    address: '',
    phone: '',
  })

  const adminData = ref({
    admin_name: '',
    admin_email: '',
    admin_phone: '',
    admin_id_type: 'CNI' as IDType,
    admin_id_number: '',
    password: '',
    password_confirmation: '',
  })

  const documentsData = ref({
    rccm_document: null as File | null,
    ifu_document: null as File | null,
    admin_id_document: null as File | null,
  })

  const acceptTerms = ref(false)
  const showPassword = ref(false)
  const showPasswordConfirm = ref(false)

  // Validation rules
  const rules = {
    required: (v: any) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
    minLength: (min: number) => (v: string) => (v && v.length >= min) || `Minimum ${min} caractères`,
    phone: (v: string) => /^[0-9]{8,}$/.test(v) || 'Téléphone invalide',
  }

  const passwordMatch = computed(() => {
    return adminData.value.password === adminData.value.password_confirmation || 'Les mots de passe ne correspondent pas'
  })

  async function handleSubmit () {
    if (!acceptTerms.value) {
      alert('Veuillez accepter les conditions')
      return
    }

    loading.value = true
    try {
      const payload = {
        ...companyData.value,
        ...adminData.value,
        accept_terms: acceptTerms.value,
      }

      await kycStore.submitKYC(payload as any)
      router.push('/kyc/pending')
    } catch (error: any) {
      console.error('KYC submission failed:', error)
      alert(error?.message || 'Erreur lors de l\'inscription')
    } finally {
      loading.value = false
    }
  }

  function goToLogin () {
    router.push('/auth/login')
  }
</script>

<template>
  <v-app>
    <v-main class="bg-grey-lighten-4">
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="12" lg="8" md="10">
            <SignupCompanyHeader />

            <v-card class="elevation-12" rounded="lg">
              <v-stepper v-model="currentStep" alt-labels>
                <v-stepper-header>
                  <v-stepper-item :complete="currentStep > 1" icon="mdi-office-building" title="Entreprise" :value="1" />
                  <v-divider />
                  <v-stepper-item :complete="currentStep > 2" icon="mdi-account" title="Administrateur" :value="2" />
                  <v-divider />
                  <v-stepper-item :complete="currentStep > 3" icon="mdi-file-document" title="Documents" :value="3" />
                  <v-divider />
                  <v-stepper-item icon="mdi-check-circle" title="Confirmation" :value="4" />
                </v-stepper-header>

                <v-stepper-window>
                  <!-- Step 1: Company Information -->
                  <v-stepper-window-item :value="1">
                    <v-card-text class="pa-6">
                      <h3 class="text-h6 mb-4">Informations de l'entreprise</h3>
                      <v-row>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="companyData.company_name"
                            label="Nom de l'entreprise *"
                            prepend-inner-icon="mdi-office-building"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="companyData.rccm_number"
                            label="Numéro RCCM *"
                            prepend-inner-icon="mdi-file-document"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="companyData.ifu_number"
                            label="Numéro IFU *"
                            prepend-inner-icon="mdi-numeric"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="companyData.city"
                            label="Ville *"
                            prepend-inner-icon="mdi-map-marker"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12">
                          <v-text-field
                            v-model="companyData.address"
                            label="Adresse complète"
                            prepend-inner-icon="mdi-home"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="companyData.phone"
                            label="Téléphone *"
                            prepend-inner-icon="mdi-phone"
                            :rules="[rules.phone]"
                            variant="outlined"
                          />
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-stepper-window-item>

                  <!-- Step 2: Admin Information -->
                  <v-stepper-window-item :value="2">
                    <v-card-text class="pa-6">
                      <h3 class="text-h6 mb-4">Informations de l'administrateur</h3>
                      <v-row>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="adminData.admin_name"
                            label="Nom complet *"
                            prepend-inner-icon="mdi-account"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="adminData.admin_email"
                            label="Email *"
                            prepend-inner-icon="mdi-email"
                            :rules="[rules.required, rules.email]"
                            type="email"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="adminData.admin_phone"
                            label="Téléphone"
                            prepend-inner-icon="mdi-phone"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-select
                            v-model="adminData.admin_id_type"
                            :items="['CNI', 'Passeport']"
                            label="Type de pièce d'identité *"
                            prepend-inner-icon="mdi-card-account-details"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="adminData.admin_id_number"
                            label="Numéro de pièce *"
                            prepend-inner-icon="mdi-numeric"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="adminData.password"
                            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                            hint="Minimum 8 caractères"
                            label="Mot de passe *"
                            prepend-inner-icon="mdi-lock"
                            :rules="[rules.required, rules.minLength(8)]"
                            :type="showPassword ? 'text' : 'password'"
                            variant="outlined"
                            @click:append-inner="showPassword = !showPassword"
                          />
                        </v-col>
                        <v-col cols="12" md="6">
                          <v-text-field
                            v-model="adminData.password_confirmation"
                            :append-inner-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                            label="Confirmer mot de passe *"
                            prepend-inner-icon="mdi-lock-check"
                            :rules="[rules.required, passwordMatch]"
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            variant="outlined"
                            @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
                          />
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-stepper-window-item>

                  <!-- Step 3: Documents -->
                  <v-stepper-window-item :value="3">
                    <v-card-text class="pa-6">
                      <h3 class="text-h6 mb-4">Documents requis</h3>
                      <v-alert class="mb-4" type="info" variant="tonal">
                        Veuillez télécharger les documents suivants (PDF, max 5MB)
                      </v-alert>
                      <v-row>
                        <v-col cols="12">
                          <v-file-input
                            v-model="documentsData.rccm_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            label="Document RCCM *"
                            prepend-icon="mdi-file-document"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12">
                          <v-file-input
                            v-model="documentsData.ifu_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            label="Document IFU *"
                            prepend-icon="mdi-file-document"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                        <v-col cols="12">
                          <v-file-input
                            v-model="documentsData.admin_id_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                            label="Pièce d'identité administrateur *"
                            prepend-icon="mdi-card-account-details"
                            :rules="[rules.required]"
                            variant="outlined"
                          />
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-stepper-window-item>

                  <!-- Step 4: Confirmation -->
                  <v-stepper-window-item :value="4">
                    <v-card-text class="pa-6">
                      <h3 class="text-h6 mb-4">Confirmation</h3>

                      <v-card class="mb-4" variant="outlined">
                        <v-card-text>
                          <h4 class="text-subtitle-1 font-weight-bold mb-3">Récapitulatif</h4>
                          <v-row dense>
                            <v-col cols="12" md="6">
                              <p class="text-caption text-medium-emphasis mb-1">Entreprise</p>
                              <p class="font-weight-medium">{{ companyData.company_name }}</p>
                            </v-col>
                            <v-col cols="12" md="6">
                              <p class="text-caption text-medium-emphasis mb-1">Administrateur</p>
                              <p class="font-weight-medium">{{ adminData.admin_name }}</p>
                            </v-col>
                            <v-col cols="12" md="6">
                              <p class="text-caption text-medium-emphasis mb-1">Email</p>
                              <p class="font-weight-medium">{{ adminData.admin_email }}</p>
                            </v-col>
                            <v-col cols="12" md="6">
                              <p class="text-caption text-medium-emphasis mb-1">Ville</p>
                              <p class="font-weight-medium">{{ companyData.city }}</p>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>

                      <SignupCompanyTerms v-model="acceptTerms" />
                    </v-card-text>
                  </v-stepper-window-item>
                </v-stepper-window>

                <v-card-actions class="pa-6">
                  <v-btn
                    v-if="currentStep > 1"
                    :disabled="loading"
                    variant="text"
                    @click="currentStep--"
                  >
                    Précédent
                  </v-btn>
                  <v-spacer />
                  <v-btn
                    v-if="currentStep < 4"
                    color="primary"
                    @click="currentStep++"
                  >
                    Suivant
                  </v-btn>
                  <v-btn
                    v-else
                    color="primary"
                    :disabled="!acceptTerms"
                    :loading="loading"
                    @click="handleSubmit"
                  >
                    S'inscrire
                  </v-btn>
                </v-card-actions>
              </v-stepper>

              <v-divider />

              <v-card-text class="text-center pa-4">
                <span class="text-body-2">Vous avez déjà un compte ?</span>
                <v-btn
                  class="ml-1"
                  color="primary"
                  size="small"
                  variant="text"
                  @click="goToLogin"
                >
                  Se connecter
                </v-btn>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>
