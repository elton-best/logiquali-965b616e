<template>
  <div class="text-center mb-8">
    <v-avatar class="mb-4 elevation-8" color="primary" size="96">
      <v-icon color="white" size="56">mdi-office-building-cog</v-icon>
    </v-avatar>
    <h1
      class="text-h3 font-weight-bold mb-3"
      style="background: linear-gradient(135deg, #4471c4 0%, #2a4a8f 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;"
    >
      Démarrez Votre Certification ISO
    </h1>
    <p class="text-h6 text-medium-emphasis mb-4">
      Rejoignez les entreprises qui pilotent leur SMI efficacement
    </p>

    <div class="d-flex justify-center gap-4 flex-wrap">
      <v-chip color="success" size="small" variant="tonal">
        <v-icon size="18" start>mdi-shield-check</v-icon>
        Conforme RGPD
      </v-chip>
      <v-chip color="primary" size="small" variant="tonal">
        <v-icon size="18" start>mdi-certificate</v-icon>
        Certifié ISO
      </v-chip>
      <v-chip color="info" size="small" variant="tonal">
        <v-icon size="18" start>mdi-cloud-lock</v-icon>
        Données Sécurisées
      </v-chip>
    </div>
  </div>
</template>
