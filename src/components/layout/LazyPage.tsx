import { Suspense } from 'react'
import { RouteFallback } from './RouteFallback'

export function LazyPage({ page: Page }: { page: React.LazyExoticComponent<React.ComponentType> }) {
  return <Suspense fallback={<RouteFallback />}><Page /></Suspense>
}
