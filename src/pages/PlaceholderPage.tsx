import { useLocation } from 'react-router-dom'
import { navigationItems } from '../config/navigation'

function PlaceholderPage() {
  const { pathname } = useLocation()
  const page = navigationItems.find((item) => item.path === pathname)

  return (
    <article className="tk-card tk-empty-state">
        <h2>{page?.label ?? 'Tính năng'} đang được hoàn thiện</h2>
        <p>Route đã sẵn sàng. Component nghiệp vụ có thể được triển khai độc lập trong module riêng.</p>
    </article>
  )
}

export default PlaceholderPage
