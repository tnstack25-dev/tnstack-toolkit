import { createHashRouter, Navigate } from 'react-router-dom'
import { AppLayout } from '../components/layout/AppLayout'
import { LazyPage } from '../components/layout/LazyPage'
import { DashboardPage, NotFoundPage, PlaceholderPage, RouteErrorPage, SettingsPage } from './lazyPages'

export const router = createHashRouter([
  {
    path: '/',
    element: <AppLayout />,
    errorElement: <LazyPage page={RouteErrorPage} />,
    children: [
      { index: true, element: <Navigate to="/dashboard" replace /> },
      { path: 'dashboard', element: <LazyPage page={DashboardPage} /> },
      { path: 'settings', element: <LazyPage page={SettingsPage} /> },
      { path: 'websites', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'monitoring', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'backups', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'malware-scan', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'security', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'reports', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'logs', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'uptime-monitor', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'seo-toolkit', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'extensions', element: <LazyPage page={PlaceholderPage} /> },
      { path: 'license', element: <LazyPage page={PlaceholderPage} /> },
      { path: '*', element: <LazyPage page={NotFoundPage} /> },
    ],
  },
])
