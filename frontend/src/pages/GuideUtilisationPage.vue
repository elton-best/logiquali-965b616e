<template>
  <v-app class="guide-app">
    <v-app-bar class="guide-nav" elevation="0" height="72">
      <v-container class="d-flex align-center">
        <button class="brand-btn" type="button" @click="goToLanding">
          <v-avatar class="mr-3" color="primary" size="38">
            <v-icon color="white">mdi-shield-check</v-icon>
          </v-avatar>
          <div class="text-left">
            <div class="brand-title">{{ appName }}</div>
            <div class="brand-subtitle">Guide d'utilisation</div>
          </div>
        </button>
        <v-spacer />
        <v-btn class="mr-2" color="primary" variant="tonal" @click="goToPrivacy">
          Politique de confidentialité
        </v-btn>
        <v-btn color="primary" variant="elevated" @click="goToLogin">
          Se connecter
        </v-btn>
      </v-container>
    </v-app-bar>

    <v-main>
      <section class="hero-section">
        <div class="hero-glow hero-glow-left" />
        <div class="hero-glow hero-glow-right" />
        <v-container class="py-14 py-md-16">
          <v-row align="center">
            <v-col cols="12" md="7">
              <v-chip class="mb-4" color="primary" variant="tonal">
                <v-icon size="18" start>mdi-book-open-page-variant</v-icon>
                Documentation utilisateur
              </v-chip>
              <h1 class="hero-title">
                Guide d'utilisation {{ appName }}
              </h1>
              <p class="hero-text">
                Démarrez rapidement, structurez vos activités QHSE et exploitez les modules clés
                avec un parcours clair, pensé pour vos équipes.
              </p>
              <div class="d-flex flex-wrap ga-3 mt-6">
                <v-btn color="primary" prepend-icon="mdi-rocket-launch" size="large" @click="scrollTo('demarrage')">
                  Commencer maintenant
                </v-btn>
                <v-btn
                  color="primary"
                  prepend-icon="mdi-book-open-variant"
                  size="large"
                  variant="tonal"
                  @click="scrollTo('guide-detaille')"
                >
                  Guide détaillé
                </v-btn>
                <v-btn
                  color="primary"
                  prepend-icon="mdi-home"
                  size="large"
                  variant="outlined"
                  @click="goToLanding"
                >
                  Retour à l'accueil
                </v-btn>
              </div>
            </v-col>
            <v-col cols="12" md="5">
              <v-card class="hero-card" elevation="0">
                <h2 class="hero-card-title">Parcours recommandé</h2>
                <v-timeline class="mt-3" density="compact" side="end">
                  <v-timeline-item
                    v-for="step in quickSteps"
                    :key="step.title"
                    dot-color="primary"
                    fill-dot
                    size="small"
                  >
                    <div class="timeline-title">{{ step.title }}</div>
                    <p class="timeline-text">{{ step.description }}</p>
                  </v-timeline-item>
                </v-timeline>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <section id="demarrage" class="py-12">
        <v-container>
          <h2 class="section-title mb-6">Démarrage en 4 étapes</h2>
          <v-row>
            <v-col v-for="(item, index) in gettingStarted" :key="item.title" cols="12" md="6">
              <v-card class="step-card h-100" elevation="0">
                <div class="step-index">{{ index + 1 }}</div>
                <h3 class="step-title">{{ item.title }}</h3>
                <p class="step-text">{{ item.description }}</p>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <section class="modules-section py-12">
        <v-container>
          <h2 class="section-title mb-6">Modules clés</h2>
          <v-row>
            <v-col v-for="module in modules" :key="module.title" cols="12" md="4">
              <v-card class="module-card h-100" elevation="0">
                <v-icon class="mb-3" color="primary" size="30">{{ module.icon }}</v-icon>
                <h3 class="module-title">{{ module.title }}</h3>
                <p class="module-text">{{ module.description }}</p>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </section>

      <section class="py-12">
        <v-container>
          <v-card class="tips-card pa-7" elevation="0">
            <h2 class="section-title mb-4">Bonnes pratiques</h2>
            <v-row>
              <v-col v-for="tip in tips" :key="tip" cols="12" md="4">
                <div class="d-flex align-start">
                  <v-icon class="mr-3 mt-1" color="success" size="20">mdi-check-circle</v-icon>
                  <p class="mb-0">{{ tip }}</p>
                </div>
              </v-col>
            </v-row>
          </v-card>
        </v-container>
      </section>

      <section id="guide-detaille" class="detail-section py-12">
        <v-container>
          <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-6">
            <h2 class="section-title mb-0">Guide détaillé par profil</h2>
            <v-chip color="primary" variant="tonal">Vue opérationnelle</v-chip>
          </div>

          <v-row>
            <v-col v-for="profile in roleGuides" :key="profile.title" cols="12" md="6">
              <v-card class="role-card h-100" elevation="0">
                <div class="d-flex align-center mb-3">
                  <v-avatar class="mr-3" color="primary" size="34">
                    <v-icon color="white" size="18">{{ profile.icon }}</v-icon>
                  </v-avatar>
                  <h3 class="role-title mb-0">{{ profile.title }}</h3>
                </div>

                <p class="role-mission mb-3">{{ profile.mission }}</p>

                <div class="role-block">
                  <p class="role-block-title">Ce que vous devez faire</p>
                  <ul class="role-list">
                    <li v-for="item in profile.actions" :key="item">{{ item }}</li>
                  </ul>
                </div>

                <div class="role-block mt-3">
                  <p class="role-block-title">Rythme recommandé</p>
                  <ul class="role-list role-list-compact">
                    <li v-for="item in profile.rhythm" :key="item">{{ item }}</li>
                  </ul>
                </div>
              </v-card>
            </v-col>
          </v-row>
        </v-container>
      </section>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { useRouter } from 'vue-router'

  const router = useRouter()
  const appName = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'

  const quickSteps = [
    {
      title: 'Créer votre organisation',
      description: 'Paramétrez votre profil entreprise, vos sites et votre périmètre de management.',
    },
    {
      title: 'Structurer les référentiels',
      description: 'Ajoutez processus, parties intéressées, risques et exigences.',
    },
    {
      title: 'Exécuter les actions',
      description: 'Lancez audits, plans d’action, indicateurs et suivi documentaire.',
    },
    {
      title: 'Piloter l’amélioration',
      description: 'Mesurez la performance et préparez vos revues de direction.',
    },
  ]

  const gettingStarted = [
    {
      title: '1. Accès et sécurité',
      description: 'Connectez-vous, activez la MFA et vérifiez les rôles utilisateurs avant tout déploiement.',
    },
    {
      title: '2. Paramètres de base',
      description: 'Définissez les unités, types de documents, notifications et règles de validation.',
    },
    {
      title: '3. Mise en service des modules',
      description: 'Activez les modules nécessaires (qualité, sécurité, environnement, énergie) selon vos besoins.',
    },
    {
      title: '4. Suivi opérationnel',
      description: 'Planifiez les revues périodiques et validez la mise à jour continue des données.',
    },
  ]

  const modules = [
    {
      icon: 'mdi-account-group',
      title: 'Parties intéressées',
      description: 'Identifiez besoins, actions associées et statut de traitement dans un espace unique.',
    },
    {
      icon: 'mdi-alert-outline',
      title: 'Risques & opportunités',
      description: 'Évaluez probabilité/gravité, planifiez les actions par type et suivez la maîtrise.',
    },
    {
      icon: 'mdi-file-document-multiple',
      title: 'Documentation',
      description: 'Centralisez vos procédures, preuves et enregistrements avec traçabilité complète.',
    },
  ]

  const tips = [
    'Utilisez des responsables clairement assignés pour chaque action.',
    'Mettez à jour les risques et preuves avant chaque revue de direction.',
    'Exportez périodiquement vos données pour contrôle et archivage.',
  ]

  const roleGuides = [
    {
      icon: 'mdi-office-building-cog',
      title: 'Administrateur Entreprise',
      mission: 'Piloter le système QHSE de l’entreprise et valider les priorités.',
      actions: [
        'Paramétrer l’organisation (sites, équipes, référents).',
        'Valider les éléments clés soumis par les équipes.',
        'Suivre les indicateurs globaux et arbitrer les actions prioritaires.',
      ],
      rhythm: [
        'Quotidien: validation des éléments en attente.',
        'Hebdomadaire: revue risques, actions, audits.',
        'Mensuel: revue de direction et bilan performance.',
      ],
    },
    {
      icon: 'mdi-domain',
      title: 'Responsable de Site',
      mission: 'Déployer la démarche QHSE sur son site et garantir les mises à jour terrain.',
      actions: [
        'Maintenir les risques, opportunités et actions du site à jour.',
        'Suivre les actions en retard et relancer les responsables.',
        'Préparer les audits et consolider les preuves du site.',
      ],
      rhythm: [
        'Quotidien: suivi des actions opérationnelles.',
        'Hebdomadaire: mise à jour des indicateurs site.',
        'Mensuel: bilan et plan d’amélioration local.',
      ],
    },
    {
      icon: 'mdi-shield-check',
      title: 'Responsable Qualité / HSE / Environnement',
      mission: 'Animer les sous-modules métier et garantir la conformité.',
      actions: [
        'Qualifier les risques/opportunités et maintenir les évaluations.',
        'Ouvrir et suivre les actions par type jusqu’à clôture.',
        'Tenir les exigences réglementaires et preuves associées.',
      ],
      rhythm: [
        'Quotidien: traitement des nouveaux événements.',
        'Hebdomadaire: suivi des actions et écarts.',
        'Mensuel: analyse tendance et efficacité.',
      ],
    },
    {
      icon: 'mdi-source-branch',
      title: 'Process Owner / Auditeur / Team Leader / Opérateur',
      mission: 'Exécuter, documenter et améliorer les activités selon le rôle terrain.',
      actions: [
        'Mettre à jour les données de processus, constats et actions.',
        'Joindre les preuves (documents, photos, commentaires).',
        'Faire remonter rapidement les blocages ou non-conformités.',
      ],
      rhythm: [
        'Quotidien: mise à jour de l’avancement.',
        'Hebdomadaire: revue des tâches en retard.',
        'Périodique: préparation des audits et revues.',
      ],
    },
  ]

  function goToLanding () {
    router.push('/landing')
  }

  function goToLogin () {
    router.push('/auth/login')
  }

  function goToPrivacy () {
    router.push('/politique-confidentialite')
  }

  function scrollTo (target: string) {
    const element = document.getElementById(target)
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap');

.guide-app {
  background: #f5f7fb;
  font-family: "Manrope", "Segoe UI", sans-serif;
}

.guide-nav {
  background: rgba(255, 255, 255, 0.9) !important;
  backdrop-filter: blur(12px);
  border-bottom: 1px solid #e2e8f0;
}

.brand-btn {
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
}

.brand-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.brand-subtitle {
  font-size: 0.78rem;
  color: #64748b;
}

.hero-section {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #eef4ff 0%, #f8fafc 100%);
}

.hero-glow {
  position: absolute;
  width: 360px;
  height: 360px;
  border-radius: 999px;
  filter: blur(70px);
  opacity: 0.45;
  z-index: 0;
}

.hero-glow-left {
  top: -130px;
  left: -110px;
  background: #60a5fa;
}

.hero-glow-right {
  bottom: -150px;
  right: -100px;
  background: #22c55e;
}

.hero-title {
  position: relative;
  z-index: 1;
  font-family: "Sora", "Segoe UI", sans-serif;
  font-size: clamp(2rem, 4.2vw, 3.1rem);
  line-height: 1.15;
  color: #0f172a;
}

.hero-text {
  position: relative;
  z-index: 1;
  margin-top: 14px;
  font-size: 1.08rem;
  color: #475569;
  max-width: 760px;
}

.hero-card {
  border-radius: 20px;
  padding: 18px 20px;
  border: 1px solid #dbeafe;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 14px 38px rgba(15, 23, 42, 0.08);
}

.hero-card-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
}

.timeline-title {
  font-weight: 700;
  color: #0f172a;
}

.timeline-text {
  margin-top: 6px;
  margin-bottom: 0;
  color: #475569;
  font-size: 0.93rem;
}

.section-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  font-size: clamp(1.4rem, 2.4vw, 1.9rem);
  color: #0f172a;
}

.step-card {
  border-radius: 16px;
  border: 1px solid #dbeafe;
  background: white;
  padding: 22px;
  position: relative;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.step-index {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #dbeafe;
  color: #1d4ed8;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.step-title {
  font-size: 1.03rem;
  font-weight: 700;
  color: #0f172a;
}

.step-text {
  margin-top: 8px;
  margin-bottom: 0;
  color: #475569;
}

.modules-section {
  background: #eef2ff;
}

.module-card {
  border-radius: 16px;
  border: 1px solid #dbeafe;
  background: linear-gradient(165deg, #ffffff 0%, #f8fafc 100%);
  padding: 22px;
  box-shadow: 0 10px 24px rgba(30, 64, 175, 0.08);
}

.module-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  color: #0f172a;
  font-size: 1.03rem;
}

.module-text {
  color: #475569;
  margin-bottom: 0;
  margin-top: 8px;
}

.tips-card {
  border-radius: 18px;
  border: 1px solid #dcfce7;
  background: #f0fdf4;
  box-shadow: 0 10px 24px rgba(22, 163, 74, 0.08);
}

.detail-section {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.role-card {
  border-radius: 22px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 22px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}

.role-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  font-size: 1.02rem;
  font-weight: 700;
  color: #0f172a;
}

.role-mission {
  color: #334155;
  line-height: 1.55;
}

.role-block-title {
  margin-bottom: 8px;
  font-size: 0.86rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #475569;
  font-weight: 700;
}

.role-list {
  margin: 0;
  padding-left: 18px;
  color: #1e293b;
  line-height: 1.55;
}

.role-list li + li {
  margin-top: 4px;
}

.role-list-compact {
  color: #334155;
}
</style>
