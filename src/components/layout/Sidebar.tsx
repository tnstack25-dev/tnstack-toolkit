import { NavLink } from 'react-router-dom'
import { navigationItems } from '../../config/navigation'
import { Icon } from '../ui/Icon'
import { Logo } from './Logo'

export function Sidebar() {
  return (
    <aside className="tk-sidebar">
      <div className="tk-brand">
        <Logo />
        <span><b>TNStack Toolkit</b><small>All-in-one WordPress Toolkit</small></span>
      </div>
      <nav aria-label="Điều hướng plugin">
        {navigationItems.map((item) => (
          <NavLink key={item.path} to={item.path}>
            <Icon name={item.icon} />
            <span>{item.label}</span>
            {item.badge && <b className="tk-menu-badge">{item.badge}</b>}
          </NavLink>
        ))}
      </nav>
      <div className="tk-upgrade">
        <Icon name="shield" />
        <b>Nâng cấp lên PRO</b>
        <small>Mở khóa toàn bộ tính năng nâng cao và nhận hỗ trợ ưu tiên.</small>
        <button>Nâng cấp ngay →</button>
      </div>
      <footer><small>TNStack Toolkit v0.0.1</small><span>Made with <b>♥</b> by TNStack</span></footer>
    </aside>
  )
}
