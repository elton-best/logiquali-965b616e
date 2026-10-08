<template>
  <div>
    <h3 class="text-h6 mb-6">Paramètres entreprise avancés</h3>

    <v-row>
      <v-col cols="12">
        <BrandingConfig
          :enterprise-id="enterpriseId"
          :initial-data="enterpriseConfig?.branding"
          @saved="emit('refresh')"
        />
      </v-col>

      <v-col cols="12">
        <LegalInfoConfig
          :current-country="currentCountry"
          :enterprise-id="enterpriseId"
          :initial-data="enterpriseConfig?.legal_raw || enterpriseConfig?.legal"
          @saved="emit('refresh')"
        />
      </v-col>

      <v-col cols="12">
        <DocumentConfig
          :enterprise-id="enterpriseId"
          :initial-data="enterpriseConfig?.document_config"
          @saved="emit('refresh')"
        />
      </v-col>

      <v-col cols="12">
        <CertificationManager :enterprise-id="enterpriseId" />
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import BrandingConfig from '@/components/enterprise/BrandingConfig.vue'
  import CertificationManager from '@/components/enterprise/CertificationManager.vue'
  import DocumentConfig from '@/components/enterprise/DocumentConfig.vue'
  import LegalInfoConfig from '@/components/enterprise/LegalInfoConfig.vue'

  defineProps({
    enterpriseId: {
      type: Number,
      required: true,
    },
    enterpriseConfig: {
      type: Object as PropType<any>,
      required: false,
      default: null,
    },
    currentCountry: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'refresh'): void
  }>()
</script>
