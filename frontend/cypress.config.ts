import { defineConfig } from 'cypress'

export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.ts',
    video: false,
    screenshotOnRunFailure: true,
    viewportWidth: 1280,
    viewportHeight: 720,
    setupNodeEvents (on, config) {
      on('task', {
        'db:seed' (_payload: unknown) {
          // Kept for backward compatibility with legacy specs.
          return null
        },
        'db:updateHabilitation' (_payload: unknown) {
          // Kept for backward compatibility with legacy specs.
          return null
        },
      })

      return config
    },
  },
  component: {
    devServer: {
      framework: 'vue',
      bundler: 'vite',
    },
    specPattern: 'src/**/*.cy.{js,jsx,ts,tsx}',
  },
})
