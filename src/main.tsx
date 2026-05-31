import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import '@wordpress/components/build-style/style.css'
import './index.css'
import App from './App.tsx'

createRoot(document.getElementById('tnstack-toolkit-root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
