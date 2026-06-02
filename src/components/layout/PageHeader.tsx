import { useLocation } from 'react-router-dom'
import { navigationItems } from '../../config/navigation'
import { Icon } from '../ui/Icon'

export function PageHeader() {
  const { pathname } = useLocation()
  const page = navigationItems.find((item) => item.path === pathname)

  return (
    <header className="tk-page-header">
      <div>
        <h1>{page?.label ?? 'TNStack Toolkit'}</h1>
        <p>{page?.description ?? 'Quản trị website tập trung'}</p>
      </div>
      <div className="tk-header-actions">
        <Icon name="moon" />
        <span className="tk-notification"><Icon name="bell" /><b>3</b></span>
        <Icon name="help" />
        <div className="tk-user"><span>Xin chào, admin<small>Administrator</small></span><b>A</b></div>
      </div>
    </header>
  )
}
