// Cypress E2E Support File
// This file is processed and loaded automatically before your test files

// Import commands
import './commands'

// Prevent TypeScript errors
declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Cypress {
    interface Chainable {
      login: (email: string, password: string) => Chainable<void>
      logout: () => Chainable<void>
    }
  }
}
