import { lazy } from 'react'

export const DashboardPage = lazy(() => import('../pages/DashboardPage'))
export const NotFoundPage = lazy(() => import('../pages/NotFoundPage'))
export const PlaceholderPage = lazy(() => import('../pages/PlaceholderPage'))
export const RouteErrorPage = lazy(() => import('../pages/RouteErrorPage'))
export const SettingsPage = lazy(() => import('../features/settings/SettingsPage'))
