import { Outlet } from 'react-router-dom'
import { PageHeader } from './PageHeader'
import { Sidebar } from './Sidebar'

export function AppLayout() {
  return (
    <div className="tk-app">
      <Sidebar />
      <main>
        <PageHeader />
        <Outlet />
      </main>
    </div>
  )
}
