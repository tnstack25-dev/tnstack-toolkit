export type Settings = {
  title: string
  is_enabled: boolean
}

export type NoticeState = {
  status: 'success' | 'error'
  message: string
}
