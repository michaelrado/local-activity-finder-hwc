// ui/src/main.jsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'

import App from './App.jsx'
import './styles.css'

// no-op use so ESLint marks them as used
void [QueryClientProvider, StrictMode, App]

const qc = new QueryClient()

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <QueryClientProvider client={qc}>
      <App />
    </QueryClientProvider>
  </StrictMode>,
)
