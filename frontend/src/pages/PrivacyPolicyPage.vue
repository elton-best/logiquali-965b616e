<template>
  <v-app class="privacy-app">
    <v-app-bar class="privacy-nav" elevation="0" height="72">
      <v-container class="d-flex align-center">
        <button class="brand-btn" type="button" @click="goToLanding">
          <v-avatar class="mr-3" color="primary" size="38">
            <v-icon color="white">mdi-shield-check</v-icon>
          </v-avatar>
          <div class="text-left">
            <div class="brand-title">{{ appName }}</div>
            <div class="brand-subtitle">Politique de confidentialité</div>
          </div>
        </button>
        <v-spacer />
        <v-btn class="mr-2" color="primary" variant="tonal" @click="goToGuide">
          Guide d'utilisation
        </v-btn>
        <v-btn color="primary" variant="elevated" @click="goToLogin">
          Se connecter
        </v-btn>
      </v-container>
    </v-app-bar>

    <v-main>
      <section class="privacy-hero">
        <v-container class="py-14 py-md-16">
          <v-chip class="mb-4" color="primary" variant="tonal">
            <v-icon size="18" start>mdi-lock-check</v-icon>
            Protection des données personnelles
          </v-chip>
          <h1 class="privacy-title">
            Politique de confidentialité et de protection des données
          </h1>
          <p class="privacy-subtitle">
            Cette politique décrit les données traitées sur {{ appName }}, les finalités, les bases légales,
            vos droits et les modalités de contact.
          </p>
          <p class="privacy-date">
            Dernière mise à jour: {{ lastUpdate }}
          </p>
        </v-container>
      </section>

      <section class="py-8">
        <v-container>
          <v-card class="toc-card pa-6" elevation="0">
            <h2 class="toc-title">Sommaire</h2>
            <div class="toc-grid mt-4">
              <a v-for="entry in toc" :key="entry.id" class="toc-link" :href="`#${entry.id}`">
                {{ entry.label }}
              </a>
            </div>
          </v-card>
        </v-container>
      </section>

      <section class="pb-14">
        <v-container class="privacy-content">
          <article id="preambule" class="policy-section">
            <h2>Préambule</h2>
            <p>
              La société BEST EXPERTS-GROUP, via sa plateforme {{ appName }}, s'engage à garantir la confidentialité et la
              sécurité des données de ses utilisateurs conformément à la Loi N° 2017-20 du 20 avril 2018 portant
              Code du Numérique en République du Bénin.
            </p>
            <p>
              Cette politique vise à instaurer une relation de confiance et détaille les processus de collecte,
              d'usage et de conservation des données personnelles.
            </p>
          </article>

          <article id="section-1" class="policy-section">
            <h2>1. Identité du responsable du traitement</h2>
            <ul>
              <li><strong>Identité :</strong> M. ANIAMBOSSOU Anicet Babatoundé</li>
              <li><strong>Fonction :</strong> Responsable de traitement</li>
              <li><strong>Coordonnées :</strong> +229 01 66 00 47 66 | aanicet@best-experts-group.com</li>
            </ul>
          </article>

          <article id="section-2" class="policy-section">
            <h2>2. Cadre légal et autorisation préalable</h2>
            <p>
              Le traitement est déclaré et autorisé auprès de l'Autorité de Protection des Données Personnelles (APDP).
            </p>
            <ul>
              <li><strong>Numéro d'autorisation :</strong> N° 2025-502/AT/APDP/DST/SC du 30 octobre 2025</li>
              <li><strong>Vérification :</strong> contact@apdp.bj</li>
            </ul>
          </article>

          <article id="section-3" class="policy-section">
            <h2>3. Catégories de données et gestion des traceurs</h2>
            <ul>
              <li><strong>Données d'identification :</strong> noms, prénoms, email, téléphone</li>
              <li><strong>Données opérationnelles :</strong> historiques d'utilisation, éléments liés aux services</li>
              <li><strong>Données de connexion :</strong> adresses IP, identifiants de session, métadonnées techniques</li>
              <li><strong>Cookies techniques :</strong> cookies strictement nécessaires au fonctionnement et à la sécurité</li>
            </ul>
          </article>

          <article id="section-4" class="policy-section">
            <h2>4. Finalités et bases juridiques</h2>
            <ul>
              <li><strong>Exécution du service :</strong> fournir les fonctionnalités de la plateforme</li>
              <li><strong>Sécurité :</strong> prévention de la fraude, contrôle d'accès, intégrité des données</li>
              <li><strong>Amélioration du service :</strong> analyse technique et optimisation de l'expérience</li>
              <li><strong>Conformité légale :</strong> respect des obligations réglementaires</li>
            </ul>
          </article>

          <article id="section-5" class="policy-section">
            <h2>5. Exercice de vos droits</h2>
            <p>
              Vous disposez notamment des droits d'accès, de rectification, d'opposition, de portabilité et d'effacement,
              dans les conditions prévues par la loi applicable.
            </p>
          </article>

          <article id="section-6" class="policy-section">
            <h2>6. Modalités de contact</h2>
            <ul>
              <li><strong>Adresse :</strong> Cotonou, Quartier Gbèdjromédé, C/1225, Maison ADANDE Georgette</li>
              <li><strong>Email :</strong> aanicet@best-experts-group.com</li>
              <li><strong>Délai indicatif de réponse :</strong> 48 heures</li>
            </ul>
          </article>

          <article id="section-7" class="policy-section">
            <h2>7. Conservation, sécurité et transfert</h2>
            <ul>
              <li><strong>Conservation :</strong> durée limitée au strict nécessaire selon la finalité</li>
              <li><strong>Sécurité :</strong> mesures techniques et organisationnelles de protection</li>
              <li><strong>Hébergement :</strong> serveurs sécurisés situés en France</li>
            </ul>
          </article>

          <article id="section-8" class="policy-section">
            <h2>8. Réclamations</h2>
            <p>
              En cas de litige non résolu, vous pouvez saisir l'APDP:
              Rue 6.076 Aïdjèdo, Cotonou - contact@apdp.bj - www.apdp.bj.
            </p>
          </article>
        </v-container>
      </section>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useRouter } from 'vue-router'

  const router = useRouter()
  const appName = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'

  const lastUpdate = computed(() => new Date().toLocaleDateString('fr-FR'))

  const toc = [
    { id: 'preambule', label: 'Préambule' },
    { id: 'section-1', label: '1. Identité du responsable' },
    { id: 'section-2', label: '2. Cadre légal et autorisation' },
    { id: 'section-3', label: '3. Données et traceurs' },
    { id: 'section-4', label: '4. Finalités et bases juridiques' },
    { id: 'section-5', label: '5. Vos droits' },
    { id: 'section-6', label: '6. Contact' },
    { id: 'section-7', label: '7. Conservation et sécurité' },
    { id: 'section-8', label: '8. Réclamations' },
  ]

  function goToLanding () {
    router.push('/landing')
  }

  function goToGuide () {
    router.push('/guide-utilisation')
  }

  function goToLogin () {
    router.push('/auth/login')
  }
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap');

.privacy-app {
  background: #f8fafc;
  font-family: "Manrope", "Segoe UI", sans-serif;
}

.privacy-nav {
  background: rgba(255, 255, 255, 0.93) !important;
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

.privacy-hero {
  background: linear-gradient(180deg, #eaf2ff 0%, #f8fafc 100%);
  border-bottom: 1px solid #dbeafe;
}

.privacy-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  color: #0f172a;
  font-size: clamp(1.7rem, 3.6vw, 2.7rem);
  line-height: 1.15;
  max-width: 920px;
}

.privacy-subtitle {
  margin-top: 12px;
  color: #475569;
  font-size: 1.02rem;
  max-width: 850px;
}

.privacy-date {
  margin-top: 12px;
  color: #334155;
  font-weight: 600;
}

.toc-card {
  border-radius: 18px;
  border: 1px solid #dbeafe;
  background: white;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.toc-title {
  font-family: "Sora", "Segoe UI", sans-serif;
  font-size: 1.2rem;
  color: #0f172a;
}

.toc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.toc-link {
  text-decoration: none;
  color: #1d4ed8;
  font-weight: 600;
  padding: 10px 12px;
  border-radius: 12px;
  background: #eff6ff;
  transition: all 0.2s ease;
}

.toc-link:hover {
  background: #dbeafe;
}

.privacy-content {
  display: grid;
  gap: 18px;
}

.policy-section {
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: white;
  padding: 22px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
  scroll-margin-top: 100px;
}

.policy-section h2 {
  font-family: "Sora", "Segoe UI", sans-serif;
  color: #0f172a;
  margin-bottom: 12px;
  font-size: 1.15rem;
}

.policy-section p,
.policy-section li {
  color: #334155;
  line-height: 1.68;
}
</style>
