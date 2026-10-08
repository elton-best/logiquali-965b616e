<template>
  <v-card class="document-preview" elevation="3">
    <!-- En-tête avec logo entreprise -->
    <v-card-title class="bg-white pa-6 border-bottom">
      <v-row align="center">
        <v-col cols="4">
          <v-img
            v-if="companyLogo"
            alt="Logo entreprise"
            contain
            max-width="150"
            :src="companyLogo"
          />
          <div v-else class="company-logo-placeholder">
            <v-icon color="primary" size="60">mdi-domain</v-icon>
          </div>
        </v-col>
        <v-col class="text-right" cols="8">
          <h2 class="text-h6 font-weight-bold">{{ documentTitle }}</h2>
          <p class="text-caption text-grey-darken-1 mt-1">
            Référence: {{ documentRef }}
          </p>
          <p class="text-caption text-grey-darken-1">
            Date: {{ formattedDate }}
          </p>
        </v-col>
      </v-row>
    </v-card-title>

    <!-- Contenu du document -->
    <v-card-text class="pa-6">
      <slot name="content">
        <!-- Le contenu du document sera inséré ici -->
      </slot>
    </v-card-text>

    <!-- Pied de page avec infos entreprise -->
    <v-card-actions class="bg-grey-lighten-4 pa-6 border-top">
      <v-row dense>
        <v-col cols="12" md="4">
          <div class="text-caption">
            <v-icon class="mr-1" size="16">mdi-domain</v-icon>
            <strong>{{ companyName }}</strong>
          </div>
          <div class="text-caption text-grey-darken-1">
            {{ companyAddress }}
          </div>
        </v-col>
        <v-col cols="12" md="4">
          <div class="text-caption">
            <v-icon class="mr-1" size="16">mdi-phone</v-icon>
            {{ companyPhone }}
          </div>
          <div class="text-caption">
            <v-icon class="mr-1" size="16">mdi-email</v-icon>
            {{ companyEmail }}
          </div>
        </v-col>
        <v-col class="text-right" cols="12" md="4">
          <div class="text-caption">
            <v-icon class="mr-1" size="16">mdi-web</v-icon>
            {{ companyWebsite }}
          </div>
          <div class="text-caption text-grey-darken-1">
            SIRET: {{ companySiret }}
          </div>
        </v-col>
      </v-row>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import { format } from 'date-fns'
  import { fr } from 'date-fns/locale'
  import { computed } from 'vue'

  interface Props {
    documentTitle: string
    documentRef: string
    documentDate?: string
    companyName?: string
    companyLogo?: string
    companyAddress?: string
    companyPhone?: string
    companyEmail?: string
    companyWebsite?: string
    companySiret?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    documentDate: () => new Date().toISOString(),
    companyName: 'BestQHSE',
    companyAddress: '123 Avenue de la Qualité, 75000 Paris',
    companyPhone: '+33 1 23 45 67 89',
    companyEmail: 'contact@BestQHSE.fr',
    companyWebsite: 'www.BestQHSE.fr',
    companySiret: '123 456 789 00000',
  })

  const formattedDate = computed(() => {
    try {
      return format(new Date(props.documentDate), 'dd MMMM yyyy', { locale: fr })
    } catch {
      return format(new Date(), 'dd MMMM yyyy', { locale: fr })
    }
  })
</script>

<style scoped>
.document-preview {
  max-width: 210mm; /* A4 width */
  margin: 0 auto;
}

.border-bottom {
  border-bottom: 2px solid #e0e0e0;
}

.border-top {
  border-top: 2px solid #e0e0e0;
}

.company-logo-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 150px;
  height: 60px;
  background: #f5f5f5;
  border-radius: 8px;
}
</style>
