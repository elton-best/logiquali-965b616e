import { fileURLToPath, URL } from 'node:url'
import Vue from '@vitejs/plugin-vue'
// Plugins
import AutoImport from 'unplugin-auto-import/vite'
import Fonts from 'unplugin-fonts/vite'
import Components from 'unplugin-vue-components/vite'
import { VueRouterAutoImports } from 'unplugin-vue-router'
import VueRouter from 'unplugin-vue-router/vite'
// Utilities
import { defineConfig } from 'vite'

import Layouts from 'vite-plugin-vue-layouts-next'
import Vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    VueRouter({
      dts: 'src/typed-router.d.ts',
    }),
    Layouts(),
    AutoImport({
      imports: [
        'vue',
        VueRouterAutoImports,
        {
          pinia: ['defineStore', 'storeToRefs'],
        },
      ],
      dts: 'src/auto-imports.d.ts',
      eslintrc: {
        enabled: true,
      },
      vueTemplate: true,
    }),
    Components({
      dts: 'src/components.d.ts',
      dirs: ['src/components', 'src/modules/shared/components'],
      // Donner priorité aux composants dans modules/shared
      directoryAsNamespace: true,
    }),
    Vue({
      template: { transformAssetUrls },
    }),
    // https://github.com/vuetifyjs/vuetify-loader/tree/master/packages/vite-plugin#readme
    Vuetify({
      autoImport: true,
      styles: {
        configFile: 'src/styles/settings.scss',
      },
    }),
    Fonts({
      fontsource: {
        families: [
          {
            name: 'Roboto',
            weights: [100, 300, 400, 500, 700, 900],
            styles: ['normal', 'italic'],
          },
        ],
      },
    }),
  ],
  optimizeDeps: {
    exclude: [
      'vuetify',
      'vue-router',
      'unplugin-vue-router/runtime',
      'unplugin-vue-router/data-loaders',
      'unplugin-vue-router/data-loaders/basic',
    ],
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks (id: string) {
          if (id.includes('node_modules/vue') || id.includes('node_modules/pinia') || id.includes('node_modules/vue-router')) {
            return 'vue-core'
          }

          if (id.includes('node_modules/vuetify')) {
            return 'vuetify'
          }

          if (id.includes('node_modules/country-state-city')) {
            return 'geo-data'
          }

          if (id.includes('node_modules/chart.js')
            || id.includes('node_modules/apexcharts')
            || id.includes('node_modules/vue-chartjs')
            || id.includes('node_modules/vue3-apexcharts')
            || id.includes('node_modules/echarts')
            || id.includes('node_modules/vue-echarts')) {
            return 'charts-vendor'
          }

          if (id.includes('node_modules/@fullcalendar')) {
            return 'calendar-vendor'
          }

          if (id.includes('node_modules/xlsx')) {
            return 'xlsx-vendor'
          }

          if (id.includes('node_modules/@tiptap')
            || id.includes('node_modules/@vueup/vue-quill')) {
            return 'editor-vendor'
          }

          if (id.includes('node_modules/lucide-vue-next')
            || id.includes('node_modules/@mdi/font')) {
            return 'icons-vendor'
          }
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
  define: { 'process.env': {} },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('src', import.meta.url)),
    },
    extensions: [
      '.js',
      '.json',
      '.jsx',
      '.mjs',
      '.ts',
      '.tsx',
      '.vue',
    ],
  },
  server: {
    port: 3000,
  },
})
