import type { Settings } from './types'

export async function requestSettings(options?: RequestInit): Promise<Settings> {
  const response = await fetch(window.TNStackToolkit.settings_endpoint, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.TNStackToolkit.nonce,
      ...options?.headers,
    },
  })

  if (!response.ok) {
    throw new Error('Không thể xử lý yêu cầu cài đặt.')
  }

  return response.json()
}
