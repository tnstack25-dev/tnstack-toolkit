import type { IconName } from '../components/ui/Icon'

export type NavigationItem = {
  icon: IconName
  label: string
  path: string
  description: string
  badge?: string
}

export const navigationItems: NavigationItem[] = [
  { icon: 'dashboard', label: 'Dashboard', path: '/dashboard', description: 'Tổng quan hệ thống và trạng thái website' },
  { icon: 'globe', label: 'Websites', path: '/websites', description: 'Quản lý danh sách website' },
  { icon: 'monitor', label: 'Monitoring', path: '/monitoring', description: 'Theo dõi trạng thái website' },
  { icon: 'backup', label: 'Backups', path: '/backups', description: 'Quản lý bản sao lưu' },
  { icon: 'search', label: 'Malware Scan', path: '/malware-scan', description: 'Quét và phát hiện mã độc' },
  { icon: 'shield', label: 'Security', path: '/security', description: 'Thiết lập bảo mật website' },
  { icon: 'report', label: 'Reports', path: '/reports', description: 'Báo cáo hoạt động hệ thống' },
  { icon: 'activity', label: 'Logs', path: '/logs', description: 'Nhật ký hoạt động gần đây' },
  { icon: 'activity', label: 'Uptime Monitor', path: '/uptime-monitor', description: 'Theo dõi uptime website' },
  { icon: 'seo', label: 'SEO Toolkit', path: '/seo-toolkit', description: 'Công cụ hỗ trợ SEO', badge: 'New' },
  { icon: 'settings', label: 'Settings', path: '/settings', description: 'Cấu hình TNStack Toolkit' },
  { icon: 'extension', label: 'Extensions', path: '/extensions', description: 'Quản lý tiện ích mở rộng' },
  { icon: 'shield', label: 'License', path: '/license', description: 'Quản lý bản quyền plugin' },
]
