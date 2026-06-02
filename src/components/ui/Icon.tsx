export type IconName =
  | 'activity'
  | 'alert'
  | 'backup'
  | 'bell'
  | 'check'
  | 'chevron'
  | 'dashboard'
  | 'extension'
  | 'globe'
  | 'help'
  | 'lock'
  | 'monitor'
  | 'moon'
  | 'refresh'
  | 'report'
  | 'search'
  | 'seo'
  | 'settings'
  | 'shield'

const iconPaths: Record<IconName, string> = {
  activity: 'M4 12h3l2-6 4 12 2-6h5',
  alert: 'M12 4 21 20H3L12 4Zm0 5v5m0 3h.01',
  backup: 'M5 8a7 7 0 1 1 1 9m-1 0v-5m0 5h5',
  bell: 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4',
  check: 'm7 12 3 3 7-7',
  chevron: 'm9 18 6-6-6-6',
  dashboard: 'M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h6v6h-6z',
  extension: 'M9 3h6v4h4v6h-4v4H9v-4H5V7h4z',
  globe: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-18c2.2 2.4 3.3 5.4 3.3 9s-1.1 6.6-3.3 9c-2.2-2.4-3.3-5.4-3.3-9S9.8 5.4 12 3ZM3 12h18',
  help: 'M9.3 9a3 3 0 1 1 4.8 2.4c-1.3 1-2.1 1.5-2.1 3.1m0 3.5h.01',
  lock: 'M7 11V8a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v6H6v-6a2 2 0 0 1 2-2Z',
  monitor: 'M4 5h16v11H4zm5 15h6m-3-4v4',
  moon: 'M20 15.5A8 8 0 0 1 8.5 4 8 8 0 1 0 20 15.5Z',
  refresh: 'M20 11a8 8 0 0 0-14.9-3M4 5v3h3m-3 5a8 8 0 0 0 14.9 3m1.1 3v-3h-3',
  report: 'M6 3h12v18H6zm3 5h6m-6 4h6m-6 4h4',
  search: 'm20 20-4-4m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z',
  seo: 'M4 18V8m5 10V4m5 14v-7m5 7V6',
  settings: 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0-12v2m0 13v2m8.5-8.5h-2m-13 0h-2m14.5-6-1.4 1.4M7.4 16.6 6 18m12 0-1.4-1.4M7.4 7.4 6 6',
  shield: 'M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6z',
}

export function Icon({ name, size = 18 }: { name: IconName; size?: number }) {
  return (
    <svg className="tk-icon" width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d={iconPaths[name]} stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}
