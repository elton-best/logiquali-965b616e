<template>
  <v-app class="md3-app">
    <LandingNavbar
      v-model:drawer="drawer"
      :is-authenticated="isAuthenticated"
      :nav-items="navItems"
      :user-display-name="userDisplayName"
      :user-email="userEmail"
      :user-initials="userInitials"
      @navigate="navigateTo"
      @scroll="scrollTo"
      @toggle-drawer="drawer = !drawer"
    />

    <!-- Main Content -->
    <v-main>
      <!-- Hero Section - Material Design 3 -->
      <section id="hero" class="hero-md3-glass">
        <!-- Background Image with Blur -->
        <div class="hero-background-image" />
        <div class="hero-overlay" />

        <v-container class="hero-container">
          <v-row align="center" class="hero-row" justify="center">
            <v-col class="hero-content text-center" cols="12" lg="9" md="10">
              <!-- Badge animé -->
              <div class="badge-glass fade-in-up" style="animation-delay: 0.1s">
                <div class="badge-pulse" />
                <v-icon class="mr-2" size="16">mdi-star-four-points</v-icon>
                <span>Plateforme qualite, conformite et gouvernance</span>
              </div>

              <!-- Titre principal -->
              <h1 class="hero-title-glass fade-in-up" style="animation-delay: 0.2s">
                Structurez votre
                <span class="gradient-text-glass">conformite</span>
                <br>
                <span class="hero-subtitle-glass">et accelerez vos decisions</span>
              </h1>

              <!-- Description -->
              <p class="hero-description-glass fade-in-up" style="animation-delay: 0.3s">
                Pilotez vos exigences qualite, vos responsabilites et vos actions dans un environnement unique.
                <strong>Centralisez les donnees, formalisez les roles et gardez une tracabilite fiable pour des decisions rapides.</strong>
              </p>

              <!-- CTA Buttons -->
              <div class="cta-group-glass fade-in-up" style="animation-delay: 0.4s">
                <v-btn
                  class="cta-primary-glass"
                  color="white"
                  elevation="0"
                  size="x-large"
                  @click="navigateTo('signup')"
                >
                  <v-icon class="mr-2" color="primary" size="20">mdi-rocket-launch</v-icon>
                  <span class="text-primary font-weight-bold">Essai gratuit 30 jours</span>
                </v-btn>

                <v-btn
                  class="cta-secondary-glass"
                  size="x-large"
                  variant="outlined"
                  @click="scrollTo('demo')"
                >
                  <v-icon class="mr-2" size="20">mdi-play-circle-outline</v-icon>
                  Voir la démo
                </v-btn>
              </div>

              <!-- Stats Cards -->
              <div class="stats-container-glass fade-in-up" style="animation-delay: 0.5s">
                <div class="stat-card-glass">
                  <div class="stat-icon-wrapper stat-icon-primary">
                    <v-icon color="white" size="24">mdi-office-building</v-icon>
                  </div>
                  <div class="stat-content">
                    <div class="stat-value-glass">500+</div>
                    <div class="stat-label-glass">Entreprises</div>
                  </div>
                </div>

                <div class="stat-card-glass">
                  <div class="stat-icon-wrapper stat-icon-success">
                    <v-icon color="white" size="24">mdi-heart</v-icon>
                  </div>
                  <div class="stat-content">
                    <div class="stat-value-glass">98%</div>
                    <div class="stat-label-glass">Satisfaction</div>
                  </div>
                </div>

                <div class="stat-card-glass">
                  <div class="stat-icon-wrapper stat-icon-info">
                    <v-icon color="white" size="24">mdi-headset</v-icon>
                  </div>
                  <div class="stat-content">
                    <div class="stat-value-glass">24/7</div>
                    <div class="stat-label-glass">Support</div>
                  </div>
                </div>
              </div>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- Workflow Section -->
      <section id="workflow" class="workflow-section py-12">
        <v-container>
          <div class="text-center mb-12">
            <v-chip class="mb-4 section-chip" color="primary" size="large" variant="tonal">
              <v-icon size="18" start>mdi-radar</v-icon>
              Processus
            </v-chip>
            <h2 class="section-title mb-4">
              Un parcours clair, de la conformite a l'execution
            </h2>
            <p class="section-subtitle">
              Cadrer, centraliser, piloter et arbitrer avec une vision consolidee.
            </p>
          </div>

          <v-row>
            <v-col
              v-for="(step, index) in workflowSteps"
              :key="step.title"
              cols="12"
              md="3"
            >
              <div class="workflow-card">
                <div class="workflow-index">{{ index + 1 }}</div>
                <div class="workflow-icon">
                  <v-icon color="primary" size="26">{{ step.icon }}</v-icon>
                </div>
                <h3 class="workflow-title">{{ step.title }}</h3>
                <p class="workflow-text">{{ step.description }}</p>
              </div>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- Features Section -->
      <section id="features" class="features-section py-12">
        <v-container>
          <div class="text-center mb-12">
            <v-chip class="mb-4 section-chip" color="primary" size="large" variant="tonal">
              <v-icon size="18" start>mdi-lightning-bolt</v-icon>
              Fonctionnalités
            </v-chip>
            <h2 class="section-title mb-4">
              Tout ce dont vous avez besoin
            </h2>
            <p class="section-subtitle">
              Une solution complète pour votre gestion QHSE
            </p>
          </div>

          <v-row>
            <v-col
              v-for="(feature, index) in features"
              :key="feature.title"
              cols="12"
              md="4"
            >
              <v-card
                class="feature-card h-100 pa-6"
                elevation="0"
                :style="`animation-delay: ${index * 0.1}s`"
              >
                <div class="feature-icon-wrapper mb-4" :class="`feature-icon-${feature.color}`">
                  <v-icon color="white" size="32">{{ feature.icon }}</v-icon>
                </div>

                <h3 class="text-h5 font-weight-bold text-grey-darken-4 mb-3">
                  {{ feature.title }}
                </h3>

                <p class="text-body-1 text-grey-darken-1 mb-4">
                  {{ feature.description }}
                </p>

                <v-btn
                  class="feature-link"
                  color="primary"
                  size="small"
                  variant="text"
                >
                  En savoir plus
                  <v-icon end size="18">mdi-arrow-right</v-icon>
                </v-btn>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=800" -->
      <!-- Demo Section -->
      <section id="demo" class="py-12 bg-grey-lighten-4">
        <v-container>
          <v-row align="center">
            <v-col cols="12" md="6" order="2" order-md="1">
              <v-img
                aspect-ratio="1.3"
                class="rounded-xl elevation-8"
                cover
                src="https://media.istockphoto.com/id/2227978237/fr/photo/concept-hse-de-sant%C3%A9-de-s%C3%A9curit%C3%A9-et-denvironnement-ax%C3%A9-sur-la-pr%C3%A9vention-des-accidents-la.webp?a=1&b=1&s=612x612&w=0&k=20&c=4ON__Upebn4gmjyXujveq9OBI79rS7SUpc22y8HE3K0="
              />
            </v-col>

            <v-col cols="12" md="6" order="1" order-md="2">
              <v-chip class="mb-4" color="success" variant="tonal">
                <v-icon start>mdi-check-circle</v-icon>
                Certifié ISO 9001
              </v-chip>

              <h2 class="text-h3 font-weight-bold text-grey-darken-4 mb-4">
                Conformité garantie
              </h2>

              <p class="text-h6 text-grey-darken-1 mb-6 font-weight-regular">
                Gérez vos certifications ISO 9001, 14001, 45001 en toute simplicité
              </p>

              <v-list>
                <v-list-item
                  v-for="item in demoFeatures"
                  :key="item"
                  class="px-0"
                >
                  <template #prepend>
                    <v-icon class="mr-3" color="success">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-1">{{ item }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- Trust Section -->
      <section class="trust-section py-12">
        <v-container>
          <v-row class="trust-grid" dense>
            <v-col cols="12" md="4">
              <div class="trust-card">
                <div class="trust-card-icon">
                  <v-icon color="primary" size="22">mdi-shield-lock</v-icon>
                </div>
                <h3 class="trust-card-title">Conformité & traçabilité</h3>
                <p class="trust-card-text">
                  Journalisation des actions, workflows validés et preuves centralisées.
                </p>
              </div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="trust-card">
                <div class="trust-card-icon">
                  <v-icon color="success" size="22">mdi-timer-sand</v-icon>
                </div>
                <h3 class="trust-card-title">Déploiement rapide</h3>
                <p class="trust-card-text">
                  Mise en place guidée avec modules prêts à l’emploi pour vos équipes.
                </p>
              </div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="trust-card">
                <div class="trust-card-icon">
                  <v-icon color="info" size="22">mdi-account-supervisor-circle</v-icon>
                </div>
                <h3 class="trust-card-title">Accompagnement expert</h3>
                <p class="trust-card-text">
                  Support métier et assistance opérationnelle sur vos priorités QHSE.
                </p>
              </div>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- Pricing Section -->
      <section id="pricing" class="pricing-section py-12">
        <v-container>
          <div class="text-center mb-12">
            <v-chip class="mb-4 section-chip" color="primary" size="large" variant="tonal">
              <v-icon size="18" start>mdi-tag-multiple</v-icon>
              Tarifs
            </v-chip>
            <h2 class="section-title mb-4">
              Choisissez votre pack
            </h2>
            <p class="section-subtitle">
              Des solutions adaptées à toutes les entreprises
            </p>
          </div>

          <v-row>
            <v-col
              v-for="(plan, index) in plans"
              :key="plan.name"
              cols="12"
              md="4"
            >
              <v-card
                class="pricing-card h-100 pa-8 position-relative"
                :class="index === 1 ? 'pricing-card-popular' : ''"
                elevation="0"
              >
                <v-chip
                  v-if="index === 1"
                  class="popular-badge"
                  color="success"
                  size="small"
                >
                  <v-icon size="14" start>mdi-star</v-icon>
                  Populaire
                </v-chip>

                <div class="text-center mb-6">
                  <h3 class="text-h5 font-weight-bold text-grey-darken-4 mb-2">
                    {{ plan.name }}
                  </h3>
                  <div class="d-flex align-center justify-center mb-2">
                    <span class="text-h3 font-weight-bold text-primary">
                      {{ plan.price }}
                    </span>
                    <span class="text-grey-darken-1 ml-2">FCFA/mois</span>
                  </div>
                  <p class="text-body-2 text-grey-darken-1">
                    {{ plan.description }}
                  </p>
                </div>

                <v-btn
                  block
                  class="mb-6"
                  :color="index === 1 ? 'primary' : 'grey-darken-1'"
                  size="large"
                  :variant="index === 1 ? 'elevated' : 'outlined'"
                  @click="plan.cta === 'Nous contacter' ? navigateTo('contact') : navigateTo('signup')"
                >
                  {{ plan.cta }}
                </v-btn>

                <v-divider class="mb-4" />

                <v-list class="bg-transparent" density="compact">
                  <v-list-item
                    v-for="feature in plan.features"
                    :key="feature"
                    class="px-0"
                  >
                    <template #prepend>
                      <v-icon class="mr-2" color="success" size="20">mdi-check</v-icon>
                    </template>
                    <v-list-item-title class="text-body-2">{{ feature }}</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- FAQ Section -->
      <section id="faq" class="faq-section py-12">
        <v-container>
          <div class="text-center mb-10">
            <v-chip class="mb-4 section-chip" color="primary" size="large" variant="tonal">
              <v-icon size="18" start>mdi-frequently-asked-questions</v-icon>
              FAQ
            </v-chip>
            <h2 class="section-title mb-4">
              Questions fréquentes
            </h2>
            <p class="section-subtitle">
              Les réponses essentielles sur les abonnements, normes et accès.
            </p>
          </div>

          <v-row justify="center">
            <v-col cols="12" lg="9" md="10">
              <v-text-field
                v-model="faqSearch"
                class="mb-5 faq-search"
                clearable
                density="comfortable"
                label="Rechercher une question"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />

              <v-expansion-panels class="faq-panels" multiple variant="accordion">
                <v-expansion-panel
                  v-for="(item, index) in filteredFaqItems"
                  :key="`${item.question}-${index}`"
                  class="faq-panel"
                >
                  <v-expansion-panel-title class="faq-panel-title">
                    <div class="d-flex align-center justify-space-between w-100 pr-2">
                      <span>{{ item.question }}</span>
                      <v-chip color="primary" size="x-small" variant="tonal">{{ item.category }}</v-chip>
                    </div>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text class="faq-panel-text">
                    {{ item.answer }}
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>

              <div class="faq-footer-cta mt-6">
                <span>Vous ne trouvez pas votre réponse ?</span>
                <v-btn color="primary" variant="text" @click="navigateTo('contact')">
                  Contacter l’équipe
                  <v-icon end size="18">mdi-arrow-right</v-icon>
                </v-btn>
              </div>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <!-- CTA Section -->
      <section class="cta-section py-12">
        <v-container>
          <v-row justify="center">
            <v-col class="text-center" cols="12" md="8">
              <h2 class="text-h3 font-weight-bold text-white mb-4">
                Prêt à transformer votre gestion qualité ?
              </h2>
              <p class="text-h6 text-white mb-8" style="opacity: 0.9;">
                Rejoignez plus de 500 entreprises qui nous font confiance
              </p>

              <v-btn
                class="px-12"
                color="white"
                size="x-large"
                variant="elevated"
                @click="navigateTo('signup')"
              >
                <v-icon color="primary" start>mdi-rocket-launch</v-icon>
                <span class="text-primary">Commencer maintenant</span>
              </v-btn>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <LandingFooter @navigate="navigateTo" @scroll="scrollTo" />
    </v-main>

    <!-- Contact Dialog -->
    <v-dialog
      v-model="contactDialog"
      max-width="600"
      persistent
    >
      <v-card style="border-radius: 12px">
        <v-card-title class="d-flex align-center justify-space-between pa-6">
          <div class="d-flex align-center">
            <v-avatar class="mr-3" color="primary" size="40">
              <v-icon color="white">mdi-email</v-icon>
            </v-avatar>
            <h2 class="text-h5 font-weight-bold">Nous contacter</h2>
          </div>
          <v-btn
            icon
            variant="text"
            @click="contactDialog = false"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text class="pa-6">
          <v-form ref="contactFormRef" @submit.prevent="handleContactSubmit">
            <v-text-field
              v-model="contactForm.name"
              class="mb-4"
              density="comfortable"
              label="Nom complet"
              prepend-inner-icon="mdi-account"
              :rules="[contactRules.required]"
              variant="outlined"
            />

            <v-text-field
              v-model="contactForm.email"
              class="mb-4"
              density="comfortable"
              label="Adresse email"
              prepend-inner-icon="mdi-email"
              :rules="[contactRules.required, contactRules.email]"
              type="email"
              variant="outlined"
            />

            <v-text-field
              v-model="contactForm.phone"
              class="mb-4"
              density="comfortable"
              label="Téléphone (optionnel)"
              prepend-inner-icon="mdi-phone"
              variant="outlined"
            />

            <v-text-field
              v-model="contactForm.subject"
              class="mb-4"
              density="comfortable"
              label="Sujet"
              prepend-inner-icon="mdi-text-subject"
              :rules="[contactRules.required]"
              variant="outlined"
            />

            <v-textarea
              v-model="contactForm.message"
              density="comfortable"
              label="Message"
              no-resize
              prepend-inner-icon="mdi-message-text"
              rows="4"
              :rules="[contactRules.required]"
              variant="outlined"
            />
          </v-form>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-6">
          <v-spacer />
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="contactDialog = false"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="contactLoading"
            variant="elevated"
            @click="handleContactSubmit"
          >
            <v-icon start>mdi-send</v-icon>
            Envoyer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuth } from '@/composables/useAuth'
  import LandingFooter from '@/pages/landing/components/LandingFooter.vue'
  import LandingNavbar from '@/pages/landing/components/LandingNavbar.vue'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const drawer = ref(false)

  const navItems = [
    { text: 'Processus', target: 'workflow' },
    { text: 'Fonctionnalités', target: 'features' },
    { text: 'Démo', target: 'demo' },
    { text: 'Tarifs', target: 'pricing' },
    { text: 'FAQ', target: 'faq' },
  ]

  const features = [
    {
      icon: 'mdi-file-document-check',
      title: 'Gestion Documentaire',
      description: 'Centralisez et organisez tous vos documents qualité en toute sécurité.',
      color: 'primary',
    },
    {
      icon: 'mdi-chart-line',
      title: 'Audits & Inspections',
      description: 'Planifiez et réalisez vos audits internes et externes facilement.',
      color: 'success',
    },
    {
      icon: 'mdi-alert-circle',
      title: 'Non-Conformités',
      description: 'Suivez et traitez vos NC avec un workflow automatisé.',
      color: 'warning',
    },
    {
      icon: 'mdi-shield-check',
      title: 'Certifications ISO',
      description: 'Gérez vos certifications ISO 9001, 14001, 45001 en un seul endroit.',
      color: 'primary',
    },
    {
      icon: 'mdi-account-group',
      title: 'Collaboration',
      description: 'Travaillez en équipe avec des rôles et permissions personnalisables.',
      color: 'success',
    },
    {
      icon: 'mdi-chart-box',
      title: 'Reporting',
      description: 'Générez des rapports détaillés et des tableaux de bord en temps réel.',
      color: 'info',
    },
  ]

  const demoFeatures = [
    'Tableaux de bord personnalisables',
    'Workflow de validation automatisé',
    'Notifications en temps réel',
    'Export PDF et Excel',
    'API REST complète',
    'Support multilingue',
  ]

  const plans = [
    {
      name: 'ISO 9001',
      price: '49 000',
      description: 'Pour les petites entreprises',
      cta: 'Essai gratuit 30 jours',
      features: [
        'Gestion documentaire',
        'Audits internes',
        'Non-conformités',
        '5 utilisateurs',
        'Support email',
      ],
    },
    {
      name: 'Pack Intégré',
      price: '89 000',
      description: 'Pour les entreprises en croissance',
      cta: 'Commencer maintenant',
      features: [
        'Tout ISO 9001',
        'ISO 14001 & 45001',
        'Gestion des risques',
        '20 utilisateurs',
        'Support prioritaire',
        'Formation incluse',
      ],
    },
    {
      name: 'Enterprise',
      price: 'Sur devis',
      description: 'Pour les grandes organisations',
      cta: 'Nous contacter',
      features: [
        'Tout Pack Intégré',
        'Multi-sites',
        'Utilisateurs illimités',
        'API avancée',
        'Support dédié 24/7',
        'Personnalisation complète',
      ],
    },
  ]

  const authStore = useAuthStore()
  const { logout } = useAuth()

  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const userDisplayName = computed(() => authStore.userName || 'Utilisateur')
  const userEmail = computed(() => authStore.userEmail || '')
  const userInitials = computed(() => {
    const source = userDisplayName.value.trim()
    if (!source) return 'U'
    const parts = source.split(' ').filter(Boolean)
    const initials = parts.slice(0, 2).map(part => part[0]?.toUpperCase()).join('')
    return initials || source[0]?.toUpperCase() || 'U'
  })

  onMounted(() => {
    if (!authStore.isAuthenticated) {
      authStore.initAuth()
    }
  })

  const contactDialog = ref(false)
  const contactForm = reactive({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
  })
  const contactLoading = ref(false)
  const contactFormRef = ref()

  const contactRules = {
    required: (v: string) => !!v || 'Ce champ est requis',
    email: (v: string) => /.+@.+\..+/.test(v) || 'Email invalide',
  }

  function navigateTo (route: string) {
    switch (route) {
      case 'signup': {
        router.push('/auth/signup')

        break
      }
      case 'login': {
        router.push('/auth/login')

        break
      }
      case 'dashboard': {
        router.push(authStore.getDashboardRoute())

        break
      }
      case 'logout': {
        void logout()

        break
      }
      case 'contact': {
        contactDialog.value = true

        break
      }
      case 'guide': {
        router.push('/guide-utilisation')

        break
      }
      case 'privacy': {
        router.push('/politique-confidentialite')

        break
      }
    // No default
    }
  }

  async function handleContactSubmit () {
    const { valid } = await contactFormRef.value?.validate()
    if (!valid) return

    contactLoading.value = true

    try {
      // TODO: Send contact form to super admin email
      console.log('Contact form submitted:', contactForm)

      // Reset form
      Object.assign(contactForm, {
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
      })

      contactDialog.value = false

      // Show success message (you can use toast here)
      alert('Message envoyé avec succès! Nous vous contacterons bientôt.')
    } catch (error) {
      console.error('Error sending contact form:', error)
      alert('Erreur lors de l\'envoi du message. Veuillez réessayer.')
    } finally {
      contactLoading.value = false
    }
  }

  function scrollTo (target: string) {
    const element = document.querySelector(`#${target}`)
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }

  const workflowSteps = [
    {
      icon: 'mdi-map-marker-path',
      title: 'Cadrer',
      description: 'Structurez vos entites, roles, permissions et regles de fonctionnement.',
    },
    {
      icon: 'mdi-clipboard-check-multiple',
      title: 'Centraliser',
      description: 'Regroupez informations critiques, historiques et validations dans des workflows lisibles.',
    },
    {
      icon: 'mdi-sync-circle',
      title: 'Piloter',
      description: 'Les equipes executent, corrigent et documentent les ecarts dans un cadre commun.',
    },
    {
      icon: 'mdi-chart-areaspline',
      title: 'Arbitrer',
      description: 'Disposez d\'une vue consolidee pour prioriser et securiser la performance dans la duree.',
    },
  ]

  const faqSearch = ref('')
  const faqItems = [
    {
      question: 'Pourquoi ne puis-je pas remplacer une norme ISO existante ?',
      answer: 'Pour préserver la cohérence de vos données. Vous pouvez ajouter une nouvelle norme en complément de l’existante, mais pas remplacer la norme active.',
      category: 'Normes',
    },
    {
      question: 'Que se passe-t-il si une norme expire alors que les autres sont encore actives ?',
      answer: 'L’accès au site est bloqué tant que toutes les normes requises ne sont pas actives. Le renouvellement de la norme expirée réactive immédiatement l’accès.',
      category: 'Abonnements',
    },
    {
      question: 'Puis-je renouveler avant la date d’expiration ?',
      answer: 'Oui. Le nouveau cycle démarre à la fin de l’abonnement en cours, sans perte de jours restants.',
      category: 'Abonnements',
    },
    {
      question: 'Comment sont calculés les arriérés après expiration ?',
      answer: 'Aucun arriéré jusqu’à 15 jours de retard. Au-delà, le calcul se fait par mois arrondi au supérieur: ceil(jours_retard / 30).',
      category: 'Facturation',
    },
    {
      question: 'La période d’essai est-elle réutilisable pour plusieurs normes ?',
      answer: 'Non. La période d’essai est globale à l’entreprise et n’est activable qu’une seule fois.',
      category: 'Essai',
    },
    {
      question: 'Comment savoir quels modules sont inclus dans mon offre ?',
      answer: 'Depuis le dashboard des abonnements et la fiche de l’offre, vous voyez les modules inclus et le détail des droits actifs.',
      category: 'Modules',
    },
  ]

  const filteredFaqItems = computed(() => {
    const query = faqSearch.value.trim().toLowerCase()
    if (!query) return faqItems
    return faqItems.filter(item => {
      const text = `${item.question} ${item.answer} ${item.category}`.toLowerCase()
      return text.includes(query)
    })
  })
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Sora:wght@400;500;600;700;800&display=swap');

/* ===== LANDING PAGE REDESIGN ===== */
:global(:root) {
  --primary: #1d4ed8;
  --primary-dark: #1e40af;
  --primary-light: #60a5fa;
  --accent: #16a34a;
  --surface: #ffffff;
  --background: #f7f8fb;
  --text-primary: #0f172a;
  --text-secondary: #475569;
  --border: #e2e8f0;
  --shadow-sm: 0 6px 18px rgba(15, 23, 42, 0.08);
  --shadow-md: 0 12px 28px rgba(15, 23, 42, 0.12);
  --shadow-lg: 0 20px 45px rgba(15, 23, 42, 0.18);
  --radius: 18px;
  --transition: cubic-bezier(0.4, 0, 0.2, 1);
  --section-spacing: 84px;
  --font-heading: "Sora", "Segoe UI", sans-serif;
  --font-body: "Manrope", "Segoe UI", sans-serif;
}

.md3-app {
  background: var(--background);
  color: var(--text-primary);
  font-family: var(--font-body);
}

/* ===== NAVBAR ===== */
.md3-navbar {
  background: rgba(255, 255, 255, 0.92) !important;
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

.logo-container {
  text-decoration: none;
  transition: transform 0.3s var(--transition);
}

.logo-container:hover {
  transform: translateY(-2px);
}

.logo-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon-md3 {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
  border-radius: 14px;
  box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
}

.logo-title-md3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  line-height: 1;
  font-family: var(--font-heading);
}

.logo-subtitle-md3 {
  font-size: 0.6875rem;
  color: var(--text-secondary);
  margin: 4px 0 0 0;
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.nav-btn-md3 {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: none;
  letter-spacing: -0.01em;
  border-radius: 12px;
  transition: all 0.2s var(--transition);
}

.nav-btn-md3:hover {
  color: var(--primary);
  background: rgba(29, 78, 216, 0.08);
}

.login-btn-md3 {
  font-weight: 600;
  text-transform: none;
  color: var(--text-primary);
  border-radius: 12px;
}

.cta-btn-md3 {
  font-weight: 600;
  text-transform: none;
  border-radius: 12px;
  padding: 0 24px !important;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s var(--transition);
}

.cta-btn-md3:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

/* Trust */
.trust-section {
  background:
    linear-gradient(180deg, rgba(29, 78, 216, 0.03), rgba(22, 163, 74, 0.02));
}

.trust-grid {
  row-gap: 16px;
}

.trust-card {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 16px;
  padding: 24px;
  height: 100%;
  transition: transform 0.24s var(--transition), box-shadow 0.24s var(--transition);
}

.trust-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-sm);
}

.trust-card-icon {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: rgba(29, 78, 216, 0.08);
  margin-bottom: 14px;
}

.trust-card-title {
  font-family: var(--font-heading);
  font-size: 1.04rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--text-primary);
}

.trust-card-text {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.6;
}

/* FAQ */
.faq-section {
  background: #ffffff;
  border-top: 1px solid rgba(148, 163, 184, 0.14);
}

.faq-search :deep(.v-field) {
  border-radius: 14px;
}

.faq-panels {
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: #fff;
}

.faq-panel {
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.faq-panel:last-child {
  border-bottom: none;
}

.faq-panel-title {
  font-weight: 600;
  color: var(--text-primary);
}

.faq-panel-text {
  color: var(--text-secondary);
  line-height: 1.65;
}

.faq-footer-cta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  padding: 14px 16px;
  border-radius: 12px;
  background: rgba(29, 78, 216, 0.05);
  color: var(--text-secondary);
}

/* Hero Glass */
.hero-md3-glass {
  position: relative;
  padding: 100px 0 80px;
  overflow: hidden;
  min-height: 80vh;
  display: flex;
  align-items: center;
}

.hero-background-image {
  position: absolute;
  inset: 0;
  background-image: url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1920&q=80');
  background-size: cover;
  background-position: center;
  filter: blur(4px) brightness(0.7);
  transform: scale(1.05);
  z-index: 0;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(29, 78, 216, 0.85) 0%,
    rgba(22, 163, 74, 0.80) 100%
  );
  z-index: 1;
}

.hero-container {
  position: relative;
  z-index: 2;
}

.hero-row {
  min-height: auto;
}

.hero-content {
  color: white;
}

/* Badge Glass */
.badge-glass {
  display: inline-flex;
  align-items: center;
  padding: 10px 24px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  color: white;
  border-radius: 30px;
  font-size: 0.875rem;
  font-weight: 700;
  margin-bottom: 32px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  position: relative;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.badge-pulse {
  position: absolute;
  top: 50%;
  left: 14px;
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
  transform: translateY(-50%);
  animation: pulse-white 2.4s ease-in-out infinite;
}

@keyframes pulse-white {
  0%, 100% {
    opacity: 1;
    transform: translateY(-50%) scale(1);
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
  }
  50% {
    opacity: 0.8;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
  }
}

/* Title Glass */
.hero-title-glass {
  font-size: clamp(2.2rem, 6vw, 4rem);
  font-weight: 900;
  line-height: 1.15;
  color: white;
  margin-bottom: 24px;
  letter-spacing: -0.02em;
  font-family: var(--font-heading);
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.hero-subtitle-glass {
  color: rgba(255, 255, 255, 0.95);
  font-weight: 600;
}

.gradient-text-glass {
  background: linear-gradient(135deg, #ffffff 0%, rgba(255, 255, 255, 0.85) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline-block;
  position: relative;
}

.gradient-text-glass::after {
  content: '';
  position: absolute;
  bottom: -6px;
  left: 0;
  right: 0;
  height: 4px;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 2px;
}

/* Description Glass */
.hero-description-glass {
  font-size: clamp(1.05rem, 2vw, 1.2rem);
  line-height: 1.65;
  color: rgba(255, 255, 255, 0.95);
  margin-bottom: 40px;
  max-width: 750px;
  margin-left: auto;
  margin-right: auto;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
}

.hero-description-glass strong {
  color: white;
  font-weight: 700;
}

/* CTA Glass */
.cta-group-glass {
  display: flex;
  gap: 20px;
  margin-bottom: 56px;
  justify-content: center;
  flex-wrap: wrap;
}

.cta-primary-glass {
  font-weight: 700;
  text-transform: none;
  border-radius: 14px;
  padding: 0 40px !important;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
  transition: all 0.3s var(--transition);
  background: white !important;
}

.cta-primary-glass:hover {
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
  transform: translateY(-3px);
}

.cta-secondary-glass {
  font-weight: 700;
  text-transform: none;
  border-radius: 14px;
  padding: 0 40px !important;
  border: 2px solid rgba(255, 255, 255, 0.4);
  color: white;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  transition: all 0.3s var(--transition);
}

.cta-secondary-glass:hover {
  border-color: white;
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-3px);
}

/* Stats Glass */
.stats-container-glass {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  max-width: 850px;
  margin: 0 auto;
}

.stat-card-glass {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(16px);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.25);
  transition: all 0.3s var(--transition);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.stat-card-glass:hover {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.35);
  transform: translateY(-6px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.stat-value-glass {
  font-size: 2rem;
  font-weight: 900;
  color: white;
  line-height: 1;
  margin-bottom: 4px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.stat-label-glass {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 600;
}

/* Remove old hero styles */
.hero-md3 {
  display: none;
}

.hero-background-pattern,
.hero-visual-md3,
.hero-image-wrapper-md3,
.hero-gradient-ring,
.hero-image-md3,
.floating-element,
.floating-card-md3,
.floating-card-title,
.floating-card-subtitle,
.trust-row,
.trust-logos,
.hero-highlights {
  display: none;
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fade-in-right {
  from {
    opacity: 0;
    transform: translateX(24px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.fade-in-up {
  animation: fade-in-up 0.6s var(--transition) both;
}

.fade-in-right {
  animation: fade-in-right 0.6s var(--transition) both;
}

/* ===== WORKFLOW ===== */
.workflow-section {
  background: #ffffff;
}

.workflow-card {
  background: linear-gradient(160deg, rgba(29, 78, 216, 0.05), rgba(255, 255, 255, 0.9));
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 20px;
  padding: 28px;
  height: 100%;
  transition: all 0.3s var(--transition);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
}

.workflow-card:hover {
  transform: translateY(-6px);
  border-color: rgba(29, 78, 216, 0.35);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
}

.workflow-index {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-bottom: 12px;
}

.workflow-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(29, 78, 216, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.workflow-title {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 10px;
  font-family: var(--font-heading);
}

.workflow-text {
  font-size: 0.95rem;
  color: var(--text-secondary);
  margin: 0;
}

/* ===== FEATURES ===== */
.features-section {
  background: linear-gradient(180deg, #f7f8fb 0%, #ffffff 100%);
}

.section-chip {
  font-weight: 600;
  padding: 8px 20px !important;
  height: auto !important;
}

.section-title {
  font-size: clamp(2rem, 4vw, 2.7rem);
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.02em;
  font-family: var(--font-heading);
}

.section-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
  font-weight: 500;
  max-width: 620px;
  margin: 0 auto;
}

.feature-card {
  background: var(--surface);
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: var(--radius);
  transition: all 0.4s var(--transition);
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.feature-card::before {
  content: '';
  position: absolute;
  inset: 0 0 auto 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s var(--transition);
}

.feature-card:hover::before {
  transform: scaleX(1);
}

.feature-card:hover {
  border-color: rgba(29, 78, 216, 0.4);
  box-shadow: var(--shadow-lg);
  transform: translateY(-8px);
}

.feature-icon-wrapper {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 18px;
  position: relative;
  transition: all 0.3s var(--transition);
}

.feature-card:hover .feature-icon-wrapper {
  transform: scale(1.08) rotate(4deg);
}

.feature-icon-primary {
  background: linear-gradient(135deg, #1d4ed8 0%, #60a5fa 100%);
  box-shadow: 0 8px 16px rgba(29, 78, 216, 0.25);
}

.feature-icon-success {
  background: linear-gradient(135deg, #16a34a 0%, #4ade80 100%);
  box-shadow: 0 8px 16px rgba(22, 163, 74, 0.25);
}

.feature-icon-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #fdba74 100%);
  box-shadow: 0 8px 16px rgba(245, 158, 11, 0.25);
}

.feature-icon-info {
  background: linear-gradient(135deg, #0ea5e9 0%, #7dd3fc 100%);
  box-shadow: 0 8px 16px rgba(14, 165, 233, 0.25);
}

.feature-link {
  text-transform: none;
  font-weight: 600;
  padding: 0 !important;
}

/* ===== PRICING ===== */
.pricing-section {
  background: #f7f8fb;
}

.pricing-card {
  background: var(--surface);
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 20px;
  transition: all 0.4s var(--transition);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
}

.pricing-card:hover {
  border-color: rgba(29, 78, 216, 0.4);
  box-shadow: var(--shadow-lg);
  transform: translateY(-8px);
}

.pricing-card-popular {
  border-color: rgba(29, 78, 216, 0.6);
  box-shadow: 0 24px 50px rgba(29, 78, 216, 0.2);
  transform: scale(1.03);
}

.pricing-card-popular:hover {
  transform: scale(1.03) translateY(-8px);
}

.popular-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  font-weight: 700;
}

.cta-section {
  background: linear-gradient(135deg, #1d4ed8 0%, #16a34a 100%);
  position: relative;
  overflow: hidden;
}

.cta-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.12) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.12) 0%, transparent 50%);
  pointer-events: none;
}

.gap-3 {
  gap: 12px;
}

.v-card {
  transition: all 0.3s ease;
}

.v-card:hover {
  transform: translateY(-4px);
}

@media (max-width: 960px) {
  :global(:root) {
    --section-spacing: 72px;
  }

  .md3-navbar {
    height: 56px !important;
  }

  .logo-wrapper {
    gap: 10px;
  }

  .logo-icon-md3 {
    width: 36px;
    height: 36px;
    border-radius: 12px;
  }

  .logo-title-md3 {
    font-size: 1rem;
  }

  .logo-subtitle-md3 {
    display: none;
  }

  .hero-md3-glass {
    padding: 80px 0 60px;
    min-height: 70vh;
  }

  .hero-title-glass {
    font-size: 2rem;
    margin-bottom: 20px;
  }

  .hero-description-glass {
    font-size: 1.05rem;
    margin-bottom: 32px;
  }

  .badge-glass {
    font-size: 0.8rem;
    padding: 8px 18px;
    margin-bottom: 24px;
  }

  .stats-container-glass {
    grid-template-columns: 1fr;
    gap: 14px;
    max-width: 100%;
  }

  .stat-card-glass {
    padding: 18px;
  }

  .stat-value-glass {
    font-size: 1.75rem;
  }

  .stat-label-glass {
    font-size: 0.85rem;
  }

  .cta-group-glass {
    flex-direction: column;
    gap: 14px;
    margin-bottom: 40px;
  }

  .cta-primary-glass,
  .cta-secondary-glass {
    width: 100%;
    padding: 0 24px !important;
  }

  .stats-container-glass {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
  }
}

@media (max-width: 600px) {
  .hero-container {
    padding-left: 12px !important;
    padding-right: 12px !important;
  }

  .badge-glass {
    padding: 8px 16px;
    margin-bottom: 18px;
  }

  .hero-md3-glass {
    padding: 60px 0 50px;
    min-height: auto;
  }

  .hero-title-glass {
    font-size: 1.75rem;
  }

  .hero-description-glass {
    font-size: 1rem;
  }

  .stat-icon-wrapper {
    width: 44px;
    height: 44px;
  }

  .cta-group-glass {
    gap: 12px;
  }

  .stats-container-glass {
    grid-template-columns: 1fr;
  }
}
</style>
