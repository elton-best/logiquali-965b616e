<template>
  <v-form ref="formRef" v-model="valid" @submit.prevent="emit('submit')">
    <v-card flat>
      <v-card-text>
        <h3 class="text-h6 mb-4">Conditions générales d'utilisation</h3>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Veuillez lire et accepter les conditions d'utilisation
        </p>

        <v-card class="mb-6" max-height="400px" style="overflow-y: auto" variant="outlined">
          <v-card-text class="text-body-2">
            <h4 class="text-subtitle-1 font-weight-bold mb-3">Conditions Générales d'Utilisation (CGU)</h4>

            <h5 class="font-weight-bold mt-4 mb-2">1. Objet</h5>
            <p class="mb-3">
              Les présentes conditions générales d'utilisation (CGU) ont pour objet de définir les modalités et
              conditions d'utilisation de la plateforme QHSE BestQHSE, ainsi que les droits et obligations des
              parties dans ce cadre.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">2. Acceptation des conditions</h5>
            <p class="mb-3">
              L'accès et l'utilisation de la plateforme sont subordonnés à l'acceptation et au respect des
              présentes CGU. En vous inscrivant, vous reconnaissez avoir pris connaissance de ces conditions
              et vous vous engagez à les respecter.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">3. Inscription et validation KYC</h5>
            <p class="mb-3">
              Pour les entreprises (Client A), l'inscription nécessite une procédure de vérification KYC
              (Know Your Customer). Vous vous engagez à fournir des informations exactes et à jour. Toute
              fausse déclaration peut entraîner le rejet de votre demande ou la suspension de votre compte.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">4. Protection des données</h5>
            <p class="mb-3">
              Les données personnelles collectées sont traitées conformément au RGPD et à la loi Informatique
              et Libertés. Elles sont utilisées uniquement dans le cadre de la gestion de votre compte et de
              nos services. Vous disposez d'un droit d'accès, de rectification et de suppression de vos données.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">5. Confidentialité</h5>
            <p class="mb-3">
              Vos identifiants de connexion sont personnels et confidentiels. Vous êtes responsable de leur
              conservation et de toute utilisation qui en serait faite. En cas de perte ou de vol, vous devez
              immédiatement nous en informer.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">6. Propriété intellectuelle</h5>
            <p class="mb-3">
              L'ensemble des contenus présents sur la plateforme (textes, images, logos, etc.) sont protégés
              par le droit de la propriété intellectuelle. Toute reproduction ou utilisation non autorisée
              est interdite.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">7. Résiliation</h5>
            <p class="mb-3">
              Nous nous réservons le droit de suspendre ou de résilier votre accès à la plateforme en cas de
              non-respect des présentes CGU, sans préavis ni indemnité.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">8. Modification des CGU</h5>
            <p class="mb-3">
              Nous nous réservons le droit de modifier les présentes CGU à tout moment. Les modifications
              entreront en vigueur dès leur publication sur la plateforme. Il vous appartient de consulter
              régulièrement les CGU.
            </p>

            <h5 class="font-weight-bold mt-4 mb-2">9. Droit applicable</h5>
            <p>
              Les présentes CGU sont régies par le droit béninois. En cas de litige, les parties s'engagent
              à rechercher une solution amiable avant toute action judiciaire.
            </p>
          </v-card-text>
        </v-card>

        <v-checkbox
          v-model="acceptedTerms"
          color="primary"
          required
          :rules="[rules.required]"
        >
          <template #label>
            <div class="text-body-2">
              J'ai lu et j'accepte les
              <a class="text-primary" href="#" @click.prevent>Conditions Générales d'Utilisation</a>
              et la
              <a class="text-primary" href="#" @click.prevent>Politique de Confidentialité</a>
            </div>
          </template>
        </v-checkbox>

        <v-divider class="my-6" />

        <div>
          <h4 class="text-subtitle-1 font-weight-bold mb-3">Signature électronique (optionnel)</h4>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Vous pouvez signer électroniquement en saisissant votre nom complet ci-dessous
          </p>

          <v-text-field
            v-model="signature"
            hint="Saisissez votre nom complet comme signature"
            label="Signature électronique"
            persistent-hint
            placeholder="Prénom Nom"
            prepend-inner-icon="mdi-draw"
            variant="outlined"
          />
        </div>

        <v-alert
          class="mt-6"
          color="info"
          icon="mdi-information"
          variant="tonal"
        >
          <div class="text-body-2">
            <strong>Prochaines étapes :</strong>
            <ol class="mt-2 pl-4">
              <li>Soumission de votre dossier KYC</li>
              <li>Validation par notre équipe (24-48h)</li>
              <li>Réception d'un email de confirmation</li>
              <li>Activation de votre compte entreprise</li>
            </ol>
          </div>
        </v-alert>
      </v-card-text>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import type { Terms } from '@/types/kyc'
  import { computed, ref } from 'vue'

  const props = defineProps<{
    modelValue: Terms
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: Terms]
    'submit': []
  }>()

  const acceptedTerms = computed({
    get: () => props.modelValue.acceptedTerms,
    set: value => emit('update:modelValue', { ...props.modelValue, acceptedTerms: value }),
  })

  const signature = computed({
    get: () => props.modelValue.signature,
    set: value => emit('update:modelValue', { ...props.modelValue, signature: value }),
  })

  const formRef = ref()
  const valid = ref(false)

  const rules = {
    required: (value: boolean) => value === true || 'Vous devez accepter les conditions d\'utilisation',
  }

  defineExpose({
    validate: () => formRef.value?.validate(),
    isValid: () => valid.value,
  })
</script>
