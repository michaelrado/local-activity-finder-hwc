// Flat config for ESLint v9+
import js from '@eslint/js'
import importPlugin from 'eslint-plugin-import' // import before react (satisfies import/order)
import reactPlugin from 'eslint-plugin-react'
import reactHooks from 'eslint-plugin-react-hooks'
import globals from 'globals'

export default [
  // Global ignores
  { ignores: ['dist/**', 'build/**', 'node_modules/**'] },

  // Browser app files
  {
    files: ['**/*.{js,jsx,ts,tsx}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      parserOptions: { ecmaFeatures: { jsx: true } },
      // 👇 give ESLint the browser globals so fetch/localStorage/navigator/document/alert are defined
      globals: {
        ...globals.browser, // fetch, window, document, navigator, localStorage, alert, etc.
      },
    },
    plugins: {
      import: importPlugin,
      react: reactPlugin,
      'react-hooks': reactHooks,
    },
    settings: { react: { version: 'detect' } },
    rules: {
      ...js.configs.recommended.rules,

      // React / Hooks
      'react/jsx-no-target-blank': 'warn',
      'react/prop-types': 'off',
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',

      // Imports
      'import/order': [
        'error',
        {
          'newlines-between': 'always',
          alphabetize: { order: 'asc', caseInsensitive: true },
          groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
        },
      ],

      // General hygiene
      'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
    },
  },

  // Node/Vite config files
  {
    files: ['vite.config.{js,ts}', '*.config.{js,ts}', 'eslint.config.{js,ts}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...(await import('globals')).default.browser,
      },
    },
    rules: {
      // Keep import order rule here too if you like
      'import/order': [
        'error',
        {
          'newlines-between': 'always',
          alphabetize: { order: 'asc', caseInsensitive: true },
          groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
        },
      ],
    },
  },
]
