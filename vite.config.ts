import { defineConfig } from 'vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  base: './',
  plugins: [
    react(),
    tailwindcss(),
    babel({ presets: [reactCompilerPreset()] })
  ],
  build: {
    outDir: 'build',
    manifest: true,
    // cssCodeSplit: false,
    rollupOptions: {
      input: {
        main: 'index.html'
      }
    }
  },
  server: {
    port: 3000,
    strictPort: true,
    cors: true,
    proxy: {
      '/wp-json': {
        target: 'http://devhub.local',  // Port WordPress của bạn
        changeOrigin: true
      }
    }
  }
})
