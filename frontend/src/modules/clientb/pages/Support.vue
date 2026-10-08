<template>
  <ClientBLayout current-page="/clientb/support">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex align-center">
            <v-avatar class="mr-4" color="primary" size="56" variant="tonal">
              <v-icon size="32">mdi-help-circle</v-icon>
            </v-avatar>
            <div>
              <h1 class="text-h4 font-weight-bold mb-1">Aide & Support</h1>
              <p class="text-body-2 text-medium-emphasis">
                Besoin d'aide ? Nous sommes là pour vous !
              </p>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row>
        <!-- Contact Support -->
        <v-col cols="12" md="8">
          <!-- Quick Contact -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-email-fast</v-icon>
                <h2 class="text-h6 font-weight-bold">Contactez-nous</h2>
              </div>

              <v-form @submit.prevent="handleSubmitTicket">
                <v-row>
                  <v-col cols="12">
                    <v-select
                      v-model="ticket.type"
                      density="comfortable"
                      :items="ticketTypes"
                      label="Type de demande"
                      prepend-inner-icon="mdi-format-list-bulleted"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12">
                    <v-text-field
                      v-model="ticket.subject"
                      density="comfortable"
                      label="Sujet"
                      placeholder="Décrivez brièvement votre demande"
                      prepend-inner-icon="mdi-text-subject"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12">
                    <v-textarea
                      v-model="ticket.message"
                      label="Message"
                      placeholder="Décrivez votre demande en détail..."
                      prepend-inner-icon="mdi-message-text"
                      rows="5"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12">
                    <div class="d-flex justify-end">
                      <v-btn
                        color="primary"
                        :loading="submitting"
                        prepend-icon="mdi-send"
                        size="large"
                        type="submit"
                        variant="flat"
                      >
                        Envoyer la demande
                      </v-btn>
                    </div>
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
          </v-card>

          <!-- FAQ -->
          <v-card class="rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="32">mdi-frequently-asked-questions</v-icon>
                <h2 class="text-h6 font-weight-bold">Questions fréquentes</h2>
              </div>

              <v-expansion-panels variant="accordion">
                <v-expansion-panel
                  v-for="(faq, index) in faqs"
                  :key="index"
                  class="mb-2"
                  elevation="0"
                >
                  <v-expansion-panel-title class="font-weight-medium">
                    <template #default="{ expanded }">
                      <div class="d-flex align-center">
                        <v-icon class="mr-3" :color="expanded ? 'success' : ''">
                          {{ faq.icon }}
                        </v-icon>
                        {{ faq.question }}
                      </div>
                    </template>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <div class="pt-2">
                      {{ faq.answer }}
                    </div>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Sidebar -->
        <v-col cols="12" md="4">
          <!-- Contact Information -->
          <v-card class="mb-6 rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="28">mdi-information</v-icon>
                <h2 class="text-h6 font-weight-bold">Informations de contact</h2>
              </div>

              <v-list class="bg-transparent">
                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="primary" size="40" variant="tonal">
                      <v-icon size="20">mdi-email</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Email</v-list-item-title>
                  <v-list-item-subtitle>support@BestQHSE.com</v-list-item-subtitle>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="primary" size="40" variant="tonal">
                      <v-icon size="20">mdi-phone</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Téléphone</v-list-item-title>
                  <v-list-item-subtitle>+229 01 44 36 36 36</v-list-item-subtitle>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="info" size="40" variant="tonal">
                      <v-icon size="20">mdi-clock</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Heures d'ouverture</v-list-item-title>
                  <v-list-item-subtitle>24 heures/ 24 - 7jours/ 7</v-list-item-subtitle>
                </v-list-item>

                <v-divider class="my-2" />

                <v-list-item class="px-0">
                  <template #prepend>
                    <v-avatar color="warning" size="40" variant="tonal">
                      <v-icon size="20">mdi-map-marker</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">Adresse</v-list-item-title>
                  <v-list-item-subtitle>Cotonou, Bénin</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Support Resources -->
          <v-card class="rounded-lg" elevation="0">
            <v-card-text class="pa-6">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-3" color="primary" size="28">mdi-book-open-page-variant</v-icon>
                <h2 class="text-h6 font-weight-bold">Ressources</h2>
              </div>

              <v-list class="bg-transparent">
                <v-list-item
                  v-for="(resource, index) in resources"
                  :key="index"
                  class="px-0 mb-2"
                  @click="openResource(resource.link)"
                >
                  <template #prepend>
                    <v-avatar :color="resource.color" size="40" variant="tonal">
                      <v-icon size="20">{{ resource.icon }}</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="font-weight-medium">{{ resource.title }}</v-list-item-title>
                  <v-list-item-subtitle>{{ resource.description }}</v-list-item-subtitle>
                  <template #append>
                    <v-icon>mdi-chevron-right</v-icon>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientBLayout>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import api from '@/api/client'
  import { useToast } from '@/composables/useToast'
  import ClientBLayout from '../components/ClientBLayout.vue'

  const toast = useToast()

  const submitting = ref(false)

  const ticket = ref({
    type: '',
    subject: '',
    message: '',
  })

  const ticketTypes = [
    { value: 'general_question', title: 'Question générale' },
    { value: 'technical_problem', title: 'Problème technique' },
    { value: 'assistance_request', title: 'Demande d\'assistance' },
    { value: 'complaint', title: 'Plainte' },
    { value: 'suggestion', title: 'Suggestion' },
    { value: 'other', title: 'Autre' },
  ]

  const faqs = [
    {
      icon: 'mdi-file-document-outline',
      question: 'Comment soumettre une plainte ?',
      answer: 'Pour soumettre une plainte, cliquez sur "Déposer une plainte" dans le menu ou sur le dashboard. Remplissez le formulaire avec les détails de votre plainte et soumettez-le. Vous recevrez une confirmation par email.',
    },
    {
      icon: 'mdi-clock-outline',
      question: 'Quel est le délai de traitement d\'une plainte ?',
      answer: 'Le délai de traitement varie selon la complexité de la plainte. En général, vous pouvez vous attendre à une première réponse dans les 48 heures. Les plaintes sont généralement traitées dans un délai de 7 à 14 jours.',
    },
    {
      icon: 'mdi-bell-outline',
      question: 'Comment puis-je suivre ma plainte ?',
      answer: 'Vous pouvez suivre l\'état de votre plainte dans la section "Mes Plaintes". Vous recevrez également des notifications par email à chaque mise à jour importante de votre plainte.',
    },
    {
      icon: 'mdi-account-outline',
      question: 'Comment modifier mes informations de profil ?',
      answer: 'Allez dans "Mon Profil" depuis le menu. Vous pourrez y modifier vos informations personnelles, votre email et votre mot de passe. N\'oubliez pas de sauvegarder vos modifications.',
    },
    {
      icon: 'mdi-email-outline',
      question: 'Puis-je annuler une plainte après l\'avoir soumise ?',
      answer: 'Oui, vous pouvez retirer une plainte tant qu\'elle n\'a pas encore été traitée. Contactez le support ou utilisez l\'option d\'annulation dans les détails de votre plainte.',
    },
    {
      icon: 'mdi-shield-check',
      question: 'Mes données sont-elles sécurisées ?',
      answer: 'Oui, nous prenons la sécurité de vos données très au sérieux. Toutes les informations sont chiffrées et stockées de manière sécurisée. Nous ne partageons jamais vos données avec des tiers sans votre consentement.',
    },
  ]

  const resources = [
    {
      title: 'Guide utilisateur',
      description: 'Apprenez à utiliser la plateforme',
      icon: 'mdi-book-open-variant',
      color: 'primary',
      link: '#',
    },
    {
      title: 'Tutoriels vidéo',
      description: 'Tutoriels pas à pas',
      icon: 'mdi-video',
      color: 'error',
      link: '#',
    },
    {
      title: 'Base de connaissances',
      description: 'Articles et documentation',
      icon: 'mdi-library',
      color: 'info',
      link: '#',
    },
    {
      title: 'Conditions d\'utilisation',
      description: 'Nos conditions et politiques',
      icon: 'mdi-file-document-outline',
      color: 'warning',
      link: '#',
    },
  ]

  async function handleSubmitTicket () {
    if (!ticket.value.type || !ticket.value.subject || !ticket.value.message) {
      toast.warning('Veuillez remplir tous les champs')
      return
    }

    submitting.value = true
    try {
      const { data } = await api.post('/clientb/support/tickets', {
        type: ticket.value.type,
        subject: ticket.value.subject,
        message: ticket.value.message,
      })

      if (data.success) {
        toast.success(data.message || 'Votre demande a été envoyée avec succès')

        // Reset form
        ticket.value = {
          type: '',
          subject: '',
          message: '',
        }
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de l\'envoi de votre demande')
    } finally {
      submitting.value = false
    }
  }

  function openResource (_link: string) {
    // For now, just show a message
    toast.info('Cette ressource sera bientôt disponible')
  }
</script>
