import vuetify from 'eslint-config-vuetify'

export default (async () => {
  const baseConfig = await (typeof vuetify === 'function' ? vuetify() : vuetify)

  return [
    ...(Array.isArray(baseConfig) ? baseConfig : [baseConfig]),
    {
      rules: {
        'complexity': 'warn',
        'unicorn/error-message': 'warn',
        'unicorn/explicit-length-check': 'warn',
        'unicorn/no-array-callback-reference': 'warn',
        'unicorn/no-array-for-each': 'warn',
        'unicorn/no-array-sort': 'warn',
        'unicorn/no-nested-ternary': 'warn',
        'unicorn/prefer-add-event-listener': 'warn',
        'unicorn/prefer-array-some': 'warn',
        'unicorn/prefer-default-parameters': 'warn',
      },
    },
  ]
})()
